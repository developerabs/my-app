<?php

namespace App\Providers;

use App\Models\landlord\Currency;
use App\Models\landlord\CurrencyRate;
use App\Models\landlord\HomepageWidget;
use App\Models\landlord\LandlordSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class LandlordViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        view()->composer(['landlord.layouts.main', 'landlord.layouts.frontend', 'landlord.login', 'backend.store_purchase.*', 'billing.*'], function ($view) {
            $data = once(function () {
                return [
                    'LandlordHeader' => Cache::tags([landlord_tag()])->remember('landlordHeader', 3600, function () {
                        return HomepageWidget::on('sherazipos_landlord')->where('type', 'header')->where('is_global', true)->first();
                    }),

                    'LandlordFooter' => Cache::tags([landlord_tag()])->remember('landlordFooter', 3600, function () {
                        return HomepageWidget::on('sherazipos_landlord')->where('type', 'footer')->where('is_global', true)->first();
                    }),

                    'LandlordGeneralSettings' => Cache::tags([landlord_tag()])->remember('landlordGeneralSettings', 3600, function () {
                        return LandlordSetting::on('sherazipos_landlord')->where('group', 'general')->pluck('value', 'key')->toArray();
                    }),

                    'landlordMailSettings' => Cache::tags([landlord_tag()])->remember('landlordMailSettings', 3600, function () {
                        return LandlordSetting::on('sherazipos_landlord')->where('group', 'email')->pluck('value', 'key')->toArray();
                    }),

                    'landlordCurrencies' => Cache::tags([landlord_tag()])->remember('landlordCurrencies', 1800, function () {
                        return Currency::on('sherazipos_landlord')
                            ->active()
                            ->where('is_active', true)
                            ->get(['code'])
                            ->toArray();
                    }),

                    'currencyRates' => Cache::tags([landlord_tag()])->remember('currencyRates', 1800, function () {
                        return optional(
                            CurrencyRate::on('sherazipos_landlord')->latest()->first()
                        )->toArray() ?? [];
                    }),
                ];
            });

            $view->with($data);
        });
    }
}
