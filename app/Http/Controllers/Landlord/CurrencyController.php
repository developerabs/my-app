<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\CurrencyDataTable;
use App\Http\Controllers\Controller;
use App\Models\landlord\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(CurrencyDataTable $dataTable)
    {
        return $dataTable->render('landlord.dashboard.currencies.index');
    }

    public function edit(Currency $currency)
    {
        return response()->json($currency);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:currencies,code',
            'name' => 'required',
            'symbol' => 'nullable',
        ]);

        Currency::create($validated);

        return response()->json(['message' => 'Currency created successfully.']);
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'code' => 'required|unique:currencies,code,' . $currency->id,
            'name' => 'required',
            'symbol' => 'nullable',
        ]);

        $currency->update($validated);

        return response()->json(['message' => 'Currency updated successfully.']);
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();
        return response()->json(['message' => 'Currency deleted successfully.']);
    }
}
