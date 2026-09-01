<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LandlordSetting extends Model
{
    protected $connection = 'sherazipos_landlord';
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function get(string $key, $default = null): mixed
    {
        return Cache::tags([landlord_tag()])->rememberForever("settings.{$key}", function () use ($key, $default) {
            return optional(static::where('key', $key)->first())->value ?? $default;
        });
    }

    public static function set(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => is_array($value) ? $value : (string) $value]
        );

        Cache::tags([landlord_tag()])->forget("settings.{$key}");
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /**
     * Clear all cached settings.
     */
    public static function clearCache(): void
    {
        Cache::tags([landlord_tag()])->flush();
    }

    
}
