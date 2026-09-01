<?php

namespace App\Models\landlord;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    const TYPE_FEATURE = 'feature';
    const TYPE_LIMIT   = 'limit';

    protected $fillable = [
        'name',
        'type',
        'reference_type',
        'reference_id',
        'price',
        'duration_days',
        'is_active',
        'meta',
    ];

    protected static function booted()
    {
        static::saving(function ($addon) {

            if (in_array($addon->type, [self::TYPE_FEATURE, self::TYPE_LIMIT])) {
                if (
                    $addon->type === self::TYPE_FEATURE &&
                    $addon->reference_type !== 'feature'
                ) {
                    throw new \Exception('Feature addon must reference a feature');
                }

                if (
                    $addon->type === self::TYPE_LIMIT &&
                    !in_array($addon->reference_type, ['feature'])
                ) {
                    throw new \Exception('Limit addon must reference a feature');
                }
            }
        });
    }

    protected $casts = [
        'meta' => 'array'
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function tenantAddons()
    {
        return $this->hasMany(TenantAddon::class, 'addon_id');
    }

    public function isUsedByTenant()
    {
        return $this->tenantAddons()->where('expires_at', '>', Carbon::now())->exists();
    }
}
