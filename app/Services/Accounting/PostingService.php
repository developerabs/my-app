<?php

namespace App\Services\Accounting;

use App\Enums\BalanceType;
use App\Enums\GeneralLedgerStatus;
use App\Enums\JournalVoucherStatus;
use App\Enums\JournalVoucherType;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\JournalVoucher;
use App\Models\Setting;
use App\Services\CurrencyConversionService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostingService
{
    protected ?int $userId = null;

    protected $now = null;

    protected array $accountBalanceCache = [];

    protected array $ledgerRows = [];

    protected array $accountUpdates = [];

    /**
     * Post Journal Voucher
     *
     * @throws Exception
     */
    public function post(JournalVoucher $voucher): JournalVoucher
    {
        try {
            return DB::transaction(function () use ($voucher) {
                $this->now = now();
                $this->userId = Auth::id();

                $voucher = JournalVoucher::query()
                    ->lockForUpdate()
                    ->findOrFail($voucher->id);

                $voucher->loadMissing([
                    'entries.account.chartOfAccount',
                    'currency',
                    'branch',
                    'fiscalYear',
                    'accountingPeriod',
                ]);

                $this->validatePosting($voucher);

                if (is_null($voucher->posting_sequence)) {
                    $voucher->update([
                        'posting_sequence' => $this->nextPostingSequence(),
                    ]);
                }

                $this->createGeneralLedger($voucher);
                $this->bulkInsertGeneralLedger();
                $this->bulkUpdateAccounts();

                $this->rebuildRunningBalanceIfNeeded($voucher);

                $this->markVoucherPosted($voucher);

                return $voucher->fresh(['entries.account']);
            });
        } finally {
            $this->resetRuntime();
        }
    }

    /**
     * Clear runtime cache.
     */
    protected function resetRuntime(): void
    {
        $this->ledgerRows = [];
        $this->accountUpdates = [];
        $this->accountBalanceCache = [];
        $this->userId = null;
        $this->now = null;
    }

    /**
     * Validate voucher before posting with Cross-Branch Security Guard
     */
    protected function validatePosting(JournalVoucher $voucher): void
    {
        if ($voucher->status !== JournalVoucherStatus::DRAFT) {
            throw AccountingException::onlyDraftCanBePosted();
        }
        if ($voucher->entries->isEmpty()) {
            throw AccountingException::voucherHasNoEntries();
        }

        if (round((float) $voucher->total_debit, 2) !== round((float) $voucher->total_credit, 2)) {
            throw AccountingException::voucherNotBalanced();
        }

        if (round((float) $voucher->total_base_debit, 2) !== round((float) $voucher->total_base_credit, 2)) {
            throw new Exception("Voucher Base Currency amounts are not balanced.");
        }

        if ((float) $voucher->total_debit <= 0) {
            throw AccountingException::voucherAmountIsZero();
        }
        if ($voucher->accountingPeriod->status === 'closed' || $voucher->fiscalYear->status === 'closed') {
            throw AccountingException::accountingPeriodClosed();
        }

        // 🔒 Cross-Branch Payment Account Protection Guard
        foreach ($voucher->entries as $entry) {
            $account = $entry->account;
            if ($account && !empty($account->branch_id) && $account->branch_id !== $voucher->branch_id) {
                throw new Exception("Cross-Branch Error: Account '{$account->account_name}' belongs to a different branch. You cannot disburse or receive funds using another branch's account.");
            }
        }
    }

    /**
     * Mark voucher as posted.
     */
    protected function markVoucherPosted(JournalVoucher $voucher): void
    {
        $voucher->update([
            'status' => JournalVoucherStatus::POSTED,
            'posted_at' => $this->now,
            'posted_by' => $this->userId,
        ]);
    }

    /**
     * Build General Ledger rows with Clean Zero-Discrepancy Cross-Currency Outflow
     */
    protected function createGeneralLedger(JournalVoucher $voucher): void
    {
        $entries = $voucher->entries->sortBy('line_no')->values();
        $baseCurrencyId = Setting::get('default_currency');
        $currencyService = app(CurrencyConversionService::class);

        foreach ($entries as $entry) {
            $debit       = (float) $entry->debit;
            $credit      = (float) $entry->credit;
            $baseDebit   = (float) $entry->base_debit;
            $baseCredit  = (float) $entry->base_credit;
            $account     = $entry->account;
            $balanceType = $account->chartOfAccount->balance_type;

            $previous = $this->accountBalanceCache[$account->id]
                ??= $this->getPreviousBalance($account, $voucher, $entry->line_no);

            $isAccountBaseCurrency = empty($account->currency_id) || $account->currency_id == $baseCurrencyId;
            $lineCurrencyId = $voucher->currency_id;

            if ($isAccountBaseCurrency) {
                $balance           = $this->calculateBalance($balanceType, $previous['base_balance'], $baseDebit, $baseCredit);
                $baseBalance       = $balance;
                $entryNativeDebit  = $baseDebit;
                $entryNativeCredit = $baseCredit;
                $lineCurrencyId    = $baseCurrencyId;
            } else {
                $accRate = (float) $currencyService->getExchangeRate($account->currency_id);

                if ($voucher->currency_id == $account->currency_id) {
                    $balance           = $this->calculateBalance($balanceType, $previous['balance'], $debit, $credit);
                    $baseBalance       = $this->calculateBalance($balanceType, $previous['base_balance'], $baseDebit, $baseCredit);
                    $entryNativeDebit  = $debit;
                    $entryNativeCredit = $credit;
                    $lineCurrencyId    = $account->currency_id;
                } else {
                    // 🟢 ক্লিন ক্রস-কারেন্সি সমাধান (Direct Outflow Match without 47-paisa micro-clutter):
                    $accDebit  = $accRate > 0 ? round($baseDebit / $accRate, 2) : $debit;
                    $accCredit = $accRate > 0 ? round($baseCredit / $accRate, 2) : $credit;

                    // আসল ডলার কর্তন অনুযায়ী নিখুঁত বিডিটি বেজ ভ্যালু লক করা
                    $baseDebit  = round($accDebit * $accRate, 2);
                    $baseCredit = round($accCredit * $accRate, 2);

                    $balance     = $this->calculateBalance($balanceType, $previous['balance'], $accDebit, $accCredit);
                    $baseBalance = $this->calculateBalance($balanceType, $previous['base_balance'], $baseDebit, $baseCredit);

                    $entryNativeDebit  = $accDebit;
                    $entryNativeCredit = $accCredit;
                    $lineCurrencyId    = $account->currency_id; // Tagged with Account native currency
                }
            }

            $this->accountBalanceCache[$account->id] = [
                'balance'      => $balance,
                'base_balance' => $baseBalance,
            ];

            $this->ledgerRows[] = [
                'journal_voucher_id'   => $voucher->id,
                'journal_entry_id'     => $entry->id,
                'account_id'           => $account->id,
                'fiscal_year_id'       => $voucher->fiscal_year_id,
                'accounting_period_id' => $voucher->accounting_period_id,
                'branch_id'            => $voucher->branch_id,
                'project_id'           => $entry->project_id ?? $voucher->project_id ?? null,
                'currency_id'          => $lineCurrencyId,
                'exchange_rate'        => $voucher->exchange_rate,
                'transaction_date'     => $voucher->voucher_date,
                'sub_ledger_type'      => $entry->sub_ledger_type,
                'sub_ledger_id'        => $entry->sub_ledger_id,
                'voucher_no'           => $voucher->voucher_no,
                'posting_sequence'     => $voucher->posting_sequence,
                'voucher_type'         => $voucher->voucher_type,
                'line_no'              => $entry->line_no,
                'reference_no'         => $voucher->reference_no,
                'narration'            => filled($entry->description) ? $entry->description : $voucher->narration,
                'debit'                => $entryNativeDebit,
                'credit'               => $entryNativeCredit,
                'base_debit'           => $baseDebit,
                'base_credit'          => $baseCredit,
                'balance'              => $balance,
                'base_balance'         => $baseBalance,
                'status'               => GeneralLedgerStatus::POSTED,
                'sourceable_type'      => $voucher->sourceable_type,
                'sourceable_id'        => $voucher->sourceable_id,
                'is_opening'           => $voucher->voucher_type === JournalVoucherType::OPENING,
                'is_system_generated'  => $voucher->voucher_type->isSystemVoucher(),
                'posted_at'            => $this->now,
                'created_by'           => $this->userId,
                'created_at'           => $this->now,
                'updated_at'           => $this->now,
            ];

            $this->accountUpdates[$account->id] = [
                'id'                   => $account->id,
                'current_balance'      => $balance,
                'base_current_balance' => $baseBalance,
                'last_transaction_date'=> $voucher->voucher_date,
            ];
        }
    }

    /**
     * Bulk insert General Ledger rows.
     */
    protected function bulkInsertGeneralLedger(): void
    {
        if (! empty($this->ledgerRows)) {
            GeneralLedger::insert($this->ledgerRows);
            $this->ledgerRows = [];
        }
    }

    /**
     * Bulk update Account balances.
     */
    protected function bulkUpdateAccounts(): void
    {
        if (empty($this->accountUpdates)) {
            return;
        }

        foreach ($this->accountUpdates as $update) {
            Account::where('id', $update['id'])->update([
                'current_balance' => $update['current_balance'],
                'base_current_balance' => $update['base_current_balance'], // 👈 Updated
                'last_transaction_date' => $update['last_transaction_date'],
            ]);
        }

        $this->accountUpdates = [];
    }

    /**
     * Get previous running balance for an account.
     */
    protected function getPreviousBalance(Account $account, JournalVoucher $voucher, int $lineNo): array
    {
        $ledger = GeneralLedger::query()
            ->where('account_id', $account->id)
            ->where('status', GeneralLedgerStatus::POSTED)
            ->where(function ($query) use ($voucher, $lineNo) {
                $query->whereDate('transaction_date', '<', $voucher->voucher_date)
                    ->orWhere(function ($q) use ($voucher, $lineNo) {
                        $q->whereDate('transaction_date', $voucher->voucher_date)
                            ->where(function ($subQ) use ($voucher, $lineNo) {
                                $subQ->where('posting_sequence', '<', $voucher->posting_sequence)
                                    ->orWhere(function ($lineQ) use ($voucher, $lineNo) {
                                        $lineQ->where('posting_sequence', $voucher->posting_sequence)
                                            ->where('line_no', '<', $lineNo);
                                    });
                            });
                    });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('posting_sequence')
            ->orderByDesc('line_no')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        return [
            'balance' => (float) ($ledger?->balance ?? 0),
            'base_balance' => (float) ($ledger?->base_balance ?? 0),
        ];
    }

    /**
     * Rebuild running balance only for backdated posting.
     */
    protected function rebuildRunningBalanceIfNeeded(JournalVoucher $voucher): void
    {
        $accountIds = $voucher->entries->pluck('account_id')->unique();

        foreach ($accountIds as $accountId) {
            $account = Account::with('chartOfAccount')->find($accountId);

            $futureLedger = GeneralLedger::query()
                ->where('account_id', $accountId)
                ->where('status', GeneralLedgerStatus::POSTED)
                ->where('journal_voucher_id', '!=', $voucher->id)
                ->where(function ($q) use ($voucher) {
                    $q->whereDate('transaction_date', '>', $voucher->voucher_date)
                        ->orWhere(function ($subQ) use ($voucher) {
                            $subQ->whereDate('transaction_date', $voucher->voucher_date)
                                ->where('posting_sequence', '>', $voucher->posting_sequence);
                        });
                })
                ->exists();

            if ($futureLedger) {
                $this->rebuildRunningBalance($account, $voucher->voucher_date);
            }
        }
    }

    /**
     * Recalculate running balance for one account.
     */
    protected function rebuildRunningBalance(Account $account, $fromDate): void
    {
        $balanceType = $account->chartOfAccount->balance_type;
        $baseCurrencyId = Setting::get('default_currency');
        $isAccountBaseCurrency = empty($account->currency_id) || $account->currency_id == $baseCurrencyId;

        $ledgers = GeneralLedger::query()
            ->where('account_id', $account->id)
            ->where('status', GeneralLedgerStatus::POSTED)
            ->whereDate('transaction_date', '>=', $fromDate)
            ->orderBy('transaction_date')
            ->orderBy('posting_sequence')
            ->orderBy('line_no')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($ledgers->isEmpty()) {
            return;
        }

        $previous = GeneralLedger::query()
            ->where('account_id', $account->id)
            ->where('status', GeneralLedgerStatus::POSTED)
            ->whereDate('transaction_date', '<', $fromDate)
            ->orderByDesc('transaction_date')
            ->orderByDesc('posting_sequence')
            ->orderByDesc('line_no')
            ->orderByDesc('id')
            ->first();

        $runningBalance = (float) ($previous?->balance ?? 0);
        $runningBaseBalance = (float) ($previous?->base_balance ?? 0);

        foreach ($ledgers as $ledger) {
            $runningBaseBalance = $this->calculateBalance($balanceType, $runningBaseBalance, (float) $ledger->base_debit, (float) $ledger->base_credit);

            if ($isAccountBaseCurrency) {
                $runningBalance = $runningBaseBalance;
            } else {
                $runningBalance = $this->calculateBalance($balanceType, $runningBalance, (float) $ledger->debit, (float) $ledger->credit);
            }

            $ledger->update([
                'balance' => $runningBalance,
                'base_balance' => $runningBaseBalance,
            ]);
        }

        Account::whereKey($account->id)->update([
            'current_balance' => $runningBalance,
            'base_current_balance' => $runningBaseBalance,
            'last_transaction_date' => $ledgers->last()->transaction_date,
        ]);
    }

    /**
     * Calculate running balance.
     */
    protected function calculateBalance(BalanceType $balanceType, float $currentBalance, float $debit, float $credit): float
    {
        $balance = ($balanceType === BalanceType::DEBIT || $balanceType->value === 'debit')
            ? ($currentBalance + $debit - $credit)
            : ($currentBalance + $credit - $debit);

        return round($balance, 2);
    }

    /**
     * Get next posting sequence.
     */
    protected function nextPostingSequence(): int
    {
        $lastSeq = DB::table('journal_vouchers')
            ->whereNotNull('posting_sequence')
            ->orderByDesc('posting_sequence')
            ->lockForUpdate()
            ->value('posting_sequence');

        return ((int) $lastSeq) + 1;
    }

    protected function currentUserId(): ?int
    {
        return Auth::id();
    }
}
