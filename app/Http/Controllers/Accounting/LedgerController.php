<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\Reports\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService
    ) {}

    public function index(Request $request, $account_id)
    {
        $fromDateInput = $request->input('from_date');
        $toDateInput   = $request->input('to_date');

        $fromDate = $fromDateInput ? Carbon::parse($fromDateInput)->format('Y-m-d') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate   = $toDateInput ? Carbon::parse($toDateInput)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        $subLedgerId = $request->input('sub_ledger_id');
        $requestedBranchId = $request->input('branch_id');
        $perPage = (int) $request->input('per_page', 100);

        // 🔒 ১. ব্রাঞ্চ পারমিশন গার্ড ও ড্রপডাউন রেজোলিউশন
        $permittedBranchIds = get_auth_permitted_branch_ids();
        $branches           = get_auth_permitted_branches(); // শুধুমাত্র অনুমোদিত ব্রাঞ্চ ভিউতে যাবে

        if (user_can_access_all_branches()) {
            $effectiveBranch = $requestedBranchId ?: null; // সুপার এডমিনের জন্য null = Consolidated
        } else {
            $effectiveBranch = ($requestedBranchId && in_array($requestedBranchId, $permittedBranchIds))
                ? $requestedBranchId
                : $permittedBranchIds; // রেস্ট্রিক্টেড ইউজারের জন্য অনুমোদিত ব্রাঞ্চের অ্যারে
        }

        $reportData = $this->ledgerService->getLedgerReport(
            $account_id,
            $fromDate,
            $toDate,
            $subLedgerId,
            $effectiveBranch,
            $perPage
        );

        $branchId = $requestedBranchId;

        return view('backend.accounting.reports.ledger', compact('reportData', 'fromDate', 'toDate', 'branchId', 'branches'));
    }

    public function subLedgerIndex($accountId, $subLedgerId, Request $request)
    {
        $fromDateInput = $request->input('from_date');
        $toDateInput   = $request->input('to_date');

        $fromDate = $fromDateInput ? Carbon::parse($fromDateInput)->format('Y-m-d') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate   = $toDateInput ? Carbon::parse($toDateInput)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        $requestedBranchId = $request->input('branch_id');
        $perPage = (int) $request->input('per_page', 100);

        // 🔒 ব্রাঞ্চ পারমিশন রেজোলিউশন
        $permittedBranchIds = get_auth_permitted_branch_ids();
        $branches           = get_auth_permitted_branches();

        if (user_can_access_all_branches()) {
            $effectiveBranch = $requestedBranchId ?: null;
        } else {
            $effectiveBranch = ($requestedBranchId && in_array($requestedBranchId, $permittedBranchIds))
                ? $requestedBranchId
                : $permittedBranchIds;
        }

        $reportData = $this->ledgerService->getLedgerReport(
            $accountId,
            $fromDate,
            $toDate,
            $subLedgerId,
            $effectiveBranch,
            $perPage
        );

        $branchId = $requestedBranchId;

        return view('backend.accounting.reports.ledger', compact('reportData', 'fromDate', 'toDate', 'branchId', 'branches'));
    }
}