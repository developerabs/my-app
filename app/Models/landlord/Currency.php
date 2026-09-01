<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
        'is_base',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
