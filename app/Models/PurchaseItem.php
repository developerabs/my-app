<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_variant_id',
        'product_batch_id',
        'batch_number',
        'expiry_date',
        'manufacturing_date',
        'purchase_unit_id',
        'base_unit_id',
        'conversion_factor',
        'quantity',
        'received_qty',
        'base_quantity',
        'unit_cost',
        'base_unit_cost',
        'allocated_landed_cost',
        'effective_unit_cost',
        'batch_price',
        'batch_wholesale_price',
        'discount_method',
        'discount_rate',
        'unit_discount',
        'total_discount',
        'tax_id',
        'tax_method',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'base_subtotal',
        'imei_list',
        'barcodes',
        'note',
    ];

    protected $casts = [
        'expiry_date'            => 'date',
        'manufacturing_date'     => 'date',
        'conversion_factor'      => 'decimal:6',
        'quantity'               => 'decimal:4',
        'received_qty'           => 'decimal:4',
        'base_quantity'          => 'decimal:4',
        'unit_cost'              => 'decimal:2',
        'base_unit_cost'         => 'decimal:2',
        'allocated_landed_cost'  => 'decimal:2',
        'effective_unit_cost'    => 'decimal:2',
        'batch_price'            => 'decimal:2',
        'batch_wholesale_price'  => 'decimal:2',
        'discount_rate'          => 'decimal:2',
        'unit_discount'          => 'decimal:2',
        'total_discount'         => 'decimal:2',
        'tax_rate'               => 'decimal:2',
        'tax_amount'             => 'decimal:2',
        'subtotal'               => 'decimal:2',
        'base_subtotal'          => 'decimal:2',
    ];

    // ==================== Relationships ====================

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}