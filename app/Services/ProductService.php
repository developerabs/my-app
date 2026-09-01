<?php

namespace App\Services;

use App\Enums\ImeiEventType;
use App\Enums\ImeiStatus;

use App\Events\ProductImeiEvent;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use App\Models\ProductImei;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\StockTransaction;
use App\Models\TenantMedia;
use App\Models\Unit;
use App\Services\Accounting\AccountingIntegrationService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductService
{
    use HasFiles;

    public function __construct(
        protected UnitFormulaService $unitFormulaService
    ) {}

    /**
     * Create Product with all relations, media, and combo items.
     */
    public function createProduct(array $validated, array $requestData, ?UploadedFile $thumbnailFile = null, array $galleryFiles = []): Product
    {
        return DB::transaction(function () use ($validated, $requestData, $thumbnailFile, $galleryFiles) {

            $action = $requestData['action'] ?? 'save';
            $hasVariants = !empty($requestData['has_variants']);
            $validated['status'] = $action == 'draft' ? 'draft' : ($hasVariants ? 'incomplete' : 'active');

            // 1. Process Unit Details
            $unitIds = [];
            if (!empty($requestData['unit_vars']) && is_array($requestData['unit_vars'])) {
                $unitIds = array_merge($unitIds, array_keys($requestData['unit_vars']));
            }

            if (!empty($requestData['base_unit_id'])) $unitIds[] = $requestData['base_unit_id'];
            if (!empty($requestData['sale_unit_id'])) $unitIds[] = $requestData['sale_unit_id'];
            if (!empty($requestData['purchase_unit_id'])) $unitIds[] = $requestData['purchase_unit_id'];

            $unitIds = array_unique(array_filter($unitIds));
            $allRelatedUnitIds = $this->getAllParentUnitIds($unitIds);
            $units = Unit::whereIn('id', $allRelatedUnitIds)->get()->keyBy('id');
            $unitDetails = [];

            foreach ($units as $unit) {
                $vars = $requestData['unit_vars'][$unit->id] ?? [];
                $unitDetails[$unit->id] = $this->prepareUnitSnapshot($unit, $vars);
            }

            $validated['unit_details'] = $unitDetails;

            // 2. Process Warranty Details
            if (!empty($requestData['has_warranty'])) {
                $validated['warranty_details'] = [
                    'warranty_type' => $requestData['warranty_type'] ?? null,
                    'warranty_provider' => $requestData['warranty_provider'] ?? null,
                    'warranty_period' => $requestData['warranty_period'] ?? null,
                    'period_type' => $requestData['period_type'] ?? null,
                    'warranty_terms_and_conditions' => $requestData['warranty_terms_and_conditions'] ?? null,
                ];
            }

            // 3. Process Thumbnail
            if ($thumbnailFile) {
                $validated['thumbnail'] = $this->processImage($thumbnailFile, 'products', [
                    'width' => 500,
                    'thumbnail' => true,
                    'thumb_width' => 150,
                ]);
            }

            // 4. Create Product
            $product = Product::create($validated);

            // 5. Sync Categories
            if (!empty($requestData['category_id']) && is_array($requestData['category_id'])) {
                $product->categories()->sync($requestData['category_id']);
            }

            // 6. Create Specifications
            if (!empty($requestData['specification_name']) && is_array($requestData['specification_name'])) {
                $specs = [];
                foreach ($requestData['specification_name'] as $index => $specName) {
                    if (!empty($specName)) {
                        $specs[] = [
                            'key'   => $specName,
                            'value' => $requestData['specification_value'][$index] ?? null,
                        ];
                    }
                }
                if (!empty($specs)) $product->specifications()->createMany($specs);
            }

            // 7. Dropship Details
            if (($requestData['type'] ?? '') === 'dropship') {
                $product->dropshippingDetail()->create([
                    'platform_name' => $requestData['platform_name'] ?? null,
                    'supplier_name' => $requestData['supplier_name'] ?? null,
                    'external_product_code' => $requestData['external_product_code'] ?? null,
                    'external_product_url' => $requestData['external_product_url'] ?? null,
                    'external_sku' => $requestData['external_sku'] ?? null,
                    'selling_price' => $requestData['selling_price'] ?? 0,
                    'buying_price' => $requestData['buying_price'] ?? 0,
                    'estimated_shipping_cost' => $requestData['estimated_shipping_cost'] ?? 0,
                    'delivery_lead_time' => $requestData['delivery_lead_time'] ?? 0,
                ]);
            }

            // 8. Gallery Images
            if (!empty($galleryFiles)) {
                $this->processGallery($galleryFiles, $product);
            }

            // 9. Combo Items Sync (With unit_id FIXED!)
            if (($requestData['type'] ?? '') === 'combo' && !empty($requestData['combo_items']) && is_array($requestData['combo_items'])) {
                $this->syncComboItems($product, $requestData['combo_items']);
            }

            return $product;
        });
    }

    /**
     * Update Product with all relations, media, and combo items.
     */
    public function updateProduct(Product $product, array $validated, array $requestData, ?UploadedFile $thumbnailFile = null, array $galleryFiles = []): Product
    {
        return DB::transaction(function () use ($product, $validated, $requestData, $thumbnailFile, $galleryFiles) {

            $variantCount = $product->has_variants ? $product->variants()->count() : 0;
            $action = $requestData['action'] ?? 'update';

            $productStatus = match ($action) {
                'update'  => $product->status,
                'publish' => ($variantCount === 0 && !empty($requestData['has_variants'])) ? 'incomplete' : 'active',
                'draft'   => 'draft',
                default   => $product->status,
            };
            $validated['status'] = $productStatus;

            // 1. Process Unit Details
            $unitIds = [];
            if (!empty($requestData['unit_vars']) && is_array($requestData['unit_vars'])) {
                $unitIds = array_merge($unitIds, array_keys($requestData['unit_vars']));
            }

            if (!empty($requestData['base_unit_id'])) $unitIds[] = $requestData['base_unit_id'];
            if (!empty($requestData['sale_unit_id'])) $unitIds[] = $requestData['sale_unit_id'];
            if (!empty($requestData['purchase_unit_id'])) $unitIds[] = $requestData['purchase_unit_id'];

            $unitIds = array_unique(array_filter($unitIds));
            $allRelatedUnitIds = $this->getAllParentUnitIds($unitIds);
            $units = Unit::whereIn('id', $allRelatedUnitIds)->get()->keyBy('id');
            $unitDetails = [];

            foreach ($units as $unit) {
                $vars = $requestData['unit_vars'][$unit->id] ?? [];
                $unitDetails[$unit->id] = $this->prepareUnitSnapshot($unit, $vars);
            }
            $validated['unit_details'] = $unitDetails;

            // 2. Warranty
            if (!empty($requestData['has_warranty'])) {
                $validated['warranty_details'] = [
                    'warranty_type' => $requestData['warranty_type'] ?? null,
                    'warranty_provider' => $requestData['warranty_provider'] ?? null,
                    'warranty_period' => $requestData['warranty_period'] ?? null,
                    'period_type' => $requestData['period_type'] ?? null,
                    'warranty_terms_and_conditions' => $requestData['warranty_terms_and_conditions'] ?? null,
                ];
            } else {
                $validated['warranty_details'] = null;
            }

            // 3. Thumbnail
            if ($thumbnailFile) {
                $validated['thumbnail'] = $this->processImage($thumbnailFile, 'products', [
                    'width' => 500,
                    'thumbnail' => true,
                    'thumb_width' => 150,
                ], $product->thumbnail);
            }

            $product->update($validated);

            // 4. Sync Categories
            if (isset($requestData['category_id'])) {
                $product->categories()->sync($requestData['category_id']);
            }

            // 5. Specifications
            $product->specifications()->delete();
            if (!empty($requestData['specification_name']) && is_array($requestData['specification_name'])) {
                $specs = [];
                foreach ($requestData['specification_name'] as $index => $specName) {
                    if (!empty($specName)) {
                        $specs[] = [
                            'key'   => $specName,
                            'value' => $requestData['specification_value'][$index] ?? null,
                        ];
                    }
                }
                if (!empty($specs)) $product->specifications()->createMany($specs);
            }

            // 6. Dropship
            if (($requestData['type'] ?? '') === 'dropship') {
                $product->dropshippingDetail()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'platform_name' => $requestData['platform_name'] ?? null,
                        'supplier_name' => $requestData['supplier_name'] ?? null,
                        'external_product_code' => $requestData['external_product_code'] ?? null,
                        'external_product_url' => $requestData['external_product_url'] ?? null,
                        'external_sku' => $requestData['external_sku'] ?? null,
                        'selling_price' => $requestData['selling_price'] ?? 0,
                        'buying_price' => $requestData['buying_price'] ?? 0,
                        'estimated_shipping_cost' => $requestData['estimated_shipping_cost'] ?? 0,
                        'delivery_lead_time' => $requestData['delivery_lead_time'] ?? 0,
                    ]
                );
            } else {
                $product->dropshippingDetail()->delete();
            }

            // 7. Gallery
            if (!empty($galleryFiles)) {
                $this->processGallery($galleryFiles, $product);
            }

            // 8. Combo Items Sync
            if (($requestData['type'] ?? '') === 'combo' && !empty($requestData['combo_items']) && is_array($requestData['combo_items'])) {
                $this->syncComboItems($product, $requestData['combo_items']);
            } else {
                $product->comboItems()->delete();
            }

            return $product;
        });
    }

    /**
     * Sync Combo Items (Saves unit_id FIXED!)
     */
    public function syncComboItems(Product $product, array $comboItems): void
    {
        $product->comboItems()->delete();

        $comboData = [];
        $totalCalculatedCost = 0;

        foreach ($comboItems as $item) {
            if (empty($item['product_id'])) continue;

            $qty = max(0.0001, (float) ($item['quantity'] ?? 1));
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            $lineCost = round($qty * $unitCost, 2);
            $linePrice = round($qty * $unitPrice, 2);
            $totalCalculatedCost += $lineCost;

            $comboData[] = [
                'product_id'         => $item['product_id'],
                'product_variant_id' => !empty($item['product_variant_id']) ? $item['product_variant_id'] : null,
                'unit_id'            => !empty($item['unit_id']) ? $item['unit_id'] : null, // 👈 FIXED: unit_id stored properly!
                'quantity'           => $qty,
                'unit_cost'          => $unitCost,
                'unit_price'         => $unitPrice,
                'total_cost'         => $lineCost,
                'total_price'        => $linePrice,
            ];
        }

        if (!empty($comboData)) {
            $product->comboItems()->createMany($comboData);
            $product->updateQuietly(['cost' => $totalCalculatedCost]);
        }
    }

    /**
     * Sync Variants for Product
     */
    public function updateVariants(Product $product, array $attributes, array $variants, array $variantFiles = []): void
    {
        DB::transaction(function () use ($product, $attributes, $variants, $variantFiles) {
            $valToIdMap = [];

            foreach ($attributes as $attrData) {
                if (empty($attrData['name'])) continue;

                $attribute = Attribute::updateOrCreate(
                    ['name' => trim($attrData['name'])],
                    ['slug' => Str::slug($attrData['name']), 'is_active' => 1]
                );

                if (isset($attrData['values']) && is_array($attrData['values'])) {
                    foreach ($attrData['values'] as $valName) {
                        $valNameTrimmed = trim($valName);
                        $attrValue = AttributeValue::updateOrCreate([
                            'attribute_id' => $attribute->id,
                            'value' => $valNameTrimmed
                        ]);

                        $mapKey = strtolower(trim($attribute->name)) . '|' . strtolower($valNameTrimmed);
                        $valToIdMap[$mapKey] = [
                            'value_id' => $attrValue->id,
                            'attribute_id' => $attribute->id
                        ];
                    }
                }
            }

            $processedVariantIds = [];
            $parentUnitDetails = $product->unit_details ?? [];

            foreach ($variants as $index => $vData) {
                $variantUnitDetails = $parentUnitDetails;
                
                if (!empty($vData['unit_vars']) && is_array($vData['unit_vars']) && !empty($variantUnitDetails)) {
                    foreach ($variantUnitDetails as $unitId => &$unitData) {
                        if (!empty($unitData['is_formulaic']) && isset($unitData['user_vars']) && is_array($unitData['user_vars'])) {
                            if (isset($vData['unit_vars'][$unitId]) && is_array($vData['unit_vars'][$unitId])) {
                                foreach ($unitData['user_vars'] as $varName => $defaultValue) {
                                    if (isset($vData['unit_vars'][$unitId][$varName])) {
                                        $unitData['user_vars'][$varName] = $vData['unit_vars'][$unitId][$varName];
                                    }
                                }
                            }
                        }
                    }
                    unset($unitData);
                }

                $variant = ProductVariant::updateOrCreate(
                    [
                        'id' => $vData['id'] ?? null,
                        'product_id' => $product->id
                    ],
                    [
                        'sku'             => $vData['sku'],
                        'code'            => $vData['code'] ?? $vData['sku'],
                        'name'            => $vData['name'],
                        'cost'            => $vData['cost'] ?? 0,
                        'price'           => $vData['price'] ?? 0,
                        'wholesale_price' => $vData['wholesale_price'] ?? 0,
                        'unit_details'    => $variantUnitDetails,
                        'is_active'       => 1,
                    ]
                );

                $processedVariantIds[] = $variant->id;

                $variantValues = explode('/', $vData['name']);
                $syncData = [];
                foreach ($variantValues as $vValue) {
                    $vValueTrimmed = strtolower(trim($vValue));
                    foreach ($valToIdMap as $key => $ids) {
                        if (str_ends_with($key, '|' . $vValueTrimmed)) {
                            $syncData[$ids['value_id']] = ['attribute_id' => $ids['attribute_id']];
                        }
                    }
                }
                $variant->options()->sync($syncData);

                if (!empty($variantFiles[$index]['images'])) {
                    $this->handleVariantImages($variant, $variantFiles[$index]['images'], $index);
                }
            }
            
            ProductVariant::where('product_id', $product->id)
                ->whereNotIn('id', $processedVariantIds)
                ->update(['is_active' => 0]);

            $product->updateQuietly(['status' => 'active']);
            Product::clearUiCache();
        });
    }

    /**
     * Process Opening Stock Update & Sync Accounting Voucher
     */
    public function updateOpeningStock(Product $product, array $requestData, AccountingIntegrationService $accIntegration): void
    {
        DB::transaction(function () use ($product, $requestData, $accIntegration) {

            $openingStockDate = !empty($requestData['opening_stock_date'])
                ? Carbon::parse($requestData['opening_stock_date'])->format('Y-m-d')
                : now()->toDateString();

            $formStockIds = [];
            $totalOpeningValuation = 0;
            $primaryBranchId = null;

            foreach ($requestData['stocks'] as $key => $stockData) {
                $quantity = (float) $stockData['quantity'];
                $variantId = $stockData['product_variant_id'] ?? null;
                $branchId = $stockData['branch_id'];
                if (!$primaryBranchId) { $primaryBranchId = $branchId; }

                $unitCost = isset($stockData['cost']) && (float)$stockData['cost'] > 0 
                    ? (float)$stockData['cost'] 
                    : (float)($product->cost ?? 0);

                $totalOpeningValuation += ($quantity * $unitCost);

                $isExisting = str_starts_with($key, 'existing_') && isset($stockData['id']);
                $existingStockId = $isExisting ? $stockData['id'] : null;

                if ($isExisting) {
                    if (!empty($stockData['batch_no'])) {
                        $batchNo = $stockData['batch_no'];
                    } else {
                        $currentStock = BranchStock::find($existingStockId);
                        $batchNo = $currentStock && $currentStock->batch ? $currentStock->batch->batch_no : $this->generateUniqueBatchCode();
                    }
                } else {
                    $batchNo = !empty($stockData['batch_no']) ? $stockData['batch_no'] : $this->generateUniqueBatchCode();
                }

                // 1. Handle Product Batch
                $batchQuery = ProductBatch::where('product_id', $product->id)->where('batch_no', $batchNo);

                if (is_null($variantId)) {
                    $batchQuery->whereNull('product_variant_id');
                } else {
                    $batchQuery->where('product_variant_id', $variantId);
                }

                $batch = $batchQuery->firstOrNew();
                if (!$batch->exists) {
                    $batch->product_id = $product->id;
                    $batch->batch_no = $batchNo;
                    $batch->product_variant_id = $variantId;
                }
                $batch->expiry_date = !empty($stockData['expiry_date']) && $stockData['expiry_date'] !== '0000-00-00'
                    ? Carbon::parse($stockData['expiry_date'])->format('Y-m-d')
                    : null;
                $batch->cost = $unitCost;
                $batch->price = $stockData['price'] ?? $product->price;
                $batch->wholesale_price = $stockData['wholesale_price'] ?? $product->wholesale_price;

                $batch->save();
                $batchId = $batch->id;

                // 2. Handle Product Price
                $customPrice = isset($stockData['price']) ? (float) $stockData['price'] : null;
                $priceData = $product->prepareProductPrices($variantId, $branchId, $batchId, $customPrice, $unitCost);

                if (isset($stockData['wholesale_price'])) {
                    $priceData['wholesale_price'] = (float) $stockData['wholesale_price'];
                }

                ProductPrice::updateOrCreate(
                    [
                        'product_id'         => $product->id,
                        'product_variant_id' => $variantId,
                        'product_batch_id'   => $batchId,
                        'branch_id'          => $branchId,
                    ],
                    $priceData
                );

                // 3. Update or Create Branch Stock
                if ($isExisting) {
                    $branchStock = BranchStock::find($existingStockId);
                    if ($branchStock) {
                        $branchStock->update([
                            'branch_id'          => $branchId,
                            'product_variant_id' => $variantId,
                            'product_batch_id'   => $batchId,
                            'quantity'           => $quantity,
                            'unit_id'            => $product->base_unit_id,
                        ]);
                    }
                } else {
                    $branchStock = BranchStock::updateOrCreate(
                        [
                            'branch_id'          => $branchId,
                            'product_id'         => $product->id,
                            'product_variant_id' => $variantId,
                            'product_batch_id'   => $batchId,
                        ],
                        [
                            'quantity' => $quantity,
                            'unit_id'  => $product->base_unit_id,
                        ]
                    );
                }

                $formStockIds[] = $branchStock->id;

                // 4. Handle IMEI Numbers
                if ($product->has_imei && isset($stockData['imeis']) && is_array($stockData['imeis'])) {
                    $newImeiNumbers = array_filter($stockData['imeis']);
                    $existingImeis = ProductImei::where('product_batch_id', $batchId)->get();
                    
                    foreach ($existingImeis as $existingImei) {
                        if (!in_array($existingImei->imei_number, $newImeiNumbers)) {
                            if ($existingImei->status !== ImeiStatus::AVAILABLE) {
                                throw new Exception("Cannot remove IMEI {$existingImei->imei_number} because it is currently marked as {$existingImei->status->value}.");
                            }
                            $existingImei->delete();
                        }
                    }

                    foreach ($newImeiNumbers as $imeiNumber) {
                        $existingRecord = ProductImei::where('product_batch_id', $batchId)
                                            ->where('imei_number', $imeiNumber)
                                            ->first();

                        if (!$existingRecord) {
                            $imei = ProductImei::create([
                                'product_id'         => $product->id,
                                'product_batch_id'   => $batchId,
                                'branch_id'          => $branchId,
                                'imei_number'        => $imeiNumber,
                                'status'             => ImeiStatus::AVAILABLE,
                                'sourceable_type'    => Product::class,
                                'sourceable_id'      => $product->id,
                            ]);

                            event(new ProductImeiEvent(
                                $imei->id, 
                                $branchId, 
                                \App\Enums\ImeiEventType::OPENING_STOCK,
                                'IMEI registered via opening stock.'
                            ));
                        }
                    }
                }

                // 5. Handle Barcodes
                if (!empty($stockData['master_barcode'])) {
                    $barcodeValue = $stockData['master_barcode'];
                } else {
                    $baseCode = $product->code;
                    if ($variantId) {
                        $variant = ProductVariant::find($variantId);
                        if ($variant) {
                            $baseCode = $variant->code ?? $variant->sku ?? $product->code;
                        }
                    }
                    $barcodeValue = $baseCode . '-' . $batchNo;
                }

                ProductBarcode::updateOrCreate(
                    [
                        'product_id'         => $product->id,
                        'product_variant_id' => $variantId,
                        'product_batch_id'   => $batchId,
                    ],
                    [
                        'barcode' => $barcodeValue,
                        'barcode_type' => $product->barcode_type ?? 'standard',
                        'barcode_symbology'    => $product->barcode_symbology ?? 'C128',
                    ]
                );

                // 6. Record Stock Transaction
                $oldTransactionSum = StockTransaction::where([
                    'branch_id'          => $branchId,
                    'product_id'         => $product->id,
                    'product_variant_id' => $variantId,
                    'product_batch_id'   => $batchId,
                    'referenceable_id'   => $product->id,
                    'referenceable_type' => get_class($product),
                ])->sum(DB::raw("CASE WHEN type = 'in' THEN quantity ELSE -quantity END"));

                $deltaQuantity = $quantity - $oldTransactionSum;

                if ($deltaQuantity != 0) {
                    $transactionType = $deltaQuantity > 0 ? 'in' : 'out';
                    $absoluteQty = abs($deltaQuantity);

                    StockTransaction::create([
                        'branch_id'          => $branchId,
                        'product_id'         => $product->id,
                        'product_variant_id' => $variantId,
                        'product_batch_id'   => $batchId,
                        'type'               => $transactionType,
                        'referenceable_id'   => $product->id,
                        'referenceable_type' => get_class($product),
                        'quantity'           => $absoluteQty,
                        'stock_after'        => $branchStock->quantity,
                        'transaction_date'   => $openingStockDate,
                        'note'               => "Opening stock adjusted. Previous: $oldTransactionSum, New: $quantity. (Delta: $deltaQuantity)",
                    ]);
                }
            }

            // 7. Cleanup Missing Rows
            $removedStocks = BranchStock::where('product_id', $product->id)
                ->whereNotIn('id', $formStockIds)
                ->get();

            foreach ($removedStocks as $oldStock) {
                if ($oldStock->quantity > 0) {
                    StockTransaction::create([
                        'branch_id'          => $oldStock->branch_id,
                        'product_id'         => $oldStock->product_id,
                        'product_variant_id' => $oldStock->product_variant_id,
                        'product_batch_id'   => $oldStock->product_batch_id,
                        'type'               => 'out',
                        'referenceable_id'   => $oldStock->product_id,
                        'referenceable_type' => get_class($product),
                        'quantity'           => $oldStock->quantity,
                        'stock_after'        => 0,
                        'transaction_date'   => $openingStockDate,
                        'note'               => "Opening stock row removed completely. Adjusted to 0.",
                    ]);
                }
                $oldStock->delete();
            }

            // 8. 1-Line Clean Accounting Sync Call
            $accIntegration->syncProductOpeningStock($product, $totalOpeningValuation, $openingStockDate, $primaryBranchId);
        });
    }

    /**
     * Delete product safely with GL transaction guard and voucher reversal
     */
    public function deleteProduct(Product $product, AccountingIntegrationService $accIntegration): void
    {
        if ($accIntegration->hasActiveTransactions($product)) {
            throw new Exception("Cannot delete product '{$product->name}' because it has active transaction records (Sales/Purchases). Please deactivate it instead.");
        }

        DB::transaction(function () use ($product, $accIntegration) {
            $accIntegration->syncProductOpeningStock($product, 0, now()->toDateString());
            $product->delete();
        });
    }

    /**
     * Bulk Delete Products safely
     */
    public function bulkDeleteProducts(array $ids, AccountingIntegrationService $accIntegration): array
    {
        $deletedCount = 0;
        $blockedCount = 0;

        DB::transaction(function () use ($ids, $accIntegration, &$deletedCount, &$blockedCount) {
            $products = Product::whereIn('id', $ids)->get();

            foreach ($products as $product) {
                if ($accIntegration->hasActiveTransactions($product)) {
                    $blockedCount++;
                    continue;
                }

                $accIntegration->syncProductOpeningStock($product, 0, now()->toDateString());
                $product->delete();
                $deletedCount++;
            }
        });

        return [
            'deleted' => $deletedCount,
            'blocked' => $blockedCount,
        ];
    }

    public function processGallery(array $files, Product $product): void
    {
        foreach ($files as $index => $file) {
            $path = $this->processImage($file, 'products/gallery', ['width' => 1000]);

            TenantMedia::create([
                'path'          => $path,
                'disk'          => config('filesystems.default'),
                'type'          => 'image',
                'original_name' => $file->getClientOriginalName(),
                'used'          => true,
                'model_type'    => get_class($product),
                'model_id'      => $product->id,
            ]);

            $product->images()->create([
                'image' => $path,
                'sort_order' => $index + 1,
                'alt_text' => $product->name,
            ]);
        }
    }

    public function handleVariantImages($variant, $files, $index): void
    {
        foreach ($files as $file) {
            $filePath = $this->processImage($file, 'products/variants', ['width' => 1000]);

            TenantMedia::create([
                'path'           => $filePath,
                'disk'           => config('filesystems.default'),
                'type'           => 'image',
                'original_name'  => $file->getClientOriginalName(),
                'used'           => true,
                'model_type'     => get_class($variant),
                'model_id'       => $variant->id,
            ]);

            $variant->images()->create([
                'image_path' => $filePath,
                'sort_order' => $index + 1,
                'alt_text'   => $variant->name,
            ]);
        }
    }

    private function prepareUnitSnapshot($unit, $userVars = []): array
    {
        return [
            'unit_id'      => $unit->id,
            'name'         => $unit->name,
            'short_name'   => $unit->short_name,
            'is_formulaic' => $unit->is_formulaic,
            'formula'      => $unit->formula,
            'operator'     => $unit->operator,
            'operator_val' => $unit->operator_value,
            'precision'    => $unit->precision,
            'base_unit_id' => $unit->base_unit_id,
            'user_vars'    => $userVars,
        ];
    }

    private function getAllParentUnitIds(array $unitIds): array
    {
        $allIds = $unitIds;
        $currentLevelIds = $unitIds;

        while (!empty($currentLevelIds)) {
            $parentIds = Unit::whereIn('id', $currentLevelIds)
                ->whereNotNull('base_unit_id')
                ->pluck('base_unit_id')
                ->toArray();

            $newIds = array_diff($parentIds, $allIds);
            if (empty($newIds)) break;

            $allIds = array_merge($allIds, $newIds);
            $currentLevelIds = $newIds;
        }

        return array_unique($allIds);
    }

    public function generateUniqueBatchCode(): string
    {
        return 'B' . strtoupper(base_convert(time() . rand(10, 99), 10, 36));
    }
}