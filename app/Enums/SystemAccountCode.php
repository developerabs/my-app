<?php

namespace App\Enums;

enum SystemAccountCode: string
{
    // ==========================================
    // 1000 - ASSETS
    // ==========================================
    case CASH_IN_HAND            = '1111';
    case BANK_ACCOUNTS           = '1112';
    case MOBILE_WALLETS          = '1113';
    case ACCOUNTS_RECEIVABLE     = '1120';
    case MERCHANDISE_INVENTORY   = '1134';
    case INVENTORY_ADJUSTMENT    = '1135';
    case INVENTORY_IN_TRANSIT    = '1136';
    case TAX_RECEIVABLE          = '1160';
    case EMPLOYEE_ADVANCES       = '1150';
    case SUPPLIER_ADVANCES       = '1190';
    case ACCUMULATED_DEPRECIATION= '1280';

    // ==========================================
    // 2000 - LIABILITIES
    // ==========================================
    case ACCOUNTS_PAYABLE        = '2110';
    case ACCRUED_LIABILITIES     = '2120';
    case TAX_PAYABLE             = '2130';
    case CUSTOMER_ADVANCES       = '2180';

    // ==========================================
    // 3000 - EQUITY
    // ==========================================
    case RETAINED_EARNINGS       = '3200';
    case CURRENT_YEAR_PROFIT_LOSS= '3300';
    case OPENING_BALANCE_EQUITY  = '3600';

    // ==========================================
    // 4000 - REVENUE / INCOME
    // ==========================================
    case SALES_REVENUE           = '4110';
    case REALIZED_FOREX_GAIN     = '4650';
    case LATE_FEE_INCOME         = '4670';

    // ==========================================
    // 5000 - COST OF GOODS SOLD
    // ==========================================
    case COGS                    = '5100';
    case FREIGHT_INWARD          = '5150';

    // ==========================================
    // 6000 - EXPENSES
    // ==========================================
    case REALIZED_FOREX_LOSS     = '6830';
    case LATE_FEE_EXPENSE        = '6840';
    case LATE_FEE_DISCOUNT       = '6850';
    case ROUND_OFF_EXPENSE       = '6950';
}