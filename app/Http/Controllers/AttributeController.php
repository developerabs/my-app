<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\AttributeDataTable;
use App\Models\Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(AttributeDataTable $dataTable)
    {
        return $dataTable->render('backend.attributes.index');
    }

    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'name'      => 'required|string|max:255|unique:attributes,name',
            'is_active' => 'required|boolean',
            'values'    => 'required|array|min:1',
            'values.*'  => 'required|string|max:255',
            'color_codes' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            // Create Attribute
            $attribute = Attribute::create([
                'name'        => $request->name,
                'slug'        => Str::slug($request->name),
                'description' => $request->description,
                'is_color'    => filter_var($request->is_color, FILTER_VALIDATE_BOOLEAN),
                'is_active'   => $request->is_active,
            ]);

            // Create Attribute Values
            if ($request->has('values')) {
                $attributeValues = [];

                // Remove duplicate values from input
                $uniqueValues = array_unique($request->values);

                foreach ($uniqueValues as $index => $value) {
                    if (empty(trim($value))) continue;

                    $attributeValues[] = [
                        'value'      => trim($value),
                        // Color code is only added if is_color is true
                        'color_code' => $attribute->is_color ? ($request->color_codes[$index] ?? null) : null,
                    ];
                }

                // Create all attribute values at once
                if (!empty($attributeValues)) {
                    $attribute->values()->createMany($attributeValues);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => __('Attribute and values created successfully.')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log exception for debugging
            Log::error("Attribute Store Error: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => __('Something went wrong! Please check the logs.')
            ], 500);
        }
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load('values');
        return response()->json([
            'status' => 'success',
            'data'   => $attribute
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:attributes,name,' . $id,
            'is_active'   => 'required|boolean',
            'values'      => 'required|array|min:1',
            'value_ids'   => 'nullable|array',
            'color_codes' => 'nullable|array',
        ]);

        $attribute = Attribute::findOrFail($id);

        DB::beginTransaction();
        try {
            // Update Attribute
            $attribute->update([
                'name'        => $request->name,
                'slug'        => Str::slug($request->name),
                'description' => $request->description,
                'is_color'    => filter_var($request->is_color, FILTER_VALIDATE_BOOLEAN),
                'is_active'   => $request->is_active,
            ]);

            // Identify which values are being removed from the UI
            $keptIds = array_filter($request->value_ids ?? []);
            $valuesToDelete = $attribute->values()->whereNotIn('id', $keptIds)->get();

            foreach ($valuesToDelete as $oldValue) {
                //If the value is linked to products, prevent deletion
                if ($oldValue->productVariantOptions()->exists()) {
                    DB::rollBack(); // If any value cannot be deleted, roll back the entire transaction to maintain data integrity
                    return response()->json([
                        'status'  => 'error',
                        'message' => __("Cannot remove '{$oldValue->value}' as it is linked to existing products.")
                    ], 422);
                }
                // If it's safe to delete, proceed
                $oldValue->delete();
            }

            // Update existing values and add new ones
            foreach ($request->values as $index => $valueName) {
                if (empty(trim($valueName))) continue;

                $valueId = $request->value_ids[$index] ?? null;
                $colorCode = $attribute->is_color ? ($request->color_codes[$index] ?? null) : null;

                if ($valueId) {
                    // English comment: Update existing attribute values safely by ID
                    $attribute->values()->where('id', $valueId)->update([
                        'value'      => trim($valueName),
                        'color_code' => $colorCode
                    ]);
                } else {
                    // English comment: Add new values that didn't exist before
                    $attribute->values()->create([
                        'value'      => trim($valueName),
                        'color_code' => $colorCode
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status'  => 'success',
                'message' => __('Attribute updated with data integrity.')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Enterprise Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => __('Server Error. Please check logs.')
            ], 500);
        }
    }

    public function destroy(Attribute $attribute)
    {
        try {
            //Check if any of the attribute values are linked to product variant options
            $isUsedInProducts = $attribute->values()->whereHas('productVariantOptions')->exists();

            if ($isUsedInProducts) {
                return response()->json([
                    'status'  => 'error',
                    'message' => __('Cannot delete attribute! It is currently linked to product variations. Please remove those products or variations first.')
                ], 422);
            }

            // Safe to delete as no dependencies were found in the product tables
            $attribute->delete();

            return response()->json([
                'status'  => 'success',
                'message' => __('Attribute and all its values deleted successfully.')
            ]);
        } catch (\Exception $e) {
            // English comment: Log the exception for server-side debugging
            Log::error("Attribute Delete Error: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => __('Something went wrong! Could not delete the attribute.')
            ], 500);
        }
    }
}
