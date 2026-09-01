<?php

namespace App\Services;

use App\Services\Central\LandlordService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    /**
     * Fetch rates based on the source and provider, strictly returning the source's verified timestamp.
     */
    public function fetchRates(string $source, string $baseCurrency, ?string $provider = null, ?string $apiKey = null): array
    {
        $rawResult = [];

        if ($source === 'system') {
            $rawResult = self::fetchFromSystemSource($baseCurrency);
        } else {
            $rawResult = match ($provider) {
                'exchangerate_api'    => self::fetchFromExchangeRateApi($baseCurrency, $apiKey),
                'open_exchange'       => self::fetchFromOpenExchange($baseCurrency, $apiKey),
                'fixer'               => self::fetchFromFixer($baseCurrency, $apiKey),
                'currencylayer'       => self::fetchFromCurrencylayer($baseCurrency, $apiKey),
                'coinlayer'           => self::fetchFromCoinlayer($baseCurrency, $apiKey),
                'free_currency_api'   => self::fetchFromFreeCurrencyApi($baseCurrency, $apiKey),
                'alpha_vantage'       => self::fetchFromAlphaVantage($baseCurrency, $apiKey),
                'ecb_api'             => self::fetchFromEcbApi($baseCurrency, $apiKey),
                default               => throw new Exception('Selected API provider is not supported.'),
            };
        }

        $rates = $rawResult['rates'] ?? [];
        $lastUpdatedAt = $rawResult['last_updated_at'] ?? null;

        // 🛑 STRICT AUDIT GUARD: Must have authentic source timestamp
        if (empty($lastUpdatedAt)) {
            throw new Exception("The currency rate source did not provide a valid 'last_updated_at' timestamp.");
        }

        // Normalize rates relative to tenant's selected base currency (Base = 1.00000000)
        $normalizedRates = self::adjustRatesForTenantBase($rates, $baseCurrency);

        return [
            'rates'           => $normalizedRates,
            'last_updated_at' => $lastUpdatedAt,
        ];
    }

    /**
     * 0. System Source (Fetches exact last_updated_at from Landlord Central DB)
     */
    private static function fetchFromSystemSource(string $baseCurrency): array
    {
        try {
            $rawData = LandlordService::getCurrencyRates();

            if (empty($rawData) || empty($rawData->rates)) {
                throw new Exception('No currency exchange rates found in the Landlord system. Please run [currency:update-rates] on Landlord first.');
            }

            if (empty($rawData->last_updated_at)) {
                throw new Exception('Landlord currency rates do not have a valid update timestamp.');
            }

            $rates = is_string($rawData->rates) ? json_decode($rawData->rates, true) : (array) $rawData->rates;

            // 🟢 Use EXACT Landlord DB timestamp
            $lastUpdated = Carbon::parse($rawData->last_updated_at)->format('Y-m-d H:i:s');

            return [
                'rates'           => $rates,
                'last_updated_at' => $lastUpdated,
            ];
        } catch (Exception $e) {
            Log::error("System Rate Fetch Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 1. ExchangeRate-API (Uses 'time_last_update_unix' or 'time_last_update_utc')
     */
    private static function fetchFromExchangeRateApi(string $baseCurrency, string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}");

            if ($response->successful() && isset($response->json()['conversion_rates'])) {
                $data = $response->json();
                $rates = $data['conversion_rates'];
                
                $timestamp = $data['time_last_update_unix'] ?? null;
                $utcString = $data['time_last_update_utc'] ?? null;

                if (!$timestamp && !$utcString) {
                    throw new Exception("ExchangeRate-API response is missing timestamp.");
                }

                $lastUpdated = $timestamp 
                    ? Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s')
                    : Carbon::parse($utcString)->format('Y-m-d H:i:s');

                return [
                    'rates'           => $rates,
                    'last_updated_at' => $lastUpdated,
                ];
            }

            throw new Exception('Failed to fetch rates from ExchangeRate-API.');
        } catch (Exception $e) {
            Log::error("ExchangeRate-API Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 2. Open Exchange Rates (Uses 'time_last_update_unix' or 'timestamp')
     */
    private static function fetchFromOpenExchange(string $baseCurrency, string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get("https://open.er-api.com/v6/latest/{$baseCurrency}?app_id={$apiKey}");

            if ($response->successful() && isset($response->json()['rates'])) {
                $data = $response->json();
                $rates = $data['rates'];
                
                $timestamp = $data['time_last_update_unix'] ?? ($data['timestamp'] ?? null);
                if (!$timestamp) {
                    throw new Exception("Open Exchange Rates response is missing timestamp.");
                }

                return [
                    'rates'           => $rates,
                    'last_updated_at' => Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s'),
                ];
            }

            throw new Exception('Failed to fetch rates from Open Exchange Rates.');
        } catch (Exception $e) {
            Log::error("Open Exchange Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 3. Fixer.io (Uses 'timestamp' or 'date')
     */
    private static function fetchFromFixer(string $baseCurrency, string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get("http://data.fixer.io/api/latest", [
                'access_key' => $apiKey
            ]);

            if ($response->successful() && ($response->json()['success'] ?? false)) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];
                
                $timestamp = $data['timestamp'] ?? null;
                $dateString = $data['date'] ?? null;

                if ($timestamp) {
                    $lastUpdated = Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
                } elseif ($dateString) {
                    $lastUpdated = Carbon::parse($dateString)->startOfDay()->format('Y-m-d H:i:s');
                } else {
                    throw new Exception("Fixer.io response is missing timestamp.");
                }

                return [
                    'rates'           => $rates,
                    'last_updated_at' => $lastUpdated,
                ];
            }

            throw new Exception($response->json()['error']['info'] ?? 'Failed to fetch rates from Fixer.io.');
        } catch (Exception $e) {
            Log::error("Fixer.io Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 4. Currencylayer (Uses 'timestamp')
     */
    private static function fetchFromCurrencylayer(string $baseCurrency, string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get("http://apilayer.net/api/live", [
                'access_key' => $apiKey
            ]);

            if ($response->successful() && ($response->json()['success'] ?? false)) {
                $data = $response->json();
                $quotes = $data['quotes'] ?? [];
                $rates = [];
                
                foreach ($quotes as $key => $value) {
                    $rates[substr($key, 3)] = $value;
                }

                $timestamp = $data['timestamp'] ?? null;
                if (!$timestamp) {
                    throw new Exception("Currencylayer response is missing timestamp.");
                }

                return [
                    'rates'           => $rates,
                    'last_updated_at' => Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s'),
                ];
            }

            throw new Exception($response->json()['error']['info'] ?? 'Failed to fetch rates from Currencylayer.');
        } catch (Exception $e) {
            Log::error("Currencylayer Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 5. Coinlayer (Uses 'timestamp')
     */
    private static function fetchFromCoinlayer(string $baseCurrency, string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get("http://api.coinlayer.com/live", [
                'access_key' => $apiKey
            ]);

            if ($response->successful() && ($response->json()['success'] ?? false)) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];
                
                $timestamp = $data['timestamp'] ?? null;
                if (!$timestamp) {
                    throw new Exception("Coinlayer response is missing timestamp.");
                }

                return [
                    'rates'           => $rates,
                    'last_updated_at' => Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s'),
                ];
            }

            throw new Exception($response->json()['error']['info'] ?? 'Failed to fetch rates from Coinlayer.');
        } catch (Exception $e) {
            Log::error("Coinlayer Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 6. Free Currency API (Uses 'meta.last_updated_at')
     */
    private static function fetchFromFreeCurrencyApi(string $baseCurrency, string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get("https://api.freecurrencyapi.com/v1/latest", [
                'apikey'        => $apiKey,
                'base_currency' => $baseCurrency
            ]);

            if ($response->successful() && isset($response->json()['data'])) {
                $data = $response->json();
                $rates = $data['data'];
                
                $lastUpdatedString = $data['meta']['last_updated_at'] ?? null;
                if (!$lastUpdatedString) {
                    throw new Exception("Free Currency API response is missing 'meta.last_updated_at'.");
                }

                return [
                    'rates'           => $rates,
                    'last_updated_at' => Carbon::parse($lastUpdatedString)->format('Y-m-d H:i:s'),
                ];
            }

            throw new Exception('Failed to fetch rates from Free Currency API.');
        } catch (Exception $e) {
            Log::error("Free Currency API Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 7. Alpha Vantage (Uses 'Realtime Currency Exchange Rate.6. Last Refreshed')
     */
    private static function fetchFromAlphaVantage(string $baseCurrency, string $apiKey): array
    {
        try {
            $response = Http::timeout(15)->get("https://www.alphavantage.co/query", [
                'function'      => 'CURRENCY_EXCHANGE_RATE',
                'from_currency' => $baseCurrency,
                'to_currency'   => 'EUR',
                'apikey'        => $apiKey
            ]);

            if ($response->successful() && isset($response->json()['Realtime Currency Exchange Rate'])) {
                $data = $response->json()['Realtime Currency Exchange Rate'];
                $toCurrency = $data['3. To_Currency Code'] ?? 'EUR';
                $exchangeRate = $data['5. Exchange Rate'] ?? 1;
                
                $lastRefreshed = $data['6. Last Refreshed'] ?? null;
                if (!$lastRefreshed) {
                    throw new Exception("Alpha Vantage response is missing '6. Last Refreshed' timestamp.");
                }

                return [
                    'rates'           => [$toCurrency => (float) $exchangeRate],
                    'last_updated_at' => Carbon::parse($lastRefreshed)->format('Y-m-d H:i:s'),
                ];
            }

            throw new Exception('Failed to fetch rates from Alpha Vantage.');
        } catch (Exception $e) {
            Log::error("Alpha Vantage Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 8. European Central Bank (ECB) API (Uses XML '@attributes.time')
     */
    private static function fetchFromEcbApi(string $baseCurrency, string $apiKey = ''): array
    {
        try {
            $response = Http::timeout(15)->get("https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");

            if ($response->successful()) {
                $xmlObject = simplexml_load_string($response->body());
                $json = json_encode($xmlObject);
                $array = json_decode($json, true);

                $rates = ['EUR' => 1.0];
                $timeString = $array['Cube']['Cube']['@attributes']['time'] ?? null;
                
                if (!$timeString) {
                    throw new Exception("ECB XML feed is missing publication date attribute.");
                }

                $cube = $array['Cube']['Cube']['Cube'] ?? [];

                foreach ($cube as $node) {
                    if (isset($node['@attributes']['currency'], $node['@attributes']['rate'])) {
                        $rates[$node['@attributes']['currency']] = (float) $node['@attributes']['rate'];
                    }
                }

                return [
                    'rates'           => $rates,
                    'last_updated_at' => Carbon::parse($timeString)->startOfDay()->format('Y-m-d H:i:s'),
                ];
            }

            throw new Exception('Failed to fetch rates from ECB API.');
        } catch (Exception $e) {
            Log::error("ECB API Error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Adjust rates based on tenant base currency with 8-decimal precision.
     */
    private static function adjustRatesForTenantBase(array $rates, string $baseCurrency): array
    {
        if (empty($rates)) {
            return $rates;
        }

        if (!isset($rates[$baseCurrency])) {
            return $rates;
        }

        $baseCurrencyRate = (float) $rates[$baseCurrency];

        if ($baseCurrencyRate <= 0) {
            return $rates;
        }

        $adjustedRates = [];
        foreach ($rates as $currency => $rate) {
            // Formula: TargetRate = APIRate / BaseCurrencyRate
            $adjustedRates[$currency] = round((float) $rate / $baseCurrencyRate, 8);
        }

        $adjustedRates[$baseCurrency] = 1.00000000;

        return $adjustedRates;
    }
}