<?php

namespace App\Services\Central;

use App\Models\landlord\Addon;
use App\Models\landlord\Currency;
use App\Models\landlord\CurrencyRate;
use App\Models\landlord\Gateway;
use App\Models\landlord\Module;
use App\Models\landlord\Package;
use App\Models\landlord\TenantAddress;
use App\Models\landlord\TenantModule;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LandlordService
{
    public function allActivePackages()
    {
        return Package::on('sherazipos_landlord')->where('is_active', true)->get();
    }

    public function getPackageById(int $id): ?Package
    {
        return Package::on('sherazipos_landlord')->with('pricing')->where('id', $id)->where('is_active', true)->first();
    }

    public function checkFeatureLimits($currentPackage, $newPackage)
    {
        $warnings = [];
        foreach ($currentPackage->features as $feature) {
            $featureName = $feature->feature->name;
            $currentMeta = json_decode($feature->meta, true) ?? [];

            $newFeature = $newPackage->features->firstWhere('feature_id', $feature->feature_id);
            if ($newFeature && $newFeature->meta) {
                $newMeta = json_decode($newFeature->meta, true);
                foreach ($currentMeta as $key => $value) {
                    if (isset($newMeta[$key]) && $newMeta[$key] < $value) {
                        $warnings[] = __('file.message.feature_limit_warning', [
                            'feature' => $featureName,
                            'current' => $value,
                            'new' => $newMeta[$key],
                        ]);
                    }
                }
            }
        }

        return $warnings;
    }

    /**
     * Get single rate for currency code
     */
    public function getCurrencyRateByCode(string $code)
    {
        $row = self::getCurrencyRates();
        $rates = $row && isset($row->rates) ? (array) $row->rates : [];

        return $rates[$code] ?? 1;
    }

    /**
     * Get full CurrencyRate model instance from Landlord Central DB
     */
    public static function getCurrencyRates(): ?CurrencyRate
    {
        return Cache::tags([landlord_tag()])->remember('daily_currency_rates_all', now()->addHours(12), function () {
            return CurrencyRate::on('sherazipos_landlord')->latest('last_updated_at')->first();
        });
    }

    public function activePaymentsGateways()
    {
        $gateways = Gateway::on('sherazipos_landlord')->where('is_active', true)->get();
        $gateways->each(function ($gateway) {
            $gateway->logo_url_path = $gateway->logo ? env('AWS_URL') . '/' . $gateway->logo : asset('assets/landlord/img/no-image.png');
        });

        return $gateways;
    }

    public function getAllActiveCurrency()
    {
        return Cache::tags([landlord_tag()])->remember('all_active_currencies', now()->addDay(), function () {
            return Currency::on('sherazipos_landlord')->where('is_active', true)->get();
        });
    }

    public function getPaymentGatewayById(int $id): ?Gateway
    {
        return Gateway::on('sherazipos_landlord')->where('id', $id)->where('is_active', true)->select('id', 'name')->first();
    }

    public function getAddons()
    {
        return Addon::on('sherazipos_landlord')->get();
    }

    public function getModules()
    {
        return Module::on('sherazipos_landlord')->get();
    }

    public function purchasedModules($tenantId)
    {
        return TenantModule::on('sherazipos_landlord')->where('tenant_id', $tenantId)->get();
    }

    public function getModuleDetailsById(int $id)
    {
        $module = Module::on('sherazipos_landlord')->where('id', $id)->first();
        if ($module) {
            $module['features'] = $module->features()->get();
        }
        return $module;
    }

    public function getAddonDetailsById(int $id)
    {
        return Addon::on('sherazipos_landlord')->where('id', $id)->first();
    }

    public function getCountries()
    {
        return Cache::tags([landlord_tag()])->rememberForever('countries', function () {
            return DB::connection('sherazipos_landlord')->table('countries')->get();
        });
    }

    public function getDivisions()
    {
        return Cache::tags([landlord_tag()])->rememberForever('divisions', function () {
            return DB::connection('sherazipos_landlord')->table('divisions')->get();
        });
    }

    public function getDistrictByDivId($id)
    {
        $cacheKey = 'districts_by_div_id_' . $id;
        return Cache::tags([landlord_tag()])->remember($cacheKey, now()->addDay(), function () use ($id) {
            return DB::connection('sherazipos_landlord')->table('districts')->where('division_id', $id)->get();
        });
    }

    public function getUpazilasByDisId($id)
    {
        $cacheKey = 'upazilas_by_dis_id_' . $id;
        return Cache::tags([landlord_tag()])->remember($cacheKey, now()->addDay(), function () use ($id) {
            return DB::connection('sherazipos_landlord')->table('upazilas')->where('district_id', $id)->get();
        });
    }

    public function getUnionsByUpazillaId($id)
    {
        $cacheKey = 'unions_by_upazilla_id_' . $id;
        return Cache::tags([landlord_tag()])->remember($cacheKey, now()->addDay(), function () use ($id) {
            return DB::connection('sherazipos_landlord')->table('unions')->where('upazilla_id', $id)->get();
        });
    }

    public function storeTenantAddress($tenantId, array $address)
    {
        $getName = function ($value) {
            if (is_null($value)) return null;
            return (str_contains($value, '-') && is_numeric(explode('-', $value, 2)[0]))
                ? explode('-', $value, 2)[1]
                : $value;
        };

        $getId = function ($value, $shouldExtract) {
            if (is_null($value)) return null;
            if ($shouldExtract && str_contains($value, '-') && is_numeric(explode('-', $value, 2)[0])) {
                return explode('-', $value, 2)[0];
            }
            return $value;
        };

        $fullAddress = collect($address)
            ->only(['street_address', 'union', 'upazilla', 'district', 'division', 'city', 'state', 'zipcode', 'country'])
            ->filter()
            ->map(function ($value, $key) use ($getName) {
                if (in_array($key, ['union', 'upazilla', 'district', 'division'])) {
                    return $getName($value);
                }
                return $value;
            })
            ->implode(', ');

        $dataToStore = [
            'tenant_id'      => $tenantId,
            'street_address' => $address['street_address'] ?? null,
            'country'        => $address['country'] ?? null,
            'city'           => $address['city'] ?? null,
            'state'          => $address['state'] ?? null,
            'post_code'      => $address['zipcode'] ?? $address['post_code'] ?? null,
            'division'       => $getId($address['division'] ?? null, true),
            'district'       => $getId($address['district'] ?? null, true),
            'upazilla'       => $getId($address['upazilla'] ?? null, true),
            'union'          => $getId($address['union'] ?? null, true),
            'full_address'   => $fullAddress,
        ];

        try {
            TenantAddress::on('sherazipos_landlord')->create($dataToStore);
            return $fullAddress;
        } catch (Exception $e) {
            Log::error('Tenant Address Creating Failed: ' . $e->getMessage());
            throw new Exception("Could not save tenant address: " . $e->getMessage());
        }
    }
}