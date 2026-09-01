<?php

namespace App\Traits;

use App\Mail\Landlord\TenantCreatedMail;
use App\Models\landlord\FeaturePermission;
use App\Models\landlord\Package;
use App\Models\landlord\PackageFeature;
use App\Models\landlord\PackagePricing;
use App\Models\landlord\Payment;
use App\Models\landlord\Reseller;
use App\Models\landlord\ResellerClient;
use App\Models\landlord\ResellerPayment;
use App\Models\landlord\Tenant;
use App\Models\landlord\TenantModule;
use App\Models\landlord\Withdraw;
use App\Models\Setting;
use App\Models\User;
use App\Services\Central\LandlordService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Symfony\Component\Clock\now;

trait ManageTenants
{
    public function createTenantFromDashboard(array $request)
    {
        $package = Package::findOrFail($request['package_id']);
        $modules = $package->modules()->get();
        $tenantId = Str::slug($request['tenant']);
        $receivedPayment = $request['received_payment'] ?? 0;
        $registrationFee = $request['registration_fee'] ?? 0;

        if ($request['is_trial'] && $package->is_trial) {
            $trial_ends_at = Carbon::now()->addDays($package->meta['trial_period']);
            $status = 'trial';
        } else {
            $trial_ends_at = null;
            $status = 'active';
        }

        $expireDate = $this->getExpireDate($request, $package);

        $data = [
            'tenant' => $request['tenant'],
            'package_id' => $request['package_id'] ?? null,
            'business_name' => $request['business_name'] ?? null,
            'email' => $request['email'] ?? null,
            'phone' => $request['phone'] ?? null,
            'reseller_id' => $request['reseller_id'] ?? 0,
            'expires_at' => $expireDate,
            'subscription_type' => $request['subscription_type'] ?? null,
            'registration_fee' => $registrationFee,
            'subscription_fee' => $request['subscription_fee'] ?? 0,
            'trial_ends_at' => $trial_ends_at,
            'status' => $status
        ];

        $features = $package->features()->get();
        $settingsData = [];

        foreach ($features as $feature) {

            $featureSlug = $feature->feature->key;

            // ১. ফিচারটি এই প্যাকেজে এভেইলএবল কি না (Status)
            // ডাটাবেসে সেভ হবে: 'product_active' => 1
            $settingsData[$featureSlug . '_active'] = $feature->is_active ? 1 : 0;

            // ২. যদি এই ফিচারের কোনো লিমিট থাকে (Limit)
            if (!empty($feature->meta)) {
                $meta = is_string($feature->meta) ? json_decode($feature->meta, true) : $feature->meta;

                if (isset($meta['limit'])) {
                    // ডাটাবেসে সেভ হবে: 'product_limit' => 100
                    $settingsData[$featureSlug . '_limit'] = $meta['limit'];
                }
            }
        }

        $moduleData = [];

        foreach ($package->modules as $packageModule) {
            $moduleKey = $packageModule->key ?? ($packageModule->module->key ?? null);

            if ($moduleKey) {
                $moduleSlug = strtolower($moduleKey);
                $moduleData[$moduleSlug . '_active'] = 1;
            }
        }

        DB::beginTransaction();

        try {
            // Create Tenant
            $tenant = Tenant::create(['id' => $tenantId]);
            $tenant->update($data);

            $domains = collect([$tenantId . '.' . config('tenancy.central_domains')[0]]);
            if (!empty($request['custom_domain'])) {
                $domains->push($request['custom_domain']);
            }

            $primaryDomain = $request['custom_domain'] ?: $domains->first();

            $tenant->domains()->createMany($domains->map(fn($d) => [
                'domain' => $d,
                'is_primary' => $d === $primaryDomain
            ])->toArray());

            $modules = $package->modules->map(fn($module) => [
                'tenant_id' => $tenant->id,
                'module_id' => $module->module_id,
                'expires_at' => $expireDate,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ])->toArray();

            TenantModule::insert($modules);

            // Run Seeder & Create Super Admin
            $tenant->run(function () use ($request, $expireDate, $settingsData, $moduleData) {
                $this->createUser($request);
                $this->createSettings($request, $expireDate, $settingsData, $moduleData);
                Artisan::call('db:seed', ['--class' => 'TenantDatabaseSeeder']);
            });

            // Payment Handling
            $due = $registrationFee - $receivedPayment;
            $isOverDue = $due > 0;
            $invoiceNumber = 'INV-' . strtoupper(uniqid());
            $payment = null;
            if ($receivedPayment > 0) {
                $payment = Payment::create([
                    'invoice_number' => $invoiceNumber,
                    'tenant_id' => $tenant->id,
                    'base_amount' => $receivedPayment,
                    'base_currency' => 'BDT',
                    'pay_amount' => $receivedPayment,
                    'pay_currency' => 'BDT',
                    'exchange_rate' => 1,
                    'payment_method' => 'cash',
                    'gateway' => 'manual',
                    'status' => 'completed',
                    'paid_for' => 'registration',
                    'paid_by' => Auth::id(),
                    'added_by' => Auth::id()
                ]);
            }

            // Reseller Handling
            $commission = 0;
            $comissionAmount = 0;
            $adminReceivable = $registrationFee;
            $adminDue = $due;

            if ($request['reseller_id'] > 0) {
                $this->handleResellerPayment($request, $tenant, $payment, $registrationFee, $receivedPayment, $commission, $comissionAmount, $adminReceivable, $adminDue);
            }

            // Reseller Client Record
            ResellerClient::create([
                'reseller_id' => $request['reseller_id'],
                'tenant_id' => $tenant->id,
                'domain' => $tenant->domains()->first()->domain ?? null,
                'package_id' => $request['package_id'],
                'registration_fee' => $registrationFee,
                'commission' => $commission,
                'comission_amount' => $comissionAmount,
                'due' => $due,
                'paid' => $receivedPayment,
                'admin_receivable' => $adminReceivable,
                'admin_due' => $adminDue,
                'is_overdue' => $isOverDue
            ]);

            DB::commit();

            $mailSettings = getLandlordMailSettings();
            if (!empty($mailSettings)) {
                setMailConfig($mailSettings);
                Mail::to($tenant->email)->send(new TenantCreatedMail($tenant, $request['password']));
            }
            return [
                'status' => 201,
                'message' => 'Tenant created successfully.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            try {
                $tenant?->deleteDatabase();
            } catch (\Exception $dbEx) {
                // log warning, database cleanup failed
                Log::warning("Failed to delete tenant database: " . $dbEx->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Handle reseller payment and commission
     */
    private function handleResellerPayment($request, $tenant, $payment, $registrationFee, $receivedPayment, &$commission, &$comissionAmount, &$adminReceivable, &$adminDue)
    {
        $reseller = Reseller::findOrFail($request['reseller_id']);
        $commission = $reseller->commission_per_registration;
        $comissionAmount = ($registrationFee * $reseller->commission_per_registration) / 100;
        $resellerRcv = min($receivedPayment, $comissionAmount);
        $adminRcv = $receivedPayment - $resellerRcv;

        if ($resellerRcv > 0) {
            ResellerPayment::create([
                'payment_id' => $payment->id ?? null,
                'reseller_id' => $reseller->id,
                'tenant_id' => $tenant->id,
                'transaction_id' => 'RPR-' . strtoupper(uniqid()),
                'amount' => $resellerRcv,
                'payment_method' => 'cash',
                'status' => 'pending',
                'note' => 'Client registration fee from reseller',
            ]);

            Withdraw::create([
                'payment_id' => $payment->id ?? null,
                'reseller_id' => $reseller->id,
                'transaction_id' => 'WTR-' . strtoupper(uniqid()),
                'amount' => $resellerRcv,
                'method' => 'manual',
                'status' => 'approved',
                'note' => 'Commission for client registration',
                'payment_details' => '{"message": "Direct payment to reseller"}',
                'approved_at' => now()
            ]);
        }

        $adminReceivable = $registrationFee - $comissionAmount;
        $adminDue = $adminReceivable - $adminRcv;
    }

    //Get Expire Date
    private function getExpireDate($request, $package)
    {
        $packagePricing = PackagePricing::where('package_id', $request['package_id'])
            ->where('type', $request['subscription_type'])
            ->first();
        if ($packagePricing && $request['subscription_type'] !== 'lifetime') {
            return Carbon::now()->addDays($packagePricing->duration_days);
        }
        return null; // Lifetime এর জন্য null মানে অসীম মেয়াদ
    }

    private function createRole(){
        Artisan::call('permission:cache-reset');
         // ১. সব ইউনিক পারমিশন সংগ্রহ করা
        $permissions = FeaturePermission::on('sherazipos_landlord')
            ->pluck('permission')
            ->unique()
            ->toArray();

        $othersPermissions = [
            'access_all_branches',
            'manage_general_settings',
            'manage_email_settings',
            'manage_currency_settings',
            'manage_analytics_settings',
            'manage_ai_settings',
            'manage_role',
            'manage_user',
            'manage_local_db',
            'manage_store_purchase',
            'manage_trash',
            'manage_custom_fields',
            'manage_attributes',
        ];

        $permissions = array_merge($permissions, $othersPermissions);

        // ২. পারমিশনগুলোকে ইনসার্ট ফরম্যাটে সাজানো
        $permissionData = array_map(fn($name) => [
            'name'       => $name,
            'guard_name' => 'web', // Spatie পারমিশন প্যাকেজ হলে সাধারণত 'web' থাকে
            'created_at' => now(),
            'updated_at' => now(),
        ], $permissions);

        // ৩. একসাথে সব পারমিশন ইনসার্ট করা (ডুপ্লিকেট থাকলে ইগনোর করবে)
        // Spatie বা স্ট্যান্ডার্ড পারমিশন টেবিলের 'name' কলাম সাধারণত ইউনিক থাকে
        Permission::insertOrIgnore($permissionData);

        // ৪. রোলস তৈরি করা (একই পদ্ধতিতে)
        $roles = ['Super Admin', 'Admin'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // ৫. এডমিনকে সব পারমিশন দেওয়া
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            // সরাসরি সব পারমিশন আইডি সিঙ্ক করে দেওয়া (সবচেয়ে দ্রুত পদ্ধতি)
            $allPermissionNames = Permission::pluck('name');
            $adminRole->syncPermissions($allPermissionNames);
        }
    }
    //Create User
    private function createUser($request)
    {
        $this->createRole();
        $user = User::create([
            'name' => 'Super Admin',
            'username' => $request['username'] ?? 'superadmin',
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => Hash::make($request['password']),
            'company_name' => $request['business_name']
        ])->assignRole('Super Admin');

        return $user;
    }
    //Create Settings
    private function createSettings($request, $expireDate, $settingsData, $moduleData)
    {
        Setting::set([
            'company_name' => $request['business_name'],
            'company_email' => $request['email'],
            'company_phone' => $request['phone'],
            'expire_date' => $expireDate,
            'subscription_type' => $request['subscription_type'],
            'package_id' => $request['package_id'],
            'company_logo' => Null,
            'favicon' => Null,
            'toggle_logo' => Null,
            'white_logo' => Null,
            'white_toggle_logo' => Null
        ], null, 'general');

        if (!empty($settingsData)) {
            Setting::set($settingsData, null, 'features');
        }

        if (!empty($moduleData)) {
            Setting::set($moduleData, null, 'modules');
        }

        return true;
    }

    //Update Tenant Subscription
    public function updateTenantSubscription($tenant, array $tenantNewData)
    {
        return DB::transaction(function () use ($tenant, $tenantNewData) {
            $tenant->update([
                'backup_data' => json_encode($this->getBackupData($tenant)),
                'package_id'        => $tenantNewData['package_id'],
                'subscription_type' => $tenantNewData['subscription_type'],
                'subscription_fee'  => $tenantNewData['subscription_fee'],
                'expires_at'        => $tenantNewData['expires_at'],
                'status'            => 'active',
                'temp_data'         => null,
            ]);
            $this->syncTenantPackage($tenant, $tenantNewData['package_id']);
            return $tenant;
        });
    }

    private function getBackupData($tenant)
    {
        return [
            'package_id' => $tenant->package_id,
            'subscription_type' => $tenant->subscription_type,
            'expires_at' => $tenant->expires_at,
        ];
    }

    private function syncTenantPackage($tenant, $packageId)
    {
        $package = Package::with('modules.module', 'features.feature')->find($packageId);

        // Sync Modules
        $tenant->modules()->where('type', 'package')->delete();
        $modules = $package->modules->map(fn($module) => [
            'tenant_id' => $tenant->id,
            'module_id' => $module->module_id,
            'expires_at' => $tenant->expires_at,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ])->toArray();

        TenantModule::insert($modules);

        tenancy()->initialize($tenant->id);

        Setting::whereIn('group', ['features', 'modules'])->delete();

        $moduleSettings = [];
        foreach ($package->modules as $pkgModule) {
            $moduleAlias = $pkgModule->module->key ?? $pkgModule->module->name;
            $moduleSettings[$moduleAlias . '_enabled'] = 1;
        }

        if (!empty($moduleSettings)) {
            Setting::set($moduleSettings, null, 'modules');
        }
        // Sync Features
        $featureSettings = [];
        foreach ($package->features as $feature) {
            $featureSlug = $feature->feature->key;
            $featureSettings[$featureSlug . '_active'] = $feature->is_active ? 1 : 0;

            if (!empty($feature->meta)) {
                $meta = is_string($feature->meta) ? json_decode($feature->meta, true) : $feature->meta;
                if (isset($meta['limit'])) {
                    $featureSettings[$featureSlug . '_limit'] = $meta['limit'];
                }
            }
        }

        if (!empty($featureSettings)) {
            Setting::set($featureSettings, null, 'features');
        }

        tenancy()->end();
    }

    public function manageStorePurchase($tenant, $data)
    {
        $landlordService = app(LandlordService::class);
        if ($data['item_type'] == 'module') {
            // Module and feature loaded from landlord database
            // Assuming $this->landlordService is available in this trait
            $item = $landlordService->getModuleDetailsById($data['item_id']);

            $daysToAdd = ($data['subscription_type'] === 'yearly') ? 365 : 30;

            DB::beginTransaction();
            try {
                if ($data['is_renewal']) {
                    // Renewal logic
                    $tenantModule = TenantModule::where('tenant_id', $tenant->id)
                        ->where('module_id', $item->id)
                        ->first();

                    if ($tenantModule) {
                        $newExpireDate = $tenantModule->expires_at && Carbon::parse($tenantModule->expires_at)->isFuture()
                            ? Carbon::parse($tenantModule->expires_at)->addDays($daysToAdd)
                            : Carbon::now()->addDays($daysToAdd);

                        $tenantModule->update([
                            'expires_at' => $newExpireDate,
                            'is_active' => true,
                        ]);
                    }
                } else {
                    // New purchase logic
                    TenantModule::create([
                        'tenant_id' => $tenant->id,
                        'module_id' => $item->id,
                        'expires_at' => Carbon::now()->addDays($daysToAdd),
                        'is_active' => true,
                        'type' => 'addon',
                    ]);
                }

                // Tenant database settings update (new purchase or renewal both cases are synced safely)
                $tenant->run(function () use ($item) {

                    // 1. Add module as addon_module enabled
                    $moduleAlias = $item->key ?? $item->name;
                    $moduleSetting = [$moduleAlias . '_enabled' => 1];
                    Setting::set($moduleSetting, null, 'addon_module');

                    // 2. Set module features as addon_feature
                    $featureSettings = [];
                    // Assuming $item->features contains the module's features
                    if (isset($item['features']) && count($item['features']) > 0) {
                        foreach ($item['features'] as $feature) {

                            // কি (Key) পাওয়ার ক্ষেত্রে সাবধানতা: 
                            // যদি সরাসরি key থাকে তবে $feature->key, আর যদি রিলেশন থাকে তবে $feature->feature->key
                            $featureSlug = $feature->key ?? ($feature->feature->key ?? null);

                            if ($featureSlug) {
                                $featureSettings[$featureSlug . '_active'] = 1;
                            }
                        }

                        if (!empty($featureSettings)) {
                            // আপনার রিকোয়ারমেন্ট অনুযায়ী গ্রুপ নাম: addon_module_feature
                            Setting::set($featureSettings, null, 'addon_module_feature');
                        }
                    }
                });

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        }
    }
}
