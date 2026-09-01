<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class ResellerClient extends Model
{
    protected $fillable = [
        'reseller_id',
        'tenant_id',
        'domain',
        'package_id',
        'registration_fee',
        'commission',
        'comission_amount',
        'due',
        'paid',
        'admin_receivable',
        'admin_due',
        'is_overdue',
    ];

    public function tenant() {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function reseller() {
        return $this->belongsTo(Reseller::class, 'reseller_id', 'id');
    }

    public function notes()
    {
        return $this->hasMany(ClientNote::class, 'reseller_client_id');
    }

    public function latestNote()
    {
        return $this->hasOne(ClientNote::class, 'reseller_client_id')->latest('created_at');
    }
    public function package()
        {
            return $this->belongsTo(Package::class, 'package_id');
        }

}
