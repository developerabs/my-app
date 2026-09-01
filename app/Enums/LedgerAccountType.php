<?php

namespace App\Enums;

enum LedgerAccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case MOBILE = 'mobile';
    case RECEIVABLE = 'receivable';
    case PAYABLE = 'payable';
    case INVENTORY = 'inventory';
    case FIXED_ASSET = 'fixed_asset';

    case EQUITY = 'equity';

    case INCOME = 'income';
    case SALES = 'sales';
    case COGS = 'cogs';

    case EXPENSE = 'expense';
    case FOREX_GAIN = 'forex_gain';
    case FOREX_LOSS = 'forex_loss';
    case OTHER = 'other';

    public static function paymentAccounts(): array
    {
        return [
            self::CASH->value,
            self::BANK->value,
            self::MOBILE->value,
        ];
    }
}
