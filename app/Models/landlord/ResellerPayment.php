<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class ResellerPayment extends Model
{
    protected $fillable = [
        'payment_id',
        'reseller_id',
        'tenant_id',
        'transaction_id',
        'amount',
        'payment_method',
        'status',
        'note',
        'meta',
        'completed_at',
    ];
}
