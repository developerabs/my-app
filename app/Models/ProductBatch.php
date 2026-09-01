<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 'product_variant_id', 'supplier_id', 'batch_no', 'expiry_date', 'cost', 'price', 'wholesale_price', 'quantity',
    ];

    protected $casts = [
        'expiry_date'     => 'date',
        'cost'            => 'decimal:2',
        'price'           => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'quantity'        => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saved(function ($model) {
            Product::clearUiCache();
        });

        static::deleted(function ($model) {
            Product::clearUiCache();
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class)->with('product');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    
    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class, 'product_variant_id');
    }

    public function masterBarcode()
    {
        return $this->hasOne(ProductBarcode::class, 'product_batch_id')->where('barcode_type', 'master');
    }

    public function imeis()
    {
        return $this->hasMany(ProductImei::class, 'product_batch_id');
    }
}