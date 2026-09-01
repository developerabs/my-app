<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\FinanceCharge;
use App\Services\Accounting\FinanceChargeService;
use Exception;
use Illuminate\Http\Request;

class FinanceChargeController extends Controller
{
    public function __construct(
        protected FinanceChargeService $chargeService
    ) {}

    /**
     * 1. Waive Finance Charge directly by Charge ID
     */
    public function waive(Request $request, FinanceCharge $financeCharge)
    {
        $request->validate([
            'waive_amount' => 'required|numeric|min:0.01|max:' . $financeCharge->amount,
            'reason'       => 'nullable|string|max:500',
        ]);

        try {
            $reason = $request->input('reason', 'Waived by manager');
            $waiveAmount = (float) $request->waive_amount;

            $this->chargeService->waiveCharge($financeCharge, $waiveAmount, $reason);

            return response()->json([
                'success' => true,
                'message' => "Finance charge {$financeCharge->charge_no} waived successfully.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to waive charge: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. Waive Finance Charge by Document (Bill / Invoice / AssetRegister)
     */
    public function waiveByDocument(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|in:bill,invoice,asset_register',
            'document_id'   => 'required|string',
            'waive_amount'   => 'required|numeric|min:0.01',
            'reason'        => 'nullable|string|max:500',
        ]);

        try {
            // ডকুমেন্টস টাইপ অনুযায়ী মডেল ক্লাসের নাম বের করা
            $modelClass = match($request->document_type) {
                'bill'           => Bill::class,
                default          => throw new Exception("Invalid document type"),
            };

            $document = $modelClass::findOrFail($request->document_id);

            // ওই ডকুমেন্টের পোস্ট হওয়া লাস্ট এক্টিভ ফিন্যান্স চার্জ বের করা
            $charge = $document->financeCharges()
                ->whereIn('status', ['posted', 'partially_waived'])
                ->latest('id')
                ->first();

            if (!$charge) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active finance charges found for this document.',
                ], 404);
            }

            $waiveAmount = min((float) $request->waive_amount, (float) $charge->amount);
            $reason = $request->input('reason', 'Waived by manager during settlement');

            $this->chargeService->waiveCharge($charge, $waiveAmount, $reason);

            return response()->json([
                'success' => true,
                'message' => "Late fee charge {$charge->charge_no} waived successfully.",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to waive charge: ' . $e->getMessage(),
            ], 500);
        }
    }
}