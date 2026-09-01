<?php

namespace App\Models\landlord;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Support\Facades\Cache;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $casts = [
        'expires_at' => 'datetime',
    ];


    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function primaryDomain()
    {
        // Try primary first
        $primary = $this->domains()->where('is_primary', true)->first();

        // যদি না থাকে → first domain fallback
        $domain = $primary ?? $this->domains()->first();

        return $domain ? $domain->domain : null;
    }

    public function addons()
    {
        return $this->hasMany(TenantAddon::class);
    }

    public function modules()
    {
        return $this->hasMany(TenantModule::class);
    }

    public function activeModules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules', 'tenant_id', 'module_id')
            ->withPivot('type')
            ->withTimestamps();
    }

    public static function findByDomain($domain)
    {
        return Cache::tags([landlord_tag()])->remember("tenant_domain_{$domain}", now()->addDays(1), function () use ($domain) {
            return parent::findByDomain($domain);
        });
    }
}
