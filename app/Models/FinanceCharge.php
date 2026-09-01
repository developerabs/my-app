<?php

namespace App\Models;

use App\Enums\JournalVoucherStatus;
use App\Services\Accounting\JournalService;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceCharge extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'charge_no',
        'chargeable_type',
        'chargeable_id',
        'charge_date',
        'days_overdue',
        'fee_type',
        'rate',
        'amount',
        'base_amount',
        'status',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'journal_voucher_id',
        'note',
        'waived_at',
        'waived_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'waived_at' => 'datetime',
        'days_overdue' => 'integer',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'chargeable_id' => 'string',
    ];

    protected static function booted()
    {
        parent::booted();

        static::restoring(function ($charge) {
            $chargeable = $charge->chargeable()->withTrashed()->first();
            if ($chargeable && $chargeable->trashed()) {
                throw new Exception("Cannot restore finance charge '{$charge->charge_no}' because the associated document is in Trash. Please restore the parent document first.");
            }
        });

        static::deleted(function ($charge) {
            if (! method_exists($charge, 'isForceDeleting') || ! $charge->isForceDeleting()) {
                $voucher = $charge->journalVoucher;
                if ($voucher && $voucher->status === JournalVoucherStatus::POSTED) {
                    app(JournalService::class)->reverse($voucher, 'Reversing due to finance charge deletion');
                }

                $charge->updateQuietly([
                    'journal_voucher_id' => null,
                ]);
            }
        });
    }

    /**
     * Polymorphic Relation to Bill or Invoice
     */
    public function chargeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function waiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
