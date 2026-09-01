<?php

namespace App\Services\Accounting;

use App\Enums\AssetEntryType;
use App\Enums\BalanceType;
use App\Enums\JournalVoucherStatus;
use App\Enums\JournalVoucherType;
use App\Enums\LedgerAccountType;
use App\Enums\SystemAccountCode; // 👈 SystemAccountCode Enum Import
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetRegister;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FundTransfer;
use App\Models\JournalVoucher;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\CurrencyConversionService;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountingIntegrationService
{
    public function __construct(
        protected JournalService $journalService,
        protected PostingService $postingService
    ) {}

    public function hasActiveTransactions($model): bool
    {
        if ($model instanceof Account) {
            return DB::table('general_ledgers')
                ->where('account_id', $model->id)
                ->where('voucher_type', '!=', JournalVoucherType::OPENING->value)
                ->whereIn('status', ['posted', 'reversed'])
                ->exists();
        }

        if ($model instanceof Supplier || $model instanceof Customer) {
            return DB::table('general_ledgers')
                ->where('sub_ledger_id', (string) $model->id)
                ->where('sub_ledger_type', get_class($model))
                ->where('voucher_type', '!=', JournalVoucherType::OPENING->value)
                ->whereIn('status', ['posted', 'reversed'])
                ->exists();
        }

        if ($model instanceof Product) {
            return DB::table('general_ledgers')
                ->where('sourceable_type', Product::class)
                ->where('sourceable_id', $model->id)
                ->where('voucher_type', '!=', JournalVoucherType::OPENING->value)
                ->whereIn('status', ['posted', 'reversed'])
                ->exists();
        }

        if ($model instanceof Asset) {
            return DB::table('general_ledgers')
                ->where('account_id', $model->account_id)
                ->where('voucher_type', '!=', JournalVoucherType::OPENING->value)
                ->whereIn('status', ['posted', 'reversed'])
                ->exists();
        }

        return false;
    }

    public function syncExpense(Expense $expense, array $itemsData): JournalVoucher
    {
        $this->reverseExistingVoucher(Expense::class, $expense->id, JournalVoucherType::EXPENSE, 'expense update');

        $exchangeRate = (float) $expense->exchange_rate;
        $journalEntries = [];
        $totalAmount = 0;

        foreach ($itemsData as $item) {
            $amount = (float) $item['amount'];
            if ($amount <= 0) {
                continue;
            }

            $totalAmount += $amount;

            $journalEntries[] = [
                'account_id' => $item['expense_account_id'],
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'project_id' => $item['project_id'] ?? $expense->project_id ?? null,
                'debit' => $amount,
                'credit' => 0,
                'description' => $item['description'] ?? 'Direct expense item payment',
            ];
        }

        if ($totalAmount <= 0) {
            throw new Exception('Expense total amount must be greater than zero.');
        }

        $paymentAccount = Account::findOrFail($expense->payment_account_id);
        $journalEntries[] = [
            'account_id' => $paymentAccount->id,
            'sub_ledger_type' => null,
            'sub_ledger_id' => null,
            'project_id' => $expense->project_id ?? null,
            'debit' => 0,
            'credit' => $totalAmount,
            'description' => 'Payment for expenses from '.$paymentAccount->account_name,
        ];

        $voucherData = [
            'voucher_date' => $expense->expense_date->format('Y-m-d'),
            'voucher_type' => JournalVoucherType::EXPENSE,
            'branch_id' => $expense->branch_id,
            'project_id' => $expense->project_id ?? null,
            'currency_id' => $expense->currency_id,
            'exchange_rate' => $exchangeRate,
            'reference_no' => $expense->expense_no,
            'attachment' => $expense->attachment,
            'narration' => $expense->note ?? ('Direct Expense Payment: '.$expense->expense_no),
            'sourceable_type' => Expense::class,
            'sourceable_id' => $expense->id,
            'entries' => $journalEntries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);

        return $voucher;
    }

    public function syncAssetRegister(AssetRegister $register, array $itemsData, string $entryType): ?JournalVoucher
    {
        if ($entryType === AssetEntryType::PURCHASE->value || $entryType === 'purchase') {
            return null;
        }

        $this->reverseExistingVoucher(AssetRegister::class, $register->id, JournalVoucherType::OPENING, 'asset register update');

        $exchangeRate = (float) ($register->exchange_rate ?? 1);
        $assetIds = array_column($itemsData, 'asset_id');
        $assets = Asset::whereIn('id', $assetIds)->get()->keyBy('id');

        $totalCostSum = 0;
        $entries = [];

        foreach ($itemsData as $item) {
            $totalCost = (float) $item['total_cost'];
            $totalCostSum += $totalCost;

            $asset = $assets->get($item['asset_id']);
            if (! $asset) {
                throw new Exception("Asset with ID {$item['asset_id']} not found.");
            }

            $entries[] = [
                'account_id' => $asset->account_id,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'project_id' => $register->project_id ?? null,
                'debit' => $totalCost,
                'credit' => 0,
                'description' => 'Opening asset balance: '.$asset->asset_name,
            ];
        }

        if ($totalCostSum <= 0) {
            return null;
        }

        $openingEquityAccountId = $this->getAccountByCodeOrType(LedgerAccountType::EQUITY, SystemAccountCode::OPENING_BALANCE_EQUITY->value);

        $entries[] = [
            'account_id' => $openingEquityAccountId,
            'sub_ledger_type' => null,
            'sub_ledger_id' => null,
            'project_id' => $register->project_id ?? null,
            'debit' => 0,
            'credit' => $totalCostSum,
            'description' => 'Opening balance equity for asset register: '.$register->register_no,
        ];

        $voucherData = [
            'voucher_date' => $register->register_date->format('Y-m-d'),
            'voucher_type' => JournalVoucherType::OPENING,
            'branch_id' => $register->branch_id,
            'project_id' => $register->project_id ?? null,
            'currency_id' => $register->currency_id,
            'exchange_rate' => $exchangeRate,
            'reference_no' => $register->register_no,
            'narration' => 'Opening Asset Register Entry: '.$register->register_no,
            'sourceable_type' => AssetRegister::class,
            'sourceable_id' => $register->id,
            'entries' => $entries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);

        return $voucher;
    }

    public function syncAccountOpeningBalance(Account $account, float $amount, string $date): ?JournalVoucher
    {
        $this->reverseExistingVoucher(Account::class, $account->id, JournalVoucherType::OPENING, 'account update');

        if ($amount <= 0) {
            return null;
        }

        $account->loadMissing('chartOfAccount', 'currency', 'branch');
        $balanceType = $account->chartOfAccount?->balance_type;
        $isDebitNormal = ($balanceType === BalanceType::DEBIT || $balanceType?->value === 'debit');

        $debitAmount = $isDebitNormal ? $amount : 0;
        $creditAmount = $isDebitNormal ? 0 : $amount;

        $equityAccountId = $this->getAccountByCodeOrType(LedgerAccountType::EQUITY, SystemAccountCode::OPENING_BALANCE_EQUITY->value);

        $currencyId = $account->currency_id
            ?? $account->branch?->currency_id
            ?? Setting::get('default_currency');

        $currencyService = app(CurrencyConversionService::class);
        $exchangeRate = $currencyService->getExchangeRate($currencyId);

        return $this->createAndPostOpeningVoucher(
            date: $date,
            narration: 'Opening balance for '.$account->account_name.($account->currency ? " ({$account->currency->code})" : ''),
            sourceableType: Account::class,
            sourceableId: $account->id,
            entries: [
                [
                    'account_id' => $account->id,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => $debitAmount,
                    'credit' => $creditAmount,
                    'description' => 'Opening balance initialization',
                ],
                [
                    'account_id' => $equityAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => $creditAmount,
                    'credit' => $debitAmount,
                    'description' => 'Opening balance equity for '.$account->account_name,
                ],
            ],
            branchId: $account->branch_id,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate
        );
    }

    public function syncSupplierOpeningBalance(Supplier $supplier, float $amount, string $date): ?JournalVoucher
    {
        $this->reverseExistingVoucher(Supplier::class, $supplier->id, JournalVoucherType::OPENING, 'supplier update');
        if ($amount <= 0) return null;

        $payableAccountId = $this->getAccountByCodeOrType(LedgerAccountType::PAYABLE, SystemAccountCode::ACCOUNTS_PAYABLE->value);
        $equityAccountId  = $this->getAccountByCodeOrType(LedgerAccountType::EQUITY, SystemAccountCode::OPENING_BALANCE_EQUITY->value);

        return $this->createAndPostOpeningVoucher(
            date: $date,
            narration: 'Opening balance for supplier: ' . $supplier->name,
            sourceableType: Supplier::class,
            sourceableId: $supplier->id,
            entries: [
                [
                    'account_id'      => $equityAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id'   => null,
                    'debit'           => $amount,
                    'credit'          => 0,
                    'description'     => 'Opening balance equity for supplier ' . $supplier->name,
                ],
                [
                    'account_id'      => $payableAccountId,
                    'sub_ledger_type' => Supplier::class,
                    'sub_ledger_id'   => $supplier->id,
                    'debit'           => 0,
                    'credit'          => $amount,
                    'description'     => 'Opening balance payable for supplier ' . $supplier->name,
                ],
            ]
        );
    }

    public function syncCustomerOpeningBalance(Customer $customer, float $amount, string $date): ?JournalVoucher
    {
        $this->reverseExistingVoucher(Customer::class, $customer->id, JournalVoucherType::OPENING, 'customer update');

        if ($amount <= 0) {
            return null;
        }

        // 🟢 FIXED: AR Code 1120 & Equity 3600
        $receivableAccountId = $this->getAccountByCodeOrType(LedgerAccountType::RECEIVABLE, SystemAccountCode::ACCOUNTS_RECEIVABLE->value);
        $equityAccountId = $this->getAccountByCodeOrType(LedgerAccountType::EQUITY, SystemAccountCode::OPENING_BALANCE_EQUITY->value);

        return $this->createAndPostOpeningVoucher(
            date: $date,
            narration: 'Opening balance for customer: '.$customer->name,
            sourceableType: Customer::class,
            sourceableId: $customer->id,
            entries: [
                [
                    'account_id' => $receivableAccountId,
                    'sub_ledger_type' => Customer::class,
                    'sub_ledger_id' => $customer->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Opening balance receivable for customer '.$customer->name,
                ],
                [
                    'account_id' => $equityAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Opening balance equity for customer '.$customer->name,
                ],
            ]
        );
    }

    public function syncProductOpeningStock(Product $product, float $totalValuation, string $date, ?string $branchId = null): ?JournalVoucher
    {
        $this->reverseExistingVoucher(Product::class, $product->id, JournalVoucherType::OPENING, 'product stock update');

        if ($totalValuation <= 0) {
            return null;
        }

        $inventoryAccountId = $product->inventory_account_id
            ?? $this->getAccountByCodeOrType(LedgerAccountType::INVENTORY, SystemAccountCode::MERCHANDISE_INVENTORY->value);
        $equityAccountId = $this->getAccountByCodeOrType(LedgerAccountType::EQUITY, SystemAccountCode::OPENING_BALANCE_EQUITY->value);

        return $this->createAndPostOpeningVoucher(
            date: $date,
            narration: 'Opening stock valuation for product: '.$product->name,
            sourceableType: Product::class,
            sourceableId: $product->id,
            branchId: $branchId,
            entries: [
                [
                    'account_id' => $inventoryAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => round($totalValuation, 2),
                    'credit' => 0,
                    'description' => 'Opening stock valuation for '.$product->name,
                ],
                [
                    'account_id' => $equityAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => 0,
                    'credit' => round($totalValuation, 2),
                    'description' => 'Opening stock equity offset for '.$product->name,
                ],
            ]
        );
    }

    public function syncPurchase(Purchase $purchase, array $itemsData): JournalVoucher
    {
        $this->reverseExistingVoucher(Purchase::class, $purchase->id, JournalVoucherType::PURCHASE, 'purchase update');

        $exchangeRate = (float) $purchase->exchange_rate;
        $journalEntries = [];
        $inventoryAccountId = $this->getAccountByCodeOrType(LedgerAccountType::INVENTORY, SystemAccountCode::MERCHANDISE_INVENTORY->value);
        $payableAccountId = $this->getAccountByCodeOrType(LedgerAccountType::PAYABLE, SystemAccountCode::ACCOUNTS_PAYABLE->value);

        $itemsNetBaseValue = (float) ($purchase->base_subtotal_amount - $purchase->base_order_discount_amount);
        $journalEntries[] = [
            'account_id' => $inventoryAccountId,
            'sub_ledger_type' => null,
            'sub_ledger_id' => null,
            'project_id' => $purchase->project_id ?? null,
            'debit' => round($itemsNetBaseValue / $exchangeRate, 2),
            'credit' => 0,
            'description' => 'Merchandise goods cost for purchase: '.$purchase->purchase_no,
        ];

        if ((float) $purchase->shipping_cost > 0) {
            $journalEntries[] = [
                'account_id' => $inventoryAccountId,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'project_id' => $purchase->project_id ?? null,
                'debit' => (float) $purchase->shipping_cost,
                'credit' => 0,
                'description' => 'Freight inward & shipping cost capitalized for purchase: '.$purchase->purchase_no,
            ];
        }

        if ((float) $purchase->other_expenses > 0) {
            $journalEntries[] = [
                'account_id' => $inventoryAccountId,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'project_id' => $purchase->project_id ?? null,
                'debit' => (float) $purchase->other_expenses,
                'credit' => 0,
                'description' => 'Handling & customs overheads capitalized for purchase: '.$purchase->purchase_no,
            ];
        }

        if ((float) $purchase->order_tax_amount > 0) {
            $taxAccountId = $this->getAccountByCodeOrType(LedgerAccountType::OTHER, SystemAccountCode::TAX_RECEIVABLE->value);
            $journalEntries[] = [
                'account_id' => $taxAccountId,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'project_id' => $purchase->project_id ?? null,
                'debit' => (float) $purchase->order_tax_amount,
                'credit' => 0,
                'description' => 'Input Tax/VAT receivable on purchase: '.$purchase->purchase_no,
            ];
        }

        if ((float) $purchase->round_off != 0) {
            $roundOffAmount = (float) $purchase->round_off;
            $roundOffAccountId = $this->getAccountByCodeOrType(LedgerAccountType::EXPENSE, SystemAccountCode::ROUND_OFF_EXPENSE->value);
            $journalEntries[] = [
                'account_id' => $roundOffAccountId,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'project_id' => $purchase->project_id ?? null,
                'debit' => $roundOffAmount > 0 ? $roundOffAmount : 0,
                'credit' => $roundOffAmount < 0 ? abs($roundOffAmount) : 0,
                'description' => 'Round off adjustment for purchase: '.$purchase->purchase_no,
            ];
        }

        $hasThirdPartyCarrier = ! empty($purchase->shipping_carrier_id) && ((float) $purchase->shipping_cost > 0);

        if ($hasThirdPartyCarrier) {
            $mainSupplierAmount = (float) ($purchase->total_amount - $purchase->shipping_cost);

            $journalEntries[] = [
                'account_id' => $payableAccountId,
                'sub_ledger_type' => Supplier::class,
                'sub_ledger_id' => $purchase->supplier_id,
                'project_id' => $purchase->project_id ?? null,
                'debit' => 0,
                'credit' => $mainSupplierAmount,
                'description' => 'Payable to supplier for goods: '.$purchase->purchase_no,
            ];

            $journalEntries[] = [
                'account_id' => $payableAccountId,
                'sub_ledger_type' => Supplier::class,
                'sub_ledger_id' => $purchase->shipping_carrier_id,
                'project_id' => $purchase->project_id ?? null,
                'debit' => 0,
                'credit' => (float) $purchase->shipping_cost,
                'description' => 'Freight payable to carrier for purchase: '.$purchase->purchase_no,
            ];
        } else {
            $journalEntries[] = [
                'account_id' => $payableAccountId,
                'sub_ledger_type' => Supplier::class,
                'sub_ledger_id' => $purchase->supplier_id,
                'project_id' => $purchase->project_id ?? null,
                'debit' => 0,
                'credit' => (float) $purchase->total_amount,
                'description' => 'Accounts payable for purchase invoice: '.$purchase->purchase_no,
            ];
        }

        $voucherData = [
            'voucher_date' => $purchase->purchase_date->format('Y-m-d'),
            'voucher_type' => JournalVoucherType::PURCHASE,
            'branch_id' => $purchase->branch_id,
            'project_id' => $purchase->project_id ?? null,
            'currency_id' => $purchase->currency_id,
            'exchange_rate' => $exchangeRate,
            'reference_no' => $purchase->purchase_no,
            'attachment' => $purchase->document,
            'narration' => $purchase->note ?? ('Purchase Invoice Booking: '.$purchase->purchase_no),
            'sourceable_type' => Purchase::class,
            'sourceable_id' => $purchase->id,
            'entries' => $journalEntries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);

        return $voucher;
    }

     public function syncVendorBill(Bill $bill, array $itemsData): JournalVoucher
    {
        $this->reverseExistingVoucher(Bill::class, $bill->id, JournalVoucherType::PURCHASE, 'bill update');

        $exchangeRate = (float) $bill->exchange_rate;
        $journalEntries = [];
        $totalAmount = 0;

        foreach ($itemsData as $item) {
            $amount = (float) $item['amount'];
            if ($amount <= 0) continue;

            $totalAmount += $amount;

            $journalEntries[] = [
                'account_id'      => $item['expense_account_id'],
                'sub_ledger_type' => null,
                'sub_ledger_id'   => null,
                'project_id'      => $item['project_id'] ?? $bill->project_id ?? null,
                'debit'           => $amount,
                'credit'          => 0,
                'description'     => $item['description'] ?? 'Vendor bill expense line',
            ];
        }

        if ($totalAmount <= 0) {
            throw new Exception('Bill total amount must be greater than zero.');
        }

        $payableAccountId = $this->getAccountByCodeOrType(LedgerAccountType::PAYABLE, SystemAccountCode::ACCOUNTS_PAYABLE->value);
        $journalEntries[] = [
            'account_id'      => $payableAccountId,
            'sub_ledger_type' => Supplier::class,
            'sub_ledger_id'   => $bill->supplier_id,
            'project_id'      => $bill->project_id ?? null,
            'debit'           => 0,
            'credit'          => $totalAmount,
            'description'     => 'Vendor bill payable for invoice ' . ($bill->vendor_invoice_no ?? $bill->bill_no),
        ];

        $voucherData = [
            'voucher_date'    => $bill->bill_date->format('Y-m-d'),
            'voucher_type'    => JournalVoucherType::PURCHASE,
            'branch_id'       => $bill->branch_id,
            'project_id'      => $bill->project_id ?? null,
            'currency_id'     => $bill->currency_id,
            'exchange_rate'   => $exchangeRate,
            'reference_no'    => $bill->bill_no,
            'attachment'      => $bill->attachment,
            'narration'       => $bill->note ?? ('Vendor Bill Entry: ' . $bill->bill_no),
            'sourceable_type' => Bill::class,
            'sourceable_id'   => $bill->id,
            'entries'         => $journalEntries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);

        return $voucher;
    }

    /**
     * Sync Supplier Bill Settlement Payment Voucher with IAS 21 Realized Forex Gain/Loss
     */
    public function syncSupplierPayment(SupplierPayment $payment): JournalVoucher
    {
        $payment->loadMissing(['paymentAccount', 'payable']);
        $exchangeRate = (float) $payment->exchange_rate;
        $payableAccountId = $this->getAccountByCodeOrType(LedgerAccountType::PAYABLE, SystemAccountCode::ACCOUNTS_PAYABLE->value ?? '2110');
        $paymentAmount = (float) $payment->amount;

        $journalEntries = [
            [
                'account_id' => $payableAccountId,
                'sub_ledger_type' => Supplier::class,
                'sub_ledger_id' => $payment->supplier_id,
                'project_id' => $payment->payable?->project_id ?? null,
                'debit' => $paymentAmount,
                'credit' => 0,
                'description' => 'Supplier payment for bill settlement: '.$payment->payment_no,
            ],
            [
                'account_id' => $payment->payment_account_id,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'project_id' => null,
                'debit' => 0,
                'credit' => $paymentAmount,
                'description' => 'Disbursement from '.($payment->paymentAccount->account_name ?? 'Payment Account'),
            ],
        ];

        $voucherData = [
            'voucher_date' => $payment->payment_date->format('Y-m-d'),
            'voucher_type' => JournalVoucherType::PAYMENT,
            'branch_id' => $payment->branch_id,
            'currency_id' => $payment->currency_id,
            'exchange_rate' => $exchangeRate,
            'reference_no' => $payment->payment_no,
            'attachment' => $payment->attachment,
            'narration' => $payment->note ?? ('Supplier Payment: '.$payment->payment_no),
            'sourceable_type' => SupplierPayment::class,
            'sourceable_id' => $payment->id,
            'entries' => $journalEntries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);

        // 🟢 আইএএস ২১ অনুযায়ী রেটের পার্থক্যে লাভ/ক্ষতি স্বয়ংক্রিয় এন্ট্রি
        $this->handleForexRealizationOnPayment($payment, $exchangeRate);

        return $voucher;
    }

    /**
     * IAS 21 Realized Foreign Exchange Gain/Loss Handler
     */
    protected function handleForexRealizationOnPayment(SupplierPayment $payment, float $paymentRate): void
    {
        $doc = $payment->payable; // Can be Bill or Purchase
        if (! $doc || empty($doc->exchange_rate)) {
            return;
        }

        $bookedRate = (float) $doc->exchange_rate;

        // If booking rate and payment rate are identical, no gain/loss
        if (abs($bookedRate - $paymentRate) < 0.00000001) {
            return;
        }

        $baseCurrencyId = Setting::get('default_currency');
        $amount = (float) $payment->amount;

        $bookedBaseAmount = round($amount * $bookedRate, 2);
        $paidBaseAmount = round($amount * $paymentRate, 2);
        $diff = round($paidBaseAmount - $bookedBaseAmount, 2); // Positive = Loss (Paid more), Negative = Gain (Paid less)

        if (abs($diff) <= 0.01) {
            return;
        }

        $payableAccountId = $this->getAccountByCodeOrType(LedgerAccountType::PAYABLE, SystemAccountCode::ACCOUNTS_PAYABLE->value);
        $forexEntries = [];

        if ($diff > 0) {
            // 🔴 Realized Forex Loss: We paid more base currency than booked liability
            $forexLossAccountId = $this->getAccountByCodeOrType(LedgerAccountType::EXPENSE, SystemAccountCode::REALIZED_FOREX_LOSS->value);

            $forexEntries = [
                [
                    'account_id' => $forexLossAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => $diff,
                    'credit' => 0,
                    'description' => "Realized Forex Loss on payment {$payment->payment_no} for ".($doc->bill_no ?? $doc->purchase_no),
                ],
                [
                    'account_id' => $payableAccountId,
                    'sub_ledger_type' => Supplier::class,
                    'sub_ledger_id' => $payment->supplier_id,
                    'debit' => 0,
                    'credit' => $diff,
                    'description' => 'AP adjustment for FX loss on settlement of '.($doc->bill_no ?? $doc->purchase_no),
                ],
            ];
        } else {
            // 🟢 Realized Forex Gain: We paid less base currency than booked liability
            $gainAmount = abs($diff);
            $forexGainAccountId = $this->getAccountByCodeOrType(LedgerAccountType::INCOME, SystemAccountCode::REALIZED_FOREX_GAIN->value);

            $forexEntries = [
                [
                    'account_id' => $payableAccountId,
                    'sub_ledger_type' => Supplier::class,
                    'sub_ledger_id' => $payment->supplier_id,
                    'debit' => $gainAmount,
                    'credit' => 0,
                    'description' => 'AP adjustment for FX gain on settlement of '.($doc->bill_no ?? $doc->purchase_no),
                ],
                [
                    'account_id' => $forexGainAccountId,
                    'sub_ledger_type' => null,
                    'sub_ledger_id' => null,
                    'debit' => 0,
                    'credit' => $gainAmount,
                    'description' => "Realized Forex Gain on payment {$payment->payment_no} for ".($doc->bill_no ?? $doc->purchase_no),
                ],
            ];
        }

        // Post Forex Adjustment Voucher in Base Currency (Rate = 1.0)
        $forexVoucherData = [
            'voucher_date' => $payment->payment_date->format('Y-m-d'),
            'voucher_type' => JournalVoucherType::ADJUSTMENT,
            'branch_id' => $payment->branch_id,
            'currency_id' => $baseCurrencyId,
            'exchange_rate' => 1.00000000,
            'reference_no' => $payment->payment_no.'-FX',
            'narration' => 'IAS 21 Realized FX Difference for '.($doc->bill_no ?? $doc->purchase_no),
            'sourceable_type' => SupplierPayment::class,
            'sourceable_id' => $payment->id,
            'entries' => $forexEntries,
        ];

        $fxVoucher = $this->journalService->create($forexVoucherData);
        $this->postingService->post($fxVoucher);
    }

    public function syncFundTransfer(FundTransfer $transfer): JournalVoucher
    {
        $transfer->loadMissing(['fromAccount', 'toAccount']);

        $this->reverseExistingVoucher(FundTransfer::class, $transfer->id, JournalVoucherType::CONTRA, 'fund transfer update');

        $exchangeRate = (float) $transfer->exchange_rate;
        $amount = (float) $transfer->amount;

        $journalEntries = [
            [
                'account_id' => $transfer->to_account_id,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Internal transfer received from '.($transfer->fromAccount->account_name ?? 'Source Account'),
            ],
            [
                'account_id' => $transfer->from_account_id,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Internal transfer sent to '.($transfer->toAccount->account_name ?? 'Destination Account'),
            ],
        ];

        $voucherData = [
            'voucher_date' => $transfer->transfer_date->format('Y-m-d'),
            'voucher_type' => JournalVoucherType::CONTRA,
            'branch_id' => $transfer->branch_id,
            'currency_id' => $transfer->currency_id,
            'exchange_rate' => $exchangeRate,
            'reference_no' => $transfer->transfer_no,
            'attachment' => $transfer->attachment,
            'narration' => $transfer->note ?? ('Internal Fund Transfer: '.$transfer->transfer_no),
            'sourceable_type' => FundTransfer::class,
            'sourceable_id' => $transfer->id,
            'entries' => $journalEntries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $this->postingService->post($voucher);

        return $voucher;
    }

    protected function reverseExistingVoucher(
        string $sourceableType,
        $sourceableId,
        JournalVoucherType $voucherType = JournalVoucherType::OPENING,
        string $reason = 'update'
    ): void {
        $existingVoucher = JournalVoucher::query()
            ->where('voucher_type', $voucherType)
            ->where('status', JournalVoucherStatus::POSTED)
            ->whereNull('reversal_of')
            ->whereNull('reversed_by_voucher')
            ->where(function ($q) use ($sourceableType, $sourceableId) {
                $q->where(function ($sub) use ($sourceableType, $sourceableId) {
                    $sub->where('sourceable_type', $sourceableType)
                        ->where('sourceable_id', $sourceableId);
                });

                if ($sourceableType === Account::class) {
                    $q->orWhereHas('entries', function ($sub) use ($sourceableId) {
                        $sub->where('account_id', $sourceableId);
                    });
                }
            })
            ->latest('id')
            ->first();

        if ($existingVoucher) {
            $this->journalService->reverse($existingVoucher, 'Reversing due to '.$reason);
        }
    }

    protected function createAndPostOpeningVoucher(
        string $date,
        string $narration,
        string $sourceableType,
        $sourceableId,
        array $entries,
        ?string $branchId = null,
        ?int $currencyId = null,
        ?float $exchangeRate = null
    ): JournalVoucher {
        $currencyService = app(CurrencyConversionService::class);

        $resolvedCurrencyId = $currencyId ?? Setting::get('default_currency');
        $resolvedExchangeRate = ($exchangeRate && $exchangeRate > 0)
            ? $exchangeRate
            : $currencyService->getExchangeRate($resolvedCurrencyId);

        $voucherData = [
            'voucher_date' => $date,
            'voucher_type' => JournalVoucherType::OPENING,
            'branch_id' => $branchId ?? session('branch_id') ?? (auth()->user()->branch_id ?? null) ?? Setting::get('default_branch'),
            'currency_id' => $resolvedCurrencyId,
            'exchange_rate' => $resolvedExchangeRate,
            'narration' => $narration,
            'sourceable_type' => $sourceableType,
            'sourceable_id' => $sourceableId,
            'entries' => $entries,
        ];

        $voucher = $this->journalService->create($voucherData);
        $postingService = app(PostingService::class);
        $postingService->post($voucher);

        return $voucher;
    }

    public function getAccountByCodeOrType(LedgerAccountType $type, string $preferredCode): int
    {
        $account = Account::where('account_type', $type)
            ->where('account_code', $preferredCode)
            ->value('id')
            ?? Account::where('account_type', $type)->value('id');

        if (! $account) {
            throw new Exception("Account for type '{$type->value}' (preferred code: {$preferredCode}) not found in Chart of Accounts.");
        }

        return $account;
    }
}
