<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Services\Accounting\Reports\TrialBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrialBalanceController extends Controller
{
    public function __construct(
        protected TrialBalanceService $trialBalanceService
    ) {}

    public function index(Request $request)
    {
        $fiscalYearId = $request->input('fiscal_year_id');
        $preset       = $request->input('period_preset', 'this_fiscal_year');
        
        $requestedBranchId  = $request->input('branch_id');
        $permittedBranchIds = get_auth_permitted_branch_ids();
        $branches           = get_auth_permitted_branches();

        if (user_can_access_all_branches()) {
            $effectiveBranch = $requestedBranchId ?: null;
        } else {
            $effectiveBranch = ($requestedBranchId && in_array($requestedBranchId, $permittedBranchIds))
                ? $requestedBranchId
                : $permittedBranchIds;
        }

        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $selectedFiscalYear = $fiscalYearId 
            ? $fiscalYears->firstWhere('id', $fiscalYearId) 
            : $fiscalYears->firstWhere('is_active', true) ?? $fiscalYears->first();

        $fyStart = $selectedFiscalYear ? Carbon::parse($selectedFiscalYear->start_date) : now()->startOfYear();
        $fyEnd   = $selectedFiscalYear ? Carbon::parse($selectedFiscalYear->end_date) : now()->endOfYear();

        $fromDate = null;
        $toDate = now()->toDateString();

        switch ($preset) {
            case 'this_fiscal_year':
                $fromDate = $fyStart->toDateString();
                $toDate = now()->isBetween($fyStart, $fyEnd) ? now()->toDateString() : $fyEnd->toDateString();
                break;
            case 'full_fiscal_year':
                $fromDate = $fyStart->toDateString();
                $toDate = $fyEnd->toDateString();
                break;
            case 'this_month':
                $fromDate = now()->startOfMonth()->toDateString();
                $toDate = now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $fromDate = now()->subMonth()->startOfMonth()->toDateString();
                $toDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'custom':
                $fromDate = $request->input('from_date');
                $toDate = $request->input('to_date', now()->toDateString());
                break;
            default:
                $fromDate = $fyStart->toDateString();
                $toDate = $fyEnd->toDateString();
                break;
        }

        $reportData = $this->trialBalanceService->getTrialBalanceData($fromDate, $toDate, $effectiveBranch);

        if ($request->ajax()) {
            return response()->json([
                'success'   => true,
                'data'      => $reportData,
                'from_date' => $fromDate,
                'to_date'   => $toDate,
            ]);
        }

        $branchId = $requestedBranchId;

        return view('backend.accounting.reports.trial_balance', array_merge($reportData, [
            'branches'           => $branches,
            'fiscalYears'        => $fiscalYears,
            'selectedFiscalYear' => $selectedFiscalYear,
            'period_preset'      => $preset,
            'from_date'          => $fromDate,
            'to_date'            => $toDate,
            'branchId'           => $branchId,
        ]));
    }
}