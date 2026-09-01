<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BranchStock extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'branch_id', 'product_id', 'product_variant_id', 'product_batch_id', 'unit_id', 'quantity',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saved(function ($branchStock) {
            static::syncTotalStock($branchStock->product_id, $branchStock->product_variant_id);

            if ($branchStock->product_batch_id) {
                static::syncBatchStock($branchStock->product_batch_id);
            }
            Product::clearUiCache();
        });

        static::deleted(function ($branchStock) {
            static::syncTotalStock($branchStock->product_id, $branchStock->product_variant_id);

            if ($branchStock->product_batch_id) {
                static::syncBatchStock($branchStock->product_batch_id);
            }
            Product::clearUiCache();
        });
    }

    protected static function syncTotalStock($productId, $variantId = null): void
    {
        if ($variantId) {
            $totalVariantStock = static::where('product_variant_id', $variantId)->sum('quantity');

            \App\Models\ProductVariant::where('id', $variantId)->update([
                'total_stock' => $totalVariantStock
            ]);
        }

        $totalProductStock = static::where('product_id', $productId)->sum('quantity');

        \App\Models\Product::where('id', $productId)->update([
            'total_stock' => $totalProductStock
        ]);
    }

    protected static function syncBatchStock($batchId)
    {
        $totalBatchStock = static::where('product_batch_id', $batchId)->sum('quantity') ?? 0;

        DB::table('product_batches')
            ->where('id', $batchId)
            ->update([
                'quantity'   => $totalBatchStock,
                'updated_at' => now()
            ]);
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
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function prices()
    {
        return $this->hasOne(ProductPrice::class, 'product_batch_id', 'product_batch_id')
            ->where('branch_id', $this->branch_id)
            ->where('product_variant_id', $this->product_variant_id);
    }

    public function shelf()
    {
        return $this->belongsTo(Shelf::class, 'shelf_id');
    }

    public function getLocationAttribute()
    {
        if ($this->shelf && $this->shelf->rack) {
            return $this->shelf->rack->name . ' ➔ ' . $this->shelf->name;
        }

        return 'No Location Assigned';
    }
}