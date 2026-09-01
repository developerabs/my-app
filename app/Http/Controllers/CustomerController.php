<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerDataTable;
use App\Models\CustomField;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Membership;
use App\Rules\UniqueWithTrashCheck;
use App\Services\Accounting\AccountingIntegrationService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    use HasFiles;

    public function index(CustomerDataTable $dataTable)
    {
        $customer_groups = CustomerGroup::active()->select('id', 'name', 'is_default')->get();
        $memberships = Membership::active()->select('id', 'name')->get();
        $custom_fields = CustomField::where('model_type', Customer::class)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return $dataTable->render('backend.customers.index', compact('customer_groups', 'memberships', 'custom_fields'));
    }

    /**
     * Store a newly created Customer with Accounting Opening Voucher
     */
    public function store(Request $request, AccountingIntegrationService $accIntegration)
    {
        $validated = $request->validate([
            // Primary Info
            'name'                 => ['required', 'string', 'max:255'],
            'phone'                => ['required', 'string', 'max:20', new UniqueWithTrashCheck(Customer::class, 'phone')],
            'email'                => ['nullable', 'string', 'email', 'max:255'],
            'customer_group_id'    => ['required', 'exists:customer_groups,id'],
            'membership_id'        => ['nullable', 'exists:memberships,id'],
            'opening_balance'      => ['nullable', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'string'],

            // Details Info
            'gender'               => ['nullable', 'in:male,female,other'],
            'date_of_birth'        => ['nullable', 'string'],
            'image'                => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg'],
            'company_name'         => ['nullable', 'string', 'max:255'],
            'tax_number'           => ['nullable', 'string', 'max:100'],
            'description'          => ['nullable', 'string'],

            // Address Info
            'full_address'         => ['nullable', 'string', 'max:500'],
            'division'             => ['nullable', 'string', 'max:255'],
            'district'             => ['nullable', 'string', 'max:255'],
            'upazila'              => ['nullable', 'string', 'max:255'],
            'state'                => ['nullable', 'string', 'max:255'],
            'country'              => ['nullable', 'string', 'max:255'],
            'city'                 => ['nullable', 'string', 'max:255'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'post_code'            => ['nullable', 'string', 'max:20'],
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

            // 1. Create Main Customer Record
            $customer = Customer::create([
                'name'                 => $validated['name'],
                'phone'                => $validated['phone'],
                'email'                => $validated['email'] ?? null,
                'customer_group_id'    => $validated['customer_group_id'],
                'membership_id'        => $validated['membership_id'] ?? null,
                'opening_balance'      => $openingBalance,
                'opening_balance_date' => $openingBalanceDate,
                'current_balance'      => $openingBalance,
                'last_transaction_date' => $openingBalanceDate,
            ]);

            // 2. Process Image and Create Details
            $customerImage = null;
            if ($request->hasFile('image')) {
                $customerImage = $this->processImage($request->file('image'), 'customers', [
                    'width'   => 500,
                    'quality' => 80
                ]);
            }

            $customer->details()->create([
                'image'         => $customerImage,
                'gender'        => $validated['gender'] ?? 'male',
                'date_of_birth' => $dateOfBirth,
                'company_name'  => $validated['company_name'] ?? null,
                'tax_number'    => $validated['tax_number'] ?? null,
                'description'   => $validated['description'] ?? null,
            ]);

            // 3. Create Primary Address
            $customer->addresses()->create([
                'address_type' => 'home',
                'is_primary'   => true,
                'full_address' => $validated['full_address'] ?? null,
                'division'     => $validated['division'] ?? null,
                'district'     => $validated['district'] ?? null,
                'upazila'      => $validated['upazila'] ?? null,
                'state'        => $validated['state'] ?? null,
                'country'      => $validated['country'] ?? 'Bangladesh',
                'city'         => $validated['city'] ?? null,
                'latitude'     => $validated['latitude'] ?? null,
                'longitude'    => $validated['longitude'] ?? null,
                'post_code'    => $validated['post_code'] ?? null,
            ]);

            if ($request->filled('custom_fields')) {
                $customer->saveCustomFields($request->custom_fields);
            }

            // 4. Clean 1-Line Accounting Integration Call
            $accIntegration->syncCustomerOpeningBalance($customer, $openingBalance, $openingBalanceDate);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => __('Customer created successfully.')
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Customer Store Failed: " . $e->getMessage(), [
                'request' => $request->except(['image', '_token'])
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'Error creating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Customer $customer)
    {
        $customer->load(['details', 'customerGroup', 'addresses', 'customFieldValues.customField']);

        return response()->json([
            'success' => true,
            'data'    => $customer,
            'image_url' => $customer->details ? $customer->details->image_url : null,
        ]);
    }

    public function edit(Customer $customer)
    {
        $customer->load(['details', 'customerGroup', 'primaryAddress', 'customFieldValues.customField']);
        
        return response()->json([
            'success' => true,
            'data'    => $customer,
            'image_url' => $customer->details ? $customer->details->image_url : null,
        ]);
    }

    /**
     * Update Customer with Opening Balance Reversal & Re-posting
     */
    public function update(Request $request, Customer $customer, AccountingIntegrationService $accIntegration)
    {
        $validated = $request->validate([
            // Primary Info
            'name'                 => ['required', 'string', 'max:255'],
            'phone'                => ['required', 'string', 'max:20', new UniqueWithTrashCheck(Customer::class, 'phone', $customer->id)],
            'email'                => ['nullable', 'string', 'email', 'max:255'],
            'customer_group_id'    => ['required', 'exists:customer_groups,id'],
            'membership_id'        => ['nullable', 'exists:memberships,id'],
            'opening_balance'      => ['nullable', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'string'],

            // Details Info
            'gender'               => ['nullable', 'in:male,female,other'],
            'date_of_birth'        => ['nullable', 'string'],
            'image'                => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg'],
            'company_name'         => ['nullable', 'string', 'max:255'],
            'tax_number'           => ['nullable', 'string', 'max:100'],
            'description'          => ['nullable', 'string'],

            // Address Info
            'full_address'         => ['nullable', 'string', 'max:500'],
            'division'             => ['nullable', 'string', 'max:255'],
            'district'             => ['nullable', 'string', 'max:255'],
            'upazila'              => ['nullable', 'string', 'max:255'],
            'state'                => ['nullable', 'string', 'max:255'],
            'country'              => ['nullable', 'string', 'max:255'],
            'city'                 => ['nullable', 'string', 'max:255'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'post_code'            => ['nullable', 'string', 'max:20'],
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

            // 1. Update Main Customer Record
            $customer->update([
                'name'                 => $validated['name'],
                'phone'                => $validated['phone'],
                'email'                => $validated['email'] ?? null,
                'customer_group_id'    => $validated['customer_group_id'],
                'membership_id'        => $validated['membership_id'] ?? null,
                'opening_balance'      => $newOpeningBalance,
                'opening_balance_date' => $newOpeningBalanceDate,
            ]);

            // 2. Handle Image and Details
            $details = $customer->details;
            $customerImage = $details->image ?? null;

            if ($request->hasFile('image')) {
                $customerImage = $this->processImage($request->file('image'), 'customers', [
                    'width'   => 500,
                    'quality' => 80
                ], $details->image ?? null);
            }

            $customer->details()->updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'image'         => $customerImage,
                    'gender'        => $validated['gender'] ?? 'male',
                    'date_of_birth' => $dateOfBirth,
                    'company_name'  => $validated['company_name'] ?? null,
                    'tax_number'    => $validated['tax_number'] ?? null,
                    'description'   => $validated['description'] ?? null,
                ]
            );

            // 3. Update Primary Address
            $customer->addresses()->updateOrCreate(
                ['is_primary' => true],
                [
                    'address_type' => 'home',
                    'full_address' => $validated['full_address'] ?? null,
                    'division'     => $validated['division'] ?? null,
                    'district'     => $validated['district'] ?? null,
                    'upazila'      => $validated['upazila'] ?? null,
                    'state'        => $validated['state'] ?? null,
                    'country'      => $validated['country'] ?? 'Bangladesh',
                    'city'         => $validated['city'] ?? null,
                    'latitude'     => $validated['latitude'] ?? null,
                    'longitude'    => $validated['longitude'] ?? null,
                    'post_code'    => $validated['post_code'] ?? null,
                ]
            );

            if ($request->has('custom_fields')) {
                $customer->saveCustomFields($request->custom_fields);
            }

            // 4. Clean 1-Line Accounting Integration Call (Reverses old & Posts new)
            $accIntegration->syncCustomerOpeningBalance($customer, $newOpeningBalance, $newOpeningBalanceDate);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Customer updated successfully.')
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("Customer Update Failed: " . $e->getMessage(), [
                'id'      => $customer->id,
                'request' => $request->except(['image', '_token'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Customer $customer, AccountingIntegrationService $accIntegration)
    {
        try {
            // Deletion Guard: Check if active transactions exist in General Ledger
            if ($accIntegration->hasActiveTransactions($customer)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete customer '{$customer->name}' because they have active transaction records. Please deactivate (set active = false) instead."
                ], 422);
            }

            DB::beginTransaction();

            // Reverse Opening Balance
            $accIntegration->syncCustomerOpeningBalance($customer, 0, now()->toDateString());

            $customer->delete(); // Soft Delete

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Customer deleted successfully.')
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Customer Deletion Failed: " . $e->getMessage(), ['id' => $customer->id]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting customer: ' . $e->getMessage()
            ], 500);
        }
    }
}