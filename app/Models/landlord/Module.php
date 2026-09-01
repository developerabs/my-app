<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'key',
        'description',
        'codebase_type',
        'service_provider',
        'base_namespace',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ]; 

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function features()
    {
        return $this->hasMany(Feature::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}
