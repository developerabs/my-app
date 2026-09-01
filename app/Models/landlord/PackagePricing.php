<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class PackagePricing extends Model
{
    protected $fillable = [
        'package_id',
        'type',
        'price',
        'duration_days',
        'is_active',
        'meta',
    ];
}
