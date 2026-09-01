<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class TenantAddress extends Model
{
    protected $fillable = [
        'tenant_id', 'state', 'street_address', 'city', 'post_code', 'division', 'district', 'upazila', 'longitude', 'latitude', 'full_address', 'country'
    ];
}
