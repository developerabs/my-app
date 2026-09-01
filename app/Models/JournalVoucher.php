<?php

namespace App\Models;

use App\Enums\JournalVoucherStatus;
use App\Enums\JournalVoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'posting_sequence',
        'voucher_no',
        'voucher_date',
        'fiscal_year_id',
        'accounting_period_id',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'voucher_type',
        'status',
        'reference_no',
        'attachment',
        'narration',
        'reverse_reason',
        'total_debit',
        'total_credit',
        'total_base_debit',
        'total_base_credit',
        'external_id',
        'source_from',
        'sourceable_type',
        'sourceable_id',
        'project_id',
        'reversal_of',
        'reversed_by_voucher',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
        'created_by',
        'updated_by',
        'deleted_by',

    ];

    protected $casts = [
        'voucher_date' => 'date',
        'posted_at' => 'datetime',
        'voucher_type' => JournalVoucherType::class,
        'status' => JournalVoucherStatus::class,
    ];

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function sourceable()
    {
        return $this->morphTo();
    }

    public function generalLedgers()
    {
        return $this->hasMany(GeneralLedger::class);
    }

    public function reversalOf()
    {
        return $this->belongsTo(
            JournalVoucher::class,
            'reversal_of'
        );
    }

    public function reversedVoucher()
    {
        return $this->belongsTo(
            JournalVoucher::class,
            'reversed_by_voucher'
        );
    }
}
