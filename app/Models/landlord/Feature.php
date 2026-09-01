<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = [
        'name',
        'key',
        'module_id',
        'description',
        'icon',
        'has_module',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_module' => 'boolean',
        'meta' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCore($query)
    {
        return $query->where('module_id', null);
    }

    public function featurePermissions()
    {
        return $this->hasMany(FeaturePermission::class);
    }
}
