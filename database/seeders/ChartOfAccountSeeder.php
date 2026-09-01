<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\BalanceType;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $structure = [
            // 1000 - ASSETS
            ['code' => '1000', 'name' => 'Assets', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                ['code' => '1100', 'name' => 'Current Assets', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code' => '1110', 'name' => 'Cash & Cash Equivalents', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                        ['code' => '1111', 'name' => 'Cash in hand', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => []],
                        ['code' => '1112', 'name' => 'Bank Accounts', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => []],
                        ['code' => '1113', 'name' => 'Mobile Wallets', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => []],
                    ]],
                    ['code' => '1120', 'name' => 'Accounts Receivable', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '1130', 'name' => 'Inventory', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                        ['code' => '1131', 'name' => 'Raw Materials', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                        ['code' => '1132', 'name' => 'Work In Progress', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                        ['code' => '1133', 'name' => 'Finished Goods', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                        ['code' => '1134', 'name' => 'Merchandise Inventory', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                        ['code' => '1135', 'name' => 'Inventory Adjustment', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                        ['code' => '1136', 'name' => 'Inventory In Transit', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ]],
                    ['code' => '1140', 'name' => 'Prepaid Expenses', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '1150', 'name' => 'Employee Advances', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '1160', 'name' => 'Tax Receivable', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '1170', 'name' => 'Security Deposits', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '1180', 'name' => 'Other Current Assets', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '1190', 'name' => 'Supplier Advances', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                ]],
                ['code' => '1200', 'name' => 'Non-Current Assets', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code' => '1210', 'name' => 'Land', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1220', 'name' => 'Buildings', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1230', 'name' => 'Machinery & Equipment', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1240', 'name' => 'Vehicles', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1250', 'name' => 'Furniture & Fixtures', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1260', 'name' => 'Computers & IT Equipment', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1270', 'name' => 'Software & Licenses', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1280', 'name' => 'Accumulated Depreciation', 'type' => AccountType::ASSET, 'bal' => BalanceType::CREDIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1290', 'name' => 'Construction In Progress', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1291', 'name' => 'Long-term Investments', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1292', 'name' => 'Investment Property', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                    ['code' => '1293', 'name' => 'Intangible Assets', 'type' => AccountType::ASSET, 'bal' => BalanceType::DEBIT, 'is_leaf'=> true, 'children' => []],
                ]],
            ]],
            // 2000 - LIABILITIES
            ['code' => '2000', 'name' => 'Liabilities', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                ['code' => '2100', 'name' => 'Current Liabilities', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                    ['code' => '2110', 'name' => 'Accounts Payable', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2120', 'name' => 'Accrued Liabilities', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2130', 'name' => 'Tax Payable', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2140', 'name' => 'Payroll Liabilities', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2150', 'name' => 'Employee Benefits Payables', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2160', 'name' => 'Short-Term Loans', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],  
                    ['code' => '2170', 'name' => 'Deferred Revenue', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2180', 'name' => 'Customer Advances', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ]],
                ['code' => '2200', 'name' => 'Long-Term Liabilities', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                    ['code' => '2210', 'name' => 'Bank Loans', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2220', 'name' => 'Lease Liabilities', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2240', 'name' => 'Security Deposits Received', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '2250', 'name' => 'Other Long-Term Liabilities', 'type' => AccountType::LIABILITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ]],
            ]],
            // 3000 - EQUITY
            ['code' => '3000', 'name' => 'Equity', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                ['code' => '3100', 'name' => 'Capital Accounts', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                    ['code' => '3110', 'name' => 'Owner\'s Capital', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '3120', 'name' => 'Share Capital', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '3130', 'name' => 'Partner\'s Capital', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ]],
                ['code' => '3200', 'name' => 'Retained Earnings', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ['code' => '3300', 'name' => 'Current Year Profit / Loss', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ['code' => '3400', 'name' => 'Drawings', 'type' => AccountType::EQUITY, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code' => '3410', 'name' => 'Owner\'s Drawings', 'type' => AccountType::EQUITY, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '3420', 'name' => 'Partner\'s Drawings', 'type' => AccountType::EQUITY, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                ]],
                ['code' => '3500', 'name' => 'Other Equity', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ['code' => '3600', 'name' => 'Opening Balance Equity', 'type' => AccountType::EQUITY, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
            ]],
            // 4000 - REVENUE
            ['code' => '4000', 'name' => 'Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                ['code' => '4100', 'name' => 'Sales Revenue', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                    ['code' => '4110', 'name' => 'Retail Sales', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4120', 'name' => 'Wholesale Sales', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4130', 'name' => 'Online Sales', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4140', 'name' => 'Export Sales', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4150', 'name' => 'Scrap Sales', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ]],
                ['code' => '4200', 'name' => 'Service Revenue', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ['code' => '4300', 'name' => 'Subscription Revenue', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ['code' => '4400', 'name' => 'Shipping Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                ['code' => '4500', 'name' => 'Sales Returns & Allowances', 'type' => AccountType::INCOME, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                ['code' => '4600', 'name' => 'Other Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => false, 'children' => [
                    ['code' => '4610', 'name' => 'Discount Received', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4620', 'name' => 'Commission Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4630', 'name' => 'Interest Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4640', 'name' => 'Rental Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4650', 'name' => 'Realized Foreign Exchange Gain', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4660', 'name' => 'Miscellaneous Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '4670', 'name' => 'Late Fee & Finance Charge Income', 'type' => AccountType::INCOME, 'bal' => BalanceType::CREDIT, 'is_leaf' => true, 'children' => []], // 👈 NEW
                ]],
            ]],
            // 5000 - Cost of Sales
            ['code' => '5000', 'name' => 'Cost of Sales', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                ['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code' => '5110', 'name' => 'Raw Materials consumption', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5120', 'name' => 'Direct Labour', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5130', 'name' => 'Manufacturing Overhead', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5140', 'name' => 'Packaging Cost', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5150', 'name' => 'Freight Inward', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5160', 'name' => 'Inventory Write-off', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5170', 'name' => 'Inventory Shrinkage', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5180', 'name' => 'Purchase Price Variance', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '5190', 'name' => 'Other Cost of Sales', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                ]],
            ]],
            //6000 - Expenses
            ['code' => '6000', 'name' => 'Expenses', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                ['code' => '6100', 'name' => 'Payroll Expense', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code' => '6110', 'name' => 'Salary Expense', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '6120', 'name' => 'Overtime Expense', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '6130', 'name' => 'Bonus Expense', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '6140', 'name' => 'Employee Benefits', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '6150', 'name' => 'Employer Payroll Taxes', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                    ['code' => '6160', 'name' => 'Staff Training Expense', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => true, 'children' => []],
                ]],
                ['code' => '6200', 'name' => 'Facility Expenses', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6210','name'=>'Rent Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6220','name'=>'Utilities Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6230','name'=>'Internet Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6240','name'=>'Office Cleaning Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6250','name'=>'Security Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6260','name'=>'Repairs & Maintenance','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                ]],
                ['code' => '6300', 'name' => 'Office Expenses', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6310','name'=>'Office Supplies','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6320','name'=>'Printing & Stationery','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6330','name'=>'Postage & Courier','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6340','name'=>'Telephone Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6350','name'=>'General Office Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]], // 👈 NEW
                ]],
                ['code' => '6400', 'name' => 'Marketing & Advertising', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6410','name'=>'Advertising Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6420','name'=>'Social Media Marketing','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6430','name'=>'Promotional Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6440','name'=>'Sponsorship Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                ]],
                ['code' => '6500', 'name' => 'Travel & Entertainment', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6510','name'=>'Travel Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6520','name'=>'Accommodation Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6530','name'=>'Meals & Entertainment','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                ]],
                ['code' => '6600', 'name' => 'Depreciation & Amortization', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6610','name'=>'Depreciation Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6620','name'=>'Amortization Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                ]],
                ['code' => '6700', 'name' => 'IT & Software', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6710','name'=>'Software Subscription','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6720','name'=>'Cloud Hosting','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6730','name'=>'Domain & SSL','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6740','name'=>'IT Maintenance','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                ]],
                ['code' => '6800', 'name' => 'Financial Expenses', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6810','name'=>'Bank Charges','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6820','name'=>'Loan Interest','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6830','name'=>'Realized Foreign Exchange Loss','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6840','name'=>'Late Fee & Finance Charge Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]], // 👈 NEW
                    ['code'=>'6850','name'=>'Late Fee Discount Allowed','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]], // 👈 NEW
                ]],
                ['code' => '6900', 'name' => 'Other Operating Expenses', 'type' => AccountType::EXPENSE, 'bal' => BalanceType::DEBIT, 'is_leaf' => false, 'children' => [
                    ['code'=>'6910','name'=>'Insurance Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6920','name'=>'Legal & Professional Fees','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6930','name'=>'Bad Debt Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6940','name'=>'Donations & Charity','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6950','name'=>'Miscellaneous Expense','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                    ['code'=>'6960','name'=>'License & Permit Fees','type'=>AccountType::EXPENSE,'bal'=>BalanceType::DEBIT,'is_leaf'=>true,'children'=>[]],
                ]]
            ]],
        ];

        $this->seedAccounts($structure);
    }

    private function seedAccounts(array $accounts, $parentId = null): void
    {
        foreach ($accounts as $account) {
            $created = ChartOfAccount::updateOrCreate(['code' => $account['code']], [
                'name' => $account['name'],
                'account_type' => $account['type'],
                'balance_type' => $account['bal'],
                'parent_id' => $parentId,
                'is_leaf' => $account['is_leaf'],
                'is_system' => true,
                'is_active' => true
            ]);

            if (!empty($account['children'])) {
                $this->seedAccounts($account['children'], $created->id);
            }
        }
    }
}