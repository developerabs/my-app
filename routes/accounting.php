<?php

use App\Http\Controllers\Accounting\AccountsController;
use App\Http\Controllers\Accounting\BalanceSheetController;
use App\Http\Controllers\Accounting\BillController;
use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\FinanceChargeController;
use App\Http\Controllers\Accounting\FundTransferController;
use App\Http\Controllers\Accounting\LedgerController;
use App\Http\Controllers\Accounting\OpeningBalanceController;
use App\Http\Controllers\Accounting\ProfitAndLossController;
use App\Http\Controllers\Accounting\SupplierPaymentController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('accounting')->group(function () {
    Route::controller(AccountsController::class)->prefix('ledger-accounts')->middleware('check_active:accounts_active')->group(function () {
        Route::get('/', 'index')->middleware('permission:acc_accounts_view')->name('accounts.index');
        Route::post('/', 'store')->middleware(['permission:account_create', 'check_limit:accounts_limit,Accounts'])->name('accounts.store');
        Route::get('/edit/{account}', 'edit')->middleware('permission:account_update')->name('accounts.edit');
        Route::patch('/update/{account}', 'update')->middleware('permission:account_update')->name('accounts.update');
        Route::delete('/delete/{account}', 'destroy')->middleware('permission:account_delete')->name('accounts.destroy');
    });

    Route::controller(OpeningBalanceController::class)->prefix('opening-balances')->middleware('permission:acc_opening_balance_manage')->group(function(){
        Route::get('/', 'index')->name('opening-balances.index');
        Route::get('/create', 'create')->name('opening-balances.create');
        Route::post('/', 'store')->name('opening-balances.store');
        Route::post('/{voucher}/reverse', 'reverse')->name('opening-balances.reverse');
    });

    Route::controller(ExpenseController::class)->prefix('expenses')->middleware('check_active:expenses_active')->group(function(){
        Route::get('/', 'index')->middleware('permission:expenses_view')->name('expenses.index');
        Route::get('/create', 'create')->middleware('permission:expenses_create')->name('expenses.create');
        Route::post('/store', 'store')->middleware('permission:expenses_create')->name('expenses.store');
        Route::get('/{expense}/show', 'show')->middleware('permission:expenses_view')->name('expenses.show');
        Route::get('/{expense}/edit', 'edit')->middleware('permission:expenses_update')->name('expenses.edit');
        Route::patch('/{expense}/update', 'update')->middleware('permission:expenses_update')->name('expenses.update');
        Route::delete('/{expense}/delete', 'destroy')->middleware('permission:expenses_delete')->name('expenses.destroy');
    });

    Route::controller(BillController::class)->prefix('bills')->middleware('check_active:bills_active')->group(function(){
        Route::get('/', 'index')->middleware('permission:bill_view')->name('bills.index');
        Route::get('/create', 'create')->middleware('permission:bill_create')->name('bills.create');
        Route::post('/store', 'store')->middleware(['permission:bill_create', 'check_limit:bills_limit,Bill'])->name('bills.store');
        Route::get('/{bill}/show', 'show')->middleware('permission:bill_view')->name('bills.show');
        Route::get('/{bill}/edit', 'edit')->middleware('permission:bill_update')->name('bills.edit');
        Route::patch('/{bill}/update', 'update')->middleware('permission:bill_update')->name('bills.update');
        Route::delete('/{bill}/delete', 'destroy')->middleware('permission:bill_delete')->name('bills.destroy');
        Route::post('/{bill}/pay', 'pay')->middleware('permission:bill_payment')->name('bills.pay');
    });

    Route::controller(FinanceChargeController::class)->prefix('finance-charges')->middleware('check_active:finance_charges_active')->group(function(){
        Route::post('/{financeCharge}/waive', 'waive')->middleware('permission:acc_finance_charge_waive')->name('finance-charges.waive');
        Route::post('/waive-document', 'waiveByDocument')->middleware('permission:acc_finance_charge_waive')->name('finance-charges.waive-document');
    });

    Route::controller(FundTransferController::class)->prefix('fund-transfer')->middleware('check_active:fund_transfers_active')->group(function(){
        Route::get('/', 'index')->middleware('permission:acc_transfer_view')->name('fund-transfers.index');
        Route::post('/', 'store')->middleware('permission:acc_transfer_create')->name('fund-transfers.store');
        Route::get('/{fundTransfer}', 'show')->middleware('permission:acc_transfer_view')->name('fund-transfers.show');
        Route::get('/{fundTransfer}/edit', 'edit')->middleware('permission:acc_transfer_update')->name('fund-transfers.edit');
        Route::match(['put', 'patch'], '/{fundTransfer}', 'update')->middleware('permission:acc_transfer_update')->name('fund-transfers.update');
        Route::delete('/{fundTransfer}', 'destroy')->middleware('permission:acc_transfer_delete')->name('fund-transfers.destroy');
    });

    Route::controller(SupplierPaymentController::class)->prefix('supplier-payments')->middleware('check_active:payments_active')->group(function(){
        Route::get('/open-invoices/{supplier}', 'getOpenInvoices')->name('supplier-payments.open-invoices');
        Route::get('/', 'index')->name('supplier-payments.index');
        Route::get('/create', 'create')->name('supplier-payments.create');
        Route::post('/store', 'store')->name('supplier-payments.store');
    });


    Route::prefix('reports')->group(function(){
        Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])->middleware(['check_active:balance_sheet_active', 'permission:acc_report_balance_sheet'])->name('reports.balance-sheet');
        Route::get('/ledger/{account_id}', [LedgerController::class, 'index'])->middleware(['check_active:ledger_active', 'permission:acc_ledger_view'])->name('reports.ledger');
        Route::get('/ledger/{account_id}/sub-ledger/{sub_ledger_id}', [LedgerController::class, 'subLedgerIndex'])->middleware(['check_active:ledger_active', 'permission:acc_ledger_view'])->name('reports.subledger');
        Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->middleware(['check_active:trial_balance_active', 'permission:acc_trial_balance_view'])->name('reports.trial-balance');
        Route::get('/profit-loss', [ProfitAndLossController::class, 'index'])->middleware(['check_active:profit_loss_active', 'permission:acc_profit_loss_view'])->name('reports.profit-loss');
    });
});
