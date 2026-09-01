<?php

namespace App\Models;

use App\Enums\ImeiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ProductImei extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'product_batch_id',
        'branch_id',
        'imei_number',
        'status',
        'sourceable_type',
        'sourceable_id',
        'actionable_type',
        'actionable_id',
    ];

    protected $casts = [
        'status' => ImeiStatus::class,
    ];

    protected $appends = ['branch_name', 'batch_no'];

    public static function booted()
    {
        parent::booted();
        $clearUiCache = function ($model) {
            Cache::tags([tenant_tag()])->forget('imei_records_' . tenant('id'));
        };
        static::saved($clearUiCache);
        static::updated($clearUiCache);
        static::deleted($clearUiCache);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function getBranchNameAttribute()
    {
        return $this->branch->name ?? 'N/A';
    }

    public function getBatchNoAttribute()
    {
        return $this->batch->batch_no ?? 'N/A';
    }

    public function sourceable()
    {
        return $this->morphTo();
    }

    public function actionable()
    {
        return $this->morphTo();
    }
}
