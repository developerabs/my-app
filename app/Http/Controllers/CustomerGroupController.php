<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerGroupDataTable;
use App\Models\CustomerGroup;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerGroupController extends Controller
{
    public function index(CustomerGroupDataTable $dataTable)
    {
        return $dataTable->render('backend.customer_group.index');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100', new UniqueWithTrashCheck(CustomerGroup::class, 'name')], 
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $customerGroup = new CustomerGroup();
            $customerGroup->name = $validatedData['name'];
            $customerGroup->discount_type = $validatedData['discount_type'];
            $customerGroup->discount_value = $validatedData['discount_value'];
            $customerGroup->min_order_amount = $validatedData['min_order_amount'];
            $customerGroup->is_active = $validatedData['is_active'] ?? false;
            $customerGroup->is_default = $validatedData['is_default'] ?? false;
            $customerGroup->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Customer group created successfully.'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function edit(CustomerGroup $customerGroup)
    {
        return response()->json($customerGroup);
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:100', new UniqueWithTrashCheck(CustomerGroup::class, 'name', $customerGroup->id)], 
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $customerGroup->name = $validatedData['name'];
            $customerGroup->discount_type = $validatedData['discount_type'];
            $customerGroup->discount_value = $validatedData['discount_value'];
            $customerGroup->min_order_amount = $validatedData['min_order_amount'];
            $customerGroup->is_active = $validatedData['is_active'] ?? false;
            $customerGroup->is_default = $validatedData['is_default'] ?? false;
            $customerGroup->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Customer group updated successfully.'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        if ($customerGroup->is_default) {
            return response()->json(['success' => false, 'message' => 'You cannot delete this customer group because it is default.'], 422);
        }

        try {
            $customerGroup->delete();
            return response()->json(['success' => true, 'message' => 'Customer group deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
