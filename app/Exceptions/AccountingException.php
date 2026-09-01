<?php

namespace App\Exceptions;

use Exception;

class AccountingException extends Exception
{
    public static function voucherAlreadyPosted(): self
    {
        return new self('This voucher has already been posted.');
    }

    public static function voucherAlreadyReversed(): self
    {
        return new self('This voucher has already been reversed.');
    }

    public static function onlyDraftCanBePosted(): self
    {
        return new self('Only draft vouchers can be posted.');
    }

    public static function voucherHasNoEntries(): self
    {
        return new self('Voucher has no journal entries.');
    }

    public static function voucherNotBalanced(): self
    {
        return new self('Total debit and total credit are not equal.');
    }

    public static function voucherAmountIsZero(): self
    {
        return new self('Voucher amount must be greater than zero.');
    }

    public static function fiscalYearClosed(): self
    {
        return new self('Fiscal year is closed.');
    }

    public static function accountingPeriodClosed(): self
    {
        return new self('Accounting period is closed.');
    }

    public static function accountNotFound(int $lineNo): self
    {
        return new self("Account not found for line {$lineNo}.");
    }

    public static function chartOfAccountMissing(string $account): self
    {
        return new self("Chart of Account not found for account '{$account}'.");
    }

    public static function inactiveAccount(string $account): self
    {
        return new self("Account '{$account}' is inactive.");
    }

    public static function invalidDebitCredit(int $lineNo): self
    {
        return new self("Line {$lineNo} cannot contain both debit and credit.");
    }

    public static function emptyJournalLine(int $lineNo): self
    {
        return new self("Line {$lineNo} has no debit or credit amount.");
    }

    public static function onlyPostedCanBeReversed(): self
    {
        return new self('Only posted vouchers can be reversed.');
    }

    public static function openingBalanceAlreadyExists(): self
    {
        return new self(
            'Opening balance has already been created for this branch and fiscal year.'
        );
    }

    public static function invalidOpeningVoucher(): self
    {
        return new self(
            'Only opening balance voucher can be reversed.'
        );
    }

    public static function duplicateAccount(
        string $accountName
    ): self {

        return new self(
            "Account '{$accountName}' is entered more than once."
        );

    }

    public static function subLedgerRequired(
        int|string $lineNo,
        string $accountName
    ): self {
        return new self(
            "Sub Ledger is required for account '{$accountName}' (Line: {$lineNo})."
        );
    }
}
