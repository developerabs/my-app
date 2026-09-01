<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'reseller_min_reg_fee',
        'description',
        'image',
        'is_trial',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function features()
    {
        return $this->hasMany(PackageFeature::class);
    }

    public function modules()
    {
        return $this->hasMany(PackageModule::class);
    }

    public function pricing()
    {
        return $this->hasMany(PackagePricing::class);
    }
    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }
    
}
