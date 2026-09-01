<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'branch_id',
        'product_id',
        'product_variant_id',
        'product_batch_id',
        'type',
        'quantity',
        'stock_after',
        'referenceable_id',  
        'referenceable_type', 
        'transaction_date',
        'note',
    ];

    protected $casts = [
        'quantity' => 'float',
        'stock_after' => 'float',
    ];

    public function referenceable()
    {
        return $this->morphTo();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class);
    }
}
