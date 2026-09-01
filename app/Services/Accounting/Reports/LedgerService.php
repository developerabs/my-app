<?php

namespace App\Services\Accounting\Reports;

use App\Models\Account;
use App\Models\Customer;
use App\Models\GeneralLedger;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class LedgerService
{
   /**
     * Get paginated ledger report data with Multi-Currency, Sub-Ledger, and Branch Authorization.
     * Fixed: Uses official spot exchange rate to eliminate reverse division rounding artifacts.
     *
     * @param int $accountId
     * @param string $fromDate
     * @param string $toDate
     * @param string|null $subLedgerId
     * @param string|array|null $branchId
     * @param int $perPage
     * @return array
     */
    public function getLedgerReport(
        int $accountId,
        string $fromDate,
        string $toDate,
        ?string $subLedgerId = null,
        string|array|null $branchId = null,
        int $perPage = 100
    ): array {
        // 1. Eager load Account, Chart of Account, and its native Currency
        $account = Account::with(['chartOfAccount', 'currency'])->findOrFail($accountId);
        $isCreditNormal = $this->checkIsCreditNormal($account);

        $defaultCurrency  = view()->shared('default_currency') ?? [];
        $baseCurrencyCode = $defaultCurrency['code'] ?? 'BDT';
        $baseCurrencyId   = Setting::get('default_currency');
        $isAccountForeignCurrency = (!empty($account->currency_id) && $account->currency_id != $baseCurrencyId);

        $currencyService = app(\App\Services\CurrencyConversionService::class);

        // 🟢 পেপ্যাল (USD) বা ফরেন অ্যাকাউন্টের অফিশিয়াল ফিক্সড স্পট রেট (যেমন: 121.60)
        $accountOfficialRate = $isAccountForeignCurrency 
            ? (float) $currencyService->getExchangeRate($account->currency_id) 
            : 1.00;

        // 2. Resolve Sub-Ledger Model Instance (Supplier or Customer)
        $subLedgerModel = null;
        if (!empty($subLedgerId)) {
            $subLedgerEntry = GeneralLedger::where('account_id', $accountId)
                ->where('sub_ledger_id', (string) $subLedgerId)
                ->whereNotNull('sub_ledger_type')
                ->first();

            if ($subLedgerEntry && !empty($subLedgerEntry->sub_ledger_type) && class_exists($subLedgerEntry->sub_ledger_type)) {
                $subLedgerModel = $subLedgerEntry->sub_ledger_type::find($subLedgerId);
            }

            if (!$subLedgerModel) {
                if (class_exists(Supplier::class)) $subLedgerModel = Supplier::find($subLedgerId);
                if (!$subLedgerModel && class_exists(Customer::class)) $subLedgerModel = Customer::find($subLedgerId);
            }
        }

        // 3. Initial Opening Balance Query (Transactions strictly before from_date)
        $priorQuery = DB::table('general_ledgers')
            ->where('account_id', $accountId)
            ->whereIn('status', ['posted', 'reversed'])
            ->whereDate('transaction_date', '<', $fromDate);

        if (!empty($subLedgerId)) { 
            $priorQuery->where('sub_ledger_id', (string) $subLedgerId); 
        }

        // 🔒 Branch Permission Guard on Opening Balance
        if (is_array($branchId)) {
            $priorQuery->whereIn('branch_id', $branchId);
        } elseif (!empty($branchId)) {
            $priorQuery->where('branch_id', $branchId);
        }

        // Functional / Base Currency (BDT) Opening Balance
        $openingBaseDebit  = (float) $priorQuery->sum('base_debit');
        $openingBaseCredit = (float) $priorQuery->sum('base_credit');
        $initialOpeningBaseBalance = $isCreditNormal
            ? ($openingBaseCredit - $openingBaseDebit)
            : ($openingBaseDebit - $openingBaseCredit);

        // Account Native Currency (e.g. USD) Opening Balance
        $openingNativeDebit  = (float) $priorQuery->sum('debit');
        $openingNativeCredit = (float) $priorQuery->sum('credit');
        $initialOpeningNativeBalance = $isCreditNormal
            ? ($openingNativeCredit - $openingNativeDebit)
            : ($openingNativeDebit - $openingNativeCredit);

        // 4. Date Range Entries Query
        $entriesQuery = GeneralLedger::query()
            ->with([
                'voucher.reversalOf', 
                'voucher.reversedVoucher', 
                'voucher.currency', 
                'currency', 
                'branch'
            ])
            ->where('account_id', $accountId)
            ->whereIn('status', ['posted', 'reversed'])
            ->whereBetween('transaction_date', [$fromDate, $toDate]);

        if (!empty($subLedgerId)) { 
            $entriesQuery->where('sub_ledger_id', (string) $subLedgerId); 
        }

        // 🔒 Branch Permission Guard on Date Range Entries
        if (is_array($branchId)) {
            $entriesQuery->whereIn('branch_id', $branchId);
        } elseif (!empty($branchId)) {
            $entriesQuery->where('branch_id', $branchId);
        }

        // Summary Totals for the specified Date Range
        $rangeSummary = (clone $entriesQuery)
            ->reorder()
            ->selectRaw('
                SUM(base_debit) as total_base_debit, 
                SUM(base_credit) as total_base_credit, 
                SUM(debit) as total_native_debit, 
                SUM(credit) as total_native_credit
            ')
            ->first();

        $totalBaseDebit    = (float) ($rangeSummary->total_base_debit ?? 0);
        $totalBaseCredit   = (float) ($rangeSummary->total_base_credit ?? 0);
        $totalNativeDebit  = (float) ($rangeSummary->total_native_debit ?? 0);
        $totalNativeCredit = (float) ($rangeSummary->total_native_credit ?? 0);

        $entriesQuery->orderBy('transaction_date', 'asc')
            ->orderBy('posting_sequence', 'asc')
            ->orderBy('line_no', 'asc');

        // 5. Server-Side Pagination
        $paginatedEntries = $entriesQuery->paginate($perPage)->withQueryString();

        // 6. Pagination Offset Balance Calculation for continuous Running Balance
        $currentPage = $paginatedEntries->currentPage();
        $pageOffset  = ($currentPage - 1) * $perPage;

        if ($pageOffset > 0) {
            $priorOffsetSummary = (clone $entriesQuery)->take($pageOffset)->get();
            
            $offsetBaseDebit  = $priorOffsetSummary->sum('base_debit');
            $offsetBaseCredit = $priorOffsetSummary->sum('base_credit');

            $pageOpeningBaseBalance = $isCreditNormal
                ? $initialOpeningBaseBalance + ($offsetBaseCredit - $offsetBaseDebit)
                : $initialOpeningBaseBalance + ($offsetBaseDebit - $offsetBaseCredit);

            $offsetNativeDebit  = $priorOffsetSummary->sum('debit');
            $offsetNativeCredit = $priorOffsetSummary->sum('credit');

            $pageOpeningNativeBalance = $isCreditNormal
                ? $initialOpeningNativeBalance + ($offsetNativeCredit - $offsetNativeDebit)
                : $initialOpeningNativeBalance + ($offsetNativeDebit - $offsetNativeCredit);
        } else {
            $pageOpeningBaseBalance   = $initialOpeningBaseBalance;
            $pageOpeningNativeBalance = $initialOpeningNativeBalance;
        }

        // 7. Dual-Currency Row-by-Row Running Balance Computation
        $runningBaseBalance   = $pageOpeningBaseBalance;
        $runningNativeBalance = $pageOpeningNativeBalance;

        foreach ($paginatedEntries as $entry) {
            $baseDebit  = (float) $entry->base_debit;
            $baseCredit = (float) $entry->base_credit;

            $nativeDebit  = (float) $entry->debit;
            $nativeCredit = (float) $entry->credit;

            if ($isCreditNormal) {
                $runningBaseBalance   += ($baseCredit - $baseDebit);
                $runningNativeBalance += ($nativeCredit - $nativeDebit);
            } else {
                $runningBaseBalance   += ($baseDebit - $baseCredit);
                $runningNativeBalance += ($nativeDebit - $nativeCredit);
            }

            $rowCurrencyCode = $entry->currency->code ?? $baseCurrencyCode;
            $isForeignRow    = ($rowCurrencyCode !== $baseCurrencyCode) || $isAccountForeignCurrency;

            $entry->is_foreign_row           = $isForeignRow;
            $entry->row_currency_code        = $rowCurrencyCode;
            // 🟢 ফিক্স: ভাগ করে রেট বের না করে সরাসরি অ্যাকাউন্টের অফিশিয়াল রেট (@ 121.60) সেট করা
            $entry->row_account_rate         = $accountOfficialRate;
            $entry->row_running_base_balance = round($runningBaseBalance, 2);
            $entry->row_running_balance      = $isAccountForeignCurrency ? round($runningNativeBalance, 2) : round($runningBaseBalance, 2);
        }

        // 8. Ending Balance Computation
        $endingBaseBalance = $isCreditNormal
            ? $initialOpeningBaseBalance + ($totalBaseCredit - $totalBaseDebit)
            : $initialOpeningBaseBalance + ($totalBaseDebit - $totalBaseCredit);

        $endingNativeBalance = $isCreditNormal
            ? $initialOpeningNativeBalance + ($totalNativeCredit - $totalNativeDebit)
            : $initialOpeningNativeBalance + ($totalNativeDebit - $totalNativeCredit);

        return [
            'account'                     => $account,
            'sub_ledger'                  => $subLedgerModel,
            'sub_ledger_id'               => $subLedgerId,
            'branch_id'                   => $branchId,
            'base_currency_code'          => $baseCurrencyCode,
            'is_account_foreign'          => $isAccountForeignCurrency,
            
            // Base Currency Balances (BDT)
            'opening_balance'             => round($initialOpeningBaseBalance, 2),
            'page_opening_balance'        => round($pageOpeningBaseBalance, 2),
            'ending_balance'              => round($endingBaseBalance, 2),
            
            // Native Account Currency Balances (e.g. USD)
            'opening_native_balance'      => round($initialOpeningNativeBalance, 2),
            'page_opening_native_balance' => round($pageOpeningNativeBalance, 2),
            'ending_native_balance'       => round($endingNativeBalance, 2),
            
            'total_debit'                 => round($totalBaseDebit, 2),
            'total_credit'                => round($totalBaseCredit, 2),
            'entries'                     => $paginatedEntries,
        ];
    }

    private function checkIsCreditNormal(Account $account): bool
    {
        $balanceType = $account->chartOfAccount?->balance_type;
        if ($balanceType instanceof \BackedEnum) { $balanceType = $balanceType->value; }

        $accountType = $account->chartOfAccount?->account_type;
        if ($accountType instanceof \BackedEnum) { $accountType = $accountType->value; }

        return strtolower((string) $balanceType) === 'credit'
            || in_array(strtolower((string) $accountType), ['liability', 'equity', 'income']);
    }
}