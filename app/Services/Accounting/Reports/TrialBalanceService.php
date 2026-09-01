<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    public function getTrialBalanceData(?string $fromDate = null, ?string $toDate = null, string|array|null $branchId = null): array
    {
        $toDate = $toDate ? Carbon::parse($toDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $fromDate = $fromDate ? Carbon::parse($fromDate)->format('Y-m-d') : null;

        $glQuery = DB::table('general_ledgers as gl')
            ->join('accounts as a', 'gl.account_id', '=', 'a.id')
            ->join('chart_of_accounts as coa', 'a.chart_of_account_id', '=', 'coa.id')
            ->select('gl.account_id', 'a.account_code', 'a.account_name', 'coa.account_type', 'coa.balance_type')
            ->selectRaw('SUM(gl.base_debit) as total_debit')
            ->selectRaw('SUM(gl.base_credit) as total_credit')
            ->whereIn('gl.status', ['posted', 'reversed']);

        if ($fromDate && $toDate) {
            $glQuery->whereBetween('gl.transaction_date', [$fromDate, $toDate]);
        } elseif ($toDate) {
            $glQuery->whereDate('gl.transaction_date', '<=', $toDate);
        }

        // 🟢 Branch Scope Guard
        if (is_array($branchId)) {
            $glQuery->whereIn('gl.branch_id', $branchId);
        } elseif (!empty($branchId)) {
            $glQuery->where('gl.branch_id', $branchId);
        }

        $accountBalances = $glQuery
            ->groupBy('gl.account_id', 'a.account_code', 'a.account_name', 'coa.account_type', 'coa.balance_type')
            ->get()
            ->keyBy('account_id');

        $chartOfAccounts = ChartOfAccount::with(['accounts'])->orderBy('code')->get();
        $rootNodes = $chartOfAccounts->whereNull('parent_id');

        $formattedTree = [];
        $grandTotalDebit = 0.0;
        $grandTotalCredit = 0.0;

        foreach ($rootNodes as $root) {
            $formattedGroup = $this->formatCoANode($root, $chartOfAccounts, $accountBalances);
            if ($formattedGroup['has_data']) {
                $formattedTree[] = $formattedGroup;
                $grandTotalDebit += $formattedGroup['total_debit'];
                $grandTotalCredit += $formattedGroup['total_credit'];
            }
        }

        $grandTotalDebit = round($grandTotalDebit, 2);
        $grandTotalCredit = round($grandTotalCredit, 2);

        return [
            'from_date'          => $fromDate,
            'to_date'            => $toDate,
            'branch_id'          => $branchId,
            'report_tree'        => $formattedTree,
            'grand_total_debit'  => $grandTotalDebit,
            'grand_total_credit' => $grandTotalCredit,
            'is_balanced'        => $grandTotalDebit === $grandTotalCredit,
            'difference'         => round(abs($grandTotalDebit - $grandTotalCredit), 2),
        ];
    }

    private function formatCoANode($coaNode, $allCoaNodes, $accountBalances): array
    {
        $childrenNodes = $allCoaNodes->where('parent_id', $coaNode->id);
        $formattedChildren = [];
        $nodeDebitSum = 0.0;
        $nodeCreditSum = 0.0;

        foreach ($childrenNodes as $child) {
            $formattedChild = $this->formatCoANode($child, $allCoaNodes, $accountBalances);
            if ($formattedChild['has_data']) {
                $formattedChildren[] = $formattedChild;
                $nodeDebitSum += $formattedChild['total_debit'];
                $nodeCreditSum += $formattedChild['total_credit'];
            }
        }

        $leafAccounts = [];
        foreach ($coaNode->accounts as $account) {
            $balData = $accountBalances->get($account->id);

            if ($balData) {
                $totalDebit = (float) $balData->total_debit;
                $totalCredit = (float) $balData->total_credit;
                $isCreditNormal = $this->checkIsCreditNormal($coaNode);

                $netDebit = 0.0;
                $netCredit = 0.0;

                if ($isCreditNormal) {
                    $netBalance = $totalCredit - $totalDebit;
                    if ($netBalance >= 0) {
                        $netCredit = $netBalance;
                    } else {
                        $netDebit = abs($netBalance);
                    }
                } else {
                    $netBalance = $totalDebit - $totalCredit;
                    if ($netBalance >= 0) {
                        $netDebit = $netBalance;
                    } else {
                        $netCredit = abs($netBalance);
                    }
                }

                if ($netDebit > 0 || $netCredit > 0) {
                    $leafAccounts[] = [
                        'account_id'   => $account->id,
                        'account_code' => $account->account_code,
                        'account_name' => $account->account_name,
                        'debit'        => round($netDebit, 2),
                        'credit'       => round($netCredit, 2),
                        'route'        => route('reports.ledger', ['account_id' => $account->id]),
                    ];

                    $nodeDebitSum += $netDebit;
                    $nodeCreditSum += $netCredit;
                }
            }
        }

        $hasData = (count($formattedChildren) > 0 || count($leafAccounts) > 0);

        return [
            'id'           => $coaNode->id,
            'code'         => $coaNode->code,
            'name'         => $coaNode->name,
            'account_type' => $coaNode->account_type,
            'has_data'     => $hasData,
            'total_debit'  => round($nodeDebitSum, 2),
            'total_credit' => round($nodeCreditSum, 2),
            'children'     => $formattedChildren,
            'accounts'     => $leafAccounts,
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

        return strtolower($balanceType) === 'credit' 
            || in_array(strtolower($accountType), ['liability', 'equity', 'income']);
    }
}