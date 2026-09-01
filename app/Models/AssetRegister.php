<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Enums\AssetEntryType;
use App\Enums\JournalVoucherStatus;
use App\Services\Accounting\JournalService;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRegister extends BaseModel implements RestorableConflictInterface
{
    use HasTrash, SoftDeletes;

    protected $fillable = [
        'register_no',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'entry_type',
        'register_date',
        'total_cost',
        'base_total_cost',
        'journal_voucher_id',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'register_date'   => 'date',
        'exchange_rate'   => 'decimal:8',
        'total_cost'      => 'decimal:4',
        'base_total_cost' => 'decimal:4',
        'entry_type'      => AssetEntryType::class,
    ];

    /**
     * Boot model events for automatic accounting sync on delete.
     */
    protected static function booted()
    {
        parent::booted();

        // Auto reverse journal voucher on soft delete (for opening entries)
        static::deleted(function ($register) {
            if (!method_exists($register, 'isForceDeleting') || !$register->isForceDeleting()) {
                $voucher = $register->journalVoucher;
                if ($voucher && $voucher->status === JournalVoucherStatus::POSTED) {
                    app(JournalService::class)->reverse($voucher, 'Reversing due to asset register deletion');
                }

                $register->updateQuietly([
                    'journal_voucher_id' => null,
                ]);
            }
        });
    }

    public function items()
    {
        return $this->hasMany(AssetRegisterItem::class, 'asset_register_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('register_no', $this->register_no)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getTrashName(): string
    {
        $amount = format_currency($this->total_cost ?? 0);
        return "Asset Register: {$this->register_no} ({$amount})";
    }
}