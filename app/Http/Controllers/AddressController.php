<?php

namespace App\Http\Controllers;

use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function lookup(Request $request, AddressService $addressService)
    {
        $query = $request->get('q');

        $provider = 'osm';
        $apiKey = null;

        $results = $addressService->search($query, $provider, $apiKey);

        return response()->json($results);
    }
}
