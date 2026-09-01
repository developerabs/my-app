<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FeatureService
{
    public static function getAllFeature()
    {
        return once(function () {
            $cacheKey = 'tenant_features_' . tenant('id');
            return Cache::tags([tenant_tag()])->remember($cacheKey, 3600, function () {
                return DB::table('settings')
                    ->where('group', 'features')
                    ->pluck('value', 'key')
                    ->toArray();
            });
        });
    }

    public static function getActive($key)
    {
        $features = self::getAllFeature();
        return isset($features[$key]) && $features[$key] == '1';
    }

    public static function getLimit($key)
    {
        $features = self::getAllFeature();

        // If key does not exist in the database, return null (Unlimited)
        if (!isset($features[$key])) {
            return null;
        }

        // If key exists, return its integer value
        return (int) $features[$key];
    }
}
