<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Enums\LedgerAccountType;
use App\Services\Accounting\AccountingIntegrationService;
use App\Traits\HasTrash;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends BaseModel implements RestorableConflictInterface
{
    use HasTrash, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'chart_of_account_id', 'account_name', 'account_code', 'account_type', 'account_number', 'bank_name',
        'branch_name', 'routing_number', 'bank_details', 'opening_balance', 'base_opening_balance', 'opening_balance_date', 'current_balance', 'base_current_balance', 'last_transaction_date',
        'notes', 'is_active', 'is_system', 'is_default', 'branch_id', 'currency_id', 'external_id', 'source_from', 'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'account_type' => LedgerAccountType::class,
        'bank_details' => 'array',
        'opening_balance'      => 'decimal:2',
        'base_opening_balance' => 'decimal:2',
        'current_balance'      => 'decimal:2',
        'base_current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        // 💡 1. Ensure only ONE default account exists per branch
        static::saving(function ($account) {
            if ($account->is_default && ($account->isDirty('is_default') || $account->isDirty('branch_id') || ! $account->exists)) {
                static::query()
                    ->where('is_default', true)
                    ->when($account->branch_id, function ($q, $branchId) {
                        return $q->where('branch_id', $branchId);
                    }, function ($q) {
                        return $q->whereNull('branch_id');
                    })
                    ->when($account->exists, function ($q) use ($account) {
                        return $q->where('id', '!=', $account->id);
                    })
                    ->update(['is_default' => false]);
            }
        });

        // 2. Auto re-post Opening Balance when Restored from Trash
        static::restored(function ($account) {
            if ((float) $account->opening_balance > 0) {
                $date = $account->opening_balance_date
                    ? Carbon::parse($account->opening_balance_date)->format('Y-m-d')
                    : now()->toDateString();

                app(AccountingIntegrationService::class)->syncAccountOpeningBalance($account, (float) $account->opening_balance, $date);
            }
        });

        // 3. Auto reverse Opening Balance when Soft Deleted
        static::deleted(function ($account) {
            if (! $account->isForceDeleting() && (float) $account->opening_balance > 0) {
                app(AccountingIntegrationService::class)->syncAccountOpeningBalance($account, 0, now()->toDateString());
            }
        });
    }

    // 🟢 Currency Relationship
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    // 🟢 Branch Relationship
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function generalLedgers()
    {
        return $this->hasMany(GeneralLedger::class);
    }

    /**
     * Restoration conflict check
     */
    public function hasRestorationConflict(): bool
    {
        return self::where('account_name', $this->account_name)
            ->where('account_code', $this->account_code)
            ->where('account_type', $this->account_type)
            ->where('chart_of_account_id', $this->chart_of_account_id)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getTrashName(): string
    {
        return "Account: {$this->account_name} ({$this->account_code})";
    }
}
