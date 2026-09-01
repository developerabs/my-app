<?php

namespace App\Http\Controllers;

use App\DataTables\MembershipDataTable;
use App\Models\Membership;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MembershipController extends Controller
{
    public function index(MembershipDataTable $dataTable)
    {
        return $dataTable->render('backend.memberships.index');
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => ['required', 'string', 'max:100', new UniqueWithTrashCheck(Membership::class, 'code')],
            'membership_fee'  => 'required|numeric|min:0',
            'minimum_spend'   => 'required|numeric|min:0',
            'validation_days' => 'required|integer|min:1',
            'discount_type'   => 'required|in:percentage,fixed',
            'discount_value'  => 'required|numeric|min:0',
            'benefits'        => 'nullable|array',
            'benefits.*'      => 'nullable|string',
            'is_active'       => 'sometimes|boolean', // Use 'sometimes' for optional boolean fields
        ]);

        // Cleanup benefits: remove null, empty, or dummy "0" values
        // This ensures only valid string values remain in the array
        $validated['benefits'] = array_filter($request->input('benefits', []), function ($value) {
            return !is_null($value) && $value !== '' && $value !== "0" && $value !== 0;
        });

        // Re-index the array to prevent key gaps after filtering
        $validated['benefits'] = array_values($validated['benefits']);

        // Ensure is_active is a boolean (handle checkbox presence)
        $validated['is_active'] = $request->boolean('is_active');

        try {
            $membership = Membership::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Membership created successfully.',
                //'data'    => $membership // Optional: return the created object
            ], 201); // 201 is more appropriate for 'Created' status

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Membership creation failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => 'Something went wrong. Please try again.'
            ], 500); // 500 for server errors
        }
    }

    public function edit(Membership $membership)
    {
        return response()->json($membership);
    }

    public function update(Request $request, Membership $membership)
    {
        //dd($request->all());
        // Validation logic
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => ['required', 'string', 'max:100', new UniqueWithTrashCheck(Membership::class, 'code', $membership->id)],
            'membership_fee'  => 'required|numeric|min:0',
            'minimum_spend'   => 'required|numeric|min:0',
            'validation_days' => 'required|integer|min:1',
            'discount_type'   => 'required|in:percentage,fixed',
            'discount_value'  => 'required|numeric|min:0',
            'benefits'        => 'nullable|array',
            'benefits.*'      => 'nullable|string',
            'is_active'       => 'sometimes', // Validating presence first
        ]);

        // Cleanup benefits properly
        // We get the raw input to ensure we are filtering the actual sent array
        $cleanBenefits = array_values(array_filter($request->input('benefits', []), function ($value) {
            return !is_null($value) && $value !== '' && $value !== "0" && $value !== 0;
        }));

        // Handle boolean for is_active
        $validated['is_active'] = $request->boolean('is_active');

        try {
            // Update the membership instance
            $membership->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Membership updated successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error("Membership update failed (ID: {$membership->id}): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => 'Something went wrong. Please try again.'
            ], 500);
        }
    }

    public function destroy(Membership $membership)
    {
        try {
            $membership->delete();

            return response()->json([
                'success' => true,
                'message' => 'Membership deleted successfully.'
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Membership deletion failed (ID: {$membership->id}): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => 'Something went wrong. Please try again.'
            ], 500);
        }
    }
}
