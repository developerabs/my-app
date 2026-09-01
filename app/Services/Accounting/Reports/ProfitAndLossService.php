<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitAndLossService
{
    public function getProfitAndLossData(?string $fromDate = null, ?string $toDate = null, string|array|null $branchId = null): array
    {
        $toDate = $toDate ? Carbon::parse($toDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        if (!$fromDate) {
            $fiscalYear = FiscalYear::whereDate('start_date', '<=', $toDate)
                ->whereDate('end_date', '>=', $toDate)
                ->first() ?? FiscalYear::where('status', 'current')->first() ?? FiscalYear::where('is_active', true)->first();

            $fromDate = $fiscalYear ? $fiscalYear->start_date->format('Y-m-d') : Carbon::parse($toDate)->startOfYear()->format('Y-m-d');
        } else {
            $fromDate = Carbon::parse($fromDate)->format('Y-m-d');
        }

        $incomeTypeValue = AccountType::INCOME instanceof \BackedEnum ? AccountType::INCOME->value : 'income';
        $expenseTypeValue = AccountType::EXPENSE instanceof \BackedEnum ? AccountType::EXPENSE->value : 'expense';

        $glQuery = DB::table('general_ledgers as gl')
            ->join('accounts as a', 'gl.account_id', '=', 'a.id')
            ->join('chart_of_accounts as coa', 'a.chart_of_account_id', '=', 'coa.id')
            ->select('gl.account_id', 'a.account_code', 'a.account_name', 'coa.code as coa_code', 'coa.account_type', 'coa.balance_type')
            ->selectRaw('SUM(gl.base_debit) as total_debit')
            ->selectRaw('SUM(gl.base_credit) as total_credit')
            ->whereIn('gl.status', ['posted', 'reversed'])
            ->whereDate('gl.transaction_date', '>=', $fromDate)
            ->whereDate('gl.transaction_date', '<=', $toDate)
            ->whereIn('coa.account_type', [$incomeTypeValue, $expenseTypeValue]);

        // 🟢 Branch Scope Guard
        if (is_array($branchId)) {
            $glQuery->whereIn('gl.branch_id', $branchId);
        } elseif (!empty($branchId)) {
            $glQuery->where('gl.branch_id', $branchId);
        }

        $accountBalances = $glQuery
            ->groupBy('gl.account_id', 'a.account_code', 'a.account_name', 'coa.code', 'coa.account_type', 'coa.balance_type')
            ->get()
            ->keyBy('account_id');

        $incomeCoa  = ChartOfAccount::with(['accounts'])->where('code', '4000')->first();
        $cogsCoa    = ChartOfAccount::with(['accounts'])->where('code', '5000')->first();
        $expenseCoa = ChartOfAccount::with(['accounts'])->where('code', '6000')->first();

        $chartOfAccounts = ChartOfAccount::with(['accounts'])
            ->whereIn('account_type', [$incomeTypeValue, $expenseTypeValue])
            ->orderBy('code')
            ->get();

        $revenueData = $incomeCoa ? $this->formatCoANode($incomeCoa, $chartOfAccounts, $accountBalances) : ['total_amount' => 0, 'tree' => null];
        $cogsData    = $cogsCoa ? $this->formatCoANode($cogsCoa, $chartOfAccounts, $accountBalances) : ['total_amount' => 0, 'tree' => null];
        $expenseData = $expenseCoa ? $this->formatCoANode($expenseCoa, $chartOfAccounts, $accountBalances) : ['total_amount' => 0, 'tree' => null];

        $totalRevenue = round($revenueData['total_amount'], 2);
        $totalCogs    = round($cogsData['total_amount'], 2);
        $grossProfit  = round($totalRevenue - $totalCogs, 2);

        $totalExpenses = round($expenseData['total_amount'], 2);
        $netProfit     = round($grossProfit - $totalExpenses, 2);

        return [
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'branch_id'      => $branchId,
            'revenue_tree'   => $revenueData['tree'],
            'total_revenue'  => $totalRevenue,
            'cogs_tree'      => $cogsData['tree'],
            'total_cogs'     => $totalCogs,
            'gross_profit'   => $grossProfit,
            'expense_tree'   => $expenseData['tree'],
            'total_expense'  => $totalExpenses,
            'net_profit'     => $netProfit,
            'is_profitable'  => $netProfit >= 0,
        ];
    }

    private function formatCoANode(ChartOfAccount $coaNode, $allCoaNodes, $accountBalances): array
    {
        $childrenNodes = $allCoaNodes->where('parent_id', $coaNode->id);
        $formattedChildren = [];
        $nodeTotal = 0.0;

        foreach ($childrenNodes as $child) {
            $childData = $this->formatCoANode($child, $allCoaNodes, $accountBalances);
            if ($childData['has_data']) {
                $formattedChildren[] = $childData['tree'];
                $nodeTotal += $childData['total_amount'];
            }
        }

        $leafAccounts = [];
        $isCreditNormal = $this->checkIsCreditNormal($coaNode);

        foreach ($coaNode->accounts as $account) {
            $balData = $accountBalances->get($account->id);

            if ($balData) {
                $debit = (float) $balData->total_debit;
                $credit = (float) $balData->total_credit;
                $netBalance = $isCreditNormal ? ($credit - $debit) : ($debit - $credit);

                if ($netBalance != 0) {
                    $leafAccounts[] = [
                        'account_id'   => $account->id,
                        'account_code' => $account->account_code,
                        'account_name' => $account->account_name,
                        'amount'       => round($netBalance, 2),
                        'route'        => route('reports.ledger', ['account_id' => $account->id]),
                    ];

                    $nodeTotal += $netBalance;
                }
            }
        }

        $hasData = (count($formattedChildren) > 0 || count($leafAccounts) > 0);

        return [
            'has_data'     => $hasData,
            'total_amount' => round($nodeTotal, 2),
            'tree'         => [
                'id'           => $coaNode->id,
                'code'         => $coaNode->code,
                'name'         => $coaNode->name,
                'account_type' => $coaNode->account_type,
                'total_amount' => round($nodeTotal, 2),
                'children'     => $formattedChildren,
                'accounts'     => $leafAccounts,
            ]
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

        return strtolower((string)$balanceType) === 'credit' 
            || in_array(strtolower((string)$accountType), ['income']);
    }
}