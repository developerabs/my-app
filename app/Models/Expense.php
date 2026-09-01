<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Services\Accounting\ExpenseService;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends BaseModel implements RestorableConflictInterface
{
    use HasFactory, HasFiles, HasTrash, SoftDeletes;

    protected $fillable = [
        'expense_no',
        'expense_date',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'payment_account_id',
        'payment_method',
        'reference_no',
        'attachment',
        'total_amount',
        'total_base_amount',
        'note',
        'status',
        'project_id',
        'supplier_id',
        'journal_voucher_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'total_amount' => 'decimal:2',
        'total_base_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        // Auto re-post accounting entry on restoration
        static::restored(function ($expense) {
            app(ExpenseService::class)->restoreExpense($expense);
        });

        // Clean up S3 attachment file on permanent force delete safely
        static::forceDeleted(function ($expense) {
            if ($expense->attachment) {
                try {
                    $expense->deleteFile($expense->attachment, 's3');
                } catch (\Throwable $e) {
                    // Prevent S3 network timeouts from hanging permanent delete
                }
            }
        });
    }

    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('expense_no', $this->expense_no)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getTrashName(): string
    {
        return "Expense {$this->expense_no} (" . format_currency($this->total_amount) . ')';
    }
}