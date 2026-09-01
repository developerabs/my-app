<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Services\Accounting\AccountingIntegrationService;
use App\Traits\HasFeatureLimit;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Customer extends BaseModel implements RestorableConflictInterface, FeatureLimitInterface
{
    use HasUuids, SoftDeletes, HasTrash, HasFiles, HasFeatureLimit;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'customer_group_id',
        'membership_id',
        'name',
        'email',
        'phone',
        'opening_balance',
        'current_balance',
        'total_points',
        'opening_balance_date',
        'last_transaction_date',
        'membership_details',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'membership_details' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        $clearUiCache = function ($model) {
            Cache::tags([tenant_tag()])->forget('customers_list_' . tenant('id'));
        };

        static::saved($clearUiCache);
        static::deleted($clearUiCache);
        static::forceDeleted(function ($customer) {
            if ($customer->image) {
                $customer->deleteFile($customer->image, 's3');
            }
            Cache::tags([tenant_tag()])->forget('customers_list_' . tenant('id'));
        });

        static::restored(function ($customer) {
            if ((float) $customer->opening_balance > 0) {
                $date = $customer->opening_balance_date 
                    ? \Carbon\Carbon::parse($customer->opening_balance_date)->format('Y-m-d') 
                    : now()->toDateString();

                app(AccountingIntegrationService::class)->syncCustomerOpeningBalance($customer, (float) $customer->opening_balance, $date);
            }
        });

        static::deleted(function ($customer) {
            if (!$customer->isForceDeleting() && (float) $customer->opening_balance > 0) {
                app(AccountingIntegrationService::class)->syncCustomerOpeningBalance($customer, 0, now()->toDateString());
            }
        });

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
        // Accounts Receivable is Debit Normal (Debit - Credit).
        // Note: Opening balance is already posted in General Ledger.
        return $this->total_debit - $this->total_credit;
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function details(): HasOne
    {
        return $this->hasOne(CustomerDetails::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('is_primary', true);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'customer_id', 'id');
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('phone', $this->phone)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getFeatureLimitKey(): string
    {
        return 'customers_limit';
    }
}