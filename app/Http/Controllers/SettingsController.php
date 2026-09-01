<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Setting;
use App\Models\TenantCurrencyRate;
use App\Services\CurrencyRateService;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    use HasFiles;

    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $zones_array = [];
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zones_array[$key]['zone'] = $zone;
            $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT '.date('P', $timestamp);
        }
        $currencies = Currency::all();

        return view('backend.settings.settings', compact('settings', 'zones_array', 'currencies'));
    }

    public function generalSettingsUpdate(Request $request)
    {
        $validateData = $request->validate([
            'company_name' => 'required|string|max:255',
            'site_title' => 'nullable|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'required|string|max:20',
            'currency_position' => 'required|in:left,right',
            'date_format' => 'nullable',
            'time_format' => 'nullable',
            'timezone' => 'nullable',
            'decimal_digits' => 'required|integer',
            'company_address' => 'nullable|string|max:500',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
            'toggle_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'white_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'white_toggle_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'thousand_separator' => 'nullable',
            'currency_display_type' => 'nullable|in:symbol,code',
        ]);

        if ($request->has('default_currency')) {
            return back()->with('error', 'Default currency cannot be changed from settings. Please contact support.');
        }

        $imageKeys = ['company_logo', 'favicon', 'toggle_logo', 'white_logo', 'white_toggle_logo'];
        foreach ($validateData as $key => $value) {
            if (in_array($key, $imageKeys) && $request->hasFile($key)) {
                $oldPath = Setting::get($key);
                $width = ($key === 'favicon') ? 64 : 500;
                $newPath = $this->processImage(
                    $request->file($key),
                    'settings',
                    ['width' => $width],
                    $oldPath,
                    's3'
                );

                Setting::set($key, $newPath, 'general');

                continue;
            }
            Setting::set($key, $value, 'general');
        }

        Cache::tags([tenant_tag()])->forget('general_settings_'.tenant()->id);

        return back()->with('success', 'General settings updated successfully.');
    }

    public function updateEmailSettings(Request $request)
    {
        // dd($request->all());
        $validateData = $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'required|string|max:255',
            'mail_encryption' => 'nullable|string|max:50',
            'mail_from_address' => 'required|email|max:255',
        ]);

        foreach ($validateData as $key => $value) {
            Setting::set($key, $value, 'email');
        }

        return back()->with('success', 'Email settings updated successfully.');
    }

    public function updateCurrencySettings(Request $request)
    {
        // 1. Validation
        $request->validate([
            'multi_currency_enabled' => 'nullable|in:1,0',
            'rate_source' => 'nullable|in:system,api',
            'api_provider' => 'nullable|required_if:rate_source,api|string',
            'api_key' => 'nullable|required_if:rate_source,api|string',
            'sync_frequency' => 'nullable|in:manual,daily,weekly,monthly',
            'sync_time' => 'nullable|string',
        ]);

        // 2. Prepare data array (Checkbox চেক করা না থাকলে স্বয়ংক্রিয়ভাবে '0' হবে)
        $isMultiCurrencyEnabled = $request->has('multi_currency_enabled') ? '1' : '0';

        Setting::set([
            'use_multi_currency' => $isMultiCurrencyEnabled,
        ], null, 'general');

        $settingsData = [
            'rate_source' => $isMultiCurrencyEnabled === '1' ? ($request->rate_source ?? 'system') : 'system',
            'api_provider' => $isMultiCurrencyEnabled === '1' && $request->rate_source === 'api' ? $request->api_provider : null,
            'api_key' => $isMultiCurrencyEnabled === '1' && $request->rate_source === 'api' ? $request->api_key : null,
            'sync_frequency' => $isMultiCurrencyEnabled === '1' ? ($request->sync_frequency ?? 'manual') : 'manual',
            'sync_time' => $isMultiCurrencyEnabled === '1' ? ($request->sync_time ?? '00:00') : '00:00',
        ];

        // 3. Save settings using model's static set method
        Setting::set($settingsData, null, 'currency');
        Cache::tags([tenant_tag()])->forget('general_settings_'.tenant()->id);
        // 4. Redirect back with success message
        return redirect()->back()->with('success', __('file.success.currency_settings_updated') ?? 'Currency settings updated successfully.');
    }

    public function updateAnalyticsSettings(Request $request)
    {
        // dd($request->all());
        $validateData = $request->validate([
            'google_tag' => 'nullable|string|max:5000',
            'facebook_pixel' => 'nullable|string|max:5000',
            'chat_script' => 'nullable|string|max:5000',
        ]);
        foreach ($validateData as $key => $value) {
            Setting::set($key, $value, 'analytics');
        }

        return back()->with('success', 'Analytics settings updated successfully.');
    }

    public function syncRatesNow(Request $request, CurrencyRateService $currencyService)
    {
        try {
            // 1. Fetch all currency settings
            $settings = Setting::group('currency');

            // Also check multi-currency status from general settings group
            $generalSettings = Setting::group('general');
            $useMultiCurrency = $generalSettings['use_multi_currency'] ?? '0';

            // 2. Check if multi-currency is enabled. If not, throw an exception and exit.
            if ($useMultiCurrency !== '1') {
                return response()->json([
                    'success' => false,
                    'message' => __('file.warning.multi_currency_disabled') ?? 'Multi-currency is not enabled in settings.',
                ], 422);
            }

            $rateSource = $settings['rate_source'] ?? 'system';
            $apiProvider = $settings['api_provider'] ?? null;
            $apiKey = $settings['api_key'] ?? null;

            // Get tenant's base currency dynamically
            $baseCurrencyCode = Currency::find($generalSettings['default_currency'])->code;

            // 3. Validate API credentials if rate source is api
            if ($rateSource === 'api' && (!$apiProvider || !$apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => __('file.warning.api_credentials_missing') ?? 'API provider or credentials are missing.',
                ], 422);
            }

            // 4. Fetch and adjust exchange rates using CurrencyRateService
            $rates = $currencyService->fetchRates($rateSource, $baseCurrencyCode, $apiProvider, $apiKey);
// Debugging: Check the fetched rates

            if (empty($rates)) {
                throw new \Exception('Failed to retrieve or calculate exchange rates.');
            }

            TenantCurrencyRate::storeRates($baseCurrencyCode, $rates['rates'], $rates['last_updated_at']);

            // 6. Update last updated date in settings
            $currentDate = now()->toDateString();
            Setting::set(['rates_last_updated_date' => $currentDate], null, 'currency');

            return response()->json([
                'success' => true,
                'message' => __('file.success.rates_synced') ?? 'Exchange rates synchronized successfully.',
                'data' => $rates, // Optional: you can return rates if needed
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCurrencyRatesForModal(Request $request)
    {
        try {
            $generalSettings = Setting::group('general');
            $baseCurrencyCode = Currency::find($generalSettings['default_currency'])->code;

            $currencyRateRecord = TenantCurrencyRate::where('base_code', $baseCurrencyCode)->first();

            if (!$currencyRateRecord) {
                return response()->json([
                    'success' => false,
                    'message' => __('file.warning.no_rates_found') ?? 'No currency rates found for the base currency.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'base_code' => $currencyRateRecord->base_code,
                    'last_updated_at' => $currencyRateRecord->last_updated_at,
                    'rates' => $currencyRateRecord->rates,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
