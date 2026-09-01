<?php

namespace App\Models;

use App\Enums\JournalVoucherType;

class VoucherSequence extends BaseModel
{
    protected $fillable = [
        'voucher_type',
        'fiscal_year_id',
        'last_number',
    ];

    protected $casts = [
        'voucher_type' => JournalVoucherType::class,
        'last_number' => 'integer',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($model) {
            unset($model->created_by, $model->updated_by, $model->deleted_by);
        });

        static::updating(function ($model) {
            unset($model->updated_by);
        });
    }

    /**
     * Fiscal Year
     */
    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
