<?php

namespace App\Services\Accounting;

use App\Enums\GeneralLedgerStatus;
use App\Enums\JournalVoucherStatus;
use App\Enums\JournalVoucherType;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\GeneralLedger;
use App\Models\JournalEntry;
use App\Models\JournalVoucher;
use App\Models\VoucherSequence;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalService
{
    public function __construct(
        protected CurrencyConversionService $currencyConversionService
    ) {}

    /**
     * Create a new Journal Voucher.
     */
    public function create(array $data): JournalVoucher
    {
        return DB::transaction(function () use ($data) {
            $exchangeRate = $this->resolveExchangeRate($data);
            $data['exchange_rate'] = $exchangeRate;

            $this->validateVoucher($data);
            $this->validateEntries($data['entries'] ?? []);

            // Universal Bidirectional Date Constraint Validation
            $this->validateVoucherDates($data['voucher_type'], $data['voucher_date'], $data['entries'] ?? []);

            $date = Carbon::parse($data['voucher_date']);
            $period = $this->resolveAccountingPeriod($date);

            $voucherNo = $this->generateVoucherNo($data['voucher_type'], $date);
            $totals = $this->calculateTotals($data['entries'], $exchangeRate);

            $voucher = JournalVoucher::create([
                'voucher_no' => $voucherNo,
                'voucher_date' => $data['voucher_date'],
                'voucher_type' => $data['voucher_type'],
                'status' => JournalVoucherStatus::DRAFT,
                'branch_id' => $data['branch_id'],
                'project_id' => $data['project_id'] ?? null, // Project ID Flow
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'fiscal_year_id' => $period['fiscal_year_id'],
                'accounting_period_id' => $period['accounting_period_id'],
                'reference_no' => $data['reference_no'] ?? null,
                'attachment' => $data['attachment'] ?? null,
                'external_id' => $data['external_id'] ?? null,
                'narration' => $data['narration'] ?? null,
                'total_debit' => $totals['total_debit'],
                'total_credit' => $totals['total_credit'],
                'total_base_debit' => $totals['total_base_debit'],
                'total_base_credit' => $totals['total_base_credit'],
                'sourceable_type' => $data['sourceable_type'] ?? null,
                'sourceable_id' => $data['sourceable_id'] ?? null,
                'created_by' => $this->currentUserId(),
            ]);

            $this->createEntries($voucher, $data['entries'], $exchangeRate);

            return $voucher->load('entries');
        });
    }

    /**
     * Update Draft Journal Voucher.
     */
    public function update(JournalVoucher $voucher, array $data): JournalVoucher
    {
        if ($voucher->status !== JournalVoucherStatus::DRAFT) {
            throw new Exception('Only draft vouchers can be updated.');
        }

        return DB::transaction(function () use ($voucher, $data) {
            $exchangeRate = $this->resolveExchangeRate($data);
            $data['exchange_rate'] = $exchangeRate;

            $this->validateVoucher($data);
            $this->validateEntries($data['entries']);

            // Universal Bidirectional Date Constraint Validation
            $this->validateVoucherDates($data['voucher_type'], $data['voucher_date'], $data['entries']);

            $period = $this->resolveAccountingPeriod(Carbon::parse($data['voucher_date']));
            $totals = $this->calculateTotals($data['entries'], $exchangeRate);

            $voucher->update([
                'voucher_date' => $data['voucher_date'],
                'voucher_type' => $data['voucher_type'],
                'branch_id' => $data['branch_id'],
                'project_id' => $data['project_id'] ?? null, // Project ID Flow
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'fiscal_year_id' => $period['fiscal_year_id'],
                'accounting_period_id' => $period['accounting_period_id'],
                'reference_no' => $data['reference_no'] ?? null,
                'attachment' => $data['attachment'] ?? null,
                'external_id' => $data['external_id'] ?? null,
                'narration' => $data['narration'] ?? null,
                'total_debit' => $totals['total_debit'],
                'total_credit' => $totals['total_credit'],
                'total_base_debit' => $totals['total_base_debit'],
                'total_base_credit' => $totals['total_base_credit'],
                'sourceable_type' => $data['sourceable_type'] ?? null,
                'sourceable_id' => $data['sourceable_id'] ?? null,
                'updated_by' => $this->currentUserId(),
            ]);

            JournalEntry::where('journal_voucher_id', $voucher->id)->delete();

            $this->createEntries($voucher, $data['entries'], $exchangeRate);

            return $voucher->fresh('entries');
        });
    }

    /**
     * Delete Draft Voucher.
     */
    public function delete(JournalVoucher $voucher): void
    {
        if ($voucher->status !== JournalVoucherStatus::DRAFT) {
            throw new Exception('Only draft vouchers can be deleted.');
        }

        DB::transaction(function () use ($voucher) {
            JournalEntry::where('journal_voucher_id', $voucher->id)->delete();
            $voucher->update(['deleted_by' => $this->currentUserId()]);
            $voucher->delete();
        });
    }

    /**
     * Reverse Posted Voucher.
     */
    public function reverse(
        JournalVoucher $voucher,
        ?string $reason = null,
        ?string $reversalDate = null
    ): JournalVoucher {
        if ($voucher->status !== JournalVoucherStatus::POSTED) {
            throw new Exception('Only posted vouchers can be reversed.');
        }

        if ($voucher->reversed_by_voucher) {
            throw new Exception('This voucher has already been reversed.');
        }

        return DB::transaction(function () use ($voucher, $reason, $reversalDate) {
            $voucher->load('entries');

            $targetDate = $reversalDate ?? $voucher->voucher_date;

            $reverseVoucher = $this->create([
                'voucher_date' => $targetDate,
                'voucher_type' => $voucher->voucher_type,
                'branch_id' => $voucher->branch_id,
                'project_id' => $voucher->project_id, // Project ID Flow to Reversal
                'currency_id' => $voucher->currency_id,
                'exchange_rate' => $voucher->exchange_rate,
                'reference_no' => $voucher->voucher_no,
                'narration' => 'Reversal of Voucher: '.$voucher->voucher_no.($reason ? " Reason: {$reason}" : ''),
                'sourceable_type' => $voucher->sourceable_type,
                'sourceable_id' => $voucher->sourceable_id,
                'entries' => $voucher->entries->map(function ($entry) use ($voucher) {
                    return [
                        'account_id' => $entry->account_id,
                        'sub_ledger_type' => $entry->sub_ledger_type,
                        'sub_ledger_id' => $entry->sub_ledger_id,
                        'project_id' => $entry->project_id ?? $voucher->project_id ?? null, // Project ID Flow
                        'debit' => $entry->credit,
                        'credit' => $entry->debit,
                        'description' => 'Reversal line for '.$entry->description,
                    ];
                })->toArray(),
            ]);

            $reverseVoucher->update(['reversal_of' => $voucher->id]);

            app(PostingService::class)->post($reverseVoucher);

            $voucher->update([
                'status' => JournalVoucherStatus::REVERSED,
                'reversed_at' => now(),
                'reversed_by' => $this->currentUserId(),
                'reverse_reason' => $reason,
                'reversed_by_voucher' => $reverseVoucher->id,
            ]);

            GeneralLedger::where('journal_voucher_id', $voucher->id)->update([
                'status' => GeneralLedgerStatus::REVERSED,
                'reversed_at' => now(),
                'reversed_by' => $this->currentUserId(),
            ]);

            return $reverseVoucher->fresh(['entries']);
        });
    }

    /**
     * Bidirectional Universal Voucher Date Validation.
     * 1. OPENING date cannot be LATER than earliest operational transaction.
     * 2. OPERATIONAL transaction date cannot be EARLIER than Opening Balance date.
     */
    protected function validateVoucherDates(JournalVoucherType|string $voucherType, string $voucherDate, array $entries): void
    {
        $normalizedType = $this->normalizeVoucherType($voucherType);
        $voucherCarbonDate = Carbon::parse($voucherDate)->format('Y-m-d');

        foreach ($entries as $index => $entry) {
            $accountId = $entry['account_id'] ?? null;
            $subLedgerType = $entry['sub_ledger_type'] ?? null;
            $subLedgerId = $entry['sub_ledger_id'] ?? null;

            if (! $accountId) {
                continue;
            }

            if ($normalizedType === JournalVoucherType::OPENING) {
                $query = DB::table('general_ledgers')
                    ->where('account_id', $accountId)
                    ->where('voucher_type', '!=', JournalVoucherType::OPENING->value)
                    ->whereIn('status', ['posted', 'reversed']);

                if (! empty($subLedgerType) && ! empty($subLedgerId)) {
                    $query->where('sub_ledger_type', $subLedgerType)
                        ->where('sub_ledger_id', $subLedgerId);
                }

                $minOperationalDate = $query->min('transaction_date');

                if ($minOperationalDate && $voucherCarbonDate > $minOperationalDate) {
                    $account = Account::find($accountId);
                    $accountName = $account?->account_name ?? 'Account #'.$accountId;
                    $formattedMinDate = Carbon::parse($minOperationalDate)->format('Y-m-d');

                    throw ValidationException::withMessages([
                        'voucher_date' => "Opening balance date ({$voucherCarbonDate}) for '{$accountName}' cannot be later than its earliest transaction date ({$formattedMinDate}).",
                    ]);
                }
            } else {
                $query = DB::table('general_ledgers')
                    ->where('account_id', $accountId)
                    ->where('voucher_type', JournalVoucherType::OPENING->value)
                    ->whereIn('status', ['posted', 'reversed']);

                if (! empty($subLedgerType) && ! empty($subLedgerId)) {
                    $query->where('sub_ledger_type', $subLedgerType)
                        ->where('sub_ledger_id', $subLedgerId);
                }

                $latestOpeningDate = $query->max('transaction_date');

                if ($latestOpeningDate && $voucherCarbonDate < $latestOpeningDate) {
                    $account = Account::find($accountId);
                    $accountName = $account?->account_name ?? 'Account #'.$accountId;
                    $formattedOpeningDate = Carbon::parse($latestOpeningDate)->format('Y-m-d');

                    throw ValidationException::withMessages([
                        'voucher_date' => "Transaction date ({$voucherCarbonDate}) for '{$accountName}' cannot be earlier than its Opening Balance date ({$formattedOpeningDate}). Please record transactions on or after {$formattedOpeningDate}, or update the Opening Balance date first.",
                    ]);
                }
            }
        }
    }

    protected function resolveExchangeRate(array $data): float
    {
        if (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0) {
            return (float) $data['exchange_rate'];
        }

        if (empty($data['currency_id'])) {
            throw ValidationException::withMessages(['currency_id' => 'Currency is required.']);
        }

        return $this->currencyConversionService->getExchangeRate($data['currency_id']);
    }

    protected function calculateTotals(array $entries, float $exchangeRate): array
    {
        $totalDebit = 0;
        $totalCredit = 0;
        $totalBaseDebit = 0;
        $totalBaseCredit = 0;

        foreach ($entries as $entry) {
            $debit = (float) ($entry['debit'] ?? 0);
            $credit = (float) ($entry['credit'] ?? 0);

            $baseDebit = round($debit * $exchangeRate, 2);
            $baseCredit = round($credit * $exchangeRate, 2);

            $totalDebit += $debit;
            $totalCredit += $credit;
            $totalBaseDebit += $baseDebit;
            $totalBaseCredit += $baseCredit;
        }

        return [
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'total_base_debit' => round($totalBaseDebit, 2),
            'total_base_credit' => round($totalBaseCredit, 2),
        ];
    }

    protected function createEntries(JournalVoucher $voucher, array $entries, float $exchangeRate): void
    {
        $rows = [];
        $now = now();

        foreach ($entries as $index => $entry) {
            $debit = (float) ($entry['debit'] ?? 0);
            $credit = (float) ($entry['credit'] ?? 0);

            $rows[] = [
                'journal_voucher_id' => $voucher->id,
                'account_id' => $entry['account_id'],
                'sub_ledger_type' => $entry['sub_ledger_type'] ?? null,
                'sub_ledger_id' => $entry['sub_ledger_id'] ?? null,
                'project_id' => $entry['project_id'] ?? $voucher->project_id ?? null, // Project ID Flow to Journal Entries
                'line_no' => $index + 1,
                'debit' => $debit,
                'credit' => $credit,
                'base_debit' => round($debit * $exchangeRate, 2),
                'base_credit' => round($credit * $exchangeRate, 2),
                'description' => $entry['description'] ?? null,
                'created_by' => $this->currentUserId(),
                'updated_by' => $this->currentUserId(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        JournalEntry::insert($rows);
    }

    protected function validateEntries(array $entries): void
    {
        if (empty($entries) || count($entries) < 2) {
            throw ValidationException::withMessages(['entries' => 'Minimum two journal entries are required.']);
        }

        $accountIds = array_filter(array_column($entries, 'account_id'));

        // 🟢 withTrashed() ব্যবহার করা হলো যাতে সফট-ডিলিট হওয়া অ্যাকাউন্টের রিভার্সাল ভাউচার ভ্যালিডেশন পাস করে
        $accounts = Account::withTrashed()->with('chartOfAccount')->whereIn('id', $accountIds)->get()->keyBy('id');

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($entries as $index => $entry) {
            $accountId = $entry['account_id'] ?? null;
            if (! $accountId || ! isset($accounts[$accountId])) {
                throw ValidationException::withMessages(["entries.$index.account_id" => 'Invalid or inactive account selected.']);
            }

            $debit = (float) ($entry['debit'] ?? 0);
            $credit = (float) ($entry['credit'] ?? 0);

            if ($debit < 0 || $credit < 0 || ($debit == 0 && $credit == 0) || ($debit > 0 && $credit > 0)) {
                throw ValidationException::withMessages(["entries.$index.amount" => 'Invalid debit/credit amount.']);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw ValidationException::withMessages(['entries' => 'Total Debit and Credit must be equal.']);
        }
    }

    protected function resolveAccountingPeriod(Carbon $date): array
    {
        $fiscalYear = $this->resolveFiscalYear($date);

        $period = AccountingPeriod::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (! $period || $period->status === 'closed') {
            throw new Exception("Accounting period for {$date->format('Y-m-d')} is invalid or closed.");
        }

        return ['fiscal_year_id' => $fiscalYear->id, 'accounting_period_id' => $period->id];
    }

    protected function resolveFiscalYear(Carbon $date): FiscalYear
    {
        $fiscalYear = FiscalYear::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (! $fiscalYear || $fiscalYear->status === 'closed') {
            throw new Exception("Fiscal year for {$date->format('Y-m-d')} is invalid or closed.");
        }

        return $fiscalYear;
    }

    protected function generateVoucherNo(JournalVoucherType|string $type, Carbon $date): string
    {
        $type = $this->normalizeVoucherType($type);
        $fiscalYear = $this->resolveFiscalYear($date);

        $sequence = VoucherSequence::query()
            ->lockForUpdate()
            ->firstOrCreate(['voucher_type' => $type, 'fiscal_year_id' => $fiscalYear->id], ['last_number' => 0]);

        $sequence->increment('last_number');
        $sequence->refresh();

        return sprintf('%s-%s-%06d', $type->prefix(), Carbon::parse($fiscalYear->start_date)->format('Y'), $sequence->last_number);
    }

    protected function validateVoucher(array $data): void
    {
        if (empty($data['voucher_date']) || empty($data['voucher_type']) || empty($data['branch_id']) || empty($data['currency_id'])) {
            throw ValidationException::withMessages(['voucher' => 'Voucher date, type, branch, and currency are required.']);
        }

        if (($data['exchange_rate'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['exchange_rate' => 'Invalid exchange rate.']);
        }
    }

    protected function normalizeVoucherType(JournalVoucherType|string $type): JournalVoucherType
    {
        return $type instanceof JournalVoucherType ? $type : JournalVoucherType::from($type);
    }

    protected function currentUserId(): ?int
    {
        return Auth::id();
    }
}
