<?php

namespace App\Http\Controllers;

use App\DataTables\TaxDataTable;
use App\Models\Tax;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index(TaxDataTable $dataTable)
    {
        return $dataTable->render('backend.taxes.index');
    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name' => ['required', 'string', 'max:255', new UniqueWithTrashCheck(Tax::class, 'name')],
            'rate' => 'required|numeric',
        ]);

        try {
            Tax::create($validateData);
            return response()->json(['success' => true, 'message' => 'Tax created successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

    }

    public function edit(Tax $tax)
    {
        return response()->json(['success' => true, 'data' => $tax]);
    }

    public function update(Request $request, Tax $tax)
    {
        $validateData = $request->validate([
            'name' => ['required', 'string', 'max:255', new UniqueWithTrashCheck(Tax::class, 'name', $tax->id)],
            'rate' => 'required|numeric',
        ]);

        try {
            $tax->update($validateData);
            return response()->json(['success' => true, 'message' => 'Tax updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy(Tax $tax)
    {
        try {
            $tax->delete();
            return response()->json(['success' => true, 'message' => 'Tax deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
