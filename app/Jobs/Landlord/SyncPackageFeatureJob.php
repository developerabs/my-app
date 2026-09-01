<?php

namespace App\Jobs\Landlord;

use App\Models\landlord\Tenant;
use App\Models\landlord\TenantModule;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SyncPackageFeatureJob implements ShouldQueue
{
    use Queueable;

    public $package;
    /**
     * Create a new job instance.
     */
    public function __construct($package)
    {
        $this->package = $package;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // প্যাকেজের সব ফিচার আগে একবারই লোড করে নিচ্ছি লুপের বাইরে
        $features = $this->package->features()->with('feature')->get();
        $modules = $this->package->modules()->with('module')->get();
        $modulesId = $modules->pluck('module_id')->toArray();

        Tenant::where('data->package_id', $this->package->id)->chunk(50, function ($tenants) use ($features, $modules, $modulesId) {
            foreach ($tenants as $tenant) {
                $tenant->run(function () use ($features, $modules, $tenant, $modulesId) {
                    
                    $tenantTag = 'tenant_' . tenant('id');
                    $settingsData = [];
                    foreach ($features as $f) {
                        $featureSlug = $f->feature->key; 
                        $settingsData[$featureSlug . '_active'] = $f->is_active ? 1 : 0;
                        if (!empty($f->meta)) {
                            $meta = is_string($f->meta) ? json_decode($f->meta, true) : $f->meta;
                            if (isset($meta['limit'])) {
                                $settingsData[$featureSlug . '_limit'] = $meta['limit'];
                            }
                        }
                        Cache::tags([$tenantTag])->forget('limit_count_' . strtolower($featureSlug) . '_' . tenant('id'));
                    }

                    if (!empty($settingsData)) {
                        Setting::set($settingsData, null, 'features');
                    }
                    //Module store process
                    $moduleData = [];
                    foreach ($modules as $m) {
                        $moduleKey = $m->module->key ?? null;
                        if ($moduleKey) {
                            $moduleSlug = strtolower($moduleKey);
                            $moduleData[$moduleSlug . '_active'] = 1; 
                        }
                    }

                    if (!empty($moduleData)) {
                        Setting::set($moduleData, null, 'modules');
                    }

                    foreach($modulesId as $mId){
                        // আমরা updateOrCreate ব্যবহার করব শুধু নতুন মডিউল ইনসার্ট বা এক্সিস্টিং আপডেট করতে।
                        // কিন্তু টাইপ যদি আগে থেকেই 'package' থাকে তবে সেটাই থাকবে।
                        TenantModule::on('sherazipos_landlord')->updateOrCreate(
                            [
                                'tenant_id' => $tenant->id,
                                'module_id' => $mId,
                                // আমরা টাইপ চেক করছি না, যাতে এডঅন কেনা থাকলেও সেটা প্যাকেজে কনভার্ট হতে পারে
                            ],
                            [
                                'type' => 'package', // প্যাকেজে নতুন আসলে টাইপ 'package' হয়ে যাবে
                                'is_active' => 1,
                                'expires_at' => $tenant->expires_at,
                                'updated_at' => now()
                            ]
                        );
                    }

                    Cache::tags([$tenantTag])->forget('tenant_features_' . tenant('id'));
                    Cache::tags([$tenantTag])->forget('general_settings_' . tenant('id'));


                });
            }
        });
    }
}
