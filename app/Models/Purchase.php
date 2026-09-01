<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Enums\JournalVoucherStatus;
use App\Services\Accounting\JournalService;
use App\Traits\HasFeatureLimit;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends BaseModel implements FeatureLimitInterface, RestorableConflictInterface
{
    use HasFactory, HasFeatureLimit, HasFiles, HasTrash, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'purchase_no',
        'reference',
        'memo_number',
        'purchase_date',
        'due_date',
        'supplier_id',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'purchase_status',
        'payment_status',
        'status',
        'total_qty',
        'subtotal_amount',
        'order_discount_method',
        'order_discount_rate',
        'order_discount_amount',
        'order_tax_id',
        'order_tax_method',
        'order_tax_rate',
        'order_tax_amount',
        'shipping_cost',
        'other_expenses',
        'round_off',
        'total_amount',
        'paid_amount',
        'due_amount',
        'total_base_amount',
        'base_subtotal_amount',
        'base_order_discount_amount',
        'base_order_tax_amount',
        'base_shipping_cost',
        'base_other_expenses',
        'base_paid_amount',
        'base_due_amount',
        'has_late_fee',
        'late_fee_config',
        'journal_voucher_id',
        'project_id',
        'document',
        'note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'purchase_date'              => 'date',
        'due_date'                   => 'date',
        'exchange_rate'              => 'decimal:8',
        'total_qty'                  => 'decimal:4',
        'subtotal_amount'            => 'decimal:2',
        'order_discount_rate'        => 'decimal:2',
        'order_discount_amount'      => 'decimal:2',
        'order_tax_rate'             => 'decimal:2',
        'order_tax_amount'           => 'decimal:2',
        'shipping_cost'              => 'decimal:2',
        'other_expenses'             => 'decimal:2',
        'round_off'                  => 'decimal:2',
        'total_amount'               => 'decimal:2',
        'paid_amount'                => 'decimal:2',
        'due_amount'                 => 'decimal:2',
        'total_base_amount'          => 'decimal:2',
        'base_subtotal_amount'       => 'decimal:2',
        'base_order_discount_amount' => 'decimal:2',
        'base_order_tax_amount'      => 'decimal:2',
        'base_shipping_cost'         => 'decimal:2',
        'base_other_expenses'        => 'decimal:2',
        'base_paid_amount'           => 'decimal:2',
        'base_due_amount'            => 'decimal:2',
        'has_late_fee'               => 'boolean',
        'late_fee_config'            => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        // Auto reverse accounting voucher when soft deleted
        static::deleted(function ($purchase) {
            if (!method_exists($purchase, 'isForceDeleting') || !$purchase->isForceDeleting()) {
                $voucher = $purchase->journalVoucher;
                if ($voucher && $voucher->status === JournalVoucherStatus::POSTED) {
                    app(JournalService::class)->reverse($voucher, 'Reversing due to purchase invoice cancellation/deletion');
                }

                $purchase->updateQuietly([
                    'journal_voucher_id' => null,
                ]);
            }
        });

        // Clean up S3 attachment file on permanent force delete safely
        static::forceDeleted(function ($purchase) {
            if ($purchase->document) {
                try {
                    $purchase->deleteFile($purchase->document, 's3');
                } catch (\Throwable $e) {}
            }
        });
    }

    // ==================== Relationships ====================

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function orderTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'order_tax_id');
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(
            SupplierPayment::class, 
            'payable', 
            'payable_type', 
            'payable_id', 
            'string_id'
        );
    }

    public function financeCharges(): MorphMany
    {
        return $this->morphMany(
            FinanceCharge::class, 
            'chargeable', 
            'chargeable_type', 
            'chargeable_id', 
            'string_id'
        );
    }

    // ==================== Late Fee Helpers ====================

    public function isEligibleForLateFeeToday(): bool
    {
        if (!$this->has_late_fee || $this->due_amount <= 0 || empty($this->late_fee_config)) {
            return false;
        }

        $config = $this->late_fee_config;

        if (!empty($config['is_frozen']) && $config['is_frozen'] === true) {
            return false;
        }

        $graceDays = (int) ($config['grace_days'] ?? 0);
        $frequency = $config['frequency'] ?? 'one_time';
        $lastAppliedAt = $config['last_applied_at'] ?? null;

        $dueDate = Carbon::parse($this->due_date ?? $this->purchase_date);
        $effectiveDueDate = $dueDate->addDays($graceDays)->startOfDay();

        if (now()->startOfDay()->lessThanOrEqualTo($effectiveDueDate)) {
            return false;
        }

        if ($frequency === 'one_time' && !empty($lastAppliedAt)) {
            return false;
        }

        if ($frequency === 'monthly' && !empty($lastAppliedAt)) {
            $nextAllowedDate = Carbon::parse($lastAppliedAt)->addMonth()->startOfDay();
            if (now()->startOfDay()->lessThan($nextAllowedDate)) {
                return false;
            }
        }

        return true;
    }

    public function calculateLateFeeFromConfig(): float
    {
        if (!$this->isEligibleForLateFeeToday()) {
            return 0.00;
        }

        $config = $this->late_fee_config;
        $feeType = $config['fee_type'] ?? 'fixed';
        $method = $config['calculation_method'] ?? 'simple';
        $rate = (float) ($config['rate'] ?? 0);
        $maxLimit = isset($config['max_fee_limit']) && is_numeric($config['max_fee_limit']) ? (float) $config['max_fee_limit'] : null;

        $calculatedFee = 0.00;

        if ($feeType === 'fixed') {
            $calculatedFee = round($rate, 2);
        } else {
            if ($method === 'compound') {
                $calculatedFee = round(($this->due_amount * $rate) / 100, 2);
            } else {
                $originalPrincipal = $this->getOriginalPrincipalDue();
                $calculatedFee = round(($originalPrincipal * $rate) / 100, 2);
            }
        }

        if ($maxLimit !== null && $maxLimit > 0) {
            $totalAlreadyCharged = (float) $this->financeCharges()->where('status', '!=', 'cancelled')->sum('amount');
            $remainingAllowedFee = max(0, $maxLimit - $totalAlreadyCharged);

            if ($calculatedFee > $remainingAllowedFee) {
                $calculatedFee = $remainingAllowedFee;
            }
        }

        return round($calculatedFee, 2);
    }

    public function getOverdueDaysAttribute(): int
    {
        if (!$this->due_date || $this->due_amount <= 0) {
            return 0;
        }

        $dueDate = Carbon::parse($this->due_date)->startOfDay();
        $today = now()->startOfDay();

        if ($today->lessThanOrEqualTo($dueDate)) {
            return 0;
        }

        return (int) $dueDate->diffInDays($today);
    }

    public function getOriginalPrincipalDue(): float
    {
        $pastCharges = (float) $this->financeCharges()->where('status', '!=', 'cancelled')->sum('amount');
        return max(0, $this->due_amount - $pastCharges);
    }

    // ==================== Contracts ====================

    public function hasRestorationConflict(): bool
    {
        return self::where('purchase_no', $this->purchase_no)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getFeatureLimitKey(): string
    {
        return 'purchases_limit';
    }

    public function getTrashName(): string
    {
        $amount = format_currency($this->total_amount ?? 0);
        return "Purchase: {$this->purchase_no} ({$amount})";
    }
}