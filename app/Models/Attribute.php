<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;
use Illuminate\Support\Facades\Cache;

class Attribute extends Model
{
    use HasAuditTrail;

    protected $fillable = ['name', 'slug', 'description', 'is_color', 'is_active', 'meta', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Casting attributes
     */

    protected $casts = [
        'is_color' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public static function booted()
    {
        parent::booted();
        static::bootHasAuditTrail();
        $clearCache = function ($attribute) {
            // Construct the same key used during cache storage
            $cacheKey = 'all_attributes_' . tenant('id');
            Cache::tags([tenant_tag()])->forget($cacheKey);
        };

        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
