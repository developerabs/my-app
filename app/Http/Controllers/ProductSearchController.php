<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductSearchController extends Controller
{
    /**
     * AJAX Product Search for Combo Items Builder (Case-Insensitive for PostgreSQL)
     */
    public function searchForCombo(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $lowerQuery = strtolower($query);

        $products = Product::where('status', 'active')
            ->whereIn('type', ['physical', 'service', 'dropship'])
            ->where(function ($q) use ($lowerQuery) {
                $q->where(DB::raw('LOWER(name)'), 'like', "%{$lowerQuery}%")
                    ->orWhere(DB::raw('LOWER(sku)'), 'like', "%{$lowerQuery}%")
                    ->orWhere(DB::raw('LOWER(code)'), 'like', "%{$lowerQuery}%");
            })
            ->with(['variants' => function ($v) {
                $v->where('is_active', true);
            }])
            ->limit(20)
            ->get();

        $results = [];

        foreach ($products as $prod) {
            $prodUnitDetails = is_array($prod->unit_details)
                ? $prod->unit_details
                : (json_decode($prod->unit_details, true) ?? []);

            if ($prod->has_variants && $prod->variants->count() > 0) {
                foreach ($prod->variants as $variant) {
                    $variantUnitDetails = !empty($variant->unit_details)
                        ? (is_array($variant->unit_details) ? $variant->unit_details : json_decode($variant->unit_details, true))
                        : $prodUnitDetails;

                    $results[] = [
                        'id'                 => $prod->id . '|' . $variant->id,
                        'product_id'         => $prod->id,
                        'product_variant_id' => $variant->id,
                        'name'               => $prod->name . ' (' . $variant->name . ')',
                        'sku'                => $variant->sku,
                        'cost'               => (float) $variant->cost,
                        'price'              => (float) $variant->price,
                        'sale_unit_id'       => $prod->sale_unit_id ?? $prod->base_unit_id,
                        'base_unit_id'       => $prod->base_unit_id,
                        'unit_details'       => $variantUnitDetails,
                    ];
                }
            } else {
                $results[] = [
                    'id'                 => $prod->id . '|null',
                    'product_id'         => $prod->id,
                    'product_variant_id' => null,
                    'name'               => $prod->name,
                    'sku'                => $prod->sku,
                    'cost'               => (float) $prod->cost,
                    'price'              => (float) $prod->price,
                    'sale_unit_id'       => $prod->sale_unit_id ?? $prod->base_unit_id,
                    'base_unit_id'       => $prod->base_unit_id,
                    'unit_details'       => $prodUnitDetails,
                ];
            }
        }

        return response()->json($results);
    }

    /**
     * Sync ALL products, multi-branch stocks, and adaptive barcodes with front-end Dexie mapping.
     */
    public function getAllProducts(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $cacheKey = "all_products_for_sync_" . tenant('id');

        if ($request->boolean('force')) {
            Cache::tags([tenant_tag()])->forget($cacheKey);
            Cache::tags([tenant_tag()])->forget('all_taxes_' . tenant('id'));
            Cache::tags([tenant_tag()])->forget('imei_records_' . tenant('id'));
        }

        $lastSyncTime = $request->input('last_sync_time');
        $lastSyncCarbon = ($lastSyncTime && $lastSyncTime !== 'null' && $lastSyncTime !== 'undefined')
            ? Carbon::createFromTimestampMs($lastSyncTime)
            : null;

        // 🚀 REDIS GATEKEEPER: Zero Database Load if no modifications happened since client last sync
        if ($lastSyncCarbon) {
            $lastTenantChangeTime = Cache::tags([tenant_tag()])->get('tenant_last_catalog_change_' . tenant('id'));

            // 1ms Bypass if no updates occurred
            if ($lastTenantChangeTime && $lastSyncCarbon->timestamp >= (int) $lastTenantChangeTime) {
                return response()->json([
                    'success'      => true,
                    'is_delta'     => true,
                    'data'         => [],
                    'deleted_uids' => [],
                    'taxes'        => Tax::select('id', 'name', 'rate')->get(),
                    'server_time'  => Carbon::now()->getPreciseTimestamp(3)
                ]);
            }

            $deltaResult = $this->getGlobalDeltaUpdates($lastSyncCarbon);

            return response()->json([
                'success'      => true,
                'is_delta'     => true,
                'data'         => $deltaResult['data'],
                'deleted_uids' => $deltaResult['deleted_uids'],
                'taxes'        => Tax::select('id', 'name', 'rate')->get(),
                'server_time'  => Carbon::now()->getPreciseTimestamp(3)
            ]);
        }

        // 2. Full Sync Mode (Cached Full Enterprise Payload)
        $fullPayload = Cache::tags([tenant_tag()])->remember($cacheKey, now()->addHours(6), function () {
            return $this->compileGlobalEnterprisePayload();
        });

        $chunkSize = 10000;
        $totalRecords = count($fullPayload);
        $totalChunks = (int) max(1, ceil($totalRecords / $chunkSize));
        $currentChunkIndex = (int) $request->input('chunk_index', 0);
        $offset = $currentChunkIndex * $chunkSize;

        $chunkData = array_slice($fullPayload, $offset, $chunkSize);

        $taxes = ($currentChunkIndex === 0)
            ? Cache::tags([tenant_tag()])->remember('all_taxes_' . tenant('id'), 3600, fn() => Tax::select('id', 'name', 'rate')->get())
            : [];

        $imeiRecords = ($currentChunkIndex === 0)
            ? Cache::tags([tenant_tag()])->remember('imei_records_' . tenant('id'), 3600, function() {
                return ProductImei::with(['branch:id,name', 'batch:id,batch_no'])
                    ->get()
                    ->map(function ($imei) {
                        return [
                            'id'                 => $imei->id,
                            'product_id'         => $imei->product_id,
                            'product_variant_id' => $imei->product_variant_id,
                            'product_batch_id'   => $imei->product_batch_id,
                            'branch_id'          => $imei->branch_id,
                            'imei'               => $imei->imei_number,
                            'status'             => $imei->status,
                            'branch_name'        => $imei->branch->name ?? 'N/A',
                            'batch_no'           => $imei->batch->batch_no ?? 'N/A',
                        ];
                    });
            })
            : [];

        return response()->json([
            'success'       => true,
            'is_delta'      => false,
            'total_records' => $totalRecords,
            'current_chunk' => $currentChunkIndex,
            'total_chunks'  => $totalChunks,
            'data'          => $chunkData,
            'taxes'         => $taxes,
            'imei_records'  => $imeiRecords,
            'server_time'   => Carbon::now()->getPreciseTimestamp(3)
        ]);
    }

    /**
     * Compiles full relational database entities into unified client-side flat schemas.
     */
    private function compileGlobalEnterprisePayload()
    {
        $records = DB::table('products')
            ->where('products.status', '=', 'active')
            ->whereNull('products.deleted_at')
            ->leftJoin('product_variants', function ($join) {
                $join->on('products.id', '=', 'product_variants.product_id')
                    ->where('product_variants.is_active', '=', 1);
            })
            ->leftJoin('branch_stocks', function ($join) {
                $join->on('products.id', '=', 'branch_stocks.product_id')
                    ->on(function ($q) {
                        $q->on('branch_stocks.product_variant_id', '=', 'product_variants.id')
                            ->orWhere(function ($subQ) {
                                $subQ->whereNull('branch_stocks.product_variant_id')
                                    ->whereNull('product_variants.id');
                            });
                    });
            })
            ->leftJoin('product_batches', 'branch_stocks.product_batch_id', '=', 'product_batches.id')
            ->leftJoin('generics', 'products.generic_id', '=', 'generics.id')
            ->leftJoin('product_prices', function ($join) {
                $join->on('branch_stocks.product_id', '=', 'product_prices.product_id')
                    ->on('branch_stocks.branch_id', '=', 'product_prices.branch_id')
                    ->where('product_prices.is_current', '=', 1);
            })
            ->select([
                'products.id as p_id',
                'products.name as product_name',
                'products.short_name as product_short_name',
                'products.code as product_code',
                'products.sku as product_sku',
                'products.type as product_type',
                'products.barcode_type as product_barcode_type',
                'products.drug_type',
                'products.unit_group_id',
                'products.base_unit_id',
                'products.unit_details as raw_product_unit_details',
                'products.manage_stock',
                'products.has_variants',
                'products.has_imei',
                'products.has_expire_date',
                'products.tax_type',
                'products.tax_id',
                'products.sale_unit_id',
                'products.purchase_unit_id',
                'products.cost as base_cost',
                'products.price as base_price',
                'products.wholesale_price as base_wholesale_price',
                'products.status',
                'generics.name as generic_name',
                'product_variants.id as v_id',
                'product_variants.name as variant_name',
                'product_variants.sku as variant_sku',
                'product_variants.code as variant_code',
                'product_variants.unit_details as raw_variant_unit_details',
                'product_variants.cost as variant_cost',
                'product_variants.price as variant_price',
                'product_variants.wholesale_price as variant_wholesale_price',
                'branch_stocks.branch_id',
                'branch_stocks.product_batch_id',
                'branch_stocks.quantity as stock_qty',
                'product_batches.batch_no',
                'product_batches.expiry_date',
                'product_batches.cost as batch_cost',
                'product_batches.price as batch_price',
                'product_prices.cost as branch_cost',
                'product_prices.price as branch_price',
                'product_prices.wholesale_price as branch_wholesale_price',
            ])
            ->get();

        $barcodesCollection = DB::table('product_barcodes')
            ->select('product_id', 'product_variant_id', 'product_batch_id', 'barcode', 'sku', 'code')
            ->get()
            ->groupBy(function ($item) {
                return $item->product_variant_id ? "v-{$item->product_variant_id}" : "p-{$item->product_id}";
            });

        $groupedRecords = $records->groupBy(function ($item) {
            return $item->v_id ? "v-{$item->v_id}" : "p-{$item->p_id}";
        });

        return $this->transformRawQueryToGlobalSchema($groupedRecords, $barcodesCollection);
    }

    /**
     * Perfect Delta Sync with Complete Product Snapshot Consistency (Postgres Safe)
     */
    private function getGlobalDeltaUpdates(Carbon $lastSyncCarbon): array
    {
        $syncThreshold = $lastSyncCarbon->copy()->subSeconds(5);

        // 1. Identify Deleted or Deactivated Product IDs
        $deletedProducts = DB::table('products')
            ->where(function ($q) use ($syncThreshold) {
                $q->where('updated_at', '>', $syncThreshold)
                    ->orWhere('deleted_at', '>', $syncThreshold);
            })
            ->where(function ($q) {
                $q->where('status', '!=', 'active')
                    ->orWhereNotNull('deleted_at');
            })
            ->pluck('id');

        $deletedProductUids = $deletedProducts->map(fn($id) => "p-{$id}")->toArray();

        // 2. Identify Deactivated Variant IDs (Clean Postgres Query without deleted_at)
        $deletedVariants = DB::table('product_variants')
            ->where('updated_at', '>', $syncThreshold)
            ->where('is_active', 0)
            ->pluck('id');

        $deletedVariantUids = $deletedVariants->map(fn($id) => "v-{$id}")->toArray();
        $allDeletedUids = array_values(array_unique(array_merge($deletedProductUids, $deletedVariantUids)));

        // 3. Identify Modified Product IDs (Stock / Batch / Variant modifications)
        $modifiedProductIds = DB::table('products')
            ->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('branch_stocks', 'products.id', '=', 'branch_stocks.product_id')
            ->leftJoin('product_batches', 'products.id', '=', 'product_batches.product_id')
            ->where('products.status', '=', 'active')
            ->whereNull('products.deleted_at')
            ->where(function ($query) use ($syncThreshold) {
                $query->where('products.updated_at', '>', $syncThreshold)
                    ->orWhere('product_variants.updated_at', '>', $syncThreshold)
                    ->orWhere('branch_stocks.updated_at', '>', $syncThreshold)
                    ->orWhere('product_batches.updated_at', '>', $syncThreshold);
            })
            ->distinct()
            ->pluck('products.id')
            ->toArray();

        if (empty($modifiedProductIds)) {
            return [
                'data'         => [],
                'deleted_uids' => $allDeletedUids,
            ];
        }

        // 4. Fetch COMPLETE Snapshot for all modified products (Guarantees all batches are present)
        $records = DB::table('products')
            ->whereIn('products.id', $modifiedProductIds)
            ->where('products.status', '=', 'active')
            ->whereNull('products.deleted_at')
            ->leftJoin('product_variants', function ($join) {
                $join->on('products.id', '=', 'product_variants.product_id')
                    ->where('product_variants.is_active', '=', 1);
            })
            ->leftJoin('branch_stocks', function ($join) {
                $join->on('products.id', '=', 'branch_stocks.product_id')
                    ->on(function ($q) {
                        $q->on('branch_stocks.product_variant_id', '=', 'product_variants.id')
                            ->orWhere(function ($subQ) {
                                $subQ->whereNull('branch_stocks.product_variant_id')
                                    ->whereNull('product_variants.id');
                            });
                    });
            })
            ->leftJoin('product_batches', 'branch_stocks.product_batch_id', '=', 'product_batches.id')
            ->leftJoin('generics', 'products.generic_id', '=', 'generics.id')
            ->leftJoin('product_prices', function ($join) {
                $join->on('branch_stocks.product_id', '=', 'product_prices.product_id')
                    ->on('branch_stocks.branch_id', '=', 'product_prices.branch_id')
                    ->where('product_prices.is_current', '=', 1);
            })
            ->select([
                'products.id as p_id',
                'products.name as product_name',
                'products.short_name as product_short_name',
                'products.code as product_code',
                'products.sku as product_sku',
                'products.type as product_type',
                'products.barcode_type as product_barcode_type',
                'products.drug_type',
                'products.unit_group_id',
                'products.base_unit_id',
                'products.unit_details as raw_product_unit_details',
                'products.manage_stock',
                'products.has_variants',
                'products.has_imei',
                'products.has_expire_date',
                'products.tax_type',
                'products.tax_id',
                'products.sale_unit_id',
                'products.purchase_unit_id',
                'products.cost as base_cost',
                'products.price as base_price',
                'products.wholesale_price as base_wholesale_price',
                'products.status',
                'generics.name as generic_name',
                'product_variants.id as v_id',
                'product_variants.name as variant_name',
                'product_variants.sku as variant_sku',
                'product_variants.code as variant_code',
                'product_variants.unit_details as raw_variant_unit_details',
                'product_variants.cost as variant_cost',
                'product_variants.price as variant_price',
                'product_variants.wholesale_price as variant_wholesale_price',
                'branch_stocks.branch_id',
                'branch_stocks.product_batch_id',
                'branch_stocks.quantity as stock_qty',
                'product_batches.batch_no',
                'product_batches.expiry_date',
                'product_batches.cost as batch_cost',
                'product_batches.price as batch_price',
                'product_prices.cost as branch_cost',
                'product_prices.price as branch_price',
                'product_prices.wholesale_price as branch_wholesale_price',
            ])
            ->get();

        $groupedRecords = $records->groupBy(function ($item) {
            return $item->v_id ? "v-{$item->v_id}" : "p-{$item->p_id}";
        });

        $barcodesCollection = DB::table('product_barcodes')
            ->whereIn('product_id', $modifiedProductIds)
            ->select('product_id', 'product_variant_id', 'product_batch_id', 'barcode', 'sku', 'code')
            ->get()
            ->groupBy(function ($item) {
                return $item->product_variant_id ? "v-{$item->product_variant_id}" : "p-{$item->product_id}";
            });

        return [
            'data'         => $this->transformRawQueryToGlobalSchema($groupedRecords, $barcodesCollection),
            'deleted_uids' => $allDeletedUids,
        ];
    }

    /**
     * Transforms relational database entities into unified client-side Dexie flat schemas.
     */
    private function transformRawQueryToGlobalSchema($groupedRecords, $barcodesCollection)
    {
        $formattedList = [];

        foreach ($groupedRecords as $uid => $rows) {
            $row = $rows->first();

            $rawUnitDetails = $row->raw_variant_unit_details ?: $row->raw_product_unit_details;
            $unitDetailsParsed = $rawUnitDetails ? json_decode($rawUnitDetails, true) : null;

            $variantProfile = [
                'uid'                  => $uid,
                'product_id'           => $row->p_id,
                'product_variant_id'   => $row->v_id,
                'product_name'         => $row->product_name,
                'variant_name'         => $row->v_id ? $row->variant_name : null,
                'product_code'         => $row->product_code,
                'variant_code'         => $row->v_id ? $row->variant_code : null,
                'cost'                 => (float) ($row->variant_cost ?: $row->base_cost),
                'price'                => (float) ($row->variant_price ?: $row->base_price),
                'wholesale_price'      => (float) ($row->variant_wholesale_price ?: $row->base_wholesale_price),
                'type'                 => $row->product_type,
                'manage_stock'         => (bool) $row->manage_stock,
                'unit_details'         => $unitDetailsParsed,
                'purchase_unit_id'     => $row->purchase_unit_id,
                'sale_unit_id'         => $row->sale_unit_id,
                'has_imei'             => (bool) $row->has_imei,
                'has_expire_date'      => (bool) $row->has_expire_date,
                'tax_method'           => $row->tax_type,
                'tax_id'               => $row->tax_id,
                'generic_name'         => $row->generic_name,
                'drug_type'            => $row->drug_type,
                'product_barcode_type' => $row->product_barcode_type,
            ];

            $stocks = [];
            $seenStocks = [];
            foreach ($rows as $r) {
                if ($r->branch_id !== null) {
                    $stockKey = $r->branch_id . '-' . $r->product_batch_id;
                    if (!isset($seenStocks[$stockKey])) {
                        $stocks[] = [
                            'branch_id'        => $r->branch_id,
                            'product_batch_id' => $r->product_batch_id,
                            'batch_no'         => $r->batch_no ?: 'DEFAULT',
                            'quantity'         => (float) $r->stock_qty,
                            'cost'             => (float) ($r->batch_cost ?: ($r->branch_cost ?: $r->variant_cost)),
                            'price'            => (float) ($r->batch_price ?: ($r->branch_price ?: $r->variant_price)),
                        ];
                        $seenStocks[$stockKey] = true;
                    }
                }
            }

            $localBarcodes = [];
            if (isset($barcodesCollection[$uid])) {
                $seenBarcodes = [];
                foreach ($barcodesCollection[$uid] as $bRow) {
                    $barcodesToParse = array_filter(array_unique([$bRow->barcode, $bRow->sku, $bRow->code]));
                    foreach ($barcodesToParse as $code) {
                        if (!isset($seenBarcodes[$code])) {
                            $localBarcodes[] = ['barcode' => $code, 'uid' => $uid, 'product_batch_id' => $bRow->product_batch_id];
                            $seenBarcodes[$code] = true;
                        }
                    }
                }
            }

            $formattedList[] = [
                'variant'  => $variantProfile,
                'stocks'   => $stocks,
                'barcodes' => $localBarcodes,
            ];
        }

        return $formattedList;
    }
}