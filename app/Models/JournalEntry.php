<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_voucher_id',
        'account_id',
        'sub_ledger_type',
        'sub_ledger_id',
        'line_no',
        'debit',
        'credit',
        'base_debit',
        'base_credit',
        'description',
        'project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'base_debit' => 'decimal:2',
        'base_credit' => 'decimal:2',
    ];

    public function voucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function subLedger()
    {
        return $this->morphTo(__FUNCTION__, 'sub_ledger_type', 'sub_ledger_id');
    }
}
