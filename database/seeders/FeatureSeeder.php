<?php

namespace Database\Seeders;

use App\Models\landlord\Feature;
use App\Models\landlord\FeaturePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = DB::table('modules')->pluck('id', 'key')->toArray();

        $features = [
            [
                'key' => 'assets',
                'name' => 'Assets',
                'description' => 'Manage your office assets.',
                'module_id' => null,
                'icon' => 'fa-solid fa-landmark-flag',
                'permissions' => ['assets_view', 'assets_create', 'assets_update', 'assets_delete', 'report_assets', 'assets_import', 'assets_export', 'assets_register_manage'],
            ],
            [
                'key' => 'products',
                'name' => 'Products',
                'description' => 'Manage your business\'s products, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-boxes-stacked',
                'permissions' => ['products_view', 'products_create', 'products_update', 'products_delete', 'products_history', 'products_price_view', 'products_price_create', 'products_price_update', 'products_price_delete', 'brand_view', 'brand_create', 'brand_update', 'brand_delete', 'unit_view', 'unit_create', 'unit_update', 'unit_delete', 'report_product', 'products_import', 'products_export']
            ],
            [
                'key' => 'pharmacy',
                'name' => 'Pharmacy',
                'description' => 'Manage your business\'s pharmacy products',
                'module_id' => null,
                'icon' => 'fa-solid fa-boxes-stacked',
                'permissions' => ['products_generic_manage']
            ],
            [
                'key' => 'categories',
                'name' => 'Categories',
                'description' => 'Manage your business\'s product categories.',
                'module_id' => null,
                'icon' => 'fa-solid fa-icons',
                'permissions' => ['category_view', 'category_create', 'category_update', 'category_delete', 'report_category']
            ],
            [
                'key' => 'customers',
                'name' => 'Customers',
                'description' => 'Manage your business\'s customers, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-users',
                'permissions' => ['customer_view', 'customer_create', 'customer_update', 'customer_delete', 'customer_dues_view', 'customer_dues_clear', 'manage_customer_group', 'report_customer', 'customer_import', 'customer_export']
            ],
            [
                'key' => 'suppliers',
                'name' => 'Suppliers',
                'description' => 'Manage your business\'s suppliers, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-users',
                'permissions' => ['supplier_view', 'supplier_create', 'supplier_update', 'supplier_delete', 'supplier_dues_view', 'supplier_dues_clear', 'report_supplier', 'supplier_import', 'supplier_export']
            ],
            [
                'key' => 'purchases',
                'name' => 'Purchases',
                'description' => 'Manage your business\'s purchases, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-cart-arrow-down',
                'permissions' => ['purchase_view', 'purchase_create', 'purchase_update', 'purchase_delete', 'purchase_dues_view', 'purchase_dues_clear', 'purchase_payments_view', 'purchase_payments_create', 'purchase_payments_update', 'purchase_payments_delete', 'report_purchase', 'purchase_import', 'purchase_export']
            ],
            [
                'key' => 'sales',
                'name' => 'Sales',
                'description' => 'Manage your business\'s sales, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-bag-shopping',
                'permissions' => ['sales_view', 'sales_create', 'sales_update', 'sales_delete', 'sales_dues_view', 'sales_payments_view', 'sales_payments_create', 'sales_payments_update', 'sales_payments_delete', 'report_sales', 'sales_import', 'sales_export']
            ],
            [
                'key' => 'purchase_returns',
                'name' => 'Purchase Returns',
                'description' => 'Manage your business\'s purchase returns, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-arrow-rotate-right',
                'permissions' => ['purchase_returns_view', 'purchase_returns_create', 'purchase_returns_update', 'purchase_returns_delete', 'report_purchase_returns', 'purchase_return_import', 'purchase_return_export']
            ],
            [
                'key' => 'sales_returns',
                'name' => 'Sales Returns',
                'description' => 'Manage your business\'s sales returns, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-arrow-rotate-left',
                'permissions' => ['sales_returns_view', 'sales_returns_create', 'sales_returns_update', 'sales_returns_delete', 'report_sales_returns', 'sales_return_import', 'sales_return_export']
            ],
            [
                'key' => 'stock_adjustments',
                'name' => 'Stock Adjustments',
                'description' => 'Manage your business\'s stock adjustments, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-sliders',
                'permissions' => ['stock_adjustments_view', 'stock_adjustments_create', 'stock_adjustments_update', 'stock_adjustments_delete', 'report_stock_adjustments', 'stock_adjustment_import', 'stock_adjustment_export']
            ],
            [
                'key' => 'branches',
                'name' => 'Branches',
                'description' => 'Manage your business\'s branches, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-code-branch',
                'permissions' => ['branch_view', 'branch_create', 'branch_update', 'branch_delete', 'report_branches', 'branch_import', 'branch_export']
            ],
            [
                'key' => 'rack_and_shelfs',
                'name' => 'Rack and Shelfs',
                'description' => 'Manage your business\'s rack and shelfs, including products, stock levels, and movements.',
                'module_id' => null,
                'icon' => 'fa-solid fa-table-list',
                'permissions' => ['rack_and_shelfs_view', 'rack_and_shelfs_create', 'rack_and_shelfs_update', 'rack_and_shelfs_delete', 'report_rack_and_shelfs', 'rack_and_shelf_import', 'rack_and_shelf_export']
            ],
            [
                'key' => 'quotations',
                'name' => 'Quotations',
                'description' => 'Manage your business\'s quotations, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-file-lines',
                'permissions' => ['quotations_view', 'quotations_create', 'quotations_update', 'quotations_delete', 'quotations_send', 'quotations_convert_to_sales', 'quotations_convert_to_purchase', 'report_quotations', 'quotations_import', 'quotations_export']
            ],
            [
                'key' => 'employees',
                'name' => 'Employees',
                'description' => 'Manage your business\'s employees, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-users',
                'permissions' => ['hrm_employee_view', 'hrm_employee_create', 'hrm_employee_update', 'hrm_employee_delete', 'hrm_report_employee', 'hrm_employee_import', 'hrm_employee_export']
            ],
            [
                'key' => 'billers',
                'name' => 'Billers',
                'description' => 'Manage your business\'s billers, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-users',
                'permissions' => ['billers_view', 'billers_create', 'billers_update', 'billers_delete', 'report_billers', 'billers_import', 'billers_export']
            ],

            /* =========================
             | ACCOUNTS & CASH MANAGEMENT
             ========================= */
            [
                'key' => 'accounts',
                'name' => 'Payment Accounts',
                'description' => 'Manage bank accounts, cash registers, and MFS wallets.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-building-columns',
                'permissions' => [
                    'acc_accounts_view',
                    'acc_accounts_create',
                    'acc_accounts_update',
                    'acc_accounts_delete',
                    'acc_accounts_statement_view',
                    'acc_report_accounts',
                    'acc_accounts_import',
                    'acc_accounts_export'
                ]
            ],
            [
                'key' => 'fund_transfers',
                'name' => 'Fund Transfers',
                'description' => 'Transfer money between bank accounts, cash, and MFS (Contra Entry).',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-money-bill-transfer',
                'permissions' => [
                    'acc_transfer_view',
                    'acc_transfer_create',
                    'acc_transfer_update',
                    'acc_transfer_delete',
                    'acc_report_transfer'
                ]
            ],
            [
                'key' => 'account_deposits_withdrawals',
                'name' => 'Deposits & Withdrawals',
                'description' => 'Manage direct cash/bank deposits and non-bill withdrawals.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-money-bill-trend-up',
                'permissions' => [
                    'acc_deposit_view',
                    'acc_deposit_create',
                    'acc_deposit_delete',
                    'acc_withdraw_view',
                    'acc_withdraw_create',
                    'acc_withdraw_delete',
                ]
            ],

            [
                'key' => 'sms_integrations',
                'name' => 'SMS Integrations',
                'description' => 'Manage your business\'s integrations, including sms gateways, courier gateways, and payment gateways.',
                'module_id' => null,
                'icon' => 'fa-solid fa-envelope',
                'permissions' => ['manage_sms_gateway', 'manage_sms_template', 'manage_sms_send', 'report_sms']
            ],
            [
                'key' => 'courier_integrations',
                'name' => 'Courier Integrations',
                'description' => 'Manage your business\'s integrations, including sms gateways, courier gateways, and payment gateways.',
                'module_id' => null,
                'icon' => 'fa-solid fa-truck',
                'permissions' => ['manage_courier_gateway', 'report_courier']
            ],
            [
                'key' => 'deliveries',
                'name' => 'Deliveries',
                'description' => 'Manage your business\'s deliveries, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-truck',
                'permissions' => ['deliveries_view', 'deliveries_create', 'deliveries_update', 'deliveries_delete', 'deliveries_send_to_courier', 'report_deliveries', 'deliveries_import', 'deliveries_export']
            ],
            [
                'key' => 'expenses',
                'name' => 'Expenses',
                'description' => 'Manage your business\'s expenses, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-coins',
                'permissions' => ['expenses_view', 'expenses_create', 'expenses_update', 'expenses_delete', 'manage_expense_category', 'report_expenses', 'expenses_import', 'expenses_export']
            ],
            [
                'key' => 'bills',
                'name' => 'Vendor Bills',
                'description' => 'Manage operational vendor bills and accounts payable.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'permissions' => ['bill_view', 'bill_create', 'bill_update', 'bill_delete', 'bill_payment', 'report_bills', 'bill_import', 'bill_export']
            ],
            [
                'key' => 'payments',
                'name' => 'Payments & Settlements',
                'description' => 'Manage customer collections and supplier disbursements.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-money-bill-wave',
                'permissions' => ['supplier_payment_view', 'supplier_payment_create', 'supplier_payment_delete', 'customer_payment_view', 'customer_payment_create', 'customer_payment_delete', 'report_payments']
            ],
            [
                'key' => 'vat_tax',
                'name' => 'VAT Tax',
                'description' => 'Manage your business\'s VAT tax, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-sack-dollar',
                'permissions' => ['manage_vat_tax', 'report_vat_tax']
            ],
            [
                'key' => 'backup_restores',
                'name' => 'Backup Restores',
                'description' => 'Manage your business\'s backup restores, including information, orders, and payments.',
                'module_id' => null,
                'icon' => 'fa-solid fa-coins',
                'permissions' => ['manage_backup_restores']
            ],
            [
                'key' => 'exchanges',
                'name' => 'Exchanges',
                'description' => 'Manage your business\'s exchanges, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-arrow-right-arrow-left',
                'permissions' => ['exchanges_view', 'exchanges_create', 'exchanges_update', 'exchanges_delete', 'report_exchanges', 'exchanges_import', 'exchanges_export']
            ],
            [
                'key' => 'api_integrations',
                'name' => 'API Integrations',
                'description' => 'Manage your business\'s API integrations, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-cloud',
                'permissions' => ['manage_api_integrations']
            ],
            [
                'key' => 'advance_reports',
                'name' => 'Advance Reports',
                'description' => 'Manage your business\'s advance reports, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-chart-line',
                'permissions' => ['report_advance']
            ],
            [
                'key' => 'report',
                'name' => 'Report',
                'description' => 'Manage your business\'s report, including inventory, pricing, and sales.',
                'module_id' => null,
                'icon' => 'fa-solid fa-chart-line',
                'permissions' => ['report_summary', 'report_best_seller', 'report_profit_loss']
            ],

            /* =========================
             | HRM MODULE FEATURES
             ========================= */
            [
                'key' => 'payroll',
                'name' => 'Payroll',
                'description' => 'Manage employee payroll and salary disbursements.',
                'module_id' => $modules['hrm'] ?? null,
                'icon' => 'fa-solid fa-sack-dollar',
                'permissions' => ['hrm_payroll_view', 'hrm_payroll_create', 'hrm_payroll_update', 'hrm_payroll_delete', 'hrm_report_payroll', 'hrm_payroll_import', 'hrm_payroll_export']
            ],
            [
                'key' => 'attendance',
                'name' => 'Attendance',
                'description' => 'Manage employee attendance.',
                'module_id' => $modules['hrm'] ?? null,
                'icon' => 'fa-regular fa-calendar-check',
                'permissions' => ['hrm_attendance_view', 'hrm_attendance_create', 'hrm_attendance_update', 'hrm_attendance_delete', 'hrm_report_attendance', 'hrm_setting_attendance', 'hrm_attendance_import', 'hrm_attendance_export']
            ],
            [
                'key' => 'leave',
                'name' => 'Leave',
                'description' => 'Manage employee leave applications.',
                'module_id' => $modules['hrm'] ?? null,
                'icon' => 'fa-solid fa-person-through-window',
                'permissions' => ['hrm_leave_view', 'hrm_leave_create', 'hrm_leave_update', 'hrm_leave_delete', 'hrm_report_leave', 'hrm_setting_leave']
            ],
            [
                'key' => 'performance',
                'name' => 'Performance',
                'description' => 'Track employee performance reviews.',
                'module_id' => $modules['hrm'] ?? null,
                'icon' => 'fa-solid fa-person-digging',
                'permissions' => ['hrm_performance_view', 'hrm_performance_create', 'hrm_performance_update', 'hrm_performance_delete', 'hrm_report_performance', 'hrm_setting_performance']
            ],
            [
                'key' => 'provident_fund',
                'name' => 'Provident Fund',
                'description' => 'Manage Provident Fund accounts.',
                'module_id' => $modules['hrm'] ?? null,
                'icon' => 'fa-solid fa-sack-dollar',
                'permissions' => ['hrm_manage_provident_fund', 'hrm_report_provident_fund', 'hrm_setting_provident_fund']
            ],
            [
                'key' => 'loan',
                'name' => 'Loan',
                'description' => 'Manage employee loans and advances.',
                'module_id' => $modules['hrm'] ?? null,
                'icon' => 'fa-solid fa-hand-holding-dollar',
                'permissions' => ['hrm_manage_loan', 'hrm_report_loan', 'hrm_setting_loan']
            ],

            /* =========================
             | CRM MODULE FEATURES
             ========================= */
            [
                'key' => 'leads',
                'name' => 'Leads',
                'description' => 'Manage customer leads and pipeline.',
                'module_id' => $modules['crm'] ?? null,
                'icon' => 'fa-solid fa-people-group',
                'permissions' => ['crm_leads_view', 'crm_leads_create', 'crm_leads_update', 'crm_leads_delete', 'crm_report_leads', 'crm_leads_manage_source', 'crm_leads_manage_status', 'crm_leads_manage_subject', 'crm_leads_import', 'crm_leads_export']
            ],
            [
                'key' => 'lead_notes',
                'name' => 'Lead Notes',
                'description' => 'Manage lead communication notes.',
                'module_id' => $modules['crm'] ?? null,
                'icon' => 'fa-solid fa-comments-dollar',
                'permissions' => ['crm_leads_notes_view', 'crm_leads_notes_create', 'crm_leads_notes_update', 'crm_leads_notes_delete']
            ],
            [
                'key' => 'deals',
                'name' => 'Deals',
                'description' => 'Manage business deals.',
                'module_id' => $modules['crm'] ?? null,
                'icon' => 'fa-solid fa-handshake',
                'permissions' => ['crm_deals_view', 'crm_deals_create', 'crm_deals_update', 'crm_deals_delete', 'crm_report_deals', 'crm_deals_import', 'crm_deals_export']
            ],

            /* =========================
             | ACCOUNTING CORE
             ========================= */
            [
                'key' => 'chart_of_accounts',
                'name' => 'Chart of Accounts',
                'description' => 'Manage account hierarchy for accounting and audit.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-sitemap',
                'permissions' => [
                    'acc_coa_view',
                    'acc_coa_create',
                    'acc_coa_update',
                    'acc_coa_delete',
                    'acc_report_coa'
                ]
            ],
            [
                'key' => 'journal_entries',
                'name' => 'Journal Entries',
                'description' => 'Record debit and credit journal entries.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-book',
                'permissions' => [
                    'acc_journal_view',
                    'acc_journal_create',
                    'acc_journal_update',
                    'acc_journal_approve',
                    'acc_journal_post',
                    'acc_journal_reverse',
                    'acc_report_journal'
                ]
            ],
            [
                'key' => 'ledger',
                'name' => 'General Ledger',
                'description' => 'Account-wise ledger for audit and reporting.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-layer-group',
                'permissions' => [
                    'acc_ledger_view',
                    'acc_report_ledger'
                ]
            ],
            [
                'key' => 'trial_balance',
                'name' => 'Trial Balance',
                'description' => 'Debit and credit verification report.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-scale-balanced',
                'permissions' => [
                    'acc_trial_balance_view',
                    'acc_report_trial_balance'
                ]
            ],

            /* =========================
             | FINANCIAL STATEMENTS
             ========================= */
            [
                'key' => 'profit_loss',
                'name' => 'Profit & Loss',
                'description' => 'Income and expense based profit report.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-chart-line',
                'permissions' => [
                    'acc_profit_loss_view',
                    'acc_report_profit_loss'
                ]
            ],
            [
                'key' => 'balance_sheet',
                'name' => 'Balance Sheet',
                'description' => 'Assets, liabilities and equity statement.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'permissions' => [
                    'acc_balance_sheet_view',
                    'acc_report_balance_sheet'
                ]
            ],
            [
                'key' => 'cash_flow',
                'name' => 'Cash Flow',
                'description' => 'Cash movement analysis.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-money-bill-transfer',
                'permissions' => [
                    'acc_cash_flow_view',
                    'acc_report_cash_flow'
                ]
            ],
            [
                'key' => 'finance_charges',
                'name' => 'Finance Charges & Late Fees',
                'description' => 'Manage overdue late fees, waivers, and interest calculations.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-percent',
                'permissions' => [
                    'acc_finance_charge_view',
                    'acc_finance_charge_create',
                    'acc_finance_charge_waive',
                    'acc_finance_charge_freeze',
                    'acc_finance_charge_delete',
                    'acc_report_finance_charge',
                ]
            ],

            /* =========================
             | FISCAL & CONTROL
             ========================= */
            [
                'key' => 'fiscal_year',
                'name' => 'Fiscal Year',
                'description' => 'Manage accounting fiscal years.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-calendar',
                'permissions' => [
                    'acc_fiscal_year_view',
                    'acc_fiscal_year_create',
                    'acc_fiscal_year_close'
                ]
            ],
            [
                'key' => 'accounting_period_lock',
                'name' => 'Accounting Period Lock',
                'description' => 'Lock accounting periods for audit safety.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-lock',
                'permissions' => [
                    'acc_period_lock_manage'
                ]
            ],

            /* =========================
             | TAX & VAT
             ========================= */
            [
                'key' => 'tax_engine',
                'name' => 'Tax & VAT Engine',
                'description' => 'Manage VAT, tax rules and payable accounts.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-percent',
                'permissions' => [
                    'acc_tax_manage',
                    'acc_tax_report'
                ]
            ],

            /* =========================
             | INVENTORY ACCOUNTING
             ========================= */
            [
                'key' => 'inventory_accounting',
                'name' => 'Inventory Accounting',
                'description' => 'COGS and inventory valuation.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-box',
                'permissions' => [
                    'acc_inventory_accounting_view',
                    'acc_report_inventory_accounting'
                ]
            ],

            /* =========================
             | VOUCHERS & AUDIT
             ========================= */
            [
                'key' => 'vouchers',
                'name' => 'Voucher Management',
                'description' => 'Payment, receipt, journal vouchers.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-receipt',
                'permissions' => [
                    'acc_voucher_view',
                    'acc_voucher_create',
                    'acc_voucher_approve',
                    'acc_voucher_print'
                ]
            ],
            [
                'key' => 'audit_trail',
                'name' => 'Audit Trail',
                'description' => 'Track all accounting changes for audit.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-user-shield',
                'permissions' => [
                    'acc_audit_trail_view'
                ]
            ],

            /* =========================
             | OPENING / CLOSING
             ========================= */
            [
                'key' => 'opening_balance',
                'name' => 'Opening Balance',
                'description' => 'Set opening balances for accounts.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-play',
                'permissions' => [
                    'acc_opening_balance_manage'
                ]
            ],
            [
                'key' => 'year_closing',
                'name' => 'Year Closing',
                'description' => 'Close fiscal year and transfer profit.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-flag-checkered',
                'permissions' => [
                    'acc_year_closing_execute'
                ]
            ],

            /* =========================
             | BANK RECONCILIATION
             ========================= */
            [
                'key' => 'bank_reconciliation',
                'name' => 'Bank Reconciliation',
                'description' => 'Match bank statement with system.',
                'module_id' => $modules['accounting'] ?? null,
                'icon' => 'fa-solid fa-building-columns',
                'permissions' => [
                    'acc_bank_reconciliation_view',
                    'acc_bank_reconciliation_manage'
                ]
            ],

            /* =========================
             | E-COMMERCE & MANUFACTURING
             ========================= */
            [
                'key' => 'ecommerce_core',
                'name' => 'Ecommerce Core',
                'description' => 'Ecommerce core features.',
                'module_id' => $modules['ecommerce'] ?? null,
                'icon' => 'fa-solid fa-store',
                'permissions' => [
                    'ecommerce_manage_settings',
                    'ecommerce_manage_cms',
                    'ecommerce_manage_payments_gateway',
                    'ecommerce_manage_shipping',
                    'ecommerce_manage_analytics',
                ]
            ],
            [
                'key' => 'manufacturing_core',
                'name' => 'Manufacturing Core',
                'description' => 'Manufacturing production, BOM, costing, and inventory integration.',
                'module_id' => $modules['manufacturing'] ?? null,
                'icon' => 'fa-solid fa-industry',
                'permissions' => [
                    'manufacturing_bom_view',
                    'manufacturing_bom_manage',
                    'manufacturing_production_view',
                    'manufacturing_production_manage',
                    'manufacturing_production_start_complete',
                    'manufacturing_material_issue',
                    'manufacturing_finished_goods_receive',
                    'manufacturing_wip_manage',
                    'manufacturing_scrap_manage',
                    'manufacturing_costing_view',
                    'manufacturing_costing_manage',
                    'manufacturing_workcenter_manage',
                    'manufacturing_accounting_sync',
                    'manufacturing_reports_view',
                    'manufacturing_settings_manage',
                    'manufacturing_full_access',
                ]
            ],
            [
                'key' => 'gift_card',
                'name' => 'Gift Card',
                'description' => 'Manage gift cards.',
                'module_id' => null,
                'icon' => 'fa-solid fa-gift',
                'permissions' => [
                    'giftcard_view',
                    'giftcard_create',
                    'giftcard_update',
                    'giftcard_delete',
                ]
            ],
            [
                'key' => 'coupon',
                'name' => 'Coupon',
                'description' => 'Manage coupons.',
                'module_id' => null,
                'icon' => 'fa-solid fa-ticket',
                'permissions' => [
                    'coupon_view',
                    'coupon_create',
                    'coupon_update',
                    'coupon_delete',
                ]
            ],
            [
                'key' => 'membership',
                'name' => 'Membership',
                'description' => 'Manage membership plans and subscriptions.',
                'module_id' => null,
                'icon' => 'fa-solid fa-id-card',
                'permissions' => [
                    'manage_membership_plans',
                ]
            ],
        ];

        foreach ($features as $index => $feature) {
            $meta = !empty($feature['module_id']) ? ['module_required' => true] : [];
            $featureModel = Feature::updateOrCreate(
                ['key' => $feature['key']],
                [
                    'name'        => $feature['name'],
                    'description' => $feature['description'],
                    'module_id'   => $feature['module_id'] ?? null,
                    'sort_order'  => $index + 1,
                    'icon'        => $feature['icon'] ?? null,
                    'meta'        => $meta
                ]
            );

            if (!empty($feature['permissions']) && is_array($feature['permissions'])) {
                $permissionsData = array_map(fn($permission) => [
                    'permission' => $permission,
                    'feature_id' => $featureModel->id,
                ], $feature['permissions']);

                FeaturePermission::upsert(
                    $permissionsData,
                    ['permission', 'feature_id'], 
                    ['permission']
                );
            }
        }
    }
}