<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class TenantAddon extends Model
{
    protected $fillable = [
        'tenant_id',
        'addon_id',
        'expires_at',
        'meta',
    ];

    public function addon()
    {
        return $this->belongsTo(Addon::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
