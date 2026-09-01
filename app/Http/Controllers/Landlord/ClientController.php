<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\ClientDataTable;
use App\DataTables\Landlord\ClientDuesDataTable;
use App\Http\Controllers\Controller;
use App\Models\landlord\Package;
use App\Models\landlord\Payment;
use App\Models\landlord\Reseller;
use App\Models\landlord\ResellerClient;
use App\Models\landlord\ResellerPayment;
use App\Models\landlord\Tenant;
use App\Models\landlord\ClientNote;
use App\Models\landlord\TenantAddon;
use App\Models\landlord\TenantFeatureOverride;
use App\Models\landlord\TenantModule;
use App\Traits\ManageTenants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientController extends Controller
{

    use ManageTenants;


    public function index(ClientDataTable $dataTable)
    {
        $packages = Package::active()->get();
        $resellers = Reseller::active()->get();

        return $dataTable->render('landlord.dashboard.clients.index', ['packages' => $packages, 'resellers' => $resellers]);
    }



    public function clientDues(ClientDuesDataTable $dataTable)
    {
        return $dataTable->render('landlord.dashboard.client_dues.index');
    }

    public function storenote(Request $request)
    {
        $request->validate([
            'note' => 'required|string',
            'reseller_client_id' => 'required|integer|exists:reseller_clients,id'
        ]);

        $resellerClient = ResellerClient::find($request->reseller_client_id);

        if (!$resellerClient) {
            return back()->with('error', 'Reseller client not found.');
        }

        ClientNote::create([
            'tenant_id' => $resellerClient->tenant_id,
            'reseller_client_id' => $request->reseller_client_id,
            'note' => $request->note,
            'added_by' => Auth::id()
        ]);

        return back()->with('success', 'Note added successfully');
    }

    public function getClientNotes($id)
    {
        $notes = ClientNote::with('user:id,name')
            ->where('reseller_client_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $notes = $notes->map(function ($note) {
            return [
                'note' => $note->note,
                'added_by' => $note->user?->name ?? 'Unknown User',
                'created_at' => $note->created_at->format('d M, Y H:i'),
            ];
        });

        return response()->json(['notes' => $notes]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required|integer|exists:packages,id',
            'reseller_id' => 'nullable|integer',
            'subscription_type' => 'required|string',
            'business_name' => 'required|string',
            'email' => 'required|string|email',
            'username' => 'required|string',
            'phone' => 'required|string',
            'password' => 'required|string|confirmed',
            'tenant' => [
                'required',
                'string',
                'unique:tenants,id',
                function ($attribute, $value, $fail) {
                    $tenantSlug = Str::slug($value);
                    $dbName = config('tenancy.database.prefix', 'sherazipos_') . $tenantSlug;
                    $exists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
                    if (!empty($exists)) {
                        $fail('DataBase Name Already Exists');
                    }
                }
            ],
            'custom_domain' => 'nullable|string|unique:domains,domain',
            'registration_fee' => 'required|numeric',
            'subscription_fee' => 'required|numeric',
        ]);

        $user = Auth::user();
        if ($request->reseller_id != 0 && $user && $user->reseller && $user->reseller->type === 'external') {
            $package = Package::find($request->package_id);
            if ($package && $request->registration_fee < $package->reseller_min_reg_fee) {
                return response()->json(['error' => 'Registration fee must be greater than or equal to ' . $package->reseller_min_reg_fee], 422);
            }
        }

        $request->has('is_trial') ? $request->merge(['is_trial' => true]) : $request->merge(['is_trial' => false]);
        $response = $this->createTenantFromDashboard($request->all());

        if ($response['status'] === 201) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tenant created successfully'
            ]);
        }
    }

    public function destroy($id)
    {
        // 1. Fetch the tenant or fail if not found
        $tenant = Tenant::findOrFail($id);

        try {
            // 2. Prepare MinIO config for cleanup
            $s3Config = config('filesystems.disks.s3');
            $s3Config['root'] = ''; // Ensure we can access the 'tenants/' base folder
            $s3Config['use_path_style_endpoint'] = true; // Required for MinIO
            $s3Config['region'] = env('AWS_DEFAULT_REGION', 'us-east-1');

            // 3. Register temporary disk and clear cache
            config(['filesystems.disks.s3_cleanup' => $s3Config]);
            Storage::forgetDisk('s3_cleanup');

            $path = "tenants/" . $tenant->id;

            // 4. Perform Storage Cleanup with a safety check
            if (!empty($tenant->id) && strlen($path) > 8) {
                // Direct deleteDirectory is the most reliable way for MinIO
                Storage::disk('s3_cleanup')->deleteDirectory($path);
                Log::info("Minio storage cleared for tenant: " . $tenant->id);
            }
            $cacheTag = 'tenant_' . $tenant->id;
            Cache::tags([$cacheTag])->flush();
        } catch (\Exception $e) {
            // Log the error but continue deleting database records
            Log::error("Minio cleanup failed for tenant {$id}: " . $e->getMessage());
        }

        // 5. Delete Related Records (This will now run correctly)
        foreach ($tenant->domains as $domain) {
            $domain->delete();
        }

        TenantAddon::where('tenant_id', $id)->delete();
        TenantModule::where('tenant_id', $id)->delete();
        TenantFeatureOverride::where('tenant_id', $id)->delete();

        $reseller_client = ResellerClient::where('tenant_id', $id)->first();
        if ($reseller_client) {
            $reseller_client->delete();
        }

        Payment::where('tenant_id', $id)->delete();
        ResellerPayment::where('tenant_id', $id)->delete();

        // 6. Delete the main tenant record
        $tenant->delete();

        // 7. Finally return the response
        return response()->json([
            'status' => 'success',
            'message' => 'Tenant and associated storage deleted successfully.'
        ]);
    }
}
