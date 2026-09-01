<?php

namespace App\Http\Controllers;

use App\DataTables\PurchaseDataTable;
use App\Http\Requests\Purchases\StorePurchaseRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tax;
use App\Services\Accounting\AccountingFormService;
use App\Services\Accounting\PurchaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    public function __construct(
        protected AccountingFormService $accformService,
        protected PurchaseService $purchaseService
    ) {}

    /**
     * Display a listing of purchases using PurchaseDataTable.
     */
    public function index(PurchaseDataTable $dataTable)
    {
        $suppliers = Cache::tags([tenant_tag()])->remember(
            'all_suppliers_'.tenant('id'),
            3600,
            fn () => Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'company_name'])
        );

        // 🟢 ফিক্স: শুধুমাত্র ইউজারের অনুমোদিত ব্রাঞ্চগুলোই ফিল্টার ড্রপডাউনে শো করবে
        $branches = get_auth_permitted_branches();

        return $dataTable->render('backend.purchases.index', compact('suppliers', 'branches'));
    }

    public function create()
    {
        $data = $this->accformService->getFormData();
        $suppliers = Cache::tags([tenant_tag()])->remember('all_suppliers_'.tenant('id'), 3600, fn () => Supplier::active()->get());
        $taxes = Cache::tags([tenant_tag()])->remember('all_taxes_'.tenant('id'), 3600, fn () => Tax::active()->get());

        return view('backend.purchases.create', $data, compact('suppliers', 'taxes'));
    }

    /**
     * Store a newly created purchase invoice in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        if (!user_can_access_all_branches() && !in_array($validated['branch_id'], get_auth_permitted_branch_ids())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => 'Unauthorized branch selected for purchase.'
            ]);
        }

        try {
            $purchase = $this->purchaseService->createPurchase(
                $request->validated(),
                $request->file('document')
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Purchase {$purchase->purchase_no} recorded and posted successfully!",
                    'data' => $purchase,
                ], 201);
            }

            return redirect()->route('purchases.index')
                ->with('success', "Purchase {$purchase->purchase_no} recorded successfully!");

        } catch (Exception $e) {
            Log::error('Purchase Creation Failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user' => auth()->id(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified purchase details with items, batches, and vouchers.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load([
            'items.product:id,name,code,sku',
            'items.variant:id,name,sku,code',
            'items.batch:id,batch_no,expiry_date',
            'items.purchaseUnit:id,name,short_name',
            'items.baseUnit:id,name,short_name',
            'items.tax:id,name,rate',
            'supplier',
            'branch',
            'currency',
            'orderTax',
            'payments.paymentAccount',
            'journalVoucher.entries.account',
        ]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $purchase,
            ]);
        }

        return view('backend.purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified purchase invoice.
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load([
            'items.product',
            'items.variant',
            'items.batch',
            'items.purchaseUnit',
        ]);

        $data = $this->accformService->getFormData();

        $suppliers = Cache::tags([tenant_tag()])->remember(
            'all_suppliers_'.tenant('id'),
            3600,
            fn () => Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'company_name'])
        );

        $taxes = Cache::tags([tenant_tag()])->remember(
            'all_taxes_'.tenant('id'),
            3600,
            fn () => Tax::active()->orderBy('rate')->get(['id', 'name', 'rate'])
        );

        return view('backend.purchases.edit', $data, compact('purchase', 'suppliers', 'taxes'));
    }

    /**
     * Update the specified purchase invoice in storage.
     */
    public function update(StorePurchaseRequest $request, Purchase $purchase)
    {
        $validated = $request->validated();

        // 🔒 সিকিউরিটি গার্ড: অন্য ব্রাঞ্চে জোরপূর্বক পারচেজ এন্ট্রি আটকানো
        if (!user_can_access_all_branches() && !in_array($validated['branch_id'], get_auth_permitted_branch_ids())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => 'Unauthorized branch selected for purchase.'
            ]);
        }

        try {
            $updatedPurchase = $this->purchaseService->updatePurchase(
                $purchase,
                $request->validated(),
                $request->file('document')
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Purchase {$updatedPurchase->purchase_no} updated and posted successfully!",
                    'data' => $updatedPurchase,
                ], 200);
            }

            return redirect()->route('purchases.index')
                ->with('success', "Purchase {$updatedPurchase->purchase_no} updated successfully!");

        } catch (Exception $e) {
            Log::error('Purchase Update Failed: '.$e->getMessage(), [
                'purchase_id' => $purchase->id,
                'trace' => $e->getTraceAsString(),
                'user' => auth()->id(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove / Cancel the specified purchase invoice with full reversal.
     */
    public function destroy(Request $request, Purchase $purchase)
    {
        try {
            $reason = $request->input('reason', 'Cancelled by user');
            $this->purchaseService->deletePurchase($purchase, $reason);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Purchase {$purchase->purchase_no} cancelled and reversed successfully.",
                ]);
            }

            return redirect()->route('purchases.index')
                ->with('success', "Purchase {$purchase->purchase_no} cancelled successfully.");

        } catch (Exception $e) {
            Log::error('Purchase Deletion Failed: '.$e->getMessage(), [
                'purchase_id' => $purchase->id,
                'user' => auth()->id(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
