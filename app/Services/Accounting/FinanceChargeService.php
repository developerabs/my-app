<?php

namespace App\Services\Accounting;

use App\Enums\JournalVoucherType;
use App\Enums\LedgerAccountType;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\FinanceCharge;
use App\Models\Supplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class FinanceChargeService
{
    public function __construct(
        protected JournalService $journalService,
        protected PostingService $postingService,
        protected AccountingIntegrationService $accIntegration
    ) {}

    /**
     * Apply Overdue Finance Charge (Late Fee) to a Bill or Invoice
     */
    public function applyCharge($model, array $data): FinanceCharge
    {
        return DB::transaction(function () use ($model, $data) {

            $chargeDate = Carbon::parse($data['charge_date'] ?? now())->format('Y-m-d');
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new Exception('Finance charge amount must be greater than zero.');
            }

            $exchangeRate = (float) ($model->exchange_rate ?? 1);
            $baseAmount = round($amount * $exchangeRate, 2);

            $chargeNo = $this->generateChargeNo(Carbon::parse($chargeDate));

            $daysOverdue = (int) ($data['days_overdue'] ?? $model->overdue_days ?? 0);

            if ($daysOverdue === 0 && ! empty($model->due_date)) {
                $dueDate = Carbon::parse($model->due_date)->startOfDay();
                $cDate = Carbon::parse($chargeDate)->startOfDay();
                if ($cDate->greaterThan($dueDate)) {
                    $daysOverdue = (int) $dueDate->diffInDays($cDate);
                }
            }
            // 1. Create Finance Charge Record
            $charge = FinanceCharge::create([
                'charge_no' => $chargeNo,
                'chargeable_type' => get_class($model),
                'chargeable_id' => (string) $model->id,
                'charge_date' => $chargeDate,
                'days_overdue' => $daysOverdue,
                'fee_type' => $data['fee_type'] ?? 'fixed',
                'rate' => (float) ($data['rate'] ?? $amount),
                'amount' => $amount,
                'base_amount' => $baseAmount,
                'status' => 'posted',
                'branch_id' => $model->branch_id,
                'currency_id' => $model->currency_id,
                'exchange_rate' => $exchangeRate,
                'note' => $data['note'] ?? ('Overdue finance charge for '.($model->bill_no ?? $model->invoice_no ?? 'Document')),
                'created_by' => auth()->id(),
            ]);

            // 2. Post Accounting Journal Voucher (AP vs AR Routing)
            $voucher = $this->postAccountingVoucher($charge, $model);
            $charge->update(['journal_voucher_id' => $voucher->id]);

            // 3. Update Parent Document Total & Due Amounts
            $newTotal = round($model->total_amount + $amount, 2);
            $newDue = round($model->due_amount + $amount, 2);

            $model->update([
                'total_amount' => $newTotal,
                'total_base_amount' => round($newTotal * $exchangeRate, 2),
                'due_amount' => $newDue,
                'base_due_amount' => round($newDue * $exchangeRate, 2),
            ]);

            return $charge;
        });
    }

    /**
     * Waive or Discount a Finance Charge (Full or Partial Waiver Supported)
     * Auto-Freezes future late fees upon waiver.
     */
    public function waiveCharge(FinanceCharge $charge, float $waiveAmount, string $reason = 'Late fee discount'): void
    {
        DB::transaction(function () use ($charge, $waiveAmount, $reason) {

            $waiveAmount = round($waiveAmount, 2);

            if ($waiveAmount <= 0 || $waiveAmount > $charge->amount) {
                throw new Exception("Invalid waive amount. Max allowable waive is {$charge->amount}");
            }

            $model = $charge->chargeable; // Invoice or Bill

            // 🛑 ১. STRICT CHECK: সম্পূর্ণ পরিশোধিত বিলে/ইনভয়েসে ওয়েভ করা যাবে না
            if ($model && ($model->due_amount <= 0 || $model->payment_status === 'paid')) {
                throw new Exception('Cannot waive late fee on a fully paid document. Please reverse or adjust payment first.');
            }

            $exchangeRate = (float) ($model->exchange_rate ?? 1);

            // 🟢 ২. FULL WAIVER (১০০% মাফ করলে অরিজিনাল ওয়াউচার রিভার্স হবে)
            if ($waiveAmount == $charge->amount) {
                if ($charge->journalVoucher && $charge->journalVoucher->status === 'posted') {
                    $this->journalService->reverse($charge->journalVoucher, 'Full late fee waived: '.$reason);
                }
                $charge->update([
                    'status' => 'waived',
                    'waived_at' => now(),
                    'waived_by' => auth()->id(),
                ]);
            }
            // 🔵 ৩. PARTIAL WAIVER (আংশিক মাফ করলে Discount Voucher পোস্ট হবে)
            else {
                $this->postPartialWaiveVoucher($charge, $model, $waiveAmount, $reason);

                $newChargeAmount = round($charge->amount - $waiveAmount, 2);
                $charge->update([
                    'amount' => $newChargeAmount,
                    'base_amount' => round($newChargeAmount * $exchangeRate, 2),
                    'status' => 'partially_waived',
                    'waived_at' => now(),
                    'waived_by' => auth()->id(),
                ]);
            }

            // ৪. Parent Document এর Total & Due Amount মওকুফকৃত টাকা দ্বারা কমানো
            if ($model) {
                $newTotal = max(0, round($model->total_amount - $waiveAmount, 2));
                $newDue = max(0, round($model->due_amount - $waiveAmount, 2));

                $model->update([
                    'total_amount' => $newTotal,
                    'total_base_amount' => round($newTotal * $exchangeRate, 2),
                    'due_amount' => $newDue,
                    'base_due_amount' => round($newDue * $exchangeRate, 2),
                ]);

                // 🛑 ৫. ওয়েভার দেওয়া মাত্রই ভবিষ্যতে নতুন লেট ফি জেনারেট হওয়া অটোমেটিক পজ (Freeze) করে দেওয়া
                $this->freezeLateFee($model, "Auto-paused because late fee was waived ({$reason})");
            }
        });
    }

    /**
     * Delete / Void an Erroneous Finance Charge with Accounting Reversal
     */
    public function deleteCharge(FinanceCharge $charge, ?string $reason = 'Deleted by user'): void
    {
        DB::transaction(function () use ($charge, $reason) {

            $model = $charge->chargeable;

            // 1. ওয়াউচার রিভার্স করা
            if ($charge->journalVoucher && $charge->journalVoucher->status === 'posted') {
                $this->journalService->reverse(
                    $charge->journalVoucher,
                    'Reversing erroneous finance charge: '.$reason
                );
            }

            // 2. মূল ডকুমেন্ট থেকে ফি-এর পরিমাণ কমানো
            if ($model) {
                $exchangeRate = (float) ($model->exchange_rate ?? 1);
                $newTotal = max(0, round($model->total_amount - $charge->amount, 2));
                $newDue = max(0, round($model->due_amount - $charge->amount, 2));

                $model->update([
                    'total_amount' => $newTotal,
                    'total_base_amount' => round($newTotal * $exchangeRate, 2),
                    'due_amount' => $newDue,
                    'base_due_amount' => round($newDue * $exchangeRate, 2),
                ]);
            }

            // 3. সফট ডিলিট
            $charge->updateQuietly([
                'journal_voucher_id' => null,
                'note' => ($charge->note ? $charge->note.' | ' : '').'Deleted: '.$reason,
                'deleted_by' => auth()->id(),
            ]);

            $charge->delete();
        });
    }

    /**
     * Freeze / Pause Future Late Fees for a Specific Bill or Invoice
     */
    public function freezeLateFee($model, string $reason = 'Paused by management'): void
    {
        DB::transaction(function () use ($model, $reason) {
            $config = $model->late_fee_config ?? [];
            $config['is_frozen'] = true;
            $config['frozen_at'] = now()->toDateString();
            $config['frozen_reason'] = $reason;

            $model->updateQuietly([
                'late_fee_config' => $config,
            ]);
        });
    }

    /**
     * Unfreeze / Resume Late Fees
     */
    public function unfreezeLateFee($model): void
    {
        DB::transaction(function () use ($model) {
            $config = $model->late_fee_config ?? [];
            $config['is_frozen'] = false;
            unset($config['frozen_at'], $config['frozen_reason']);

            $model->updateQuietly([
                'late_fee_config' => $config,
            ]);
        });
    }

    /**
     * Generate Unique Finance Charge Serial Number (Scan by String Prefix to avoid year mismatch)
     */
    protected function generateChargeNo(Carbon $date): string
    {
        $year = $date->format('Y');
        $prefix = "FC-{$year}-";

        // 💡 ফিক্সড: charge_date এর বছর না দেখে সরাসরি FC-2026- প্রিফিক্স ওয়ালা সর্বোচ্চ চার্জ নম্বরটি খুঁজবে
        $lastCharge = FinanceCharge::withTrashed()
            ->where('charge_no', 'like', "{$prefix}%")
            ->orderBy('charge_no', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastCharge && ! empty($lastCharge->charge_no)) {
            if (preg_match('/FC-\d{4}-(\d+)/', $lastCharge->charge_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
        }

        return sprintf('FC-%s-%06d', $year, $nextNumber);
    }

    /**
     * Post Double Entry Voucher for Late Fee
     */
    protected function postAccountingVoucher(FinanceCharge $charge, $model)
    {
        $entries = [];

        // Purchase Side (Bill - Accounts Payable)
        if ($model instanceof Bill) {
            $lateFeeExpenseAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::EXPENSE, SystemAccountCode::LATE_FEE_EXPENSE->value);
            $payableAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::PAYABLE, SystemAccountCode::ACCOUNTS_PAYABLE->value);

            $entries = [
                [
                    'account_id' => $lateFeeExpenseAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => $charge->amount,
                    'credit' => 0,
                    'description' => 'Overdue late fee expense for bill: '.$model->bill_no,
                ],
                [
                    'account_id' => $payableAccountId,
                    'sub_ledger_type' => Supplier::class,
                    'sub_ledger_id' => $model->supplier_id,
                    'debit' => 0,
                    'credit' => $charge->amount,
                    'description' => 'Late fee payable to supplier: '.$charge->charge_no,
                ],
            ];
        }
        // Sales Side (Invoice - Accounts Receivable)
        else {
            $receivableAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::RECEIVABLE, SystemAccountCode::ACCOUNTS_RECEIVABLE->value);
            $lateFeeIncomeAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::INCOME, SystemAccountCode::LATE_FEE_INCOME->value);

            $entries = [
                [
                    'account_id' => $receivableAccountId,
                    'sub_ledger_type' => Customer::class,
                    'sub_ledger_id' => $model->customer_id,
                    'debit' => $charge->amount,
                    'credit' => 0,
                    'description' => 'Late fee charged to customer for invoice: '.($model->invoice_no ?? $charge->charge_no),
                ],
                [
                    'account_id' => $lateFeeIncomeAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => 0,
                    'credit' => $charge->amount,
                    'description' => 'Overdue late fee revenue',
                ],
            ];
        }

        $voucherData = [
            'voucher_date' => $charge->charge_date->format('Y-m-d'),
            'voucher_type' => JournalVoucherType::FINANCE_CHARGE,
            'branch_id' => $charge->branch_id,
            'currency_id' => $charge->currency_id,
            'exchange_rate' => $charge->exchange_rate,
            'reference_no' => $charge->charge_no,
            'narration' => $charge->note,
            'sourceable_type' => FinanceCharge::class,
            'sourceable_id' => $charge->id,
            'entries' => $entries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);

        return $voucher;
    }

    /**
     * Post Discount Voucher for Partial Waiving
     */
    protected function postPartialWaiveVoucher(FinanceCharge $charge, $model, float $waiveAmount, string $reason)
    {
        $entries = [];

        if ($model instanceof Bill) {
            $payableAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::PAYABLE, SystemAccountCode::ACCOUNTS_PAYABLE->value);
            $lateFeeExpenseAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::EXPENSE, SystemAccountCode::LATE_FEE_EXPENSE->value);

            $entries = [
                [
                    'account_id' => $payableAccountId,
                    'sub_ledger_type' => Supplier::class,
                    'sub_ledger_id' => $model->supplier_id,
                    'debit' => $waiveAmount,
                    'credit' => 0,
                    'description' => 'Supplier late fee waived/reduced: '.$reason,
                ],
                [
                    'account_id' => $lateFeeExpenseAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => 0,
                    'credit' => $waiveAmount,
                    'description' => 'Late fee expense offset for waiver',
                ],
            ];
        } else {
            $receivableAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::RECEIVABLE, SystemAccountCode::ACCOUNTS_RECEIVABLE->value);
            $discountAccountId = $this->accIntegration->getAccountByCodeOrType(LedgerAccountType::EXPENSE, SystemAccountCode::LATE_FEE_DISCOUNT->value);

            $entries = [
                [
                    'account_id' => $discountAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => $waiveAmount,
                    'credit' => 0,
                    'description' => 'Late fee discount allowed to customer: '.$reason,
                ],
                [
                    'account_id' => $receivableAccountId,
                    'sub_ledger_type' => Customer::class,
                    'sub_ledger_id' => $model->customer_id,
                    'debit' => 0,
                    'credit' => $waiveAmount,
                    'description' => 'Late fee partial waiver for invoice',
                ],
            ];
        }

        $voucherData = [
            'voucher_date' => now()->toDateString(),
            'voucher_type' => JournalVoucherType::FINANCE_CHARGE,
            'branch_id' => $charge->branch_id,
            'currency_id' => $charge->currency_id,
            'exchange_rate' => $charge->exchange_rate,
            'reference_no' => $charge->charge_no.'-WAIVE',
            'narration' => 'Partial Late Fee Waived: '.$reason,
            'sourceable_type' => FinanceCharge::class,
            'sourceable_id' => $charge->id,
            'entries' => $forexEntries ?? $entries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);
    }
}
