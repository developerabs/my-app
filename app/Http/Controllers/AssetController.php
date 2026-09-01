<?php

namespace App\Http\Controllers;

use App\DataTables\AssetDataTable;
use App\DataTables\AssetRegisterDataTable;
use App\Enums\AssetEntryType;
use App\Enums\DepreciationMethod;
use App\Enums\LedgerAccountType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetRegister;
use App\Models\Bill;
use App\Models\Supplier;
use App\Rules\UniqueWithTrashCheck;
use App\Services\Accounting\AccountingFormService;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\Accounting\BillService;
use App\Services\Accounting\SupplierPaymentService;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetController extends Controller
{
    public function __construct(
        protected AccountingFormService $accformservice,
        protected BillService $billService,
        protected SupplierPaymentService $paymentService
    ) {}

    public function index(AssetDataTable $dataTable)
    {
        $accounts = Cache::tags([tenant_tag()])->rememberForever('asset_accounts_'.tenant('id'), fn () => Account::active()->where('account_type', LedgerAccountType::FIXED_ASSET)->get());

        return $dataTable->render('backend.assets.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'account_id'          => 'required|exists:accounts,id',
            'asset_code'          => ['required', 'string', new UniqueWithTrashCheck(Asset::class, 'asset_code')],
            'asset_name'          => 'required|string|max:255',
            'unit'                => 'nullable|string|max:50',
            'depreciation_method' => ['required', Rule::enum(DepreciationMethod::class)],
            'is_depreciable'      => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'notes'               => 'nullable|string',
        ]);

        try {
            $validatedData['is_depreciable'] = $request->boolean('is_depreciable', true);
            $validatedData['is_active'] = $request->boolean('is_active', true);
            $asset = Asset::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => __('file.message.asset_created'),
                'id'      => $asset->id,
            ]);
        } catch (Exception $e) {
            Log::error('Asset Creation Failed: '.$e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => __('file.message.asset_create_failed'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Asset $asset, AccountingIntegrationService $accIntegration)
    {
        try {
            if ($accIntegration->hasActiveTransactions($asset)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete asset '{$asset->asset_name}' because it has active transaction records attached. Please deactivate (is_active = false) instead.",
                ], 422);
            }

            $asset->delete();

            return response()->json(['success' => true, 'message' => 'Asset deleted successfully.']);
        } catch (Exception $e) {
            Log::error('Asset Delete Failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error deleting asset: '.$e->getMessage()], 500);
        }
    }

    public function destroyRegister(AssetRegister $register)
    {
        try {
            DB::beginTransaction();

            $register->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Asset Register deleted and accounting entries reversed successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Asset Register Delete Failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error deleting asset register: '.$e->getMessage()], 500);
        }
    }

    public function createAssetRegister()
    {
        $data = $this->accformservice->getFormData();
        $suppliers = Cache::tags([tenant_tag()])->remember('all_suppliers_'.tenant('id'), 3600, fn () => Supplier::active()->get());
        $asset_accounts = Cache::tags([tenant_tag()])->rememberForever('asset_accounts_'.tenant('id'), fn () => Account::active()->where('account_type', LedgerAccountType::FIXED_ASSET)->get());
        $assets = Cache::tags([tenant_tag()])->remember('all_assets_'.tenant('id'), 3600, fn () => Asset::active()->get());

        return view('backend.assets.create-register', $data, compact('asset_accounts', 'assets', 'suppliers'));
    }

    /**
     * Store Asset Register with Automated Supplier Grouped Billing & Zero Duplicate Accounting
     */
    public function storeAssetRegister(
        Request $request,
        AccountingIntegrationService $accIntegration,
        CurrencyConversionService $currencyService
    ) {
        $isPurchase = $request->entry_type === AssetEntryType::PURCHASE->value || $request->entry_type === 'purchase';

        $request->validate([
            'register_date'                   => 'required|string',
            'branch_id'                       => 'required|uuid',
            'currency_id'                     => 'required',
            'exchange_rate'                   => 'nullable|numeric|min:0.00000001',
            'entry_type'                      => 'required|in:'.AssetEntryType::OPENING->value.','.AssetEntryType::PURCHASE->value,
            'payment_account_id'              => $isPurchase ? 'nullable|exists:accounts,id' : 'nullable',
            'remarks'                         => 'nullable|string',
            'items'                           => 'required|array|min:1',
            'items.*.asset_id'                => 'required|exists:assets,id',
            'items.*.supplier_id'             => $isPurchase ? 'required|uuid|exists:suppliers,id' : 'nullable',
            'items.*.quantity'                => 'required|numeric|min:0.0001',
            'items.*.unit_cost'               => 'required|numeric|min:0',
            'items.*.total_cost'              => 'required|numeric|min:0',
            'items.*.paid_amount'             => 'nullable|numeric|min:0',
            'items.*.salvage_value'           => 'nullable|numeric|min:0',
            'items.*.useful_life'             => 'nullable|string',
            'items.*.depreciation_start_date' => 'nullable|string',
        ]);

        if (!user_can_access_all_branches() && !in_array($request->branch_id, get_auth_permitted_branch_ids())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'branch_id' => 'Unauthorized branch selected. You do not have permission for this branch.'
            ]);
        }

        try {
            return DB::transaction(function () use ($request, $isPurchase, $accIntegration, $currencyService) {

                $registerDate = Carbon::parse($request->register_date)->format('Y-m-d');

                $exchangeRate = ($request->filled('exchange_rate') && (float) $request->exchange_rate > 0)
                    ? (float) $request->exchange_rate
                    : $currencyService->getExchangeRate($request->currency_id);

                $totalCostSum = array_sum(array_column($request->items, 'total_cost'));
                $baseTotalCostSum = round($totalCostSum * $exchangeRate, 2);

                // 1. Generate Standard Asset Register Serial No (AST-REG-2026-000001)
                $registerNo = 'AST-REG-'.date('Y').'-'.sprintf('%06d', AssetRegister::withTrashed()->whereYear('register_date', date('Y'))->count() + 1);

                // 2. Create AssetRegister Master Record (No payment_account_id on master)
                $assetRegister = AssetRegister::create([
                    'register_no'        => $registerNo,
                    'branch_id'          => $request->branch_id,
                    'currency_id'        => $request->currency_id,
                    'exchange_rate'      => $exchangeRate,
                    'entry_type'         => $request->entry_type,
                    'register_date'      => $registerDate,
                    'total_cost'         => round($totalCostSum, 2),
                    'base_total_cost'    => $baseTotalCostSum,
                    'remarks'            => $request->remarks,
                    'created_by'         => auth()->id(),
                ]);

                // 3. Create AssetRegisterItem Records
                $createdItems = [];
                foreach ($request->items as $item) {
                    $quantity = (float) $item['quantity'];
                    $unitCost = (float) $item['unit_cost'];
                    $totalCost = (float) $item['total_cost'];
                    $paidAmount = (float) ($item['paid_amount'] ?? 0);
                    $salvageValue = (float) ($item['salvage_value'] ?? 0);

                    $depDate = !empty($item['depreciation_start_date'])
                        ? Carbon::parse($item['depreciation_start_date'])->format('Y-m-d')
                        : $registerDate;

                    $createdItem = $assetRegister->items()->create([
                        'asset_id'                => $item['asset_id'],
                        'supplier_id'             => $item['supplier_id'] ?? null,
                        'quantity'                => $quantity,
                        'remaining_quantity'      => $quantity,
                        'unit_cost'               => $unitCost,
                        'base_unit_cost'          => round($unitCost * $exchangeRate, 2),
                        'total_cost'              => $totalCost,
                        'base_total_cost'         => round($totalCost * $exchangeRate, 2),
                        'paid_amount'             => $paidAmount,
                        'base_paid_amount'        => round($paidAmount * $exchangeRate, 2),
                        'salvage_value'           => $salvageValue,
                        'base_salvage_value'      => round($salvageValue * $exchangeRate, 2),
                        'useful_life'             => $item['useful_life'] ?? null,
                        'depreciation_start_date' => $depDate,
                    ]);

                    $createdItems[] = array_merge($item, ['id' => $createdItem->id]);
                }

                // =========================================================================
                // 🚀 4. AUTOMATED GROUPED VENDOR BILL GENERATION (FOR ASSET PURCHASES)
                // =========================================================================
                if ($isPurchase) {
                    $itemsBySupplier = collect($createdItems)->groupBy('supplier_id');
                    $assetIds = array_column($request->items, 'asset_id');
                    $assetsMap = Asset::whereIn('id', $assetIds)->get()->keyBy('id');

                    foreach ($itemsBySupplier as $supplierId => $supplierItems) {
                        $billItems = [];
                        $totalSupplierAmount = 0.0;
                        $totalSupplierPaid = 0.0;

                        foreach ($supplierItems as $sItem) {
                            $assetModel = $assetsMap->get($sItem['asset_id']);
                            $lineCost = (float) $sItem['total_cost'];
                            $totalSupplierAmount += $lineCost;
                            $totalSupplierPaid += (float) ($sItem['paid_amount'] ?? 0);

                            // Bill line debits the specific Fixed Asset Account (12xx)
                            $billItems[] = [
                                'expense_account_id' => $assetModel->account_id, // 👈 e.g. 1260 IT Equipment
                                'amount'             => $lineCost,
                                'base_amount'        => round($lineCost * $exchangeRate, 2),
                                'description'        => "Asset Purchase: {$assetModel->asset_name} (Qty: {$sItem['quantity']}) [Ref: {$assetRegister->register_no}]",
                            ];
                        }

                        // Create Grouped Vendor Bill
                        $billData = [
                            'vendor_invoice_no' => "ASSET-{$assetRegister->register_no}",
                            'bill_date'         => $registerDate,
                            'due_date'          => $registerDate,
                            'supplier_id'       => $supplierId,
                            'branch_id'         => $request->branch_id,
                            'currency_id'       => $request->currency_id,
                            'exchange_rate'     => $exchangeRate,
                            'items'             => $billItems,
                            'note'              => "Auto-generated vendor bill for Asset Purchase Register: {$assetRegister->register_no}",
                        ];

                        $createdBill = $this->billService->createBill($billData);

                        // Link Bill ID to AssetRegisterItems
                        $itemIdsForThisSupplier = $supplierItems->pluck('id')->toArray();
                        $assetRegister->items()->whereIn('id', $itemIdsForThisSupplier)->update(['bill_id' => $createdBill->id]);

                        // 💡 Instant Payment Settlement if paid_amount > 0
                        if ($totalSupplierPaid > 0 && !empty($request->payment_account_id)) {
                            $paymentPayload = [
                                'supplier_id'        => $supplierId,
                                'payable_type'       => Bill::class,
                                'payable_id'         => $createdBill->id,
                                'payment_date'       => $registerDate,
                                'amount'             => min($totalSupplierPaid, $totalSupplierAmount),
                                'payment_account_id' => $request->payment_account_id,
                                'payment_method'     => 'cash',
                                'reference_no'       => $assetRegister->register_no,
                                'note'               => "Instant payment for Asset Bill: {$createdBill->bill_no}",
                            ];

                            $this->paymentService->createPayment($paymentPayload);
                        }
                    }
                } else {
                    // 5. Opening Balance Equity Voucher (Only for Opening Assets)
                    $voucher = $accIntegration->syncAssetRegister($assetRegister, $request->items, $request->entry_type);
                    if ($voucher) {
                        $assetRegister->updateQuietly(['journal_voucher_id' => $voucher->id]);
                    }
                }

                return redirect()->route('assets.register.index')
                    ->with('success', 'Asset register and accounting entries recorded successfully.');
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Asset Register Creation Failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user'  => auth()->id(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Error creating asset register: '.$e->getMessage());
        }
    }

    public function assetRegisterIndex(AssetRegisterDataTable $dataTable)
    {
        return $dataTable->render('backend.assets.register-index');
    }

    public function showAssetRegister(AssetRegister $register)
    {
        $register->load(['items.asset', 'branch', 'currency', 'creator']);

        return response()->json([
            'success' => true,
            'data'    => $register,
        ]);
    }
}