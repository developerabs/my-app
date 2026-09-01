<?php

namespace App\Services\Accounting;

use App\Enums\LedgerAccountType;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\Setting;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AccountingService
{
    public function createFiscalYear(array $data): FiscalYear
    {
        $startDate = Carbon::createFromFormat('M-Y', $data['fiscal_start_from'])->startOfMonth();

        $endDate = $startDate->copy()
            ->addYear()
            ->subDay();

        $startYear = $startDate->format('y');
        $endYear = $endDate->format('y');
        $code = "FY{$startYear}{$endYear}";
        $fiscalYear = FiscalYear::create([
            'name' => $data['fiscal_year_name'],
            'code' => $code,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => 'current',
            'allow_adjustment_entries' => false,
            'created_by' => Auth::id(),
        ]);
        $currentPeriod = $data['current_period'];
        $this->createAccountingPeriods($fiscalYear, $currentPeriod);

        return $fiscalYear;
    }

    protected function createAccountingPeriods(
        FiscalYear $fiscalYear,
        int $currentPeriod
    ): void {

        $start = Carbon::parse($fiscalYear->start_date);
        /*
        |--------------------------------------------------------------------------
        | Fiscal Month Order
        |--------------------------------------------------------------------------
        */
        $months = [];
        for ($m = 0; $m < 12; $m++) {
            $months[] = $start
                ->copy()
                ->addMonths($m)
                ->month;
        }
        /*
        |--------------------------------------------------------------------------
        | Month => Index Mapping
        |--------------------------------------------------------------------------
        */
        $monthIndexes = array_flip($months);
        if (! isset($monthIndexes[$currentPeriod])) {
            throw new \InvalidArgumentException(
                'Invalid current accounting period.'
            );
        }
        $currentIndex = $monthIndexes[$currentPeriod];
        /*
        |--------------------------------------------------------------------------
        | Create Accounting Periods
        |--------------------------------------------------------------------------
        */

        for ($i = 1; $i <= 12; $i++) {
            $periodStart = $start
                ->copy()
                ->addMonths($i - 1)
                ->startOfMonth();

            $periodEnd = $periodStart
                ->copy()
                ->endOfMonth();

            $periodIndex = $monthIndexes[$periodStart->month];

            $status = match (true) {
                $periodIndex < $currentIndex => 'closed',
                $periodIndex === $currentIndex => 'current',
                default => 'upcoming',
            };

            AccountingPeriod::create([
                'fiscal_year_id' => $fiscalYear->id,
                'period_no' => $i,
                'name' => $periodStart->format('F Y'),
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'status' => $status,
                'created_by' => Auth::id(),
            ]);
        }
    }

    public function createLedgerAccount(array $data): Account
    {
        $coaCode = match ($data['account_type']) {
            'cash' => '1111',
            'bank' => '1112',
            'mobile' => '1113',
            'other' => '1110',
            default => '1111',
        };

        $coa = ChartOfAccount::where('code', $coaCode)->firstOrFail();
        $code = $this->genLedgerAccCode($coa);

        // 🟢 কারেন্সি এবং এক্সচেঞ্জ রেট নির্ধারণ
        $currencyId = $data['currency_id']
            ?? (! empty($data['branch_id']) ? Branch::find($data['branch_id'])?->currency_id : null)
            ?? Setting::get('default_currency');

        $currencyService = app(CurrencyConversionService::class);
        $exchangeRate = $currencyService->getExchangeRate($currencyId);

        $openingBalance = (float) ($data['opening_balance'] ?? 0);
        $baseOpeningBalance = round($openingBalance * $exchangeRate, 2);

        return Account::create([
            'chart_of_account_id' => $coa->id,
            'account_name' => $data['account_name'],
            'account_code' => $code,
            'account_type' => LedgerAccountType::from($data['account_type']),
            'account_number' => $data['account_number'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'branch_name' => $data['branch_name'] ?? null,
            'routing_number' => $data['routing_number'] ?? null,
            'opening_balance' => $openingBalance,
            'base_opening_balance' => $baseOpeningBalance, // 👈 Saved in BDT
            'opening_balance_date' => $data['opening_balance_date'] ?? null,
            'current_balance' => 0,
            'base_current_balance' => 0,
            'is_active' => true,
            'is_system' => false,
            'is_default' => $data['is_default'] ?? false,
            'branch_id' => $data['branch_id'] ?? null,
            'currency_id' => $currencyId,
            'source_from' => 'setup',
            'created_by' => Auth::id(),
        ]);
    }

    public function updateLedgerAccount(Account $account, array $data): Account
    {
        $coaId = $account->chart_of_account_id;
        $code = $account->account_code;

        if (isset($data['account_type']) && $data['account_type'] !== $account->account_type->value) {
            $coaCode = match ($data['account_type']) {
                'cash' => '1111',
                'bank' => '1112',
                'mobile' => '1113',
                'other' => '1110',
                default => '1111',
            };

            $coa = ChartOfAccount::where('code', $coaCode)->firstOrFail();
            $coaId = $coa->id;
            $code = $this->genLedgerAccCode($coa);
        }

        $currencyId = $data['currency_id'] ?? $account->currency_id ?? Setting::get('default_currency');
        $currencyService = app(CurrencyConversionService::class);
        $exchangeRate = $currencyService->getExchangeRate($currencyId);

        $openingBalance = isset($data['opening_balance']) ? (float) $data['opening_balance'] : (float) $account->opening_balance;
        $baseOpeningBalance = round($openingBalance * $exchangeRate, 2);

        $account->update([
            'chart_of_account_id' => $coaId,
            'account_name' => $data['account_name'] ?? $account->account_name,
            'account_code' => $code,
            'account_type' => isset($data['account_type']) ? LedgerAccountType::from($data['account_type']) : $account->account_type,
            'account_number' => $data['account_number'] ?? $account->account_number,
            'bank_name' => $data['bank_name'] ?? $account->bank_name,
            'branch_name' => $data['branch_name'] ?? $account->branch_name,
            'routing_number' => $data['routing_number'] ?? $account->routing_number,
            'opening_balance' => $openingBalance,
            'base_opening_balance' => $baseOpeningBalance, // 👈 Updated in BDT
            'opening_balance_date' => $data['opening_balance_date'] ?? $account->opening_balance_date,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? $account->is_default,
            'branch_id' => $data['branch_id'] ?? $account->branch_id,
            'currency_id' => $currencyId,
            'updated_by' => Auth::id(),
        ]);

        return $account->fresh();
    }

    protected function genLedgerAccCode(ChartOfAccount $coa)
    {
        $lastAccount = $coa->accounts()
            ->latest('account_code')
            ->first();

        $sequence = 1;
        if ($lastAccount) {
            $sequence = (int) substr($lastAccount->account_code, strlen($coa->code)) + 1;
        }

        return $coa->code.str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
