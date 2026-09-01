<?php

namespace App\Services\Accounting;

use App\Enums\ImeiEventType;
use App\Enums\ImeiStatus;
use App\Enums\JournalVoucherStatus;
use App\Events\ProductImeiEvent;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductImei;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\StockTransaction;
use App\Models\SupplierPayment;
use App\Services\CurrencyConversionService;
use App\Services\UnitFormulaService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    use HasFiles;

    public function __construct(
        protected JournalService $journalService,
        protected PostingService $postingService,
        protected CurrencyConversionService $currencyService,
        protected UnitFormulaService $unitFormulaService,
        protected AccountingIntegrationService $accIntegration,
        protected SupplierPaymentService $supplierPayment
    ) {}

    /**
     * Create Purchase Invoice, Process Line Items & Post Double-Entry Accounting Voucher
     */
    public function createPurchase(array $data, ?UploadedFile $documentFile = null): Purchase
    {
        return DB::transaction(function () use ($data, $documentFile) {

            $purchaseDate = Carbon::parse($data['purchase_date'])->format('Y-m-d');
            $dueDate = ! empty($data['due_date']) ? Carbon::parse($data['due_date'])->format('Y-m-d') : null;

            // 1. Resolve Exchange Rate
            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            // 2. Upload Document
            $documentPath = $documentFile ? $this->uploadUploadedFile($documentFile, 'purchases', 's3') : null;
            $purchaseNo = $this->generatePurchaseNo(Carbon::parse($purchaseDate));

            // 3. Process Calculation & IAS 2 Landed Cost Distribution
            $shippingCost = round((float) ($data['shipping_cost'] ?? 0), 2);
            $otherExpenses = round((float) ($data['other_expenses'] ?? 0), 2);
            $totalOverheadExpenses = $shippingCost + $otherExpenses;

            $rawItemsList = $data['products'] ?? ($data['items'] ?? []);

            $rawSubtotal = 0.0;
            $totalQty = 0.0;

            foreach ($rawItemsList as $item) {
                $qty = (float) $item['quantity'];
                $cost = (float) ($item['price'] ?? ($item['unit_cost'] ?? 0));
                $rawSubtotal += ($qty * $cost);
                $totalQty += $qty;
            }

            if ($rawSubtotal <= 0) {
                throw new Exception('Purchase total amount must be greater than zero.');
            }

            $subtotalAmount = 0.0;
            $orderTaxRate = (float) ($data['order_tax_rate'] ?? 0.0);
            $processedItems = [];

            foreach ($rawItemsList as $uidKey => $item) {
                $productId = $item['product_id'];
                $product = Product::findOrFail($productId);

                $variantId = $item['product_variant_id'] ?? null;
                if (! $variantId && is_string($uidKey) && str_starts_with($uidKey, 'v-')) {
                    $variantId = substr($uidKey, 2);
                }

                $qty = (float) $item['quantity'];
                $unitCost = (float) ($item['price'] ?? ($item['unit_cost'] ?? 0));
                $purchaseUnitId = $item['unit_id'] ?? ($item['purchase_unit_id'] ?? $product->base_unit_id);
                $baseUnitId = $product->base_unit_id;

                // Unit Ratio
                $unitDetailsJSON = $variantId
                    ? (ProductVariant::find($variantId)?->unit_details ? json_encode(ProductVariant::find($variantId)->unit_details) : json_encode($product->unit_details))
                    : json_encode($product->unit_details);

                $conversionFactor = (float) $this->unitFormulaService->getRatioFromJSON(json_decode($unitDetailsJSON, true) ?? [], $purchaseUnitId);
                $baseQuantity = round($qty * $conversionFactor, 4);
                $baseUnitCost = $conversionFactor > 0 ? round($unitCost / $conversionFactor, 2) : $unitCost;

                // Line Discount
                $lineDiscountMethod = $item['discount_method'] ?? 'flat';
                $unitDiscountInput = (float) ($item['unit_discount'] ?? 0);
                $unitDiscount = ($lineDiscountMethod === 'percentage') ? round(($unitCost * $unitDiscountInput) / 100, 2) : $unitDiscountInput;
                $totalLineDiscount = round($unitDiscount * $qty, 2);
                $lineNetCost = max(0, ($unitCost * $qty) - $totalLineDiscount);

                // Line Tax
                $lineTaxRate = (float) ($item['tax_rate'] ?? 0.0);
                $lineTaxAmount = round(($lineNetCost * $lineTaxRate) / 100, 2);
                $lineSubtotal = round($lineNetCost + $lineTaxAmount, 2);

                // IAS 2: Allocated Landed Cost
                $itemValueShareRatio = $rawSubtotal > 0 ? (($qty * $unitCost) / $rawSubtotal) : 0;
                $allocatedLandedCost = $qty > 0 ? round(($totalOverheadExpenses * $itemValueShareRatio) / $qty, 2) : 0.0;
                $effectiveUnitCost = round($unitCost + $allocatedLandedCost, 2);

                $subtotalAmount += $lineSubtotal;

                $rawImeis = ! empty($item['imei_list'])
                    ? (is_array($item['imei_list']) ? $item['imei_list'] : array_filter(array_map('trim', explode(',', (string) $item['imei_list']))))
                    : [];

                $expiryDate = ! empty($item['expire_date']) ? Carbon::parse($item['expire_date'])->format('Y-m-d') : null;
                $receivedQty = ($data['purchase_status'] === 'received') ? $qty : (float) ($item['received_qty'] ?? 0);

                $processedItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'product_batch_id' => $item['batch_id'] ?? ($item['product_batch_id'] ?? null),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $expiryDate,
                    'purchase_unit_id' => $purchaseUnitId,
                    'base_unit_id' => $baseUnitId,
                    'conversion_factor' => $conversionFactor,
                    'quantity' => $qty,
                    'received_qty' => $receivedQty,
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $unitCost,
                    'base_unit_cost' => $baseUnitCost,
                    'allocated_landed_cost' => $allocatedLandedCost,
                    'effective_unit_cost' => $effectiveUnitCost,
                    'batch_price' => ! empty($item['batch_price']) ? (float) $item['batch_price'] : null,
                    'batch_wholesale_price' => ! empty($item['batch_wholesale_price']) ? (float) $item['batch_wholesale_price'] : null,
                    'discount_method' => $lineDiscountMethod,
                    'discount_rate' => $unitDiscountInput,
                    'unit_discount' => $unitDiscount,
                    'total_discount' => $totalLineDiscount,
                    'tax_method' => $item['tax_method'] ?? 'exclusive',
                    'tax_rate' => $lineTaxRate,
                    'tax_amount' => $lineTaxAmount,
                    'subtotal' => $lineSubtotal,
                    'base_subtotal' => round($lineSubtotal * $exchangeRate, 2),
                    'imei_list' => ! empty($rawImeis) ? implode(',', $rawImeis) : null,
                    'barcodes' => $item['barcodes'] ?? null,
                    'raw_imeis' => $rawImeis,
                ];
            }

            // Order Discount & Tax
            $orderDiscountMethod = $data['order_discount_method'] ?? 'flat';
            $orderDiscountRate = (float) ($data['order_discount_rate'] ?? 0);
            $orderDiscountAmount = ($orderDiscountMethod === 'percentage')
                ? round(($subtotalAmount * $orderDiscountRate) / 100, 2)
                : round($orderDiscountRate, 2);

            $discountedSubtotal = max(0, $subtotalAmount - $orderDiscountAmount);
            $orderTaxAmount = ($orderTaxRate > 0) ? round(($discountedSubtotal * $orderTaxRate) / 100, 2) : 0.0;
            $roundOff = (float) ($data['round_off'] ?? 0);

            $totalAmount = round($discountedSubtotal + $orderTaxAmount + $shippingCost + $otherExpenses + $roundOff, 2);
            $paidAmount = min($totalAmount, max(0, (float) ($data['paid_amount'] ?? 0)));
            $dueAmount = round($totalAmount - $paidAmount, 2);
            $paymentStatus = $dueAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');

            $purchaseStatus = match ($data['purchase_status']) {
                'partial' => 'partial_received',
                default => $data['purchase_status'],
            };

            // 4. Create Master Purchase Record
            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'reference' => $data['reference'] ?? null,
                'memo_number' => $data['memo_number'] ?? null,
                'purchase_date' => $purchaseDate,
                'due_date' => $dueDate,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'purchase_status' => $purchaseStatus,
                'payment_status' => $paymentStatus,
                'status' => 'posted',
                'total_qty' => $totalQty,
                'subtotal_amount' => $subtotalAmount,
                'order_discount_method' => $orderDiscountMethod,
                'order_discount_rate' => $orderDiscountRate,
                'order_discount_amount' => $orderDiscountAmount,
                'order_tax_method' => $data['order_tax_method'] ?? '0',
                'order_tax_rate' => $orderTaxRate,
                'order_tax_amount' => $orderTaxAmount,
                'shipping_cost' => $shippingCost,
                'other_expenses' => $otherExpenses,
                'round_off' => $roundOff,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'total_base_amount' => round($totalAmount * $exchangeRate, 2),
                'base_subtotal_amount' => round($subtotalAmount * $exchangeRate, 2),
                'base_order_discount_amount' => round($orderDiscountAmount * $exchangeRate, 2),
                'base_order_tax_amount' => round($orderTaxAmount * $exchangeRate, 2),
                'base_shipping_cost' => round($shippingCost * $exchangeRate, 2),
                'base_other_expenses' => round($otherExpenses * $exchangeRate, 2),
                'base_paid_amount' => round($paidAmount * $exchangeRate, 2),
                'base_due_amount' => round($dueAmount * $exchangeRate, 2),
                'has_late_fee' => ! empty($data['has_late_fee']),
                'late_fee_config' => ! empty($data['has_late_fee']) ? ($data['late_fee_config'] ?? null) : null,
                'shipping_carrier_id' => $data['shipping_carrier_id'] ?? null,
                'document' => $documentPath,
                'note' => $data['note'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 5. Ingest Items, Batches, Prices & Stocks
            $this->ingestPurchaseItems($purchase, $processedItems, $data['branch_id'], $data['supplier_id'], $purchaseDate, $purchaseStatus);

            // 6. Post Double-Entry Accounting Voucher
            $voucher = $this->accIntegration->syncPurchase($purchase, $processedItems);
            $purchase->updateQuietly(['journal_voucher_id' => $voucher->id]);

            // 7. Record Instant Payment (if paid)
            if ($paidAmount > 0 && ! empty($data['payment_account_id'])) {
                $paymentNo = $this->supplierPayment->generatePaymentNo(Carbon::parse($purchaseDate));

                $payment = SupplierPayment::create([
                    'payment_no' => $paymentNo,
                    'supplier_id' => $purchase->supplier_id,
                    'payment_date' => $purchaseDate,
                    'payment_account_id' => $data['payment_account_id'],
                    'branch_id' => $purchase->branch_id,
                    'currency_id' => $purchase->currency_id,
                    'exchange_rate' => $exchangeRate,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'amount' => $paidAmount,
                    'base_amount' => round($paidAmount * $exchangeRate, 2),
                    'reference_no' => $data['reference'] ?? $purchase->purchase_no,
                    'note' => "Instant payment on purchase: {$purchase->purchase_no}",
                    'payable_type' => Purchase::class,
                    'payable_id' => $purchase->id,
                    'created_by' => auth()->id(),
                ]);

                $payVoucher = $this->accIntegration->syncSupplierPayment($payment);
                $payment->updateQuietly(['journal_voucher_id' => $payVoucher->id]);
            }

            return $purchase->load('items.product', 'supplier', 'branch');
        });
    }

    /**
     * Update Existing Purchase with Full Automated Reversals
     */
    public function updatePurchase(Purchase $purchase, array $data, ?UploadedFile $documentFile = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $documentFile) {

            if ($purchase->status === 'cancelled') {
                throw new Exception('Cannot update a cancelled purchase invoice. Please restore it first.');
            }

            $purchaseDate = Carbon::parse($data['purchase_date'])->format('Y-m-d');
            $dueDate = ! empty($data['due_date']) ? Carbon::parse($data['due_date'])->format('Y-m-d') : null;

            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            $documentPath = $purchase->document;
            if ($documentFile) {
                if ($documentPath) {
                    $this->deleteFile($documentPath, 's3');
                }
                $documentPath = $this->uploadUploadedFile($documentFile, 'purchases', 's3');
            }

            // Step A: Revert Previous Stock
            if (in_array($purchase->purchase_status, ['received', 'partial_received'])) {
                $purchase->load('items');

                foreach ($purchase->items as $oldItem) {
                    $oldStockQty = ($purchase->purchase_status === 'received')
                        ? (float) $oldItem->base_quantity
                        : (float) ($oldItem->received_qty * $oldItem->conversion_factor);

                    if ($oldStockQty > 0) {
                        $branchStock = BranchStock::where([
                            'branch_id' => $purchase->branch_id,
                            'product_id' => $oldItem->product_id,
                            'product_variant_id' => $oldItem->product_variant_id,
                            'product_batch_id' => $oldItem->product_batch_id,
                        ])->first();

                        if ($branchStock) {
                            $branchStock->quantity = max(0, (float) $branchStock->quantity - $oldStockQty);
                            $branchStock->save(); // 💡 Model booted event auto-syncs total stock & batch stock!

                            StockTransaction::create([
                                'branch_id' => $purchase->branch_id,
                                'product_id' => $oldItem->product_id,
                                'product_variant_id' => $oldItem->product_variant_id,
                                'product_batch_id' => $oldItem->product_batch_id,
                                'type' => 'out',
                                'quantity' => $oldStockQty,
                                'stock_after' => $branchStock->quantity,
                                'referenceable_type' => Purchase::class,
                                'referenceable_id' => $purchase->id,
                                'transaction_date' => now()->toDateString(),
                                'note' => "Stock reverted for purchase invoice update: {$purchase->purchase_no}",
                            ]);
                        }

                        ProductImei::where('sourceable_type', Purchase::class)
                            ->where('sourceable_id', $purchase->id)
                            ->where('product_id', $oldItem->product_id)
                            ->where('status', ImeiStatus::AVAILABLE)
                            ->delete();
                    }
                }
            }

            // Step B: Calculate New Metrics
            $shippingCost = round((float) ($data['shipping_cost'] ?? 0), 2);
            $otherExpenses = round((float) ($data['other_expenses'] ?? 0), 2);
            $totalOverheadExpenses = $shippingCost + $otherExpenses;

            $rawItemsList = $data['products'] ?? ($data['items'] ?? []);
            $rawSubtotal = 0.0;
            $totalQty = 0.0;

            foreach ($rawItemsList as $item) {
                $qty = (float) $item['quantity'];
                $cost = (float) ($item['price'] ?? ($item['unit_cost'] ?? 0));
                $rawSubtotal += ($qty * $cost);
                $totalQty += $qty;
            }

            if ($rawSubtotal <= 0) {
                throw new Exception('Purchase total amount must be greater than zero.');
            }

            $subtotalAmount = 0.0;
            $orderTaxRate = (float) ($data['order_tax_rate'] ?? 0.0);
            $processedItems = [];

            foreach ($rawItemsList as $uidKey => $item) {
                $productId = $item['product_id'];
                $product = Product::findOrFail($productId);

                $variantId = $item['product_variant_id'] ?? null;
                if (! $variantId && is_string($uidKey) && str_starts_with($uidKey, 'v-')) {
                    $variantId = substr($uidKey, 2);
                }

                $qty = (float) $item['quantity'];
                $unitCost = (float) ($item['price'] ?? ($item['unit_cost'] ?? 0));
                $purchaseUnitId = $item['unit_id'] ?? ($item['purchase_unit_id'] ?? $product->base_unit_id);
                $baseUnitId = $product->base_unit_id;

                $unitDetailsJSON = $variantId
                    ? (ProductVariant::find($variantId)?->unit_details ? json_encode(ProductVariant::find($variantId)->unit_details) : json_encode($product->unit_details))
                    : json_encode($product->unit_details);

                $conversionFactor = (float) $this->unitFormulaService->getRatioFromJSON(json_decode($unitDetailsJSON, true) ?? [], $purchaseUnitId);
                $baseQuantity = round($qty * $conversionFactor, 4);
                $baseUnitCost = $conversionFactor > 0 ? round($unitCost / $conversionFactor, 2) : $unitCost;

                $lineDiscountMethod = $item['discount_method'] ?? 'flat';
                $unitDiscountInput = (float) ($item['unit_discount'] ?? 0);
                $unitDiscount = ($lineDiscountMethod === 'percentage') ? round(($unitCost * $unitDiscountInput) / 100, 2) : $unitDiscountInput;
                $totalLineDiscount = round($unitDiscount * $qty, 2);
                $lineNetCost = max(0, ($unitCost * $qty) - $totalLineDiscount);

                $lineTaxRate = (float) ($item['tax_rate'] ?? 0.0);
                $lineTaxAmount = round(($lineNetCost * $lineTaxRate) / 100, 2);
                $lineSubtotal = round($lineNetCost + $lineTaxAmount, 2);

                $itemValueShareRatio = $rawSubtotal > 0 ? (($qty * $unitCost) / $rawSubtotal) : 0;
                $allocatedLandedCost = $qty > 0 ? round(($totalOverheadExpenses * $itemValueShareRatio) / $qty, 2) : 0.0;
                $effectiveUnitCost = round($unitCost + $allocatedLandedCost, 2);

                $subtotalAmount += $lineSubtotal;

                $rawImeis = ! empty($item['imei_list'])
                    ? (is_array($item['imei_list']) ? $item['imei_list'] : array_filter(array_map('trim', explode(',', (string) $item['imei_list']))))
                    : [];

                $expiryDate = ! empty($item['expire_date']) ? Carbon::parse($item['expire_date'])->format('Y-m-d') : null;
                $receivedQty = ($data['purchase_status'] === 'received') ? $qty : (float) ($item['received_qty'] ?? 0);

                $processedItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'product_batch_id' => $item['batch_id'] ?? ($item['product_batch_id'] ?? null),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $expiryDate,
                    'purchase_unit_id' => $purchaseUnitId,
                    'base_unit_id' => $baseUnitId,
                    'conversion_factor' => $conversionFactor,
                    'quantity' => $qty,
                    'received_qty' => $receivedQty,
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $unitCost,
                    'base_unit_cost' => $baseUnitCost,
                    'allocated_landed_cost' => $allocatedLandedCost,
                    'effective_unit_cost' => $effectiveUnitCost,
                    'batch_price' => ! empty($item['batch_price']) ? (float) $item['batch_price'] : null,
                    'batch_wholesale_price' => ! empty($item['batch_wholesale_price']) ? (float) $item['batch_wholesale_price'] : null,
                    'discount_method' => $lineDiscountMethod,
                    'discount_rate' => $unitDiscountInput,
                    'unit_discount' => $unitDiscount,
                    'total_discount' => $totalLineDiscount,
                    'tax_method' => $item['tax_method'] ?? 'exclusive',
                    'tax_rate' => $lineTaxRate,
                    'tax_amount' => $lineTaxAmount,
                    'subtotal' => $lineSubtotal,
                    'base_subtotal' => round($lineSubtotal * $exchangeRate, 2),
                    'imei_list' => ! empty($rawImeis) ? implode(',', $rawImeis) : null,
                    'barcodes' => $item['barcodes'] ?? null,
                    'raw_imeis' => $rawImeis,
                ];
            }

            // Order Discounts & Taxes
            $orderDiscountMethod = $data['order_discount_method'] ?? 'flat';
            $orderDiscountRate = (float) ($data['order_discount_rate'] ?? 0);
            $orderDiscountAmount = ($orderDiscountMethod === 'percentage')
                ? round(($subtotalAmount * $orderDiscountRate) / 100, 2)
                : round($orderDiscountRate, 2);

            $discountedSubtotal = max(0, $subtotalAmount - $orderDiscountAmount);
            $orderTaxAmount = ($orderTaxRate > 0) ? round(($discountedSubtotal * $orderTaxRate) / 100, 2) : 0.0;
            $roundOff = (float) ($data['round_off'] ?? 0);
            $totalAmount = round($discountedSubtotal + $orderTaxAmount + $shippingCost + $otherExpenses + $roundOff, 2);

            if ($totalAmount < $purchase->paid_amount) {
                throw new Exception('New purchase total ('.number_format($totalAmount, 2).') cannot be less than already paid amount ('.number_format($purchase->paid_amount, 2).').');
            }

            $newPaidAmount = $purchase->paid_amount;
            $newDueAmount = round($totalAmount - $newPaidAmount, 2);
            $paymentStatus = $newDueAmount <= 0 ? 'paid' : ($newPaidAmount > 0 ? 'partially_paid' : 'unpaid');

            $purchaseStatus = match ($data['purchase_status']) {
                'partial' => 'partial_received',
                default => $data['purchase_status'],
            };

            // Step C: Update Master Record
            $purchase->update([
                'purchase_date' => $purchaseDate,
                'due_date' => $dueDate,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $exchangeRate,
                'purchase_status' => $purchaseStatus,
                'payment_status' => $paymentStatus,
                'total_qty' => $totalQty,
                'subtotal_amount' => $subtotalAmount,
                'order_discount_method' => $orderDiscountMethod,
                'order_discount_rate' => $orderDiscountRate,
                'order_discount_amount' => $orderDiscountAmount,
                'order_tax_method' => $data['order_tax_method'] ?? '0',
                'order_tax_rate' => $orderTaxRate,
                'order_tax_amount' => $orderTaxAmount,
                'shipping_cost' => $shippingCost,
                'other_expenses' => $otherExpenses,
                'round_off' => $roundOff,
                'total_amount' => $totalAmount,
                'due_amount' => $newDueAmount,
                'total_base_amount' => round($totalAmount * $exchangeRate, 2),
                'base_subtotal_amount' => round($subtotalAmount * $exchangeRate, 2),
                'base_order_discount_amount' => round($orderDiscountAmount * $exchangeRate, 2),
                'base_order_tax_amount' => round($orderTaxAmount * $exchangeRate, 2),
                'base_shipping_cost' => round($shippingCost * $exchangeRate, 2),
                'base_other_expenses' => round($otherExpenses * $exchangeRate, 2),
                'base_due_amount' => round($newDueAmount * $exchangeRate, 2),
                'shipping_carrier_id' => $data['shipping_carrier_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo_number' => $data['memo_number'] ?? null,
                'document' => $documentPath,
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            // Refresh Line Items
            $purchase->items()->delete();

            // Step D: Re-apply Ingested Items
            $this->ingestPurchaseItems($purchase, $processedItems, $data['branch_id'], $data['supplier_id'], $purchaseDate, $purchaseStatus);

            // Step E: Re-sync Accounting Voucher
            $voucher = $this->accIntegration->syncPurchase($purchase, $processedItems);
            $purchase->updateQuietly(['journal_voucher_id' => $voucher->id]);

            return $purchase->fresh(['items.product', 'supplier', 'branch']);
        });
    }

    /**
     * Shared Item Ingestion Sub-routine (Handles Batches, IAS 2 AVCO Costing, Dynamic Prices, Stock & IMEIs)
     */
    protected function ingestPurchaseItems(Purchase $purchase, array $processedItems, $branchId, $supplierId, $purchaseDate, $purchaseStatus): void
    {
        foreach ($processedItems as $pItem) {
            $productId = $pItem['product_id'];
            $variantId = $pItem['product_variant_id'];
            $batchId = $pItem['product_batch_id'];
            $batchNumber = $pItem['batch_number'];
            $effectiveCost = (float) $pItem['effective_unit_cost'];

            $productModel = Product::find($productId);
            $variantModel = $variantId ? ProductVariant::find($variantId) : null;

            $batchSellingPrice = ! empty($pItem['batch_price']) && (float) $pItem['batch_price'] > 0
                ? (float) $pItem['batch_price']
                : (float) ($variantModel?->price ?? ($productModel?->price ?? 0));

            $batchWholesalePrice = ! empty($pItem['batch_wholesale_price']) && (float) $pItem['batch_wholesale_price'] > 0
                ? (float) $pItem['batch_wholesale_price']
                : (float) ($variantModel?->wholesale_price ?? ($productModel?->wholesale_price ?? 0));

            // 1. Batch Resolution
            $batch = null;
            if (! empty($batchId)) {
                $batch = ProductBatch::find($batchId);
            }
            if (! $batch && ! empty($batchNumber)) {
                $batchQuery = ProductBatch::where('product_id', $productId)->where('batch_no', $batchNumber);
                $batch = $variantId
                    ? $batchQuery->where('product_variant_id', $variantId)->first()
                    : $batchQuery->whereNull('product_variant_id')->first();
            }

            if ($batch) {
                $oldBatchStock = (float) BranchStock::where('product_batch_id', $batch->id)->sum('quantity');
                $oldBatchCost = (float) $batch->cost;
                $newPurchaseQty = (float) $pItem['base_quantity'];

                if ($oldBatchStock > 0) {
                    $totalCombinedQty = $oldBatchStock + $newPurchaseQty;
                    $weightedAverageCost = $totalCombinedQty > 0
                        ? round((($oldBatchStock * $oldBatchCost) + ($newPurchaseQty * $effectiveCost)) / $totalCombinedQty, 2)
                        : $effectiveCost;
                } else {
                    $weightedAverageCost = $effectiveCost;
                }

                $batch->update([
                    'supplier_id' => $supplierId,
                    'cost' => $weightedAverageCost,
                    'price' => $batchSellingPrice > 0 ? $batchSellingPrice : $batch->price,
                    'wholesale_price' => $batchWholesalePrice > 0 ? $batchWholesalePrice : $batch->wholesale_price,
                    'expiry_date' => ! empty($pItem['expiry_date']) ? $pItem['expiry_date'] : $batch->expiry_date,
                ]);
                $batchId = $batch->id;
            } else {
                if (empty($batchNumber)) {
                    $batchNumber = 'B'.strtoupper(base_convert(time().rand(10, 99), 10, 36));
                }
                $batch = ProductBatch::create([
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'supplier_id' => $supplierId,
                    'batch_no' => $batchNumber,
                    'expiry_date' => $pItem['expiry_date'],
                    'cost' => $effectiveCost,
                    'price' => $batchSellingPrice,
                    'wholesale_price' => $batchWholesalePrice,
                    'quantity' => 0,
                ]);
                $batchId = $batch->id;
            }

            $pItem['product_batch_id'] = $batchId;
            $pItem['batch_price'] = $batchSellingPrice;
            $pItem['batch_wholesale_price'] = $batchWholesalePrice;
            $rawImeis = $pItem['raw_imeis'];
            unset($pItem['raw_imeis']);

            // 2. Insert Purchase Line Item
            $purchase->items()->create($pItem);

            // 3. Record Dynamic Price Matrix Row
            if ($productModel) {
                $priceData = $productModel->prepareProductPrices(
                    variantId: $variantId,
                    branchId: $branchId,
                    batchId: $batchId,
                    customPrice: $batchSellingPrice > 0 ? $batchSellingPrice : null,
                    customCost: $effectiveCost,
                    customWholesalePrice: $batchWholesalePrice > 0 ? $batchWholesalePrice : null
                );
                ProductPrice::create($priceData);
            }

            // 4. Ingest Physical Stock & Register IMEIs
            $isStockIn = in_array($purchaseStatus, ['received', 'partial_received']);
            $stockInQty = ($purchaseStatus === 'received')
                ? $pItem['base_quantity']
                : (float) ($pItem['received_qty'] * $pItem['conversion_factor']);

            if ($isStockIn && $stockInQty > 0) {
                $branchStock = BranchStock::firstOrCreate(
                    [
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'product_variant_id' => $variantId,
                        'product_batch_id' => $batchId,
                    ],
                    [
                        'quantity' => 0,
                        'unit_id' => $pItem['base_unit_id'],
                    ]
                );

                $branchStock->quantity = (float) $branchStock->quantity + $stockInQty;
                $branchStock->save();

                // Record Inventory Transaction Ledger
                StockTransaction::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'product_batch_id' => $batchId,
                    'type' => 'in',
                    'quantity' => $stockInQty,
                    'stock_after' => $branchStock->quantity,
                    'referenceable_type' => Purchase::class,
                    'referenceable_id' => $purchase->id,
                    'transaction_date' => $purchaseDate,
                    'note' => "Purchase received/updated: {$purchase->purchase_no}",
                ]);

                // Register Unique IMEIs / Serial Numbers
                if (! empty($rawImeis) && is_array($rawImeis)) {
                    foreach ($rawImeis as $imeiNum) {
                        $imeiNum = trim($imeiNum);
                        if (empty($imeiNum)) {
                            continue;
                        }

                        $imei = ProductImei::updateOrCreate(
                            ['imei_number' => $imeiNum],
                            [
                                'product_id' => $productId,
                                'product_variant_id' => $variantId,
                                'product_batch_id' => $batchId,
                                'branch_id' => $branchId,
                                'status' => ImeiStatus::AVAILABLE,
                                'sourceable_type' => Purchase::class,
                                'sourceable_id' => $purchase->id,
                            ]
                        );

                        event(new ProductImeiEvent(
                            $imei->id,
                            $branchId,
                            ImeiEventType::PURCHASE,
                            "IMEI registered from purchase {$purchase->purchase_no}"
                        ));
                    }
                }
            }
        }
    }

    /**
     * Delete / Cancel Purchase with Inventory, IMEI, Payment, and Voucher Reversals
     */
    public function deletePurchase(Purchase $purchase, ?string $reason = null): void
    {
        DB::transaction(function () use ($purchase, $reason) {

            if (in_array($purchase->purchase_status, ['received', 'partial_received'])) {
                $purchase->load('items');

                foreach ($purchase->items as $item) {
                    $branchStock = BranchStock::where([
                        'branch_id' => $purchase->branch_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_batch_id' => $item->product_batch_id,
                    ])->first();

                    if ($branchStock) {
                        $revertQty = ($purchase->purchase_status === 'received')
                            ? $item->base_quantity
                            : ($item->received_qty * $item->conversion_factor);

                        $branchStock->quantity = max(0, (float) $branchStock->quantity - $revertQty);
                        $branchStock->save();

                        StockTransaction::create([
                            'branch_id' => $purchase->branch_id,
                            'product_id' => $item->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'product_batch_id' => $item->product_batch_id,
                            'type' => 'out',
                            'quantity' => $revertQty,
                            'stock_after' => $branchStock->quantity,
                            'referenceable_type' => Purchase::class,
                            'referenceable_id' => $purchase->id,
                            'transaction_date' => now()->toDateString(),
                            'note' => "Stock reversed due to purchase cancellation: {$purchase->purchase_no}",
                        ]);
                    }

                    ProductImei::where('sourceable_type', Purchase::class)
                        ->where('sourceable_id', $purchase->id)
                        ->where('status', ImeiStatus::AVAILABLE)
                        ->delete();
                }
            }

            // Reverse Payments
            $purchase->load('payments.journalVoucher');
            foreach ($purchase->payments as $payment) {
                if ($payment->journalVoucher && $payment->journalVoucher->status === JournalVoucherStatus::POSTED) {
                    $this->journalService->reverse($payment->journalVoucher, "Auto-reversed due to purchase deletion: {$purchase->purchase_no}");
                }
                $payment->delete();
            }

            // Reverse Purchase Booking Journal Voucher
            if ($purchase->journalVoucher && $purchase->journalVoucher->status === JournalVoucherStatus::POSTED) {
                $this->journalService->reverse($purchase->journalVoucher, $reason ?? 'Purchase cancelled/deleted by user');
            }

            // Soft Delete Purchase
            $purchase->updateQuietly([
                'status' => 'cancelled',
                'journal_voucher_id' => null,
                'note' => ($purchase->note ? $purchase->note.' | ' : '').'Cancelled: '.($reason ?? 'Deleted by user'),
                'deleted_by' => auth()->id(),
            ]);

            $purchase->delete();
        });
    }

    /**
     * Generate Unique Purchase Serial Number
     */
    protected function generatePurchaseNo(Carbon $date): string
    {
        $year = $date->format('Y');
        $prefix = "PUR-{$year}-";

        $query = Purchase::withTrashed()->where('purchase_no', 'like', "{$prefix}%");
        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $lastPurchase = $query->orderBy('purchase_no', 'desc')->first();

        $nextNumber = 1;
        if ($lastPurchase && !empty($lastPurchase->purchase_no)) {
            if (preg_match('/PUR-\d{4}-(\d+)/', $lastPurchase->purchase_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
        }

        return sprintf('PUR-%s-%06d', $year, $nextNumber);
    }
}
