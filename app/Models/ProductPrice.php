<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'product_batch_id',
        'branch_id',
        'unit_id',
        'price',
        'cost',
        'wholesale_price',
        'other_prices',
        'is_current',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'other_prices' => 'array',
        'is_current'   => 'boolean',
    ];

    #[Override]
    public static function booted()
    {
        parent::booted();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
            if ($model->is_current) {
                $query = static::where('product_id', $model->product_id);

                $model->product_variant_id
                    ? $query->where('product_variant_id', $model->product_variant_id)
                    : $query->whereNull('product_variant_id');

                $model->product_batch_id
                    ? $query->where('product_batch_id', $model->product_batch_id)
                    : $query->whereNull('product_batch_id');

                $query->where('is_current', true)->update(['is_current' => false]);
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

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
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}