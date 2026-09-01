<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\FundTransfer;
use App\Services\CurrencyConversionService;
use App\Traits\HasFiles;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FundTransferService
{
    use HasFiles;

    public function __construct(
        protected JournalService $journalService,
        protected PostingService $postingService,
        protected CurrencyConversionService $currencyService,
        protected AccountingIntegrationService $accIntegration
    ) {}

    /**
     * Create Fund Transfer & Post Contra Voucher
     */
    public function createTransfer(array $data, ?UploadedFile $attachmentFile = null): FundTransfer
    {
        return DB::transaction(function () use ($data, $attachmentFile) {

            $transferDate = Carbon::parse($data['transfer_date'])->format('Y-m-d');
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new Exception("Transfer amount must be greater than zero.");
            }

            if ($data['from_account_id'] == $data['to_account_id']) {
                throw new Exception("Source and destination accounts cannot be the same.");
            }

            // Check Source Account Balance (Optional Soft Safety)
            $fromAccount = Account::findOrFail($data['from_account_id']);

            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            $attachmentPath = null;
            if ($attachmentFile) {
                $attachmentPath = $this->uploadUploadedFile($attachmentFile, 'fund_transfers', 's3');
            }

            $transferNo = $this->generateTransferNo(Carbon::parse($transferDate));
            $baseAmount = round($amount * $exchangeRate, 2);

            // 1. Create Fund Transfer Record
            $transfer = FundTransfer::create([
                'transfer_no'     => $transferNo,
                'transfer_date'   => $transferDate,
                'from_account_id' => $data['from_account_id'],
                'to_account_id'   => $data['to_account_id'],
                'amount'          => $amount,
                'base_amount'     => $baseAmount,
                'branch_id'       => $data['branch_id'],
                'currency_id'     => $data['currency_id'],
                'exchange_rate'   => $exchangeRate,
                'payment_method'  => $data['payment_method'] ?? 'cash',
                'reference_no'    => $data['reference_no'] ?? null,
                'attachment'      => $attachmentPath,
                'note'            => $data['note'] ?? null,
                'status'          => 'posted',
                'created_by'      => auth()->id(),
            ]);

            // 2. Post Contra Journal Voucher
            $voucher = $this->accIntegration->syncFundTransfer($transfer);
            $transfer->update(['journal_voucher_id' => $voucher->id]);

            return $transfer->load('fromAccount', 'toAccount');
        });
    }

    /**
     * Update Fund Transfer & Re-post Voucher
     */
    public function updateTransfer(FundTransfer $transfer, array $data, ?UploadedFile $attachmentFile = null): FundTransfer
    {
        return DB::transaction(function () use ($transfer, $data, $attachmentFile) {

            $transferDate = Carbon::parse($data['transfer_date'])->format('Y-m-d');
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new Exception("Transfer amount must be greater than zero.");
            }

            if ($data['from_account_id'] == $data['to_account_id']) {
                throw new Exception("Source and destination accounts cannot be the same.");
            }

            $exchangeRate = (isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0)
                ? (float) $data['exchange_rate']
                : $this->currencyService->getExchangeRate($data['currency_id']);

            $attachmentPath = $transfer->attachment;
            if ($attachmentFile) {
                if ($attachmentPath) {
                    $this->deleteFile($attachmentPath, 's3');
                }
                $attachmentPath = $this->uploadUploadedFile($attachmentFile, 'fund_transfers', 's3');
            }

            $baseAmount = round($amount * $exchangeRate, 2);

            $transfer->update([
                'transfer_date'   => $transferDate,
                'from_account_id' => $data['from_account_id'],
                'to_account_id'   => $data['to_account_id'],
                'amount'          => $amount,
                'base_amount'     => $baseAmount,
                'branch_id'       => $data['branch_id'],
                'currency_id'     => $data['currency_id'],
                'exchange_rate'   => $exchangeRate,
                'payment_method'  => $data['payment_method'] ?? 'cash',
                'reference_no'    => $data['reference_no'] ?? null,
                'attachment'      => $attachmentPath,
                'note'            => $data['note'] ?? null,
                'updated_by'      => auth()->id(),
            ]);

            // Re-sync Contra Voucher
            $voucher = $this->accIntegration->syncFundTransfer($transfer);
            $transfer->update(['journal_voucher_id' => $voucher->id]);

            return $transfer->load('fromAccount', 'toAccount');
        });
    }

    /**
     * Delete Fund Transfer & Reverse Contra Voucher
     */
    public function deleteTransfer(FundTransfer $transfer, ?string $reason = null): void
    {
        DB::transaction(function () use ($transfer, $reason) {

            if ($transfer->journalVoucher && $transfer->journalVoucher->status === 'posted') {
                $this->journalService->reverse(
                    $transfer->journalVoucher,
                    $reason ?? 'Fund transfer cancelled/deleted by user'
                );
            }

            $transfer->updateQuietly([
                'status'             => 'cancelled',
                'journal_voucher_id' => null,
                'note'               => ($transfer->note ? $transfer->note . ' | ' : '') . 'Cancelled: ' . ($reason ?? 'Deleted by user'),
                'deleted_by'         => auth()->id(),
            ]);

            $transfer->delete();
        });
    }

    /**
     * Restore Soft-Deleted Transfer & Re-post Voucher
     */
    public function restoreTransfer(FundTransfer $transfer): FundTransfer
    {
        return DB::transaction(function () use ($transfer) {

            $voucher = $this->accIntegration->syncFundTransfer($transfer);

            $transfer->updateQuietly([
                'status'             => 'posted',
                'journal_voucher_id' => $voucher->id,
                'deleted_by'         => null,
            ]);

            return $transfer->load('fromAccount', 'toAccount');
        });
    }

    /**
     * Generate Unique Transfer Serial Number
     */
    protected function generateTransferNo(Carbon $date): string
    {
        $year = $date->format('Y');
        $prefix = "TRF-{$year}-";

        $lastTransfer = FundTransfer::withTrashed()
            ->where('transfer_no', 'like', "{$prefix}%")
            ->orderBy('transfer_no', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastTransfer && !empty($lastTransfer->transfer_no)) {
            if (preg_match('/TRF-\d{4}-(\d+)/', $lastTransfer->transfer_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
        }

        return sprintf('TRF-%s-%06d', $year, $nextNumber);
    }
}