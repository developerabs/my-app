<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Setting;
use App\Models\TenantCurrencyRate;
use Exception;
use Illuminate\Support\Facades\Cache;

class CurrencyConversionService
{
    /**
     * Cache for loaded currencies and rates within runtime execution.
     */
    protected array $currencyCache = [];
    protected ?Currency $baseCurrency = null;
    protected ?array $latestRates = null;

    /**
     * Get System Base Currency.
     */
    public function getBaseCurrency(): Currency
    {
        if ($this->baseCurrency) {
            return $this->baseCurrency;
        }

        $baseCurrencyId = Setting::get('default_currency');

        if (!$baseCurrencyId) {
            throw new Exception('Default base currency is not configured in settings.');
        }

        $this->baseCurrency = Currency::find($baseCurrencyId);

        if (!$this->baseCurrency) {
            throw new Exception("Base currency with ID {$baseCurrencyId} not found.");
        }

        return $this->baseCurrency;
    }

    /**
     * Get exchange rate relative to Base Currency.
     *
     * Returns how many units of Base Currency equal 1 unit of Target Currency.
     * Example: If Base is BDT and Target is USD, returns 123.35 (1 USD = 123.35 BDT).
     */
    public function getExchangeRate(int|string|Currency $currency): float
    {
        $targetCurrency = $this->resolveCurrency($currency);
        $baseCurrency = $this->getBaseCurrency();

        // If target currency is base currency, rate is always 1.0
        if ($targetCurrency->id === $baseCurrency->id || strtoupper($targetCurrency->code) === strtoupper($baseCurrency->code)) {
            return 1.0;
        }

        $ratesData = $this->getLatestTenantRates($baseCurrency->code);

        $targetCode = strtoupper($targetCurrency->code);

        if (!isset($ratesData[$targetCode]) || (float) $ratesData[$targetCode] <= 0) {
            throw new Exception("Exchange rate for currency '{$targetCode}' relative to base '{$baseCurrency->code}' is missing or invalid.");
        }

        $rateInBase = (float) $ratesData[$targetCode];

        // JSON stores rate as: 1 Base Unit = X Target Units (e.g., 1 BDT = 0.008107 USD)
        // Multiplier to get Base Amount from Target = 1 / rateInBase (e.g., 1 / 0.008107 = 123.3502035)
        return round(1.0 / $rateInBase, 8);
    }

    /**
     * Convert an amount from foreign currency to base currency.
     */
    public function convertToBase(float $amount, int|string|Currency $fromCurrency, ?float $customExchangeRate = null): float
    {
        $rate = $customExchangeRate && $customExchangeRate > 0
            ? $customExchangeRate
            : $this->getExchangeRate($fromCurrency);

        return round($amount * $rate, 2);
    }

    /**
     * Fetch latest currency rates JSON for base code.
     */
    protected function getLatestTenantRates(string $baseCode): array
    {
        if ($this->latestRates !== null) {
            return $this->latestRates;
        }

        $tenantRateRecord = TenantCurrencyRate::where('base_code', $baseCode)
            ->latest('last_updated_at')
            ->first();

        if (!$tenantRateRecord || empty($tenantRateRecord->rates)) {
            throw new Exception("No currency exchange rates found for base currency code '{$baseCode}'.");
        }

        $rates = is_string($tenantRateRecord->rates)
            ? json_decode($tenantRateRecord->rates, true)
            : $tenantRateRecord->rates;

        if (!is_array($rates)) {
            throw new Exception("Invalid rates JSON format in TenantCurrencyRate.");
        }

        $this->latestRates = $rates;

        return $this->latestRates;
    }

    /**
     * Resolve Currency model from ID, Code, or Instance.
     */
    protected function resolveCurrency(int|string|Currency $currency): Currency
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        $cacheKey = (string) $currency;
        if (isset($this->currencyCache[$cacheKey])) {
            return $this->currencyCache[$cacheKey];
        }

        $query = Currency::query();
        if (is_numeric($currency)) {
            $model = $query->find($currency);
        } else {
            $model = $query->where('code', strtoupper($currency))->first();
        }

        if (!$model) {
            throw new Exception("Currency '{$currency}' not found in database.");
        }

        $this->currencyCache[$cacheKey] = $model;

        return $model;
    }
}