<?php

namespace App\Models;

use App\Enums\GeneralLedgerStatus;
use App\Enums\JournalVoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'posting_sequence',
        'journal_voucher_id',
        'journal_entry_id',
        'account_id',
        'fiscal_year_id',
        'accounting_period_id',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'transaction_date',
        'sub_ledger_type',
        'sub_ledger_id',
        'voucher_no',
        'voucher_type',
        'reference_no',
        'narration',
        'debit',
        'base_debit',
        'credit',
        'base_credit',
        'balance',
        'base_balance',
        'status',
        'sourceable_type',
        'sourceable_id',
        'project_id',
        'is_opening',
        'is_system_generated',
        'posted_at',
        'reversed_at',
        'reversed_by',
        'created_by',
    ];

    protected $casts = [
        'voucher_type' => JournalVoucherType::class,
        'transaction_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'debit' => 'decimal:2',
        'base_debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'base_credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'base_balance' => 'decimal:2',
        'posted_at' => 'datetime',
        'status' => GeneralLedgerStatus::class,
    ];

    public function voucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
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

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function subLedger()
    {
        return $this->morphTo(__FUNCTION__, 'sub_ledger_type', 'sub_ledger_id');
    }

    public function sourceable()
    {
        return $this->morphTo();
    }
}
