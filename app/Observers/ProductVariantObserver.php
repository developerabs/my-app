<?php

namespace App\Observers;

use App\Models\ProductPrice;
use App\Models\ProductVariant;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "created" event.
     */
    public function created(ProductVariant $productVariant): void
    {
        $product = $productVariant->product;
        if ($product) {
            $priceData = $product->prepareProductPrices(
                $productVariant->id,          // $variantId
                null,                  // $branchId
                null,                  // $batchId
                $productVariant->price,       // $customPrice
                $productVariant->cost,         // $customCost
                $productVariant->wholesale_price ?? null // $customWholesalePrice
            );
            $product->prices()->create($priceData);

            $product->barcodes()->create([
                'barcode'           => $productVariant->code ?? $productVariant->sku,
                'barcode_type'      => 'standard',
                'barcode_symbology' => 'C128',
                'display_name'      => ($product->short_name ?? $product->name) . ' (' . $productVariant->name . ')',
                'sku'               => $productVariant->sku,
                'code'              => $productVariant->code ?? null,
                'product_variant_id'=> $productVariant->id // English Comment: Ensure link to variant exists if column allows
            ]);
        }
    }

    /**
     * Handle the ProductVariant "updated" event.
     */
    public function updated(ProductVariant $productVariant): void
    {
        $product = $productVariant->product;
        if ($product) {
            if ($productVariant->isDirty(['price', 'cost', 'wholesale_price', 'updated_at'])) {

                $product->unsetRelations(); // English Comment: Clear relation cache to ensure fresh data retrieval

                $priceData = $product->prepareProductPrices(
                    $productVariant->id,          // $variantId
                    null,                  // $branchId
                    null,                  // $batchId
                    $productVariant->price,       // $customPrice
                    $productVariant->cost,         // $customCost
                    $productVariant->wholesale_price ?? null, // $customWholesalePrice
                    $productVariant->unit_details ?? null // $unitDetails
                );

                //dd($priceData); // English Comment: Debug output to verify price data structure before database operations
                $currentPriceRow = ProductPrice::where('product_id', $product->id)
                    ->where('product_variant_id', $productVariant->id)
                    ->where('is_current', true)
                    ->first();
                
                if($currentPriceRow){
                    $hasPriceChanged = 
                        (float)$currentPriceRow->price !== (float)$priceData['price'] ||
                        (float)$currentPriceRow->cost !== (float)$priceData['cost'] ||
                        (float)$currentPriceRow->wholesale_price !== (float)$priceData['wholesale_price'];
                    
                    if($hasPriceChanged){
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

            if ($productVariant->isDirty(['code', 'sku', 'name'])) {
                $oldCode = $productVariant->getOriginal('code');
                $oldSku = $productVariant->getOriginal('sku');

                $product->barcodes()
                    ->where('product_variant_id', $productVariant->id)
                    ->whereIn('barcode', array_filter([$oldCode, $oldSku]))
                    ->delete();

                $product->barcodes()->create([
                    'product_variant_id'=> $productVariant->id,
                    'barcode'           => $productVariant->code ?? $productVariant->sku,
                    'barcode_type'      => $product->barcode_type ?? 'standard',
                    'barcode_symbology' => $product->barcode_symbology ?? 'C128',
                    'display_name'      => ($product->short_name ?? $product->name) . ' (' . $productVariant->name . ')',
                    'sku'               => $productVariant->sku ?? null,
                    'code'              => $productVariant->code ?? null,
                ]);
            }
        }
    }

    /**
     * Handle the ProductVariant "deleted" event.
     */
    public function deleted(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "restored" event.
     */
    public function restored(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "force deleted" event.
     */
    public function forceDeleted(ProductVariant $productVariant): void
    {
        //
    }
}
