<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Services\Accounting\AccountingIntegrationService;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Supplier extends BaseModel implements RestorableConflictInterface
{
    use HasFiles, HasTrash, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'company_name',
        'company_tax_id',
        'opening_balance',
        'opening_balance_date',
        'current_balance',
        'last_transaction_date',
        'address',
        'bank_details',
        'is_active',
        'external_id',
        'source_from',
        'image',
        'description',
        'date_of_birth',
        'gender',
    ];

    protected $casts = [
        'address' => 'array',
        'bank_details' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        $clearUiCache = function ($model) {
            Cache::tags([tenant_tag()])->forget('all_suppliers_'.tenant('id'));
        };

        static::saved($clearUiCache);
        static::updated($clearUiCache);
        static::deleted($clearUiCache);

        static::forceDeleted(function ($supplier) {
            if ($supplier->image) {
                $supplier->deleteFile($supplier->image, 's3');
            }
            Cache::tags([tenant_tag()])->forget('all_suppliers_'.tenant('id'));
        });

        static::restored(function ($supplier) {
            if ((float) $supplier->opening_balance > 0) {
                $date = $supplier->opening_balance_date 
                    ? \Carbon\Carbon::parse($supplier->opening_balance_date)->format('Y-m-d') 
                    : now()->toDateString();

                app(AccountingIntegrationService::class)->syncSupplierOpeningBalance($supplier, (float) $supplier->opening_balance, $date);
            }
        });

        static::deleted(function ($supplier) {
            if (!$supplier->isForceDeleting() && (float) $supplier->opening_balance > 0) {
                app(AccountingIntegrationService::class)->syncSupplierOpeningBalance($supplier, 0, now()->toDateString());
            }
        });
    }

    /**
     * Smart Display Title for Dropdowns & Reports
     * Priority: 1. Company Name -> 2. Phone -> 3. Short Unique ID
     */
    public function getDisplayTitleAttribute(): string
    {
        if (!empty($this->company_name)) {
            return "{$this->name} ({$this->company_name})";
        }

        if (!empty($this->phone)) {
            return "{$this->name} - {$this->phone}";
        }

        // কোম্পানি বা ফোন না থাকলে প্রথম ৮ ডিজিটের ইউনিক আইডি দেখাবে
        $shortId = strtoupper(substr($this->id, 0, 8));
        return "{$this->name} [ID: #{$shortId}]";
    }

    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'sub_ledger', 'sub_ledger_type', 'sub_ledger_id');
    }

    public function generalLedgers()
    {
        return $this->morphMany(GeneralLedger::class, 'sub_ledger', 'sub_ledger_type', 'sub_ledger_id');
    }

    public function getTotalDebitAttribute()
    {
        return $this->generalLedgers()->whereIn('status', ['posted', 'reversed'])->sum('base_debit');
    }

    public function getTotalCreditAttribute()
    {
        return $this->generalLedgers()->whereIn('status', ['posted', 'reversed'])->sum('base_credit');
    }

    public function getCurrentBalanceAttribute()
    {
        // Accounts Payable is Credit Normal (Credit - Debit).
        // Note: Opening balance is already posted in General Ledger, so no double adding.
        return $this->total_credit - $this->total_debit;
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('phone', $this->phone)
            ->whereNull('deleted_at')
            ->exists();
    }
}