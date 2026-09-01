<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
     protected $fillable = [
        'reseller_id',
        'tenant_id',
        'proposal_number',
        'customer_name',
        'company_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'proposal_details',
        'package',
        'registration_fee',
        'discount',
        'discount_type',
        'subscription_fee',
        'monthly',
        'yearly',
        'lifetime',
        'validity',
        'demo_link',
        'username',
        'password',
        'special_note',
        'added_by',
        'status',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proposal) {
            if (empty($proposal->proposal_number)) {
                $proposal->proposal_number = "PRP-" . date("Ymd") . '-' . date("His");
            }
        });
    }

   public function packageInfo()
{
    return $this->belongsTo(Package::class, 'package', 'id');
}

}
