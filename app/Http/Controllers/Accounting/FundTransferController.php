<?php

namespace App\Http\Controllers\Accounting;

use App\DataTables\FundTransferDataTable;
use App\Enums\LedgerAccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FundTransfers\StoreFundTransferRequest;
use App\Models\Account;
use App\Models\FundTransfer;
use App\Services\Accounting\AccountingFormService;
use App\Services\Accounting\FundTransferService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FundTransferController extends Controller
{
    public function __construct(
        protected FundTransferService $transferService,
        protected AccountingFormService $accformservice
    ) {}

    public function index(FundTransferDataTable $dataTable)
    {
        // Liquid Payment Accounts Only (Cash, Bank, Mobile)
        $paymentAccounts = Account::active()
            ->whereIn('account_type', LedgerAccountType::paymentAccounts())
            ->orderBy('account_name')
            ->get();

        $formFormData = $this->accformservice->getFormData();

        return $dataTable->render('backend.accounting.fund_transfers.index', array_merge(
            $formFormData,
            compact('paymentAccounts')
        ));
    }

    public function store(StoreFundTransferRequest $request)
    {
        try {
            $transfer = $this->transferService->createTransfer(
                $request->validated(), 
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => "Fund transfer {$transfer->transfer_no} created & posted successfully.",
                'data' => $transfer,
            ], 201);

        } catch (Exception $e) {
            Log::error('Fund Transfer Creation Failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process transfer: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(FundTransfer $fundTransfer)
    {
        $fundTransfer->load(['fromAccount', 'toAccount', 'branch', 'currency', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $fundTransfer,
            'attachment_url' => $fundTransfer->attachment ? $this->transferService->getFileUrl($fundTransfer->attachment, 's3') : null,
        ]);
    }

    public function edit(FundTransfer $fundTransfer)
    {
        $fundTransfer->load(['fromAccount', 'toAccount']);

        return response()->json([
            'success' => true,
            'data' => $fundTransfer,
        ]);
    }

    public function update(StoreFundTransferRequest $request, FundTransfer $fundTransfer)
    {
        try {
            $updatedTransfer = $this->transferService->updateTransfer(
                $fundTransfer,
                $request->validated(),
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => "Fund transfer {$updatedTransfer->transfer_no} updated & re-posted successfully.",
                'data' => $updatedTransfer,
            ]);

        } catch (Exception $e) {
            Log::error('Fund Transfer Update Failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update transfer: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, FundTransfer $fundTransfer)
    {
        try {
            $reason = $request->input('reason', 'Fund transfer cancelled/deleted by user');
            
            $this->transferService->deleteTransfer($fundTransfer, $reason);

            return response()->json([
                'success' => true,
                'message' => "Fund transfer {$fundTransfer->transfer_no} deleted and contra voucher reversed."
            ]);

        } catch (Exception $e) {
            Log::error('Fund Transfer Deletion Failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transfer: ' . $e->getMessage()
            ], 500);
        }
    }
}