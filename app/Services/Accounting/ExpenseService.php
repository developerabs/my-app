<?php

namespace App\Services\Accounting;

use App\Enums\JournalVoucherStatus;
use App\Models\Account;
use App\Models\Expense;
use App\Services\CurrencyConversionService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    use HasFiles;

    public function __construct(
        protected JournalService $journalService,
        protected PostingService $postingService,
        protected CurrencyConversionService $currencyService,
        protected AccountingIntegrationService $accIntegration
    ) {}

    /**
     * Create Direct Expense, handle S3 attachment, and Post Accounting Voucher
     */
    public function createExpense(array $data, ?UploadedFile $attachmentFile = null): Expense
    {
        return DB::transaction(function () use ($data, $attachmentFile) {

            $expenseDate = Carbon::parse($data['expense_date'])->format('Y-m-d');

            // 1. Resolve Exchange Rate
            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            // 2. Upload Attachment
            $attachmentPath = null;
            if ($attachmentFile) {
                $attachmentPath = $this->uploadUploadedFile($attachmentFile, 'expenses', 's3');
            }

            // 3. Process Expense Items & Calculate Totals
            $totalAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $amount = (float) $item['amount'];
                if ($amount <= 0) {
                    continue;
                }

                $totalAmount += $amount;

                $itemsData[] = [
                    'expense_account_id' => $item['expense_account_id'],
                    'amount' => $amount,
                    'base_amount' => round($amount * $exchangeRate, 2),
                    'project_id' => $item['project_id'] ?? $data['project_id'] ?? null,
                    'description' => $item['description'] ?? null,
                ];
            }

            if ($totalAmount <= 0) {
                throw new Exception('Total expense amount must be greater than zero.');
            }

            $expenseNo = $this->generateExpenseNo(Carbon::parse($expenseDate));

            // 4. Create Master Expense Record
            $expense = Expense::create([
                'expense_no' => $expenseNo,
                'expense_date' => $expenseDate,
                'branch_id' => $data['branch_id'],
                'project_id' => $data['project_id'] ?? null,
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'payment_account_id' => $data['payment_account_id'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'attachment' => $attachmentPath,
                'total_amount' => round($totalAmount, 2),
                'total_base_amount' => round($totalAmount * $exchangeRate, 2),
                'note' => $data['note'] ?? null,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // 5. Create Detail Expense Items
            $expense->items()->createMany($itemsData);

            // 6. Clean 1-Line Integration Call for Expense Journal Voucher Posting
            $voucher = $this->accIntegration->syncExpense($expense, $itemsData);

            $expense->update(['journal_voucher_id' => $voucher->id]);

            return $expense->load('items.expenseAccount', 'paymentAccount');
        });
    }

    /**
     * Update Direct Expense, handle S3 attachment replacement, Reverse Old Voucher & Post New Voucher
     */
    public function updateExpense(Expense $expense, array $data, ?UploadedFile $attachmentFile = null): Expense
    {
        return DB::transaction(function () use ($expense, $data, $attachmentFile) {

            $expenseDate = Carbon::parse($data['expense_date'])->format('Y-m-d');

            // 1. Resolve Exchange Rate
            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            // 2. Handle Attachment Update
            $attachmentPath = $expense->attachment;
            if ($attachmentFile) {
                if ($attachmentPath) {
                    $this->deleteFile($attachmentPath, 's3');
                }
                $attachmentPath = $this->uploadUploadedFile($attachmentFile, 'expenses', 's3');
            }

            // 3. Process Items & Calculate Totals
            $totalAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $amount = (float) $item['amount'];
                if ($amount <= 0) {
                    continue;
                }

                $totalAmount += $amount;

                $itemsData[] = [
                    'expense_account_id' => $item['expense_account_id'],
                    'amount' => $amount,
                    'base_amount' => round($amount * $exchangeRate, 2),
                    'project_id' => $item['project_id'] ?? $data['project_id'] ?? null,
                    'description' => $item['description'] ?? null,
                ];
            }

            if ($totalAmount <= 0) {
                throw new Exception('Total expense amount must be greater than zero.');
            }

            // 4. Update Master Expense Record
            $expense->update([
                'expense_date' => $expenseDate,
                'branch_id' => $data['branch_id'],
                'project_id' => $data['project_id'] ?? null,
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'payment_account_id' => $data['payment_account_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_no' => $data['reference_no'] ?? null,
                'attachment' => $attachmentPath,
                'total_amount' => round($totalAmount, 2),
                'total_base_amount' => round($totalAmount * $exchangeRate, 2),
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            // 5. Refresh Detail Items
            $expense->items()->delete();
            $expense->items()->createMany($itemsData);

            // 6. Clean 1-Line Integration Call for Expense Re-Posting
            $voucher = $this->accIntegration->syncExpense($expense, $itemsData);

            $expense->update(['journal_voucher_id' => $voucher->id]);

            return $expense->load('items.expenseAccount', 'paymentAccount');
        });
    }

    /**
     * Safely Delete / Void Expense with Automatic Accounting Reversal
     */
    public function deleteExpense(Expense $expense, ?string $reason = null): void
    {
        DB::transaction(function () use ($expense, $reason) {

            // 1. Reverse the associated journal voucher to return money back to Cash/Bank
            if ($expense->journalVoucher && $expense->journalVoucher->status === JournalVoucherStatus::POSTED) {
                $this->journalService->reverse(
                    $expense->journalVoucher,
                    $reason ?? 'Expense deleted/voided by user'
                );
            }

            // 2. Mark expense status as cancelled & nullify voucher ID quietly, then Soft Delete
            $expense->updateQuietly([
                'status' => 'cancelled',
                'journal_voucher_id' => null, // 👈 ওয়াউচার রেফারেন্স নাল করা হলো
                'note' => ($expense->note ? $expense->note.' | ' : '').'Cancelled: '.($reason ?? 'Deleted by user'),
                'deleted_by' => auth()->id(),
            ]);

            $expense->delete();
        });
    }

    /**
     * Restore Soft-Deleted Expense with Automatic Accounting Re-posting
     */
    public function restoreExpense(Expense $expense): Expense
    {
        return DB::transaction(function () use ($expense) {

            // 1. Eager load items including soft-deleted child items
            $expense->load('items');

            $itemsData = [];
            foreach ($expense->items as $item) {
                $itemsData[] = [
                    'expense_account_id' => $item->expense_account_id,
                    'amount' => (float) $item->amount,
                    'project_id' => $item->project_id ?? $expense->project_id ?? null,
                    'description' => $item->description ?? 'Restored expense item payment',
                ];
            }

            // 2. Clean 1-Line Integration Call for Re-Posting
            $voucher = $this->accIntegration->syncExpense($expense, $itemsData);

            // 3. Update Expense record quietly
            $expense->updateQuietly([
                'status' => 'posted',
                'journal_voucher_id' => $voucher->id,
                'deleted_by' => null,
            ]);

            return $expense->load('items.expenseAccount', 'paymentAccount');
        });
    }

    /**
     * Generate Unique Expense Voucher Number
     */
    protected function generateExpenseNo(Carbon $date): string
    {
        $year = $date->format('Y');

        $lastExpense = Expense::withTrashed()
            ->whereYear('expense_date', $year)
            ->latest('id')
            ->first();

        $nextNumber = 1;
        if ($lastExpense && ! empty($lastExpense->expense_no)) {
            if (preg_match('/EXP-\d{4}-(\d+)/', $lastExpense->expense_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            } else {
                $nextNumber = ((int) $lastExpense->id) + 1;
            }
        }

        return sprintf('EXP-%s-%06d', $year, $nextNumber);
    }
}