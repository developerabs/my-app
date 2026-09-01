<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function creating(Product $product):void
    {
        if (empty($product->code)) {
            $product->code = Product::generateItemCode(); 
        }
        
        if (empty($product->slug)) {
            $product->slug = Product::generateUniqueSlug($product->name);
        }
    }
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $priceData = $product->prepareProductPrices();
        $product->prices()->create($priceData);

        $this->generateBarcodes($product);

    }

    /**
     * Handle the Product "updating" event.
     */
    public function updating(Product $product): void
    {
        // English Comment: Regenerate unique slug only if the product name has been modified
        if ($product->isDirty('name')) {
            $product->slug = Product::generateUniqueSlug($product->name);
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $priceData = $product->prepareProductPrices();
        $product->prices()->whereNull('product_variant_id')->whereNull('product_batch_id')->whereNull('branch_id')->update($priceData);

        if($product->wasChanged(['base_unit_id', 'sale_unit_id', 'purchase_unit_id'])) {
            foreach($product->variants as $variant) {
                $variant->updated_at = now();
                $variant->save();
            }
        }

        if ($product->wasChanged(['code', 'sku', 'short_name', 'name', 'barcode_type', 'barcode_symbology'])) {
            $oldCode = $product->getOriginal('code');
            $oldSku = $product->getOriginal('sku');

            $product->barcodes()
                ->whereIn('barcode', array_filter([$oldCode, $oldSku]))
                ->delete();

            $this->generateBarcodes($product);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }

    /**
     * English Comment: Helper method to generate structure for product barcodes.
     */
    private function generateBarcodes(Product $product): void
    {
        $barcodes = [];

        if ($product->code) {
            $barcodes[] = [
                'barcode'           => $product->code,
                'barcode_type'      => $product->barcode_type ?? 'standard',
                'barcode_symbology' => $product->barcode_symbology ?? 'C128',
                'display_name'      => $product->short_name ?? $product->name,
                'sku'               => $product->sku ?? null,
                'code'              => $product->code
            ];
        }

        if ($product->sku) {
            $barcodes[] = [
                'barcode'           => $product->sku,
                'barcode_type'      => 'standard',
                'barcode_symbology' => 'C128',
                'display_name'      => $product->short_name ?? $product->name,
                'sku'               => $product->sku,
                'code'              => $product->code ?? null
            ];
        }

        if (!empty($barcodes)) {
            $product->barcodes()->createMany($barcodes);
        }
    }
}
