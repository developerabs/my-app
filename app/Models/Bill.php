<?php

namespace App\Models;

use App\Contracts\FeatureLimitInterface;
use App\Contracts\RestorableConflictInterface;
use App\Services\Accounting\BillService;
use App\Traits\HasFeatureLimit;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends BaseModel implements FeatureLimitInterface, RestorableConflictInterface
{
    use HasFactory, HasFeatureLimit, HasFiles, HasTrash, SoftDeletes;

    protected $fillable = [
        'bill_no',
        'vendor_invoice_no',
        'bill_date',
        'due_date',
        'supplier_id',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'project_id',
        'total_amount',
        'total_base_amount',
        'paid_amount',
        'base_paid_amount',
        'due_amount',
        'base_due_amount',
        'payment_status',
        'has_late_fee',
        'late_fee_config',
        'status',
        'attachment',
        'note',
        'journal_voucher_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'total_amount' => 'decimal:2',
        'total_base_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'base_paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'base_due_amount' => 'decimal:2',
        'has_late_fee' => 'boolean',
        'late_fee_config' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        // Auto re-post accounting entry on restoration
        static::restored(function ($bill) {
            app(BillService::class)->restoreBill($bill);
        });

        // Clean up S3 attachment on permanent force delete
        static::forceDeleted(function ($bill) {
            if ($bill->attachment) {
                try {
                    $bill->deleteFile($bill->attachment, 's3');
                } catch (\Throwable $e) {
                }
            }
        });
    }

    public function items()
    {
        return $this->hasMany(BillItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function payments()
    {
        return $this->morphMany(SupplierPayment::class, 'payable', 'payable_type', 'payable_id');
    }

    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('bill_no', $this->bill_no)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function financeCharges()
    {
        return $this->morphMany(FinanceCharge::class, 'chargeable', 'chargeable_type', 'chargeable_id');
    }

    public function isEligibleForLateFeeToday(): bool
    {
        // ১. ফ্ল্যাগ অন না থাকলে বা বকেয়া না থাকলে
        if (! $this->has_late_fee || $this->due_amount <= 0 || empty($this->late_fee_config)) {
            return false;
        }

        $config = $this->late_fee_config;

        // 🛑 ২. যদি লেট ফি ফ্রিজ (Pause) করা থাকে
        if (! empty($config['is_frozen']) && $config['is_frozen'] === true) {
            return false;
        }

        $graceDays = (int) ($config['grace_days'] ?? 0);
        $frequency = $config['frequency'] ?? 'one_time';
        $lastAppliedAt = $config['last_applied_at'] ?? null;

        // ৩. Due Date + Grace Days পার হয়েছে কি না চেক
        $dueDate = Carbon::parse($this->due_date);
        $effectiveDueDate = $dueDate->addDays($graceDays)->startOfDay();

        if (now()->startOfDay()->lessThanOrEqualTo($effectiveDueDate)) {
            return false; // এখনও গ্রেস পিরিয়ডের মধ্যে আছে
        }

        // ৪. এককালীন (one_time) হলে আগে ফি কাটা হয়েছে কি না চেক
        if ($frequency === 'one_time' && ! empty($lastAppliedAt)) {
            return false;
        }

        // ৫. মান্থলি (monthly) হলে চলতি মাসে আগে ফি কাটা হয়েছে কি না চেক
        if ($frequency === 'monthly' && ! empty($lastAppliedAt)) {
            $nextAllowedDate = Carbon::parse($lastAppliedAt)->addMonth()->startOfDay();

            // আজ যদি শেষ ফি কাটার ১ মাসের চেয়ে কম দিন হয়, তবে রিটার্ন ফলস করবে
            if (now()->startOfDay()->lessThan($nextAllowedDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 4. Calculate Late Fee Amount based on JSON config
     */
    public function calculateLateFeeFromConfig(): float
    {
        if (! $this->isEligibleForLateFeeToday()) {
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
                // 🟢 চক্রবৃদ্ধি হার: বর্তমান চলতি বকেয়া ($this->due_amount)-এর ওপর পার্সেন্টেজ ধরা হবে
                $calculatedFee = round(($this->due_amount * $rate) / 100, 2);
            } else {
                // 🔵 সরল হার: মূল বকেয়ার ওপর পার্সেন্টেজ ধরা হবে
                $originalPrincipal = $this->getOriginalPrincipalDue();
                $calculatedFee = round(($originalPrincipal * $rate) / 100, 2);
            }
        }

        // ম্যাক্স ক্যাপ লিমিট চেক
        if ($maxLimit !== null && $maxLimit > 0) {
            $totalAlreadyCharged = (float) $this->financeCharges()->where('status', '!=', 'cancelled')->sum('amount');
            $remainingAllowedFee = max(0, $maxLimit - $totalAlreadyCharged);

            if ($calculatedFee > $remainingAllowedFee) {
                $calculatedFee = $remainingAllowedFee;
            }
        }

        return round($calculatedFee, 2);
    }

    /**
     * Dynamic Overdue Days Accessor
     */
    public function getOverdueDaysAttribute(): int
    {
        if (! $this->due_date || $this->due_amount <= 0) {
            return 0;
        }

        $dueDate = Carbon::parse($this->due_date)->startOfDay();
        $today = now()->startOfDay();

        if ($today->lessThanOrEqualTo($dueDate)) {
            return 0;
        }

        return (int) $dueDate->diffInDays($today);
    }

    /**
     * 5. Get original principal due without past finance charges
     */
    public function getOriginalPrincipalDue(): float
    {
        $pastCharges = (float) $this->financeCharges()->where('status', '!=', 'cancelled')->sum('amount');

        return max(0, $this->due_amount - $pastCharges);
    }

    public function getFeatureLimitKey(): string
    {
        return 'bills_limit';
    }

    public function getTrashName(): string
    {
        return "Vendor Bill {$this->bill_no} (".format_currency($this->total_amount).')';
    }
}
