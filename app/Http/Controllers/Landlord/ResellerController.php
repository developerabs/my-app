<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\ResellerDataTable;
use App\Http\Controllers\Controller;
use App\Models\landlord\Reseller;
use App\Models\User;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ResellerController extends Controller
{
    use HasFiles;

    public function index(ResellerDataTable $dataTable)
    {
        return $dataTable->render('landlord.dashboard.reseller.index');
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,name'],
            'type' => ['required', 'in:internal,external'],
            'phone' => ['required', 'string', 'max:255', 'unique:resellers,phone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'address' => ['nullable', 'string', 'max:255'],
            'commission_per_registration' => ['required', 'numeric', 'between:0,100'],
            'commission_per_subscription' => ['required', 'numeric', 'between:0,100'],
        ]);

        DB::beginTransaction();

        try {
            $companyLogoPath = $request->hasFile('company_logo')
                ? $this->uploadFiles($request, 'company_logo', 'landlord/resellers')
                : null;

            $reseller = Reseller::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'type' => $validated['type'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'company_name' => $validated['company_name'] ?? null,
                'company_logo' => $companyLogoPath,
                'address' => $validated['address'] ?? null,
                'commission_per_registration' => $validated['commission_per_registration'],
                'commission_per_subscription' => $validated['commission_per_subscription'],
            ]);

            $user = User::create([
                'name' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'company_name' => $validated['company_name'] ?? null,
                'role_id' => 4,
                'reseller_id' => $reseller->id
            ])->assignRole('Reseller');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reseller created successfully!'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            
            report($th);

            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function edit(Reseller $reseller)
    {
        if ($reseller->company_logo) {
            $reseller['company_logo'] = asset('storage/'.$reseller->company_logo);
        }
        return response()->json([
            'reseller' => $reseller
        ]);
    }

    public function update(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'unique:resellers,phone,' . $reseller->id],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($reseller->user->id)],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'address' => ['nullable', 'string', 'max:255'],
            'commission_per_registration' => ['required', 'numeric', 'between:0,100'],
            'commission_per_subscription' => ['required', 'numeric', 'between:0,100'],
        ]);

        DB::beginTransaction();

        try {
            
            $companyLogoPath = $request->hasFile('company_logo')
                ? $this->uploadFiles($request, 'company_logo', 'landlord/resellers')
                : $reseller->company_logo;

            $reseller->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'company_name' => $validated['company_name'] ?? null,
                'company_logo' => $companyLogoPath,
                'address' => $validated['address'] ?? null,
                'commission_per_registration' => $validated['commission_per_registration'],
                'commission_per_subscription' => $validated['commission_per_subscription'],
            ]);

            if($reseller->user){
                $reseller->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'company_name' => $validated['company_name'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reseller updated successfully!'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            
            report($th);

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong!',
            ], 500);
        }
    }

    public function destroy(Reseller $reseller)
    {
        try {
            $reseller->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Reseller deleted successfully!'
            ], 200);
        } catch (\Throwable $th) {
            report($th);
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong!',
            ], 500);
        }
    }
}
