<?php

namespace App\Enums;

enum JournalVoucherType: string
{
    case JOURNAL = 'journal';
    case PAYMENT = 'payment';
    case RECEIPT = 'receipt';
    case CONTRA = 'contra';
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case OPENING = 'opening';
    case CLOSING = 'closing';
    case ADJUSTMENT = 'adjustment';
    case DEPRECIATION = 'depreciation';
    case EXPENSE = 'expense';
    case FINANCE_CHARGE = 'finance_charge';

    /**
     * Voucher Prefix
     */
    public function prefix(): string
    {
        return match ($this) {
            self::JOURNAL => 'JV',
            self::PAYMENT => 'PV',
            self::RECEIPT => 'RV',
            self::CONTRA => 'CV',
            self::PURCHASE => 'PR',
            self::SALE => 'SV',
            self::OPENING => 'OB',
            self::CLOSING => 'CL',
            self::ADJUSTMENT => 'AJ',
            self::DEPRECIATION => 'DP',
            self::EXPENSE => 'EV',
            self::FINANCE_CHARGE => 'FC',
        };
    }

    /**
     * Voucher Display Name
     */
    public function label(): string
    {
        return match ($this) {
            self::JOURNAL => 'Journal Voucher',
            self::PAYMENT => 'Payment Voucher',
            self::RECEIPT => 'Receipt Voucher',
            self::CONTRA => 'Contra Voucher',
            self::PURCHASE => 'Purchase Voucher',
            self::SALE => 'Sales Voucher',
            self::OPENING => 'Opening Balance',
            self::CLOSING => 'Closing Voucher',
            self::ADJUSTMENT => 'Adjustment Voucher',
            self::DEPRECIATION => 'Depreciation Voucher',
            self::EXPENSE => 'Expense Voucher',
            self::FINANCE_CHARGE => 'Finance Charge Voucher',
        };
    }

    public function allowEdit(): bool
    {
        return ! in_array($this, [
            self::CLOSING,
        ]);
    }

    public function allowDelete(): bool
    {
        return ! in_array($this, [
            self::OPENING,
            self::CLOSING,
        ]);
    }

    public function isSystemVoucher(): bool
    {
        return in_array($this, [
            self::OPENING,
            self::CLOSING,
            self::DEPRECIATION,
            self::ADJUSTMENT,
            self::FINANCE_CHARGE,
        ]);
    }

    public function affectsCash(): bool
    {
        return in_array($this, [
            self::PAYMENT,
            self::RECEIPT,
            self::CONTRA,
            self::EXPENSE,
        ]);
    }

    public function requireReference(): bool
    {
        return in_array($this, [
            self::PAYMENT,
            self::RECEIPT,
            self::PURCHASE,
            self::SALE,
            self::FINANCE_CHARGE,
        ]);
    }

    public function icon(): string
    {
        return match ($this) {
            self::JOURNAL => 'ri-book-line',
            self::PAYMENT => 'ri-bank-card-line',
            self::RECEIPT => 'ri-money-dollar-circle-line',
            self::CONTRA => 'ri-exchange-line',
            self::PURCHASE => 'ri-shopping-cart-line',
            self::SALE => 'ri-store-line',
            self::OPENING => 'ri-door-open-line',
            self::CLOSING => 'ri-door-closed-line',
            self::ADJUSTMENT => 'ri-tools-line',
            self::DEPRECIATION => 'ri-line-chart-line',
            self::EXPENSE => 'ri-wallet-3-line',
            self::FINANCE_CHARGE => 'ri-percent-line',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::JOURNAL => 'primary',
            self::PAYMENT => 'danger',
            self::RECEIPT => 'success',
            self::CONTRA => 'info',
            self::PURCHASE => 'warning',
            self::SALE => 'success',
            self::OPENING => 'dark',
            self::CLOSING => 'secondary',
            self::ADJUSTMENT => 'primary',
            self::DEPRECIATION => 'warning',
            self::EXPENSE => 'danger',
            self::FINANCE_CHARGE => 'warning',
        };
    }
}