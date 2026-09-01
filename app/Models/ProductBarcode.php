<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

class ProductBarcode extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 'product_variant_id', 'product_batch_id', 'barcode', 'barcode_type', 'barcode_symbology', 'display_name', 'sku', 'code',
    ];

    #[Override]
    public static function booted()
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
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }
}