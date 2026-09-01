<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDropshippingDetail extends Model
{
    protected $fillable = [
        'product_id',
        'platform_name',
        'supplier_name',
        'external_product_code',
        'external_product_url',
        'external_sku',
        'selling_price',
        'buying_price',
        'estimated_shipping_cost',
        'delivery_lead_time',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
