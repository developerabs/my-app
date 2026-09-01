<?php

namespace App\Models\landlord;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reseller extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'type',
        'phone',
        'email',
        'company_name',
        'company_logo',
        'address',
        'commission_per_registration',
        'commission_per_subscription',
        'balance',
        'meta',
        'status',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeBanned($query)
    {
        return $query->where('status', 'banned');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'reseller_id');
    }

}
