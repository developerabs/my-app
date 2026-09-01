<?php

namespace App\Jobs\Landlord;

use App\Models\landlord\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class MigrateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;
    /**
     * Create a new job instance.
     */


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $tenants = Tenant::all();
            if(count($tenants)){
                Artisan::call('tenants:migrate',[
                    '--force' => true
                ]);
                Artisan::call('tenants:seed',[
                    // '--class' => 'Modules\Accounting\Database\Seeders\AccountingDatabaseSeeder',
                    '--force' => true
                ]);
                Artisan::call('tenants:seed',[
                    '--class' => 'TenantRolePermissionSeeder',
                    '--force' => true
                ]);
            }
            Log::info('Tenant migrated successfully');
        } catch (\Exception $e) {
            Log::error('Error migrating tenant: ' . $e->getMessage());
            throw $e;
        }

    }
}
