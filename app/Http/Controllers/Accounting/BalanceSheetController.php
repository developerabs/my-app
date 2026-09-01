<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\Reports\BalanceSheetService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BalanceSheetController extends Controller
{
    public function __construct(
        protected BalanceSheetService $balanceSheetService
    ) {}

    public function index(Request $request)
    {
        $rawDate  = $request->input('as_of_date');
        $asOfDate = $rawDate ? Carbon::parse($rawDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        
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

        $reportData = $this->balanceSheetService->getBalanceSheetData($asOfDate, $effectiveBranch);

        if ($request->wantsJson()) {
            return response()->json($reportData);
        }

        $branchId = $requestedBranchId;

        return view('backend.accounting.reports.balance-sheet', compact('reportData', 'asOfDate', 'branchId', 'branches'));
    }
}