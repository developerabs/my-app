<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\LogIP;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use LogsActivity, LogIP;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
    ];

    protected $casts = [];

    //Get Attribute
    public function getValueAttribute($value)
    {
        if (is_null($value)) return $value;

        $decoded = json_decode($value, true);
        
        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            return $decoded;
        }

        return $value;
    }


    public function setValueAttribute($value)
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
    }

    public static function get(string $key, $default = null): mixed
    {
        return Cache::tags([tenant_tag()])->rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value = null, string $group = 'general'): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                static::updateOrCreate(
                    ['key' => $k],
                    ['value' => $v, 'group' => $group]
                );
                Cache::tags([tenant_tag()])->forget("settings.{$k}");
            }
            return;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::tags([tenant_tag()])->forget("settings.{$key}");

        if (function_exists('tenant') && tenant('id')) {
            Cache::tags([tenant_tag()])->forget('general_settings_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('default_currency_' . tenant('id'));
        }
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)->get()->pluck('value', 'key')->toArray();
    }


    public static function clearCache(): void
    {
        Cache::tags([tenant_tag()])->flush();
    }

    public function currencyData()
    {
        return $this->belongsTo(Currency::class, 'value', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}