<?php

namespace App\Services\Accounting;

use App\Enums\JournalVoucherStatus;
use App\Enums\JournalVoucherType;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\JournalVoucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OpeningBalanceService
{
    protected JournalService $journalService;

    protected PostingService $postingService;

    public function __construct(
        JournalService $journalService,
        PostingService $postingService
    ) {
        $this->journalService = $journalService;
        $this->postingService = $postingService;
    }

    /**
     * Create Opening Balance.
     *
     * @throws AccountingException
     */
    public function store(array $data): JournalVoucher
    {
        return DB::transaction(function () use ($data) {
            /*
            |--------------------------------------------------------------------------
            | Validate Request
            |--------------------------------------------------------------------------
            */
            $this->validateOpeningBalance($data);
            /*
            |--------------------------------------------------------------------------
            | Ensure Opening Balance Doesn't Exist
            |--------------------------------------------------------------------------
            */
            $this->ensureOpeningBalanceNotExists($data);
            /*
            |--------------------------------------------------------------------------
            | Create Draft Voucher
            |--------------------------------------------------------------------------
            */
            $voucher = $this->createOpeningVoucher($data);
            /*
            |--------------------------------------------------------------------------
            | Auto Post
            |--------------------------------------------------------------------------
            */
            $this->postingService->post($voucher);

            return $voucher->fresh([
                'entries.account.chartOfAccount',
                'branch',
                'currency',
                'fiscalYear',
                'accountingPeriod',
            ]);

        });
    }

    /**
     * Reverse Opening Balance.
     *
     * @throws AccountingException
     */
    public function reverse(
        JournalVoucher $voucher,
        ?string $reason = null
    ): JournalVoucher {

        /*
        |--------------------------------------------------------------------------
        | Validate Voucher Type
        |--------------------------------------------------------------------------
        */

        if ($voucher->voucher_type !== JournalVoucherType::OPENING) {
            throw AccountingException::invalidOpeningVoucher();
        }

        $voucher->loadMissing([
            'entries.account.chartOfAccount',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Reverse Voucher
        |--------------------------------------------------------------------------
        */

        return $this->postingService->reverse(
            $voucher,
            $reason
        );
    }

    /**
     * Validate Opening Balance data.
     *
     * @throws AccountingException
     */
    protected function validateOpeningBalance(array $data): void
    {
        $entries = collect($data['entries'] ?? []);

        if ($entries->isEmpty()) {
            throw AccountingException::voucherHasNoEntries();
        }

        $totalDebit = 0;
        $totalCredit = 0;

        $usedAccounts = [];

        foreach ($entries as $index => $entry) {

            $lineNo = $index + 1;

            $debit = (float) ($entry['debit'] ?? 0);
            $credit = (float) ($entry['credit'] ?? 0);

            /*
            |--------------------------------------------------------------------------
            | Account
            |--------------------------------------------------------------------------
            */

            $account = Account::query()

                ->with('chartOfAccount')

                ->find($entry['account_id'] ?? null);

            if (! $account) {
                throw AccountingException::accountNotFound($lineNo);
            }

            if (! $account->chartOfAccount) {
                throw AccountingException::chartOfAccountMissing(
                    $account->account_name
                );
            }

            if (! $account->is_active) {
                throw AccountingException::inactiveAccount(
                    $account->account_name
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate Account
            |--------------------------------------------------------------------------
            */

            if (isset($usedAccounts[$account->id])) {

                throw AccountingException::duplicateAccount(
                    $account->account_name
                );

            }

            $usedAccounts[$account->id] = true;

            /*
            |--------------------------------------------------------------------------
            | Debit / Credit Validation
            |--------------------------------------------------------------------------
            */

            if ($debit > 0 && $credit > 0) {
                throw AccountingException::invalidDebitCredit($lineNo);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw AccountingException::emptyJournalLine($lineNo);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        /*
        |--------------------------------------------------------------------------
        | Balance Check
        |--------------------------------------------------------------------------
        */

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw AccountingException::voucherNotBalanced();
        }

        /*
        |--------------------------------------------------------------------------
        | Zero Amount
        |--------------------------------------------------------------------------
        */

        if ($totalDebit <= 0) {
            throw AccountingException::voucherAmountIsZero();
        }
    }

    /**
     * Ensure opening balance does not already exist.
     *
     * @throws AccountingException
     */
    protected function ensureOpeningBalanceNotExists(array $data): void
    {
        $period = $this->journalService->getAccountingPeriod(
            Carbon::parse($data['voucher_date'])
        );

        $exists = JournalVoucher::query()

            ->where('voucher_type', JournalVoucherType::OPENING)

            ->where('branch_id', $data['branch_id'])

            ->where('fiscal_year_id', $period['fiscal_year_id'])

            ->where('status', '!=', JournalVoucherStatus::REVERSED)

            ->exists();

        if ($exists) {
            throw AccountingException::openingBalanceAlreadyExists();
        }
    }

    /**
     * Create opening balance journal voucher.
     */
    protected function createOpeningVoucher(array $data): JournalVoucher
    {
        $voucherData = [
            'voucher_type' => JournalVoucherType::OPENING,
            'voucher_date' => $data['voucher_date'],
            'branch_id' => $data['branch_id'],
            'currency_id' => $data['currency_id'],
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'reference_no' => $data['reference_no'] ?? null,
            'narration' => $data['narration'] ?? 'Opening Balance',
            'entries' => $this->prepareJournalEntries($data),
        ];

        return $this->journalService->create($voucherData);
    }

    /**
     * Prepare journal entries.
     */
    protected function prepareJournalEntries(array $data): array
    {
        $entries = [];

        foreach ($data['entries'] as $index => $entry) {

            $entries[] = [

                'line_no' => $index + 1,

                'account_id' => (int) $entry['account_id'],

                'debit' => (float) ($entry['debit'] ?? 0),

                'credit' => (float) ($entry['credit'] ?? 0),

                'reference_no' => $entry['reference_no'] ?? null,

                'description' => $entry['description'] ?? null,

            ];
        }

        return $entries;
    }
}
