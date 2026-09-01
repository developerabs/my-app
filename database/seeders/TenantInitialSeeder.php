<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CategoryType;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\UnitGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantInitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // English comment: Check if currency exists to avoid null reference
       $isSetupComplete = Setting::get('setup', 'is_setup_complete', false);

        if (!$isSetupComplete) {
            $settings = [
                'is_setup_complete' => false,
                'setup_step' => 'initial',
                'agreed_terms_and_conditions' => false,
                'setup_completed_at' => null,
            ];

            Setting::set($settings, null, 'setup');
        }

        // Create Category Types
        $categoryTypes = [
            ['name' => 'product', 'display_name' => 'Product'],
            ['name' => 'service', 'display_name' => 'Service'],
            ['name' => 'raw_material', 'display_name' => 'Raw Material'],
            ['name' => 'lead', 'display_name' => 'Lead'],
            ['name' => 'deal', 'display_name' => 'Deal'],
            ['name' => 'expense', 'display_name' => 'Expense'],
            ['name' => 'income', 'display_name' => 'Income'],
            ['name' => 'asset', 'display_name' => 'Asset'],
            ['name' => 'liability', 'display_name' => 'Liability'],
            ['name' => 'customer', 'display_name' => 'Customer'],
            ['name' => 'vendor', 'display_name' => 'Vendor'],
        ];

        foreach ($categoryTypes as $type) {
            CategoryType::updateOrCreate(
                ['name' => $type['name']],
                ['display_name' => $type['display_name']]
            );
        }
    }
}
