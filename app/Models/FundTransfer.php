<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Enums\JournalVoucherStatus;
use App\Services\Accounting\JournalService;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

class FundTransfer extends BaseModel implements RestorableConflictInterface
{
    use HasFactory, SoftDeletes, HasTrash;

    protected $fillable = [
        'transfer_no',
        'transfer_date',
        'from_account_id',
        'to_account_id',
        'amount',
        'base_amount',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'payment_method',
        'reference_no',
        'attachment',
        'note',
        'status',
        'journal_voucher_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount'        => 'decimal:2',
        'base_amount'   => 'decimal:2',
        'exchange_rate' => 'decimal:8',
    ];

    protected static function booted()
    {
        parent::booted();

        // 🟢 ১. ডিলিট হলে একাউন্টিং জাবেদা ওয়াউচার রিভার্স হবে এবং ওয়াউচার আইডি নাল হবে
        static::deleted(function ($transfer) {
            if (!method_exists($transfer, 'isForceDeleting') || !$transfer->isForceDeleting()) {
                $voucher = $transfer->journalVoucher;
                if ($voucher && $voucher->status === JournalVoucherStatus::POSTED) {
                    app(JournalService::class)->reverse($voucher, 'Reversing due to fund transfer deletion');
                }

                $transfer->updateQuietly([
                    'journal_voucher_id' => null,
                ]);
            }
        });

        // 🟢 ২. ট্র্যাশ থেকে রিস্টোর হলে ওয়াউচার রি-পোস্ট হবে
        static::restored(function ($transfer) {
            app(\App\Services\Accounting\FundTransferService::class)->restoreTransfer($transfer);
        });

        // 🟢 ৩. পারমানেন্ট ডিলিটে ফাইল ক্লিনআপ
        static::forceDeleted(function ($transfer) {
            if ($transfer->attachment) {
                try {
                    $transfer->deleteFile($transfer->attachment, 's3');
                } catch (\Throwable $e) {}
            }
        });
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    #[Override]
    public function hasRestorationConflict(): bool
    {
        return self::where('transfer_no', $this->transfer_no)
            ->whereNull('deleted_at')
            ->exists();
    }
}