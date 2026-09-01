<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetRegisterItem extends Model
{
    protected $fillable = [
        'asset_register_id',
        'asset_id',
        'supplier_id',
        'bill_id',
        'quantity',
        'remaining_quantity',
        'unit_cost',
        'base_unit_cost',
        'total_cost',
        'base_total_cost',
        'paid_amount',
        'base_paid_amount',
        'salvage_value',
        'base_salvage_value',
        'useful_life',
        'depreciation_start_date',
    ];

    protected $casts = [
        'quantity'                => 'decimal:4',
        'remaining_quantity'      => 'decimal:4',
        'unit_cost'               => 'decimal:4',
        'base_unit_cost'          => 'decimal:4',
        'total_cost'              => 'decimal:4',
        'base_total_cost'         => 'decimal:4',
        'paid_amount'             => 'decimal:4',
        'base_paid_amount'        => 'decimal:4',
        'salvage_value'           => 'decimal:4',
        'base_salvage_value'      => 'decimal:4',
        'depreciation_start_date' => 'date',
    ];

    public function register()
    {
        return $this->belongsTo(AssetRegister::class, 'asset_register_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
