<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Enums\DrugType;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\UnitFormulaService;
use App\Traits\HasFeatureLimit;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Override;

class Product extends BaseModel implements FeatureLimitInterface, RestorableConflictInterface
{
    use HasFeatureLimit, HasFiles, HasTrash, HasUniqueSlug, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'short_name', 'code', 'sku', 'slug', 'type', 'drug_type',
        'barcode_type', 'barcode_symbology', 'brand_id', 'generic_id',
        'unit_group_id', 'base_unit_id', 'sale_unit_id', 'purchase_unit_id',
        'unit_details', 'cost', 'price', 'wholesale_price', 'tax_type',
        'tax_id', 'manage_stock', 'allow_oversale', 'has_variants',
        'has_imei', 'has_expire_date', 'is_featured', 'has_specification',
        'sale_online', 'has_warranty', 'enable_preorder', 'has_opening_stock',
        'warranty_details', 'short_description', 'description', 'alert_quantity',
        'max_sale_commision', 'product_seo', 'digital_file', 'digital_external_link',
        'video_url', 'thumbnail', 'meta', 'status', 'created_by', 'updated_by',
        'deleted_by', 'hs_code', 'profit_margin',
    ];

    protected $casts = [
        'drug_type'         => DrugType::class,
        'manage_stock'      => 'boolean',
        'allow_oversale'    => 'boolean',
        'has_variants'      => 'boolean',
        'has_imei'          => 'boolean',
        'has_expire_date'   => 'boolean',
        'is_featured'       => 'boolean',
        'has_specification' => 'boolean',
        'sale_online'       => 'boolean',
        'has_warranty'      => 'boolean',
        'enable_preorder'   => 'boolean',
        'warranty_details'  => 'array',
        'unit_details'      => 'array',
        'product_seo'       => 'array',
        'has_opening_stock' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        $clearUiCache = function ($model) {
            self::clearUiCache();
        };

        static::saved($clearUiCache);
        static::deleted($clearUiCache);

        static::restored(function ($product) {
            $totalValuation = BranchStock::where('product_id', $product->id)
                ->get()
                ->sum(function ($stock) use ($product) {
                    return $stock->quantity * ($stock->batch->cost ?? $product->cost ?? 0);
                });

            if ($totalValuation > 0) {
                app(AccountingIntegrationService::class)->syncProductOpeningStock(
                    $product,
                    $totalValuation,
                    now()->toDateString()
                );
            }
            self::clearUiCache();
        });

        static::deleted(function ($product) {
            if (!$product->isForceDeleting()) {
                app(AccountingIntegrationService::class)->syncProductOpeningStock($product, 0, now()->toDateString());
            }
            self::clearUiCache();
        });
    }

    /**
     * Clear all Product UI Caches & update Redis Gatekeeper timestamp
     */
    public static function clearUiCache(): void
    {
        Cache::tags([tenant_tag()])->forget('all_products_full_' . tenant('id'));
        Cache::tags([tenant_tag()])->forget('all_products_for_sync_' . tenant('id'));
        Cache::tags([tenant_tag()])->forget('imei_records_' . tenant('id'));

        // 🚀 Set the latest timestamp for the Redis Gatekeeper (1ms bypass)
        Cache::tags([tenant_tag()])->put('tenant_last_catalog_change_' . tenant('id'), now()->timestamp, now()->addDays(7));
    }

    public static function generateItemCode()
    {
        $lastProduct = Product::whereRaw('LENGTH(code) = 5')
            ->lockForUpdate()
            ->withTrashed()
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = $lastProduct ? intval($lastProduct->code) + 1 : 1;
        if ($nextNumber > 99999) {
            throw new \Exception('Item code limit reached (99999).');
        }

        return str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function prepareProductPrices($variantId = null, $branchId = null, $batchId = null, $customPrice = null, $customCost = null, $customWholesalePrice = null, $unitDetails = null): array
    {
        $basePrice = !is_null($customPrice) ? (float) $customPrice : (float) ($this->price ?? 0);
        $baseCost = !is_null($customCost) ? (float) $customCost : (float) ($this->cost ?? 0);
        $baseWholesalePrice = !is_null($customWholesalePrice) ? (float) $customWholesalePrice : (float) ($this->wholesale_price ?? 0);

        if (is_null($unitDetails)) {
            if (!is_null($variantId)) {
                if (!$this->relationLoaded('variants')) {
                    $this->loadMissing('variants');
                }
                $variant = $this->variants->firstWhere('id', $variantId);
                $unitDetails = $variant ? $variant->unit_details : $this->unit_details;
            } else {
                $unitDetails = $this->unit_details;
            }
        }

        $unitFormulaService = app(UnitFormulaService::class);
        $saleRatio = $unitFormulaService->getRatioFromJSON($unitDetails, $this->sale_unit_id);
        $purchaseRatio = $unitFormulaService->getRatioFromJSON($unitDetails, $this->purchase_unit_id);

        $otherPrices = [
            'sale_unit' => [
                'unit_id' => $this->sale_unit_id,
                'price'   => $basePrice * $saleRatio,
                'cost'    => $baseCost * $saleRatio,
            ],
            'purchase_unit' => [
                'unit_id' => $this->purchase_unit_id,
                'price'   => $basePrice * $purchaseRatio,
                'cost'    => $baseCost * $purchaseRatio,
            ],
        ];

        return [
            'product_id'         => $this->id,
            'product_variant_id' => $variantId,
            'product_batch_id'   => $batchId,
            'branch_id'          => $branchId,
            'unit_id'            => $this->base_unit_id,
            'price'              => $basePrice,
            'cost'               => $baseCost,
            'wholesale_price'    => $baseWholesalePrice,
            'is_current'         => true,
            'other_prices'       => $otherPrices,
        ];
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('code', $this->code)
            ->where('slug', $this->slug)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getThumbUrlAttribute()
    {
        if (!$this->thumbnail) {
            return file_url(null);
        }
        $thumbPath = $this->getThumbnailPath($this->thumbnail);
        return $this->getFileUrl($thumbPath, 's3');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category_product', 'category_id', 'product_id');
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class, 'product_id');
    }

    public function unitGroup()
    {
        return $this->belongsTo(UnitGroup::class, 'unit_group_id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function saleUnit()
    {
        return $this->belongsTo(Unit::class, 'sale_unit_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->where('is_active', true);
    }

    public function allVariants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function comboItems()
    {
        return $this->hasMany(ComboProductItem::class, 'combo_product_id')->with(['product', 'variant']);
    }

    public function partOfCombos()
    {
        return $this->hasMany(ComboProductItem::class, 'product_id');
    }

    public function attributes()
    {
        return $this->hasManyThrough(
            ProductVariantOption::class,
            ProductVariant::class,
            'product_id',
            'variant_id'
        )->distinct('attribute_id');
    }

    public function dropshippingDetail()
    {
        return $this->hasOne(ProductDropshippingDetail::class, 'product_id');
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_id');
    }

    public function currentPrice()
    {
        return $this->hasOne(ProductPrice::class, 'product_id')->where('is_current', true)->latest('created_at');
    }

    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class, 'product_id');
    }

    public function imeis()
    {
        return $this->hasMany(ProductImei::class, 'product_id');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class, 'product_id');
    }

    public function generic()
    {
        return $this->belongsTo(Generic::class, 'generic_id');
    }

    public function branch_stocks()
    {
        return $this->hasMany(BranchStock::class, 'product_id')->with(['branch' => function ($query) {
            $query->select('id', 'name');
        }]);
    }

    public function aggregated_branch_stocks()
    {
        return $this->hasMany(BranchStock::class, 'product_id')
            ->select('product_id', 'branch_id', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('product_id', 'branch_id')
            ->with(['branch' => function ($query) {
                $query->select('id', 'name');
            }]);
    }

    #[Override]
    public function getFeatureLimitKey(): string
    {
        return 'products_limit';
    }
}