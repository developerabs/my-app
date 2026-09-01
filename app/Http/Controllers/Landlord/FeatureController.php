<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\FeatureDataTable;
use App\Http\Controllers\Controller;
use App\Models\landlord\Feature;
use App\Models\landlord\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FeatureController extends Controller
{
    public function index(FeatureDataTable $dataTable)
    {
        return $dataTable->render('landlord.dashboard.package_manager.features');
    }
}
