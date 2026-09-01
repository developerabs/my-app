<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Cache;

class ProductVariant extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $touches = ['product'];
    
    protected $fillable = [
        'product_id',
        'sku',
        'code',
        'name',
        'cost',
        'price',
        'wholesale_price',
        'opening_stock',
        'variant_details',
        'unit_details',
        'is_active',
    ];

    protected $casts = [
        'variant_details' => 'array',
        'unit_details'    => 'array',
    ];

    public static function booted()
    {
        parent::booted();

        $clearUiCache = function ($model) {
            Product::clearUiCache();
        };

        static::saved($clearUiCache);
        static::updated($clearUiCache);
        static::deleted($clearUiCache);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function options()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_options', 'variant_id', 'attribute_value_id')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id');
    }

    public function currentPrice()
    {
        return $this->hasOne(ProductPrice::class, 'product_variant_id')->where('is_current', true);
    }

    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class, 'product_variant_id');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class, 'product_variant_id');
    }

    public function branch_stocks()
    {
        return $this->hasMany(BranchStock::class, 'product_variant_id');
    }

    public function images()
    {
        return $this->hasMany(ProductVariantImage::class, 'variant_id');
    }
}