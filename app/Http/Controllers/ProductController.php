<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use App\Enums\DrugType;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Attribute;
use App\Models\BranchStock;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\Generic;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\UnitGroup;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(ProductDataTable $dataTable)
    {
        return $dataTable->render('backend.products.index');
    }

    public function create()
    {
        $data = $this->getFormData();
        $modelName = strtolower(class_basename(Product::class));
        $cacheKey = "custom_fields_{$modelName}_" . tenant('id');
        $customFields = Cache::tags([tenant_tag()])->remember($cacheKey, 86400, function () {
            return \App\Models\CustomField::where('model_type', 'App\Models\Product')
                ->where('is_active', 1)
                ->get();
        });
        $data['custom_fields'] = $customFields;

        return view('backend.products.create', $data);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->createProduct(
                $request->validated(),
                $request->all(),
                $request->file('thumbnail'),
                $request->file('gallery', [])
            );

            if ($request->has_variants) {
                $redirectUrl = route('products.variants.manage', $product->id);
            } elseif ($product->has_opening_stock) {
                $redirectUrl = route('products.openingStock.manage', $product->id);
            } else {
                $redirectUrl = route('products.index');
            }

            if ($request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Product created successfully',
                    'redirect' => $redirectUrl,
                ], 201);
            }

            return redirect()->to($redirectUrl);
        } catch (Exception $e) {
            Log::error('Product Store Failed: ' . $e->getMessage(), ['request' => $request->except(['thumbnail', 'gallery', '_token'])]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Error creating product: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $data = $this->getFormData();
        $modelName = strtolower(class_basename(Product::class));
        $cacheKey = "custom_fields_{$modelName}_" . tenant('id');
        $customFields = Cache::tags([tenant_tag()])->remember($cacheKey, 86400, function () {
            return \App\Models\CustomField::where('model_type', 'App\Models\Product')
                ->where('is_active', 1)
                ->get();
        });
        $data['custom_fields'] = $customFields;
        
        // Eager load comboItems & relations for clean edit view
        $product->load(['specifications', 'categories', 'images', 'dropshippingDetail', 'comboItems.product', 'comboItems.variant']);
        $product['selected_categories'] = $product->categories->pluck('id')->toArray();
        $data['product'] = $product;

        return view('backend.products.edit', $data);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $updatedProduct = $this->productService->updateProduct(
                $product,
                $request->validated(),
                $request->all(),
                $request->file('thumbnail'),
                $request->file('gallery', [])
            );

            if ($request->has_variants) {
                $redirectUrl = route('products.variants.manage', $updatedProduct->id);
            } elseif ($updatedProduct->has_opening_stock) {
                $redirectUrl = route('products.openingStock.manage', $updatedProduct->id);
            } else {
                $redirectUrl = route('products.index');
            }

            if ($request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'message'  => __('file.message.product_updated_success'),
                    'redirect' => $redirectUrl,
                ], 200);
            }

            return redirect()->to($redirectUrl)->with('success', __('file.message.product_updated_success'));
        } catch (Exception $e) {
            Log::error('Product Update Failed: ' . $e->getMessage(), ['product_id' => $product->id]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Error updating product: ' . $e->getMessage());
        }
    }

    public function manageVariants(Product $product)
    {
        $product->load(['variants.images', 'variants.options.attribute']);

        $activeVariants = $product->variants->filter(function ($v) {
            return (bool) $v->is_active;
        });

        $existingVariants = $activeVariants->map(function ($v) {
            return [
                'id'              => $v->id,
                'name'            => $v->name,
                'sku'             => $v->sku,
                'price'           => $v->price,
                'cost'            => $v->cost,
                'code'            => $v->code,
                'wholesale_price' => $v->wholesale_price,
                'unit_details'    => $v->unit_details,
                'images'          => $v->images->map(function ($img) {
                    return ['id' => $img->id, 'image_path' => $img->image_path_url];
                })
            ];
        })->values();

        $savedAttributes = [];
        foreach ($activeVariants as $variant) {
            foreach ($variant->options as $option) {
                if ($option->attribute) {
                    $attrName = $option->attribute->name;
                    $savedAttributes[$attrName]['name'] = $attrName;
                    $savedAttributes[$attrName]['values'][] = $option->value;
                }
            }
        }

        foreach ($savedAttributes as &$sa) {
            $sa['values'] = array_unique($sa['values']);
        }

        $allAttributes = Attribute::with('values')->where('is_active', true)->get();

        return view('backend.products.manage_variants', [
            'product'          => $product,
            'allAttributes'    => $allAttributes,
            'savedAttributes'  => array_values($savedAttributes),
            'existingVariants' => $existingVariants
        ]);
    }

    public function updateVariants(Request $request, Product $product)
    {
        try {
            $attributes = $request->input('attributes', []);
            $variants = $request->input('variants', []);
            $files = $request->allFiles();

            $this->productService->updateVariants($product, $attributes, $variants, $files['variants'] ?? []);

            if ($product->has_opening_stock) {
                $activeVariantsCount = ProductVariant::where('product_id', $product->id)->where('is_active', 1)->count();
                $stockedVariantsCount = BranchStock::where('product_id', $product->id)->whereNotNull('product_variant_id')->distinct('product_variant_id')->count();
                $hasGeneralStock = BranchStock::where('product_id', $product->id)->exists();

                if ($activeVariantsCount > $stockedVariantsCount || !$hasGeneralStock) {
                    return redirect()->route('products.openingStock.manage', $product->id)
                        ->with('success', __('file.message.variant_updated_successfully') . ' ' . __('Please update opening stock for new variants.'));
                }
            }

            return redirect()->route('products.index')->with('success', __('file.message.variant_updated_successfully'));
        } catch (Exception $e) {
            Log::error('Update Variants Failed: ' . $e->getMessage(), ['product_id' => $product->id]);
            return redirect()->back()->with('error', 'Variant update failed: ' . $e->getMessage());
        }
    }

    public function show(Product $product)
    {
        $product->load([
            'specifications',
            'categories',
            'images',
            'variants.images',
            'variants.options.attribute',
            'variants.prices',
            'variants.barcodes',
            'variants.batches',
            'brand',
            'generic',
            'dropshippingDetail',
            'comboItems.product',
            'comboItems.variant',
            'comboItems.unit',
            'prices',
            'barcodes',
            'branch_stocks',
            'imeis.branch',
            'imeis.batch',
            'batches' => function ($query) {
                $query->whereNull('product_variant_id');
            },
        ]);

        $unitDetails = is_array($product->unit_details)
            ? $product->unit_details
            : json_decode($product->unit_details, true) ?? [];

        $product->formatted_stock = format_stock_with_unit($product->total_stock, $unitDetails);
        $product->baseStock = format_base_unit_stock($product->total_stock, $unitDetails, $product->base_unit_id);

        $product->images->map(function ($image) {
            $image->full_url = $image->image_url;
            return $image;
        });

        if ($product->aggregated_branch_stocks) {
            $product->aggregated_branch_stocks->map(function ($stock) use ($unitDetails, $product) {
                $stock->formatted_stock = format_stock_with_unit($stock->quantity, $unitDetails);
                $stock->baseStock = format_base_unit_stock($stock->quantity, $unitDetails, $product->base_unit_id);
                $stock->branch_name = $stock->branch->name ?? 'N/A';
                return $stock;
            });
        }

        $product->allVariants->map(function ($variant) use ($unitDetails, $product) {
            $variantStock = $variant->stock ?? $variant->total_stock ?? 0;
            $variant->formatted_stock = format_stock_with_unit($variantStock, $unitDetails);
            $variant->baseStock = format_base_unit_stock($variantStock, $unitDetails, $product->base_unit_id);

            $variant->images->map(function ($vImage) {
                $vImage->full_url = $vImage->image_path_url;
                return $vImage;
            });

            if ($variant->batches) {
                $variant->batches->map(function ($vBatch) use ($unitDetails, $product) {
                    $vBatchStock = $vBatch->quantity ?? 0;
                    $vBatch->formatted_stock = format_stock_with_unit($vBatchStock, $unitDetails);
                    $vBatch->baseStock = format_base_unit_stock($vBatchStock, $unitDetails, $product->base_unit_id);
                    return $vBatch;
                });
            }

            return $variant;
        });

        if ($product->batches) {
            $product->batches->map(function ($batch) use ($unitDetails, $product) {
                $batchStock = $batch->quantity ?? 0;
                $batch->formatted_stock = format_stock_with_unit($batchStock, $unitDetails);
                $batch->baseStock = format_base_unit_stock($batchStock, $unitDetails, $product->base_unit_id);
                return $batch;
            });
        }

        $product['thumb_url'] = $product->thumbnail_url;

        return response()->json($product);
    }

    public function openingStockManage(Product $product)
    {
        $product->load(['variants' => function ($query) {
            $query->where('is_active', 1);
        }]);
        $branches = Cache::tags([tenant_tag()])->remember('branches_list_' . tenant('id'), 3600, function () {
            return \App\Models\Branch::select('id', 'name')->get();
        });
        $existingStocks = BranchStock::where('product_id', $product->id)
            ->with(['batch', 'branch', 'variant', 'prices'])
            ->get();

        return view('backend.products.manage_opening_stock', compact('product', 'branches', 'existingStocks'));
    }

    public function openingStockUpdate(
        Request $request, 
        Product $product, 
        AccountingIntegrationService $accIntegration
    ) {
        try {
            $this->productService->updateOpeningStock($product, $request->all(), $accIntegration);

            return redirect()->route('products.index')->with('success', 'Opening stock and inventory journal vouchers synchronized successfully.');
        } catch (Exception $e) {
            Log::error('Opening Stock Update Failed: ' . $e->getMessage(), ['product_id' => $product->id]);
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product, AccountingIntegrationService $accIntegration)
    {
        try {
            $this->productService->deleteProduct($product, $accIntegration);

            return response()->json(['success' => true, 'message' => 'Product deleted successfully.']);
        } catch (Exception $e) {
            Log::error("Product Deletion Failed: " . $e->getMessage(), ['product_id' => $product->id]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function bulkDelete(Request $request, AccountingIntegrationService $accIntegration)
    {
        $ids = $request->ids;

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No products selected for deletion.'], 400);
        }

        try {
            $result = $this->productService->bulkDeleteProducts($ids, $accIntegration);

            $deletedCount = $result['deleted'];
            $blockedCount = $result['blocked'];

            if ($blockedCount > 0) {
                return response()->json([
                    'success' => true, 
                    'message' => "{$deletedCount} products deleted. {$blockedCount} products skipped because they have active sales/purchase transactions."
                ]);
            }

            return response()->json(['success' => true, 'message' => "{$deletedCount} products deleted successfully."]);
        } catch (Exception $e) {
            Log::error("Bulk Product Deletion Failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error during bulk deletion: ' . $e->getMessage()], 500);
        }
    }

    private function getFormData(): array
    {
        $typeId = Cache::tags([tenant_tag()])->remember('type_id_product_' . tenant('id'), 3600, function () {
            return CategoryType::where('name', 'product')->value('id');
        });

        return [
            'typeId'     => $typeId,
            'taxes'      => Cache::tags([tenant_tag()])->remember('taxes_active_' . tenant('id'), 3600, fn() => Tax::active()->get()),
            'unitGroups' => Cache::tags([tenant_tag()])->remember('unitGroups_' . tenant('id'), 3600, function () {
                return UnitGroup::withCount('units')->get();
            }),
            'brands'     => Cache::tags([tenant_tag()])->remember('brands_' . tenant('id'), 3600, fn() => Brand::select('id', 'name')->get()),
            'drug_types' => DrugType::cases(),
            'generics'   => Cache::tags([tenant_tag()])->remember('generics_' . tenant('id'), 3600, fn() => Generic::active()->select('id', 'name')->get()),
            'categories' => Cache::tags([tenant_tag()])->remember('product_categories_' . tenant('id'), 3600, function () use ($typeId) {
                return Category::where('category_type_id', $typeId)
                    ->whereNull('parent_id')
                    ->with('allChildren')
                    ->select('id', 'name', 'parent_id')
                    ->orderBy('sort_order', 'asc')
                    ->get();
            }),
        ];
    }
}