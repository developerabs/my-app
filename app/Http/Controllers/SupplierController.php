<?php

namespace App\Http\Controllers;

use App\DataTables\SupplierDataTable;
use App\Models\CustomField;
use App\Models\Supplier;
use App\Rules\UniqueWithTrashCheck;
use App\Services\Accounting\AccountingIntegrationService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    use HasFiles;

    public function index(SupplierDataTable $dataTable)
    {
        $custom_fields = CustomField::where('model_type', Supplier::class)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return $dataTable->render('backend.suppliers.index', compact('custom_fields'));
    }

    /**
     * Store a newly created Supplier with Accounting Opening Voucher
     */
    public function store(Request $request, AccountingIntegrationService $accIntegration)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', new UniqueWithTrashCheck(Supplier::class, 'phone')],
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_tax_id' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_balance_date' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'bank_details' => 'nullable|array',
            'date_of_birth' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $openingBalanceDate = $request->filled('opening_balance_date')
                ? Carbon::parse($request->opening_balance_date)->format('Y-m-d')
                : now()->toDateString();

            $dateOfBirth = $request->filled('date_of_birth')
                ? Carbon::parse($request->date_of_birth)->format('Y-m-d')
                : null;

            $openingBalance = (float) ($request->opening_balance ?? 0);

            $address = [
                'full_address' => $request->full_address,
                'country' => $request->country,
                'division' => $request->division,
                'district' => $request->district,
                'upazila' => $request->upazila,
                'state' => $request->state,
                'city' => $request->city,
                'post_code' => $request->post_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ];

            $supplierImage = null;
            if ($request->hasFile('image')) {
                $supplierImage = $this->processImage($request->file('image'), 'suppliers', [
                    'width' => 300,
                    'quality' => 80,
                ]);
            }

            // Create Supplier
            $supplier = Supplier::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'company_name' => $request->company_name,
                'company_tax_id' => $request->company_tax_id,
                'opening_balance' => $openingBalance,
                'opening_balance_date' => $openingBalanceDate,
                'current_balance' => $openingBalance,
                'last_transaction_date' => $openingBalanceDate,
                'gender' => $request->gender,
                'bank_details' => $request->bank_details,
                'date_of_birth' => $dateOfBirth,
                'description' => $request->description,
                'image' => $supplierImage,
                'address' => $address,
            ]);

            if ($request->filled('custom_fields')) {
                $supplier->saveCustomFields($request->custom_fields);
            }

            // Clean 1-Line Accounting Integration Call
            $accIntegration->syncSupplierOpeningBalance($supplier, $openingBalance, $openingBalanceDate);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully',
                'data' => $supplier,
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Supplier Store Failed: ' . $e->getMessage(), [
                'request' => $request->except(['image', '_token']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('customFieldValues.customField');
        $supplier->append(['current_balance']);

        return response()->json([
            'success' => true,
            'data' => $supplier,
            'image_url' => $supplier->image ? $supplier->image_url : url('images/preview_image.png'),
        ]);
    }

    public function edit(Supplier $supplier)
    {
        $supplier->load('customFieldValues.customField');

        return response()->json([
            'success' => true,
            'data' => $supplier,
            'image_url' => $supplier->image ? $supplier->image_url : url('images/preview_image.png'),
        ]);
    }

    /**
     * Update Supplier with Opening Balance Reversal & Re-posting
     */
    public function update(Request $request, Supplier $supplier, AccountingIntegrationService $accIntegration)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', new UniqueWithTrashCheck(Supplier::class, 'phone', $supplier->id)],
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_tax_id' => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_balance_date' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'bank_details' => 'nullable|array',
            'date_of_birth' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $newOpeningBalanceDate = $request->filled('opening_balance_date')
                ? Carbon::parse($request->opening_balance_date)->format('Y-m-d')
                : now()->toDateString();

            $dateOfBirth = $request->filled('date_of_birth')
                ? Carbon::parse($request->date_of_birth)->format('Y-m-d')
                : null;

            $newOpeningBalance = (float) ($request->opening_balance ?? 0);

            $address = [
                'full_address' => $request->full_address,
                'country' => $request->country,
                'division' => $request->division,
                'district' => $request->district,
                'upazila' => $request->upazila,
                'state' => $request->state,
                'city' => $request->city,
                'post_code' => $request->post_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ];

            $supplierImage = $supplier->image;
            if ($request->hasFile('image')) {
                $supplierImage = $this->processImage($request->file('image'), 'suppliers', [
                    'width' => 300,
                    'quality' => 80,
                ], $supplier->image);
            }

            // Update Supplier Attributes
            $supplier->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'company_name' => $request->company_name,
                'company_tax_id' => $request->company_tax_id,
                'opening_balance' => $newOpeningBalance,
                'opening_balance_date' => $newOpeningBalanceDate,
                'gender' => $request->gender,
                'bank_details' => $request->bank_details,
                'date_of_birth' => $dateOfBirth,
                'description' => $request->description,
                'image' => $supplierImage,
                'address' => $address,
            ]);

            if ($request->filled('custom_fields')) {
                $supplier->saveCustomFields($request->custom_fields);
            }

            // Clean 1-Line Accounting Integration Call (Reverses old & Posts new)
            $accIntegration->syncSupplierOpeningBalance($supplier, $newOpeningBalance, $newOpeningBalanceDate);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Supplier Update Failed: ' . $e->getMessage(), [
                'request' => $request->except(['image', '_token']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Supplier $supplier, AccountingIntegrationService $accIntegration)
    {
        try {
            // Deletion Guard: Check if active transactions exist in General Ledger
            if ($accIntegration->hasActiveTransactions($supplier)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete supplier '{$supplier->name}' because they have active transaction records. Please deactivate (set active = false) instead."
                ], 422);
            }

            DB::beginTransaction();

            // Reverse Opening Balance
            $accIntegration->syncSupplierOpeningBalance($supplier, 0, now()->toDateString());

            $supplier->delete(); // Soft Delete

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Supplier Deletion Failed: ' . $e->getMessage(), ['supplier_id' => $supplier->id]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting supplier: ' . $e->getMessage(),
            ], 500);
        }
    }
}