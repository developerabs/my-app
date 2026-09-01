<?php

namespace App\Http\Controllers;

use App\DataTables\UnitGroupDataTable;
use App\Models\UnitGroup;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Http\Request;

class UnitGroupController extends Controller
{
        public function index(UnitGroupDataTable $dataTable)
    {
        return $dataTable->render('backend.units.unit_group');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueWithTrashCheck(UnitGroup::class, 'name'),
            ],
            'description' => 'nullable|string',
        ]);

        try {
            UnitGroup::create($validate);
            return response()->json(['success' => true, 'message' => 'Unit group created successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function edit(UnitGroup $unitGroup)
    {
        return response()->json(['success' => true, 'data' => $unitGroup]);
    }

    public function update(Request $request, UnitGroup $unitGroup)
    {
        $validate = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueWithTrashCheck(UnitGroup::class, 'name', $unitGroup->id),
            ],
            'description' => 'nullable|string',
        ]);

        try {
            $unitGroup->update($validate);
            return response()->json(['success' => true, 'message' => 'Unit group updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy(UnitGroup $unitGroup)
    {
        if($unitGroup->units()->exists()) {
            return response()->json(['success' => false, 'message' => 'You cannot delete this unit group because it has units.']);
        }
        try {
            $unitGroup->delete();
            return response()->json(['success' => true, 'message' => 'Unit group deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getUnitsByGroup(UnitGroup $unitGroup)
    {
        return response()->json(['success' => true, 'data' => $unitGroup->units]);
    }

    public function getBaseUnitsByGroup(UnitGroup $unitGroup)
    {
        return response()->json(['success' => true, 'data' => $unitGroup->baseUnits]);
    }

}
