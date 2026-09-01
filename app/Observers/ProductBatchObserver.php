<?php

namespace App\Observers;

use App\Models\ProductBatch;
use App\Models\ProductPrice;

class ProductBatchObserver
{
    /**
     * Handle the ProductBatch "created" event.
     */
    public function created(ProductBatch $productBatch): void
    {
        $product = $productBatch->product;
        
        if ($product) {
            // 🔥 English Comment: Layered fallback resolution for multi-branch environments
            $branchId = null;
            if (request()->has('branch_id') && !is_null(request()->input('branch_id'))) {
                $branchId = request()->input('branch_id');
            } elseif (auth()->check()) {
                $branchId = auth()->user()->branch_id;
            }

            // 🔥 English Comment: Model level clean call using memory-optimized structure
            $priceData = $product->prepareProductPrices(
                $productBatch->product_variant_id ?? null,
                $branchId,
                $productBatch->id,
                $productBatch->price ?? 0,
                $productBatch->cost ?? 0,
                $productBatch->wholesale_price ?? null
            );

            $product->prices()->create($priceData);

            // English Comment: Default batch barcode construction layer
            $baseCode = $product->code;
            if ($productBatch->product_variant_id) {
                // English Comment: Leverage relationship caching mechanisms
                $product->loadMissing('variants');
                $variant = $product->variants->firstWhere('id', $productBatch->product_variant_id);
                if ($variant) {
                    $baseCode = $variant->code ?? $variant->sku ?? $product->code;
                }
            }

            $product->barcodes()->create([
                'product_id'         => $product->id,
                'product_variant_id' => $productBatch->product_variant_id ?? null,
                'product_batch_id'   => $productBatch->id,
                'barcode'           => $baseCode . '-' . $productBatch->batch_no,
                'barcode_type'      => $product->barcode_type ?? 'standard',
                'barcode_symbology' => $product->barcode_symbology ?? 'C128',
            ]);
        }
    }

    /**
     * Handle the ProductBatch "updated" event.
     */
    public function updated(ProductBatch $productBatch): void
    {
        $product = $productBatch->product;

        if ($product) {
            // English Comment: Trigger price re-calculation tracking dirty states
            if ($productBatch->isDirty(['price', 'cost', 'wholesale_price', 'updated_at'])) {
                
                $product->unsetRelations();

                $branchId = null;
                if (request()->has('branch_id') && !is_null(request()->input('branch_id'))) {
                    $branchId = request()->input('branch_id');
                } elseif (auth()->check()) {
                    $branchId = auth()->user()->branch_id;
                }

                $priceData = $product->prepareProductPrices(
                    $productBatch->product_variant_id ?? null,
                    $branchId,
                    $productBatch->id,
                    $productBatch->price,
                    $productBatch->cost,
                    $productBatch->wholesale_price ?? null
                );

                $currentPriceRow = ProductPrice::where('product_id', $product->id)
                    ->where('product_batch_id', $productBatch->id)
                    ->where('is_current', true)
                    ->first();

                if ($currentPriceRow) {
                    $hasPriceChanged = 
                        (float)$currentPriceRow->price !== (float)$priceData['price'] ||
                        (float)$currentPriceRow->cost !== (float)$priceData['cost'] ||
                        (float)$currentPriceRow->wholesale_price !== (float)$priceData['wholesale_price'];

                    if ($hasPriceChanged) {
                        ProductPrice::create($priceData);
                    } else {
                        $currentPriceRow->updateQuietly([
                            'unit_id'      => $priceData['unit_id'] ?? $currentPriceRow->unit_id,
                            'other_prices' => $priceData['other_prices'] ?? $currentPriceRow->other_prices,
                        ]);
                    }
                } else {
                    ProductPrice::create($priceData);
                }
            }

            // English Comment: Clean and regenerate the code string maps dynamically if batch number tags alter
            if ($productBatch->isDirty(['batch_no'])) {
                $oldBatchNo = $productBatch->getOriginal('batch_no');

                $product->barcodes()
                    ->where('product_batch_id', $productBatch->id)
                    ->delete();

                $baseCode = $product->code;
                if ($productBatch->product_variant_id) {
                    $product->loadMissing('variants');
                    $variant = $product->variants->firstWhere('id', $productBatch->product_variant_id);
                    if ($variant) {
                        $baseCode = $variant->code ?? $variant->sku ?? $product->code;
                    }
                }

                $product->barcodes()->create([
                    'product_id'         => $product->id,
                    'product_variant_id' => $productBatch->product_variant_id ?? null,
                    'product_batch_id'   => $productBatch->id,
                    'barcode'           => $baseCode . '-' . $productBatch->batch_no,
                    'barcode_type'      => $product->barcode_type ?? 'standard',
                    'barcode_symbology' => $product->barcode_symbology ?? 'C128',
                ]);
            }
        }
    }

    /**
     * Handle the ProductBatch "deleted" event.
     */
    public function deleted(ProductBatch $productBatch): void
    {
        //
    }

    /**
     * Handle the ProductBatch "restored" event.
     */
    public function restored(ProductBatch $productBatch): void
    {
        //
    }

    /**
     * Handle the ProductBatch "force deleted" event.
     */
    public function forceDeleted(ProductBatch $productBatch): void
    {
        //
    }
}
