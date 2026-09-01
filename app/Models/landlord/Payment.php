<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $connection = 'sherazipos_landlord';
    
    protected $fillable = [
        'invoice_number',
        'tenant_id',
        'base_amount',
        'base_currency',
        'pay_amount',
        'pay_currency',
        'exchange_rate',
        'payment_method',
        'gateway',
        'payment_id',
        'status',
        'credential_owner',
        'paid_for',
        'paid_by',
        'added_by',
    ];
}
