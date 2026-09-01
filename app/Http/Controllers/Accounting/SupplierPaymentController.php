<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreSupplierPaymentRequest;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Accounting\AccountingFormService;
use App\Services\Accounting\SupplierPaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SupplierPaymentController extends Controller
{
    public function __construct(
        protected SupplierPaymentService $paymentService,
        protected AccountingFormService $formService
    ) {}

    /**
     * Display listing of supplier payments with DataTable.
     */
    public function index()
    {
        return 'ok';
    }

    public function create(Request $request)
    {
        $data = $this->formService->getFormData();

        $suppliers = Cache::tags([tenant_tag()])->remember(
            'all_suppliers_'.tenant('id'),
            3600,
            fn () => Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'company_name', 'current_balance'])
        );

        $selectedSupplierId = $request->input('supplier_id');
        $selectedSupplier = $selectedSupplierId ? Supplier::find($selectedSupplierId) : null;

        return view('backend.accounting.payments.supplier_payments.create', array_merge($data, compact('suppliers', 'selectedSupplierId', 'selectedSupplier')));
    }

    /**
     * Store new Supplier Payment (Handles Quick Pay & Multi-Bill Pay).
     */
    public function store(StoreSupplierPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->createPayment(
                $request->validated(),
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => "Payment {$payment->payment_no} of ".format_currency($payment->amount).' processed successfully!',
                'data' => $payment,
            ], 201);

        } catch (Exception $e) {
            Log::error('Supplier Payment Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Fetch Open Bills & Purchases for a selected Supplier (QuickBooks Style).
     */
    public function getOpenInvoices(Request $request, Supplier $supplier): JsonResponse
    {
        try {
            $currencyId = $request->input('currency_id');
            $branchId = $request->input('branch_id');

            $data = $this->paymentService->getSupplierOpenInvoices($supplier->id, $currencyId, $branchId);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete Payment with Reversal.
     */
    public function destroy(Request $request, SupplierPayment $supplierPayment): JsonResponse
    {
        try {
            $this->paymentService->deletePayment($supplierPayment, $request->input('reason'));

            return response()->json([
                'success' => true,
                'message' => "Payment {$supplierPayment->payment_no} deleted and reversed successfully.",
            ]);
        } catch (Exception $e) {
            Log::error('Payment Deletion Failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
