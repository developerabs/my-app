<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CurrencyRate extends Model
{
    protected $connection = 'sherazipos_landlord';

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

        $clearLandlordCache = function ($model) {
            Cache::tags([landlord_tag()])->forget('daily_currency_rates_all');
            Cache::tags([landlord_tag()])->forget('daily_currency_rates');
        };

        static::saved($clearLandlordCache);
        static::deleted($clearLandlordCache);
    }
}