<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TenantCurrencyRate extends Model
{
    protected $fillable = [
        'base_code',
        'last_updated_at',
        'rates',
    ];

    protected $casts = [
        'rates'           => 'array', 
        'last_updated_at' => 'datetime',
    ];

    public static function booted(): void
    {
        parent::booted();

        $clearTenantCache = function ($model) {
            Cache::tags([tenant_tag()])->forget('tenant_currency_rates_' . tenant('id'));
        };

        static::saved($clearTenantCache);
        static::deleted($clearTenantCache);
    }

    /**
     * Currency Store method with strict source timestamp enforcement.
     */
    public static function storeRates(string $baseCode, array $ratesData, $updatedAt = null): self
    {
        if (empty($updatedAt)) {
            throw new Exception("Cannot save currency rates without a verified source publication timestamp.");
        }

        return static::updateOrCreate(
            ['base_code' => $baseCode],
            [
                'rates'           => $ratesData,
                'last_updated_at' => Carbon::parse($updatedAt)->format('Y-m-d H:i:s'),
            ]
        );
    }
}