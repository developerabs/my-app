<?php

namespace App\Console\Commands;

use App\Models\landlord\Tenant;
use Illuminate\Console\Command;

class CheckTenantSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:check-subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        Tenant::chunk(100, function ($tenants) use ($now) {
            foreach ($tenants as $tenant) {

                // Trial check
                if ($tenant->status === 'trial') {
                    if ($tenant->trial_ends_at && $now->greaterThan($tenant->trial_ends_at)) {
                        $tenant->status = 'billing';
                        $tenant->save();
                    }
                    continue;
                }

                if (!$tenant->expires_at) continue;

                // 3️⃣ Date difference
                $diff = $now->diffInDays($tenant->expires_at, false);

                // 4️⃣ 15 days before expire
                if ($diff > 0 && $diff <= 15) {
                    $tenant->update(['status' => 'will_expire']);
                }
                // 5️⃣ Expired but within 7 days (view-only)
                if ($diff <= 0 && $diff >= -7) {
                    $tenant->update(['status' => 'expired']);
                }

                // 6️⃣ Grace period over → billing
                if ($diff < -7) {
                    $tenant->update(['status' => 'billing']);
                }
            }
        });
    }
}
