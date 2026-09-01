<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HasFeatureLimit
{
    protected static function bootHasFeatureLimit()
    {
        // Use a standardized pattern: lowercase basename to align perfectly with middleware naming
        $cacheKey = 'limit_count_' . strtolower(class_basename(static::class)) . '_' . tenant('id');

        static::created(function ($model) use ($cacheKey) {
            Cache::tags([tenant_tag()])->forget($cacheKey);
        });

        static::deleted(function ($model) use ($cacheKey) {
            Cache::tags([tenant_tag()])->forget($cacheKey);
        });

        // English Comment: Register restored event safely without strict method_exists checks on the core class
        static::registerModelEvent('restored', function ($model) use ($cacheKey) {
            Cache::tags([tenant_tag()])->forget($cacheKey);
        });
    }
}
