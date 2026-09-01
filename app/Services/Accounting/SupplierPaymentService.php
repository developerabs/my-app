<?php

namespace App\Services\Accounting;

use App\Enums\JournalVoucherStatus;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\CurrencyConversionService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SupplierPaymentService
{
    use HasFiles;

    public function __construct(
        protected JournalService $journalService,
        protected PostingService $postingService,
        protected CurrencyConversionService $currencyService,
        protected AccountingIntegrationService $accIntegration
    ) {}

    /**
     * Create Supplier Payment (Supports Quick Document Pay, Multi-Bill Allocation & Direct Advance)
     */
    public function createPayment(array $data, ?UploadedFile $attachmentFile = null): SupplierPayment
    {
        return DB::transaction(function () use ($data, $attachmentFile) {

            $paymentAmount = round((float) $data['amount'], 2);
            if ($paymentAmount <= 0) {
                throw new Exception('Payment amount must be greater than zero.');
            }

            $paymentDate = Carbon::parse($data['payment_date'] ?? now())->format('Y-m-d');
            $supplierId = $data['supplier_id'];
            $supplier = Supplier::findOrFail($supplierId);

            $payableType = null;
            $payableId = null;
            $payableDoc = null;
            $branchId = null;
            $currencyId = null;
            $exchangeRate = null;

            if (! empty($data['payable_type']) && ! empty($data['payable_id'])) {
                $payableClass = match ($data['payable_type']) {
                    'bill', Bill::class => Bill::class,
                    'purchase', Purchase::class => Purchase::class,
                    default => null,
                };

                if ($payableClass) {
                    $payableDoc = $payableClass::findOrFail($data['payable_id']);
                    $payableType = $payableClass;
                    $payableId = (string) $payableDoc->id;

                    $branchId = $payableDoc->branch_id;
                    $currencyId = $payableDoc->currency_id;
                    $exchangeRate = (float) $payableDoc->exchange_rate;

                    $this->settleSingleDocument($payableDoc, $paymentAmount, $exchangeRate);
                }
            } elseif (! empty($data['allocations']) && is_array($data['allocations'])) {
                // Multi-document allocations (Bill / Purchase)
                $firstAlloc = reset($data['allocations']);
                $modelClass = match ($firstAlloc['type'] ?? '') {
                    'bill', Bill::class => Bill::class,
                    'purchase', Purchase::class => Purchase::class,
                    default => null
                };

                if ($modelClass && ! empty($firstAlloc['id'])) {
                    $firstDoc = $modelClass::find($firstAlloc['id']);
                    $branchId = $firstDoc?->branch_id;
                    $currencyId = $firstDoc?->currency_id;
                    $exchangeRate = (float) ($firstDoc?->exchange_rate ?? 1);
                }

                $this->processAllocations($data['allocations'], $exchangeRate ?? 1);
                $payableType = Supplier::class;
                $payableId = (string) $supplier->id;
            } else {
                // Direct Advance / On-Account to Supplier
                $payableType = Supplier::class;
                $payableId = (string) $supplier->id;
            }

            if (empty($branchId)) {
                $branchId = session('branch_id')
                    ?? (auth()->user()->branch_id ?? null)
                    ?? Setting::get('default_branch')
                    ?? Branch::where('is_default', true)->value('id')
                    ?? Branch::value('id');
            }

            if (empty($currencyId)) {
                $currencyId = $data['currency_id'] ?? $this->currencyService->getBaseCurrency()->id;
            }

            if (empty($exchangeRate)) {
                $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                    ? (float) $data['exchange_rate']
                    : (float) $this->currencyService->getExchangeRate($currencyId);
            }

            $attachmentPath = $attachmentFile ? $this->uploadUploadedFile($attachmentFile, 'supplier_payments', 's3') : null;
            $paymentNo = $this->generatePaymentNo(Carbon::parse($paymentDate));
            $baseAmount = round($paymentAmount * $exchangeRate, 2);

            // Create Supplier Payment Record
            $payment = SupplierPayment::create([
                'payment_no' => $paymentNo,
                'supplier_id' => $supplierId,
                'payment_date' => $paymentDate,
                'payment_account_id' => $data['payment_account_id'],
                'branch_id' => $branchId,
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'amount' => $paymentAmount,
                'base_amount' => $baseAmount,
                'reference_no' => $data['reference_no'] ?? null,
                'attachment' => $attachmentPath,
                'note' => $data['note'] ?? "Payment disbursement to {$supplier->name}",
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'created_by' => auth()->id(),
            ]);

            // Post Double-Entry Accounting Voucher
            $voucher = $this->accIntegration->syncSupplierPayment($payment);
            $payment->updateQuietly(['journal_voucher_id' => $voucher->id]);

            return $payment->load('paymentAccount', 'supplier');
        });
    }

    /**
     * Settle single Bill or Purchase
     */
    protected function settleSingleDocument(Model $doc, float $paymentAmount, float $exchangeRate): void
    {
        $currentDue = round((float) $doc->due_amount, 2);
        if ($paymentAmount > $currentDue) {
            throw new Exception('Payment amount ('.number_format($paymentAmount, 2).') exceeds due amount ('.number_format($currentDue, 2).').');
        }

        $newPaid = round((float) $doc->paid_amount + $paymentAmount, 2);
        $newBasePaid = round((float) $doc->base_paid_amount + ($paymentAmount * $exchangeRate), 2);
        $newDue = max(0, round((float) $doc->total_amount - $newPaid, 2));
        $newBaseDue = max(0, round((float) $doc->total_base_amount - $newBasePaid, 2));
        $paymentStatus = $newDue <= 0 ? 'paid' : 'partially_paid';

        $doc->updateQuietly([
            'paid_amount' => $newPaid,
            'base_paid_amount' => $newBasePaid,
            'due_amount' => $newDue,
            'base_due_amount' => $newBaseDue,
            'payment_status' => $paymentStatus,
        ]);
    }

    /**
     * Settle Multiple Invoices (QuickBooks Multi-Bill Allocations)
     */
    protected function processAllocations(array $allocations, float $exchangeRate): void
    {
        foreach ($allocations as $alloc) {
            $amount = round((float) ($alloc['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $modelClass = match ($alloc['type'] ?? '') {
                'bill', Bill::class => Bill::class,
                'purchase', Purchase::class => Purchase::class,
                default => null
            };

            if ($modelClass && ! empty($alloc['id'])) {
                $doc = $modelClass::find($alloc['id']);
                if ($doc) {
                    $this->settleSingleDocument($doc, $amount, $exchangeRate);
                }
            }
        }
    }

    /**
     * Fetch All Open Bills & Purchases for a Supplier filtered by Currency & Permitted Branches
     */
    public function getSupplierOpenInvoices(string $supplierId, $currencyId = null, $selectedBranchId = null): array
    {
        $supplier = Supplier::findOrFail($supplierId);
        $user = auth()->user();

        // 1. Get Permitted Branches for Auth User
        $permittedBranchIds = get_auth_permitted_branch_ids();
        $targetBranchIds = $permittedBranchIds;

        // If specific branch filter requested (and authorized)
        if (! empty($selectedBranchId)) {
            if (! in_array($selectedBranchId, $permittedBranchIds)) {
                throw new Exception('Unauthorized: You do not have access to this branch.');
            }
            $targetBranchIds = [$selectedBranchId];
        }

        // 2. Fetch All Unpaid Bills & Purchases in permitted branches
        $billsQuery = Bill::where('supplier_id', $supplierId)
            ->where('due_amount', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->whereIn('branch_id', $targetBranchIds)
            ->with(['currency:id,code,symbol', 'branch:id,name']);

        $purchasesQuery = Purchase::where('supplier_id', $supplierId)
            ->where('due_amount', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->whereIn('branch_id', $targetBranchIds)
            ->with(['currency:id,code,symbol', 'branch:id,name']);

        // Filter by Selected Currency
        if (! empty($currencyId)) {
            $billsQuery->where('currency_id', $currencyId);
            $purchasesQuery->where('currency_id', $currencyId);
        }

        $bills = $billsQuery->orderBy('bill_date', 'asc')->get();
        $purchases = $purchasesQuery->orderBy('purchase_date', 'asc')->get();

        // 3. Multi-Currency Summary for Banner Badges
        $allOpenDocs = Bill::where('supplier_id', $supplierId)
            ->where('due_amount', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->whereIn('branch_id', $permittedBranchIds)
            ->with('currency:id,code,symbol')
            ->get()
            ->concat(
                Purchase::where('supplier_id', $supplierId)
                    ->where('due_amount', '>', 0)
                    ->where('status', '!=', 'cancelled')
                    ->whereIn('branch_id', $permittedBranchIds)
                    ->with('currency:id,code,symbol')
                    ->get()
            );

        $currencySummary = [];
        foreach ($allOpenDocs as $doc) {
            $cCode = $doc->currency->code ?? 'BDT';
            $cSym = $doc->currency->symbol ?? $cCode;
            if (! isset($currencySummary[$cCode])) {
                $currencySummary[$cCode] = ['symbol' => $cSym, 'total_due' => 0, 'count' => 0];
            }
            $currencySummary[$cCode]['total_due'] += (float) $doc->due_amount;
            $currencySummary[$cCode]['count']++;
        }

        $selectedCurrencyDue = round($bills->sum('due_amount') + $purchases->sum('due_amount'), 2);

        return [
            'supplier_name' => $supplier->name,
            'supplier_phone' => $supplier->phone,
            'company_name' => $supplier->company_name,
            'total_ledger_due' => (float) $supplier->current_balance,
            'currency_summary' => $currencySummary,
            'selected_currency_due' => $selectedCurrencyDue,
            'bills' => $bills,
            'purchases' => $purchases,
            'is_super_admin' => $user->hasRole('Super Admin') || $user->can('access_all_branches'),
        ];
    }

    /**
     * Delete Payment with Document & GL Reversal
     */
    public function deletePayment(SupplierPayment $payment, ?string $reason = null): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $voucher = $payment->journalVoucher;
            if ($voucher && $voucher->status === JournalVoucherStatus::POSTED) {
                $this->journalService->reverse($voucher, $reason ?? 'Supplier payment deleted');
            }

            if ($payment->payable_type === Bill::class || $payment->payable_type === Purchase::class) {
                $doc = $payment->payable;
                if ($doc) {
                    $exchangeRate = (float) ($doc->exchange_rate ?? 1);
                    $newPaid = max(0, round((float) $doc->paid_amount - (float) $payment->amount, 2));
                    $newBasePaid = max(0, round((float) $doc->base_paid_amount - ((float) $payment->amount * $exchangeRate), 2));
                    $newDue = round((float) $doc->total_amount - $newPaid, 2);
                    $newBaseDue = round((float) $doc->total_base_amount - $newBasePaid, 2);
                    $paymentStatus = $newPaid <= 0 ? 'unpaid' : 'partially_paid';

                    $doc->updateQuietly([
                        'paid_amount' => $newPaid,
                        'base_paid_amount' => $newBasePaid,
                        'due_amount' => $newDue,
                        'base_due_amount' => $newBaseDue,
                        'payment_status' => $paymentStatus,
                    ]);
                }
            }

            $payment->updateQuietly([
                'journal_voucher_id' => null,
                'note' => ($payment->note ? $payment->note.' | ' : '').'Deleted: '.($reason ?? 'Deleted by user'),
                'deleted_by' => auth()->id(),
            ]);

            $payment->delete();
        });
    }

    public function generatePaymentNo(Carbon $date): string
    {
        $year = $date->format('Y');
        $prefix = "SPAY-{$year}-";

        $lastPayment = SupplierPayment::withTrashed()
            ->where('payment_no', 'like', "{$prefix}%")
            ->orderBy('payment_no', 'desc')
            ->lockForUpdate()
            ->first();

        $nextNumber = 1;
        if ($lastPayment && ! empty($lastPayment->payment_no)) {
            if (preg_match('/SPAY-\d{4}-(\d+)/', $lastPayment->payment_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
        }

        return sprintf('SPAY-%s-%06d', $year, $nextNumber);
    }
}
