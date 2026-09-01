<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class TenantFeatureOverride extends Model
{
    protected $fillable = [
        'tenant_id',
        'feature_id',
        'meta',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
