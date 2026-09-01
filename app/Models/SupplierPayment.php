<?php

namespace App\Models;

use App\Contracts\RestorableConflictInterface;
use App\Enums\JournalVoucherStatus;
use App\Models\Bill;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\Accounting\JournalService;
use App\Traits\HasFiles;
use App\Traits\HasTrash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends BaseModel implements RestorableConflictInterface
{
    use HasFactory, SoftDeletes, HasTrash, HasFiles;

    protected $fillable = [
        'payment_no',
        'supplier_id',
        'payment_date',
        'payment_account_id',
        'branch_id',
        'currency_id',
        'exchange_rate',
        'payment_method',
        'amount',
        'base_amount',
        'reference_no',
        'attachment',
        'note',
        'payable_type',
        'payable_id',
        'journal_voucher_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
    ];

    /**
     * Boot model events for automatic accounting & bill due synchronization
     */
    protected static function booted()
    {
        parent::booted();

        // 🛑 ১. Guard Check: ট্র্যাশে পড়া বিলের পেমেন্ট রিস্টোর করা ব্লক করবে
        static::restoring(function ($payment) {
            if ($payment->payable_type === Bill::class && $payment->payable_id) {
                $bill = Bill::withTrashed()->find($payment->payable_id);
                if ($bill && $bill->trashed()) {
                    throw new \Exception("Cannot restore payment '{$payment->payment_no}' because the associated Vendor Bill '{$bill->bill_no}' is still in Trash. Please restore the bill first.");
                }
            }
        });

        // 🟢 ২. Auto reverse accounting voucher & increase bill due when payment is soft deleted
        static::deleted(function ($payment) {
            if (!$payment->isForceDeleting()) {
                
                // Reverse Payment Journal Voucher
                $voucher = $payment->journalVoucher;
                if ($voucher && $voucher->status === JournalVoucherStatus::POSTED) {
                    app(JournalService::class)->reverse($voucher, 'Reversing due to supplier payment deletion');
                }

                // 💡 ওয়াউচার রিভার্স হলে পেমেন্ট টেবিলে ক্যানসেলড ওয়াউচারের আইডি সাইলেন্টলি নাল করে দেওয়া
                $payment->updateQuietly([
                    'journal_voucher_id' => null
                ]);

                // Adjust Bill due & paid amounts if linked to an active Bill
                if ($payment->payable_type === Bill::class && $payment->payable_id) {
                    $bill = Bill::find($payment->payable_id);
                    if ($bill) {
                        $newPaid = max(0, round($bill->paid_amount - $payment->amount, 2));
                        $newBasePaid = max(0, round($bill->base_paid_amount - $payment->base_amount, 2));
                        $newDue = round($bill->total_amount - $newPaid, 2);
                        $newBaseDue = round($bill->total_base_amount - $newBasePaid, 2);

                        $bill->update([
                            'paid_amount' => $newPaid,
                            'base_paid_amount' => $newBasePaid,
                            'due_amount' => $newDue,
                            'base_due_amount' => $newBaseDue,
                            'payment_status' => $newPaid <= 0 ? 'unpaid' : 'partially_paid',
                        ]);
                    }
                }
            }
        });

        // 🟢 ৩. Auto re-post accounting voucher & reduce bill due when payment is restored from trash
        static::restored(function ($payment) {
            
            // Re-post Payment Journal Voucher & link new voucher ID silently
            $voucher = app(AccountingIntegrationService::class)->syncSupplierPayment($payment);
            
            $payment->updateQuietly([
                'journal_voucher_id' => $voucher->id,
                'deleted_by' => null,
            ]);

            // Adjust Bill due & paid amounts if linked to an active Bill
            if ($payment->payable_type === Bill::class && $payment->payable_id) {
                $bill = Bill::find($payment->payable_id);
                if ($bill) {
                    $newPaid = round($bill->paid_amount + $payment->amount, 2);
                    $newBasePaid = round($bill->base_paid_amount + $payment->base_amount, 2);
                    $newDue = round($bill->total_amount - $newPaid, 2);
                    $newBaseDue = round($bill->total_base_amount - $newBasePaid, 2);

                    $bill->update([
                        'paid_amount' => $newPaid,
                        'base_paid_amount' => $newBasePaid,
                        'due_amount' => max(0, $newDue),
                        'base_due_amount' => max(0, $newBaseDue),
                        'payment_status' => $newDue <= 0 ? 'paid' : 'partially_paid',
                    ]);
                }
            }
        });

        // 🟢 ৪. Clean up S3 attachment file on permanent force delete
        static::forceDeleted(function ($payment) {
            if ($payment->attachment) {
                try {
                    $payment->deleteFile($payment->attachment, 's3');
                } catch (\Throwable $e) {}
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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

    public function payable()
    {
        return $this->morphTo();
    }

    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function hasRestorationConflict(): bool
    {
        return self::where('payment_no', $this->payment_no)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function getTrashName(): string
    {
        $amount = format_currency($this->amount ?? 0);
        return "Supplier Payment: {$this->payment_no} ({$amount})";
    }
}