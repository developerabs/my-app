<?php

namespace App\Services\Accounting;

use App\Enums\JournalVoucherStatus;
use App\Models\Bill;
use App\Models\SupplierPayment;
use App\Services\CurrencyConversionService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillService
{
    use HasFiles;

    public function __construct(
        protected JournalService $journalService,
        protected PostingService $postingService,
        protected CurrencyConversionService $currencyService,
        protected AccountingIntegrationService $accIntegration
    ) {}

    /**
     * Create Vendor Bill & Post Accrual Expense Voucher
     */
    public function createBill(array $data, ?UploadedFile $attachmentFile = null): Bill
    {
        return DB::transaction(function () use ($data, $attachmentFile) {

            $billDate = Carbon::parse($data['bill_date'])->format('Y-m-d');
            $dueDate = Carbon::parse($data['due_date'] ?? $data['bill_date'])->format('Y-m-d');

            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            $attachmentPath = null;
            if ($attachmentFile) {
                $attachmentPath = $this->uploadUploadedFile($attachmentFile, 'bills', 's3');
            }

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
                throw new Exception('Bill total amount must be greater than zero.');
            }

            $billNo = $this->generateBillNo(Carbon::parse($billDate));
            $totalBaseAmount = round($totalAmount * $exchangeRate, 2);

            // 🛑 LATE FEE CONFIG PROCESSING
            $hasLateFee = ! empty($data['has_late_fee']);
            $lateFeeConfig = $hasLateFee ? ($data['late_fee_config'] ?? null) : null;

            $bill = Bill::create([
                'bill_no' => $billNo,
                'vendor_invoice_no' => $data['vendor_invoice_no'] ?? null,
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'project_id' => $data['project_id'] ?? null,
                'total_amount' => round($totalAmount, 2),
                'total_base_amount' => $totalBaseAmount,
                'paid_amount' => 0,
                'base_paid_amount' => 0,
                'due_amount' => round($totalAmount, 2),
                'base_due_amount' => $totalBaseAmount,
                'has_late_fee' => $hasLateFee, // 👈 Late fee flag
                'late_fee_config' => $lateFeeConfig, // 👈 Late fee JSON config
                'payment_status' => 'unpaid',
                'status' => 'posted',
                'attachment' => $attachmentPath,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $bill->items()->createMany($itemsData);

            // 1-Line Clean Accounting Integration Call
            $voucher = $this->accIntegration->syncVendorBill($bill, $itemsData);

            $bill->update(['journal_voucher_id' => $voucher->id]);

            return $bill->load('items.expenseAccount', 'supplier');
        });
    }

    /**
     * Pay / Settle a Vendor Bill
     */
    public function payBill(Bill $bill, array $data, ?UploadedFile $attachmentFile = null): SupplierPayment
    {
        return DB::transaction(function () use ($bill, $data, $attachmentFile) {

            $paymentAmount = (float) $data['amount'];
            if ($paymentAmount <= 0 || $paymentAmount > $bill->due_amount) {
                throw new Exception("Invalid payment amount. Max payable due is {$bill->due_amount}");
            }

            $paymentDate = Carbon::parse($data['payment_date'])->format('Y-m-d');
            $exchangeRate = (float) ($data['exchange_rate'] ?? $bill->exchange_rate ?? 1);

            $attachmentPath = null;
            if ($attachmentFile) {
                $attachmentPath = $this->uploadUploadedFile($attachmentFile, 'supplier_payments', 's3');
            }

            // 💡 withTrashed() সহ ইউনিক পেমেন্ট নম্বর জেনারেট করা (Soft-deleted records check)
            $paymentNo = $this->generatePaymentNo(Carbon::parse($paymentDate));

            // 1. Create Supplier Payment Record
            $payment = SupplierPayment::create([
                'payment_no' => $paymentNo,
                'supplier_id' => $bill->supplier_id,
                'payment_date' => $paymentDate,
                'payment_account_id' => $data['payment_account_id'],
                'branch_id' => $data['branch_id'] ?? $bill->branch_id,
                'currency_id' => $data['currency_id'] ?? $bill->currency_id,
                'exchange_rate' => $exchangeRate,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'amount' => round($paymentAmount, 2),
                'base_amount' => round($paymentAmount * $exchangeRate, 2),
                'reference_no' => $data['reference_no'] ?? null,
                'attachment' => $attachmentPath,
                'note' => $data['note'] ?? ('Bill payment for '.$bill->bill_no),
                'payable_type' => Bill::class,
                'payable_id' => $bill->id,
                'created_by' => auth()->id(),
            ]);

            // 2. Post Payment Journal Voucher
            $voucher = $this->accIntegration->syncSupplierPayment($payment);
            $payment->update(['journal_voucher_id' => $voucher->id]);

            // 3. Update Bill Paid/Due status in Both Transaction & Base Currency
            $newPaid = round($bill->paid_amount + $paymentAmount, 2);
            $newBasePaid = round($bill->base_paid_amount + ($paymentAmount * $bill->exchange_rate), 2);

            $newDue = round($bill->total_amount - $newPaid, 2);
            $newBaseDue = round($bill->total_base_amount - $newBasePaid, 2);

            $paymentStatus = $newDue <= 0 ? 'paid' : 'partially_paid';

            $bill->update([
                'paid_amount' => $newPaid,
                'base_paid_amount' => $newBasePaid,
                'due_amount' => max(0, $newDue),
                'base_due_amount' => max(0, $newBaseDue),
                'payment_status' => $paymentStatus,
            ]);

            return $payment;
        });
    }

    /**
     * Generate Unique Supplier Payment Serial Number (Includes Soft-Deleted Records)
     */
    protected function generatePaymentNo(Carbon $date): string
    {
        $year = $date->format('Y');
        $prefix = "SPAY-{$year}-";

        $query = SupplierPayment::withTrashed()->where('payment_no', 'like', "{$prefix}%");
        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $lastPayment = $query->orderBy('payment_no', 'desc')->first();

        $nextNumber = 1;
        if ($lastPayment && !empty($lastPayment->payment_no)) {
            if (preg_match('/SPAY-\d{4}-(\d+)/', $lastPayment->payment_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
        }

        return sprintf('SPAY-%s-%06d', $year, $nextNumber);
    }

    /**
     * Generate Unique Vendor Bill Serial Number (Includes Soft-Deleted Records)
     */
    protected function generateBillNo(Carbon $date): string
    {
        $year = $date->format('Y');
        $prefix = "BILL-{$year}-";

        $query = Bill::withTrashed()->where('bill_no', 'like', "{$prefix}%");
        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $lastBill = $query->orderBy('bill_no', 'desc')->first();

        $nextNumber = 1;
        if ($lastBill && !empty($lastBill->bill_no)) {
            if (preg_match('/BILL-\d{4}-(\d+)/', $lastBill->bill_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
        }

        return sprintf('BILL-%s-%06d', $year, $nextNumber);
    }

    /**
     * Update Vendor Bill & Re-post Accounting Voucher
     */
    public function updateBill(Bill $bill, array $data, ?UploadedFile $attachmentFile = null): Bill
    {
        return DB::transaction(function () use ($bill, $data, $attachmentFile) {

            $billDate = Carbon::parse($data['bill_date'])->format('Y-m-d');
            $dueDate = Carbon::parse($data['due_date'] ?? $data['bill_date'])->format('Y-m-d');

            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            $attachmentPath = $bill->attachment;
            if ($attachmentFile) {
                if ($attachmentPath) {
                    $this->deleteFile($attachmentPath, 's3');
                }
                $attachmentPath = $this->uploadUploadedFile($attachmentFile, 'bills', 's3');
            }

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
                throw new Exception('Bill total amount must be greater than zero.');
            }

            $totalAmount = round($totalAmount, 2);

            // 🛑 STRICT VALIDATION CHECK: বিলের নতুন দাম ইতিমধ্যে পরিশোধিত টাকার চেয়ে কম হতে পারবে না
            if ($totalAmount < $bill->paid_amount) {
                throw ValidationException::withMessages([
                    'items' => 'New bill total ('.number_format($totalAmount, 2).') cannot be less than the already paid amount ('.number_format($bill->paid_amount, 2).'). Please reverse/adjust supplier payments first.',
                ]);
            }

            $totalBaseAmount = round($totalAmount * $exchangeRate, 2);
            $newDueAmount = round($totalAmount - $bill->paid_amount, 2);
            $newBaseDueAmount = round($totalBaseAmount - $bill->base_paid_amount, 2);

            // 🛑 LATE FEE CONFIG PROCESSING
            $hasLateFee = ! empty($data['has_late_fee']);
            $lateFeeConfig = $hasLateFee ? ($data['late_fee_config'] ?? $bill->late_fee_config) : null;

            // Update Master Record
            $bill->update([
                'vendor_invoice_no' => $data['vendor_invoice_no'] ?? null,
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'project_id' => $data['project_id'] ?? null,
                'total_amount' => $totalAmount,
                'total_base_amount' => $totalBaseAmount,
                'due_amount' => $newDueAmount,
                'base_due_amount' => $newBaseDueAmount,
                'has_late_fee' => $hasLateFee, // 👈 Late fee flag
                'late_fee_config' => $lateFeeConfig, // 👈 Late fee JSON config
                'payment_status' => $newDueAmount <= 0 ? 'paid' : ($bill->paid_amount > 0 ? 'partially_paid' : 'unpaid'),
                'attachment' => $attachmentPath,
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            // Refresh Detail Items
            $bill->items()->delete();
            $bill->items()->createMany($itemsData);

            // Re-sync Accounting Voucher
            $voucher = $this->accIntegration->syncVendorBill($bill, $itemsData);
            $bill->update(['journal_voucher_id' => $voucher->id]);

            return $bill->load('items.expenseAccount', 'supplier');
        });
    }

    /**
     * Delete / Void Vendor Bill & Reverse Accounting Voucher (Cascading Payment Reversal Supported)
     */
    public function deleteBill(Bill $bill, ?string $reason = null, bool $forceReversePayments = true): void
    {
        DB::transaction(function () use ($bill, $reason, $forceReversePayments) {

            // ১. বিলে পেমেন্ট থাকলে তা চেক ও অটো-রিভার্স করা
            if ($bill->paid_amount > 0) {
                if (! $forceReversePayments) {
                    throw new Exception("Cannot delete bill '{$bill->bill_no}' because payments exist. Reverse payments first or enable force reversal.");
                }

                $bill->load('payments.journalVoucher');

                foreach ($bill->payments as $payment) {
                    if ($payment->journalVoucher && $payment->journalVoucher->status === JournalVoucherStatus::POSTED) {
                        $this->journalService->reverse(
                            $payment->journalVoucher,
                            "Auto-reversed due to cancellation of Vendor Bill '{$bill->bill_no}'"
                        );
                    }
                    $payment->delete(); // Soft Delete Payment
                }
            }

            // ২. মূল বিলের জাবেদা ওয়াউচার রিভার্স করা
            if ($bill->journalVoucher && $bill->journalVoucher->status === JournalVoucherStatus::POSTED) {
                $this->journalService->reverse(
                    $bill->journalVoucher,
                    $reason ?? 'Vendor bill deleted/voided by user'
                );
            }

            // ৩. বিল ক্যানসেল ও রিসেট করা
            $bill->updateQuietly([
                'paid_amount' => 0,
                'base_paid_amount' => 0,
                'due_amount' => 0,
                'base_due_amount' => 0,
                'payment_status' => 'unpaid',
                'status' => 'cancelled',
                'journal_voucher_id' => null,
                'note' => ($bill->note ? $bill->note.' | ' : '').'Cancelled: '.($reason ?? 'Deleted by user'),
                'deleted_by' => auth()->id(),
            ]);

            $bill->delete(); // Soft Delete Bill
        });
    }

    /**
     * Restore a Soft-Deleted Vendor Bill as Unpaid & Re-post Accounting Voucher
     */
    public function restoreBill(Bill $bill): void
    {
        DB::transaction(function () use ($bill) {
            // ১. ডিটেইলস আইটেম লোড করা
            $bill->load('items');

            // ২. জাবেদার জন্য আইটেম ফরম্যাট করা
            $itemsData = [];
            foreach ($bill->items as $item) {
                $itemsData[] = [
                    'expense_account_id' => $item->expense_account_id,
                    'amount' => (float) $item->amount,
                    'base_amount' => (float) $item->base_amount,
                    'project_id' => $item->project_id,
                    'description' => $item->description,
                ];
            }

            // ৩. রি-পোস্ট অ্যাকাউন্টিং ওয়াউচার
            $voucher = $this->accIntegration->syncVendorBill($bill, $itemsData);

            // ৪. রিস্টোর হবে সম্পূর্ণ Unpaid অবস্থায় (ক্যাশ/ব্যাংকের টাকা ডিরেক্ট কাটা যাবে না)
            $bill->updateQuietly([
                'paid_amount' => 0,
                'base_paid_amount' => 0,
                'due_amount' => $bill->total_amount,
                'base_due_amount' => $bill->total_base_amount,
                'payment_status' => 'unpaid',
                'status' => 'posted',
                'journal_voucher_id' => $voucher->id,
                'deleted_by' => null,
            ]);
        });
    }
}
