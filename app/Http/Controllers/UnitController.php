<?php

namespace App\Http\Controllers;

use App\DataTables\UnitDataTable;
use App\Models\Trash;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Rules\UniqueWithTrashCheck;
use App\Rules\ValidMathFormula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index(UnitDataTable $dataTable)
    {
        $unitGroups = UnitGroup::all('id', 'name');
        return $dataTable->render('backend.units.unit', compact('unitGroups'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            'group_id'       => 'required|exists:unit_groups,id',
            'name'           => 'required|string|max:100',
            'short_name'     => 'required|string|max:20',
            'is_base_unit'   => 'nullable|boolean',
            'precision'      => 'required|integer|min:0|max:10',
            'base_unit_id'   => 'required_without:is_base_unit|nullable|exists:units,id',
            'logic_type'     => 'required_without:is_base_unit|nullable|in:operator,formula',
            'operator'       => 'required_if:logic_type,operator|nullable|in:*,/',
            'operator_value' => 'required_if:logic_type,operator|nullable|numeric',
            'formula'        => [
                'required_if:logic_type,formula',
                'nullable',
                new ValidMathFormula('x')
            ],
            'display_units'  => 'nullable|array',
        ]);

        // Custom validation for checking name uniqueness and soft-deleted records
        $validator->after(function ($validator) use ($request) {
            if (!$request->name || !$request->group_id) return;

            $activeExists = Unit::where('name', $request->name)
                ->where('group_id', $request->group_id)
                ->exists();

            if ($activeExists) {
                $validator->errors()->add('name', "This unit name is already taken in this group.");
                return;
            }

            $trashed = Unit::onlyTrashed()
                ->where('name', $request->name)
                ->where('group_id', $request->group_id)
                ->first();

            if ($trashed) {
                if (auth()->user()->can('manage_trash')) {
                    $trashId = Trash::where('trashable_type', Unit::class)
                        ->where('trashable_id', $trashed->id)
                        ->value('id');

                    $validator->errors()->add('name', json_encode([
                        'is_trashed' => true,
                        'id'         => $trashId,
                        'name'       => $trashed->name,
                        'message'    => "This unit is already in trash for this group. Restore it?"
                    ]));
                } else {
                    $validator->errors()->add('name', "This unit name for this group is in trash.");
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $unit = new Unit();
            $unit->group_id     = $request->group_id;
            $unit->name         = $request->name;
            $unit->short_name   = $request->short_name;
            $unit->description  = $request->description;

            // Handle boolean conversion explicitly
            $isBaseUnit = filter_var($request->is_base_unit, FILTER_VALIDATE_BOOLEAN);
            $unit->is_base_unit = $isBaseUnit;
            $unit->precision    = $request->precision;

            if (!$isBaseUnit) {
                $unit->base_unit_id = $request->base_unit_id;

                if ($request->logic_type === 'formula') {
                    $unit->is_formulaic = true;
                    $unit->formula      = $request->formula; // Saving the formula string

                    // Extract variables and store in JSON column
                    $extractedVars = $this->extractVariables($request->formula);

                    $unit->display_params = [
                        'variables' => $extractedVars,
                        'hierarchy' => $request->display_units ?? []
                    ];
                } else {
                    $unit->is_formulaic   = false;
                    $unit->operator       = $request->operator ?? '*';
                    $unit->operator_value = $request->operator_value ?? 1.0;
                    $unit->display_params = [
                        'hierarchy' => $request->display_units ?? []
                    ];
                }
            } else {
                // Reset fields if it is a base unit
                $unit->base_unit_id = null;
                $unit->is_formulaic = false;
            }

            $unit->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Unit saved successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        // English comment: Prepare hierarchy data for the frontend to populate rows easily.
        $hierarchy = $unit->display_params['hierarchy'] ?? [];

        return response()->json([
            'success' => true,
            'data' => $unit,
            'hierarchy' => $hierarchy
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $unit = Unit::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'group_id'       => 'required|exists:unit_groups,id',
            'name'           => 'required|string|max:100',
            'short_name'     => 'required|string|max:20',
            'is_base_unit'   => 'nullable|boolean',
            'precision'      => 'required|integer|min:0|max:10',
            'base_unit_id'   => 'required_without:is_base_unit|nullable|exists:units,id',
            'logic_type'     => 'required_without:is_base_unit|nullable|in:operator,formula',
            'operator'       => 'required_if:logic_type,operator|nullable|in:*,/',
            'operator_value' => 'required_if:logic_type,operator|nullable|numeric',
            'formula'        => ['required_if:logic_type,formula', 'nullable', new ValidMathFormula('x')],
            'display_units'  => 'nullable|array',
        ]);

        $validator->after(function ($validator) use ($request, $id) {
            if (!$request->name || !$request->group_id) return;

            // English comment: Check uniqueness excluding current record
            $activeExists = Unit::where('name', $request->name)
                ->where('group_id', $request->group_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($activeExists) {
                $validator->errors()->add('name', "This unit name is already taken in this group.");
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $unit->group_id     = $request->group_id;
            $unit->name         = $request->name;
            $unit->short_name   = $request->short_name;

            $isBaseUnit = filter_var($request->is_base_unit, FILTER_VALIDATE_BOOLEAN);
            $unit->is_base_unit = $isBaseUnit;
            $unit->precision    = $request->precision;

            $hierarchy = ['hierarchy' => $request->display_units ?? []];

            if (!$isBaseUnit) {
                $unit->base_unit_id = $request->base_unit_id;
                if ($request->logic_type === 'formula') {
                    $unit->is_formulaic = true;
                    $unit->formula      = $request->formula;
                    $unit->display_params = array_merge($hierarchy, [
                        'variables' => $this->extractVariables($request->formula)
                    ]);
                    $unit->operator = null;
                    $unit->operator_value = null;
                } else {
                    $unit->is_formulaic   = false;
                    $unit->formula        = null;
                    $unit->operator       = $request->operator ?? '*';
                    $unit->operator_value = $request->operator_value ?? 1.0;
                    $unit->display_params = $hierarchy;
                }
            } else {
                $unit->base_unit_id = null;
                $unit->is_formulaic = false;
                $unit->formula      = null;
                $unit->operator     = null;
                $unit->operator_value = null;
                $unit->display_params = $hierarchy;
            }

            $unit->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Unit updated successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Unit $unit)
    {
        if ($unit->subUnits()->exists()) {
            return response()->json(['message' => 'You cannot delete this unit because it has sub-units.'], 422);
        }

        try {
            $unit->delete();
            return response()->json(['success' => true, 'message' => 'Unit deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function extractVariables($formula)
    {
        // English comment: Identify all words (variables), ignoring 'x' and standard math functions.
        preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $formula, $matches);

        $reserved = ['x', 'round', 'ceil', 'floor', 'abs', 'sqrt', 'pow', 'sin', 'cos', 'tan'];

        $variables = array_filter(array_unique($matches[0]), function ($var) use ($reserved) {
            return !in_array(strtolower($var), $reserved);
        });

        return array_values($variables);
    }

    public function getSubUnits(Unit $unit)
    {
        // English: Eager load the recursive relationship before returning
        $unit->load('allSubUnits');

        return response()->json([
            'success' => true,
            'data' => $unit->allSubUnits // এটি এখন কালেকশন রিটার্ন করবে, নাল নয়
        ]);
    }
}
