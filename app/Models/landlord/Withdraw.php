<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    protected $fillable = [
        'payment_id',
        'reseller_id',
        'transaction_id',
        'amount',
        'method',
        'status',
        'note',
        'payment_details',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
    ];
}
