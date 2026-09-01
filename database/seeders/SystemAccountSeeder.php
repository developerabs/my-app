<?php

namespace Database\Seeders;

use App\Enums\LedgerAccountType;
use App\Models\Account;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class SystemAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ChartOfAccount::where('is_leaf', true)->get() as $coa) {
            Account::firstOrCreate(
                [
                    'chart_of_account_id' => $coa->id,
                ],
                [
                    'account_name' => $coa->name,
                    'account_code' => $coa->code,
                    'account_type' => $this->resolveAccountType($coa),
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                    'is_system' => true,
                    'source_from' => 'system',
                ]
            );
        }
    }

    protected function resolveAccountType(
        ChartOfAccount $coa
    ): LedgerAccountType {

        return match (true) {

            /*
            |--------------------------------------------------------------------------
            | Cash & Cash Equivalents
            |--------------------------------------------------------------------------
            */
            $coa->code === '1111' => LedgerAccountType::CASH,
            $coa->code === '1112' => LedgerAccountType::BANK,
            $coa->code === '1113' => LedgerAccountType::MOBILE,
            /*
            |--------------------------------------------------------------------------
            | Receivable / Payable
            |--------------------------------------------------------------------------
            */
            $coa->code === '1120' => LedgerAccountType::RECEIVABLE,
            $coa->code === '2110' => LedgerAccountType::PAYABLE,

            $coa->code === '4650' => LedgerAccountType::FOREX_GAIN,
            $coa->code === '6830' => LedgerAccountType::FOREX_LOSS,
            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */
            str_starts_with($coa->code, '113') => LedgerAccountType::INVENTORY,
            /*
            |--------------------------------------------------------------------------
            | Fixed Assets
            |--------------------------------------------------------------------------
            */
            str_starts_with($coa->code, '12') => LedgerAccountType::FIXED_ASSET,
            /*
            |--------------------------------------------------------------------------
            | Equity
            |--------------------------------------------------------------------------
            */
            str_starts_with($coa->code, '3') => LedgerAccountType::EQUITY,
            /*
            |--------------------------------------------------------------------------
            | Sales / Income (4 Series)
            |--------------------------------------------------------------------------
            */
            str_starts_with($coa->code, '41'),
            in_array($coa->code, [
                '4200',
                '4300',
                '4400',
            ], true) => LedgerAccountType::SALES,

            // 💡 ৪ সিরিজের অন্যান্য সব আয় (যেমন 4600) INCOME হিসেবে ম্যাপিং হবে
            str_starts_with($coa->code, '4') => LedgerAccountType::INCOME,

            /*
            |--------------------------------------------------------------------------
            | Cost of Goods Sold (COGS)
            |--------------------------------------------------------------------------
            */
            str_starts_with($coa->code, '5') => LedgerAccountType::COGS,
            /*
            |--------------------------------------------------------------------------
            | Operating Expenses
            |--------------------------------------------------------------------------
            */
            str_starts_with($coa->code, '6') => LedgerAccountType::EXPENSE,
            default => LedgerAccountType::OTHER,
        };
    }
}