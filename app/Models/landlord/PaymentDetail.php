<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    protected $fillable = [
        'payment_id',
        'tenant_id',
        'paymentID',
        'trxID',
        'transactionStatus',
        'amount',
        'currency',
        'intent',
        'paymentExecuteTime',
        'merchantInvoiceNumber',
        'payerType',
        'payerReference',
        'customerMsisdn',
        'payerAccount',
        'statusCode',
        'statusMessage',
    ];
}
