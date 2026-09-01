<?php

namespace App\Listeners;

use App\Models\Attribute;
use App\Models\Setting;
use App\Models\UnitGroup;
use App\Services\Accounting\AccountingFormService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Stancl\Tenancy\Events\TenancyInitialized;

class ShareTenantDataWithViews
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected AccountingFormService $accFormService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(TenancyInitialized $event): void
    {
        $tenant = $event->tenancy->tenant;
        $tenantTag = tenant_tag();
        $tenantId = tenant('id');

        $general_settings = Cache::tags([$tenantTag])->remember('general_settings_'.$tenantId, 86400 * 7, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Setting::where('group', 'general')
                ->get()
                ->mapWithKeys(function ($item) {
                    $imageKeys = ['company_logo', 'favicon', 'toggle_logo', 'white_logo', 'white_toggle_logo'];
                    $value = (in_array($item->key, $imageKeys) && ! empty($item->value))
                        ? file_url($item->value)
                        : $item->value;

                    return [$item->key => $value];
                })->toArray();
        });

        $default_currency = Cache::tags([$tenantTag])->remember('default_currency_'.$tenantId, 86400 * 7, function () use ($general_settings) {
            $currencyId = $general_settings['default_currency'] ?? null;
            if ($currencyId && Schema::hasTable('currencies')) {
                $currency = DB::table('currencies')->where('id', $currencyId)->first();

                return $currency ? (array) $currency : null;
            }

            return null;
        });

        $unit_groups = Cache::tags([$tenantTag])->remember('unitGroups_'.$tenantId, 3600, function () {
            if (! Schema::hasTable('unit_groups')) {
                return collect([]);
            }

            return UnitGroup::withCount('units')->get();
        });

        $allAttributes = Cache::tags([$tenantTag])->remember('all_attributes_'.tenant('id'), 3600, function () {
            if (! Schema::hasTable('attributes')) {
                return collect([]);
            }

            return Attribute::with('values')->where('is_active', true)->get();
        });

        $currentFiscalYear = Cache::tags([$tenantTag])->remember('current_fiscal_year_'.$tenantId, 3600, function () {
            if (! Schema::hasTable('fiscal_years')) {
                return null;
            }

            return DB::table('fiscal_years')->where('status', 'current')->first();
        });

        View::share([
            'tenant' => $tenant,
            'general_settings' => $general_settings,
            'default_currency' => $default_currency,
            'unit_groups' => $unit_groups,
            'allAttributes' => $allAttributes,
            'currentFiscalYear' => $currentFiscalYear,
        ]);
    }
}
