<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    /**
     * Get balance sheet data with Sub-Ledger breakdown and Branch Permission Security.
     */
    public function getBalanceSheetData(?string $asOfDate = null, string|array|null $branchId = null): array
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        // ১. Account-level Totals
        $accountQuery = DB::table('general_ledgers')
            ->select('account_id')
            ->selectRaw('SUM(base_debit) as total_debit')
            ->selectRaw('SUM(base_credit) as total_credit')
            ->whereIn('status', ['posted', 'reversed'])
            ->whereDate('transaction_date', '<=', $asOfDate);

        $this->applyBranchScope($accountQuery, $branchId);

        $accountBalances = $accountQuery->groupBy('account_id')->get()->keyBy('account_id');

        // ২. Sub-Ledger-level Totals
        $subLedgerQuery = DB::table('general_ledgers')
            ->select('account_id', 'sub_ledger_type', 'sub_ledger_id')
            ->selectRaw('SUM(base_debit) as total_debit')
            ->selectRaw('SUM(base_credit) as total_credit')
            ->whereIn('status', ['posted', 'reversed'])
            ->whereNotNull('sub_ledger_id')
            ->whereNotNull('sub_ledger_type')
            ->whereDate('transaction_date', '<=', $asOfDate);

        $this->applyBranchScope($subLedgerQuery, $branchId);

        $subLedgerEntries = $subLedgerQuery
            ->groupBy('account_id', 'sub_ledger_type', 'sub_ledger_id')
            ->get();

        $subLedgerBalancesGrouped = $subLedgerEntries->groupBy('account_id');

        // ৩. Batch Load Sub-Ledger Models
        $subLedgerModels = $this->batchLoadSubLedgerModels($subLedgerEntries);

        // ৪. Build Account Trees
        $assetTree     = $this->buildTypeTree(AccountType::ASSET, $accountBalances, $subLedgerBalancesGrouped, $subLedgerModels);
        $liabilityTree = $this->buildTypeTree(AccountType::LIABILITY, $accountBalances, $subLedgerBalancesGrouped, $subLedgerModels);
        $equityTree    = $this->buildTypeTree(AccountType::EQUITY, $accountBalances, $subLedgerBalancesGrouped, $subLedgerModels);

        $totalAsset     = collect($assetTree)->sum('total_balance');
        $totalLiability = collect($liabilityTree)->sum('total_balance');
        $totalEquity    = collect($equityTree)->sum('total_balance');

        // ৫. Retained Earnings & Net Income
        $netIncomeData = $this->calculateRetainedEarningsAndNetIncome($asOfDate, $branchId);

        $totalEquity += $netIncomeData['prior_years_retained_earnings'] + $netIncomeData['current_year_net_income'];
        $totalLiabilitiesAndEquity = $totalLiability + $totalEquity;

        return [
            'as_of_date'                   => $asOfDate,
            'branch_id'                    => $branchId,
            'assets'                       => $assetTree,
            'total_asset'                  => round($totalAsset, 2),
            'liabilities'                  => $liabilityTree,
            'total_liability'              => round($totalLiability, 2),
            'equities'                     => $equityTree,
            'total_equity'                 => round($totalEquity, 2),
            'prior_years_retained_earnings'=> round($netIncomeData['prior_years_retained_earnings'], 2),
            'net_income'                   => round($netIncomeData['current_year_net_income'], 2),
            'total_liabilities_and_equity' => round($totalLiabilitiesAndEquity, 2),
            'is_balanced'                  => round($totalAsset, 2) === round($totalLiabilitiesAndEquity, 2),
        ];
    }

    protected function applyBranchScope($query, string|array|null $branchId, string $column = 'branch_id'): void
    {
        if (is_array($branchId)) {
            $query->whereIn($column, $branchId);
        } elseif (!empty($branchId)) {
            $query->where($column, $branchId);
        }
    }

    private function batchLoadSubLedgerModels($subLedgerEntries): array
    {
        $models = [];
        $groupedByType = $subLedgerEntries->groupBy('sub_ledger_type');

        foreach ($groupedByType as $type => $items) {
            if (class_exists($type)) {
                $ids = $items->pluck('sub_ledger_id')->unique()->filter()->toArray();
                if (!empty($ids)) {
                    $models[$type] = $type::whereIn('id', $ids)->get()->keyBy('id');
                }
            }
        }

        return $models;
    }

    private function buildTypeTree(AccountType|string $accountType, $accountBalances, $subLedgerBalancesGrouped, $subLedgerModels): array
    {
        $typeValue = $accountType instanceof \BackedEnum ? $accountType->value : $accountType;

        $chartOfAccounts = ChartOfAccount::with(['accounts'])
            ->where('account_type', $typeValue)
            ->get();

        $rootNodes = $chartOfAccounts->whereNull('parent_id');

        $tree = [];
        foreach ($rootNodes as $root) {
            $tree[] = $this->formatCoAChildren($root, $chartOfAccounts, $accountBalances, $subLedgerBalancesGrouped, $subLedgerModels);
        }

        return $tree;
    }

    private function formatCoAChildren($coaNode, $allCoaNodes, $accountBalances, $subLedgerBalancesGrouped, $subLedgerModels): array
    {
        $childrenNodes = $allCoaNodes->where('parent_id', $coaNode->id);

        $formattedChildren = [];
        $childrenTotal = 0;

        foreach ($childrenNodes as $child) {
            $formattedChild = $this->formatCoAChildren($child, $allCoaNodes, $accountBalances, $subLedgerBalancesGrouped, $subLedgerModels);
            $formattedChildren[] = $formattedChild;
            $childrenTotal += $formattedChild['total_balance'];
        }

        $isCreditNormal = $this->checkIsCreditNormal($coaNode);
        $ledgerTotal = 0;
        $directRoute = null;

        foreach ($coaNode->accounts as $ledgerAccount) {
            $subEntries = $subLedgerBalancesGrouped->get($ledgerAccount->id);
            $isDuplicateName = strtolower(trim($ledgerAccount->account_name)) === strtolower(trim($coaNode->name));

            if ($subEntries && $subEntries->isNotEmpty()) {
                $subLedgerChildren = [];
                $subTotal = 0;

                foreach ($subEntries as $subEntry) {
                    $subDebit = (float) $subEntry->total_debit;
                    $subCredit = (float) $subEntry->total_credit;
                    $subBalance = $isCreditNormal ? ($subCredit - $subDebit) : ($subDebit - $subCredit);

                    if ($subBalance != 0) {
                        $subType = $subEntry->sub_ledger_type;
                        $subId = $subEntry->sub_ledger_id;
                        $subModel = $subLedgerModels[$subType]->get($subId) ?? null;

                        $subName = $subModel->name ?? $subModel->company_name ?? $subModel->title ?? ('Sub-ledger #' . $subId);
                        $subCode = $subModel->code ?? $subModel->supplier_code ?? $subModel->customer_code ?? '';

                        $subLedgerChildren[] = [
                            'id'            => 'subledger-' . $ledgerAccount->id . '-' . $subId,
                            'code'          => $subCode,
                            'name'          => $subName,
                            'is_leaf'       => true,
                            'balance'       => round($subBalance, 2),
                            'total_balance' => round($subBalance, 2),
                            'children'      => [],
                            'route'         => route('reports.subledger', ['account_id' => $ledgerAccount->id, 'sub_ledger_id' => $subId]),
                        ];

                        $subTotal += $subBalance;
                    }
                }

                if (!empty($subLedgerChildren)) {
                    if ($isDuplicateName) {
                        foreach ($subLedgerChildren as $subChild) {
                            $formattedChildren[] = $subChild;
                        }
                        $directRoute = route('reports.ledger', ['account_id' => $ledgerAccount->id]);
                    } else {
                        $formattedChildren[] = [
                            'id'            => 'ledger-' . $ledgerAccount->id,
                            'code'          => $ledgerAccount->account_code,
                            'name'          => $ledgerAccount->account_name,
                            'is_leaf'       => false,
                            'balance'       => round($subTotal, 2),
                            'total_balance' => round($subTotal, 2),
                            'children'      => $subLedgerChildren,
                            'route'         => route('reports.ledger', ['account_id' => $ledgerAccount->id]),
                        ];
                    }
                    $ledgerTotal += $subTotal;
                }
            } else {
                $balData = $accountBalances->get($ledgerAccount->id);
                $debit = (float) ($balData->total_debit ?? 0);
                $credit = (float) ($balData->total_credit ?? 0);

                $balance = $isCreditNormal ? ($credit - $debit) : ($debit - $credit);

                if ($balance != 0) {
                    if ($isDuplicateName && $coaNode->accounts->count() === 1) {
                        $directRoute = route('reports.ledger', ['account_id' => $ledgerAccount->id]);
                    } else {
                        $formattedChildren[] = [
                            'id'            => 'ledger-' . $ledgerAccount->id,
                            'code'          => $ledgerAccount->account_code,
                            'name'          => $ledgerAccount->account_name,
                            'is_leaf'       => true,
                            'balance'       => round($balance, 2),
                            'total_balance' => round($balance, 2),
                            'children'      => [],
                            'route'         => route('reports.ledger', ['account_id' => $ledgerAccount->id]),
                        ];
                    }
                    $ledgerTotal += $balance;
                }
            }
        }

        $grandTotal = $childrenTotal + $ledgerTotal;

        return [
            'id'            => $coaNode->id,
            'code'          => $coaNode->code,
            'name'          => $coaNode->name,
            'is_leaf'       => $coaNode->is_leaf && count($formattedChildren) === 0,
            'balance'       => round($ledgerTotal, 2),
            'total_balance' => round($grandTotal, 2),
            'children'      => $formattedChildren,
            'route'         => $directRoute ?? null,
        ];
    }

    private function checkIsCreditNormal(ChartOfAccount $coaNode): bool
    {
        $balanceType = $coaNode->balance_type instanceof \BackedEnum 
            ? $coaNode->balance_type->value 
            : (string) $coaNode->balance_type;

        $accountType = $coaNode->account_type instanceof \BackedEnum 
            ? $coaNode->account_type->value 
            : (string) $coaNode->account_type;

        return strtolower($balanceType) === 'credit' || in_array(strtolower($accountType), ['liability', 'equity', 'income']);
    }

    private function calculateRetainedEarningsAndNetIncome(string $asOfDate, string|array|null $branchId = null): array
    {
        $fiscalYear = FiscalYear::whereDate('start_date', '<=', $asOfDate)
            ->whereDate('end_date', '>=', $asOfDate)
            ->first();

        $incomeTypeValue = AccountType::INCOME instanceof \BackedEnum ? AccountType::INCOME->value : 'income';
        $expenseTypeValue = AccountType::EXPENSE instanceof \BackedEnum ? AccountType::EXPENSE->value : 'expense';

        $currentYearNetIncome = 0.0;
        if ($fiscalYear) {
            $currentQuery = DB::table('general_ledgers as gl')
                ->join('accounts as a', 'gl.account_id', '=', 'a.id')
                ->join('chart_of_accounts as coa', 'a.chart_of_account_id', '=', 'coa.id')
                ->select('coa.account_type')
                ->selectRaw('SUM(gl.base_debit) as total_debit')
                ->selectRaw('SUM(gl.base_credit) as total_credit')
                ->whereIn('gl.status', ['posted', 'reversed'])
                ->whereDate('gl.transaction_date', '>=', $fiscalYear->start_date)
                ->whereDate('gl.transaction_date', '<=', $asOfDate)
                ->whereIn('coa.account_type', [$incomeTypeValue, $expenseTypeValue]);

            $this->applyBranchScope($currentQuery, $branchId, 'gl.branch_id');

            $currentSummary = $currentQuery->groupBy('coa.account_type')->get()->keyBy('account_type');

            $inc = $currentSummary->get($incomeTypeValue);
            $exp = $currentSummary->get($expenseTypeValue);

            $rev = ($inc->total_credit ?? 0) - ($inc->total_debit ?? 0);
            $ex  = ($exp->total_debit ?? 0) - ($exp->total_credit ?? 0);

            $currentYearNetIncome = $rev - $ex;
        }

        $priorYearsRetainedEarnings = 0.0;
        if ($fiscalYear) {
            $priorQuery = DB::table('general_ledgers as gl')
                ->join('accounts as a', 'gl.account_id', '=', 'a.id')
                ->join('chart_of_accounts as coa', 'a.chart_of_account_id', '=', 'coa.id')
                ->select('coa.account_type')
                ->selectRaw('SUM(gl.base_debit) as total_debit')
                ->selectRaw('SUM(gl.base_credit) as total_credit')
                ->whereIn('gl.status', ['posted', 'reversed'])
                ->whereDate('gl.transaction_date', '<', $fiscalYear->start_date)
                ->whereIn('coa.account_type', [$incomeTypeValue, $expenseTypeValue]);

            $this->applyBranchScope($priorQuery, $branchId, 'gl.branch_id');

            $priorSummary = $priorQuery->groupBy('coa.account_type')->get()->keyBy('account_type');

            $pInc = $priorSummary->get($incomeTypeValue);
            $pExp = $priorSummary->get($expenseTypeValue);

            $pRev = ($pInc->total_credit ?? 0) - ($pInc->total_debit ?? 0);
            $pEx  = ($pExp->total_debit ?? 0) - ($pExp->total_credit ?? 0);

            $priorYearsRetainedEarnings = $pRev - $pEx;
        }

        return [
            'prior_years_retained_earnings' => $priorYearsRetainedEarnings,
            'current_year_net_income'       => $currentYearNetIncome,
        ];
    }
}