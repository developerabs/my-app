<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Services\Accounting\FinanceChargeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyOverdueLateFeesCommand extends Command
{
    /**
     * --date অপশন দিয়ে টেস্ট ডেট এবং --tenant অপশন দিয়ে নির্দিষ্ট টেন্যান্ট আইডি দেওয়া যাবে
     */
    protected $signature = 'app:apply-overdue-late-fees 
                            {--date= : Simulate running on a specific test date (YYYY-MM-DD)} 
                            {--tenant= : Specify a single tenant ID for testing}';

    /**
     * The console command description.
     */
    protected $description = 'Scan overdue Vendor Bills and Customer Invoices to automatically apply late fees & finance charges across all tenants.';

    /**
     * Execute the console command.
     */
    public function handle(FinanceChargeService $chargeService): int
    {
        // 🧪 ১. সিমুলেশন ডেট হ্যান্ডেল করা
        $testDate = $this->option('date');
        if ($testDate) {
            try {
                $parsedDate = Carbon::parse($testDate);
                Carbon::setTestNow($parsedDate);
                $this->warn("⚠️  SIMULATION MODE: Running command as if today is [ {$parsedDate->toDateString()} ]");
            } catch (Exception $e) {
                $this->error("Invalid date format provided in --date option. Please use YYYY-MM-DD format.");
                return Command::FAILURE;
            }
        }

        // 🏢 ২. টেন্যান্ট ফিল্টার ও সুইচ করা
        $specificTenantId = $this->option('tenant');

        if ($specificTenantId) {
            $tenants = DB::table('tenants')->where('id', $specificTenantId)->get();
            if ($tenants->isEmpty()) {
                $this->error("Tenant with ID '{$specificTenantId}' not found in landlord database.");
                return Command::FAILURE;
            }
        } else {
            $tenants = DB::table('tenants')->get();
        }

        if ($tenants->isEmpty()) {
            $this->warn("No active tenants found.");
            return Command::SUCCESS;
        }

        $totalAppliedCount = 0;

        // 🔄 ৩. প্রতিটি টেন্যান্টের ডাটাবেজে ঘুরে বিল প্রসেস করা
        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;
            $this->newLine();
            $this->info("==================================================");
            $this->info("Processing Late Fees for Tenant: [ {$tenantId} ]");
            $this->info("==================================================");

            try {
                // টেন্যান্ট ডাটাবেজ কন্ট্যাক্সটে সুইচ করা
                tenancy()->initialize($tenantId);

                $appliedInTenant = 0;

                // 1. Process Vendor Bills
                $appliedInTenant += $this->processBills($chargeService);

                // 2. Process Customer Invoices (Future Scope Ready)
                if (class_exists(\App\Models\Invoice::class)) {
                    $appliedInTenant += $this->processInvoices($chargeService);
                }

                $totalAppliedCount += $appliedInTenant;

            } catch (Exception $e) {
                Log::error("Late Fee Command Error for Tenant [{$tenantId}]: " . $e->getMessage());
                $this->error("Error processing tenant [ {$tenantId} ]: " . $e->getMessage());
            } finally {
                // টেন্যান্ট কন্ট্যাক্সট বন্ধ করা
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->info("🎉 Process complete across all tenants! Total {$totalAppliedCount} late fee(s) applied.");

        // টাইম ট্রাভেল রিসেট করা
        Carbon::setTestNow();

        return Command::SUCCESS;
    }

    /**
     * Process Vendor Bills for current tenant
     */
    protected function processBills(FinanceChargeService $chargeService): int
    {
        $bills = Bill::where('has_late_fee', true)
            ->where('due_amount', '>', 0)
            ->get();

        $count = 0;
        $this->info("Checking " . $bills->count() . " vendor bill(s) with late fee enabled...");

        foreach ($bills as $bill) {
            if ($bill->isEligibleForLateFeeToday()) {
                $feeAmount = $bill->calculateLateFeeFromConfig();

                if ($feeAmount > 0) {
                    $charge = $chargeService->applyCharge($bill, [
                        'amount' => $feeAmount,
                        'fee_type' => $bill->late_fee_config['fee_type'] ?? 'fixed',
                        'rate' => $bill->late_fee_config['rate'] ?? $feeAmount,
                        'days_overdue' => $bill->overdue_days ?? 0,
                        'note' => 'System auto-applied overdue late fee on ' . now()->toDateString(),
                    ]);

                    // Update last_applied_at in late_fee_config JSON
                    $config = $bill->late_fee_config ?? [];
                    $config['last_applied_at'] = now()->toDateString();

                    $bill->updateQuietly([
                        'late_fee_config' => $config,
                    ]);

                    $this->line("  ✓ Applied {$feeAmount} BDT late fee to Vendor Bill: <fg=cyan>{$bill->bill_no}</> (Charge No: <fg=yellow>{$charge->charge_no}</>)");
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Process Customer Invoices for current tenant
     */
    protected function processInvoices(FinanceChargeService $chargeService): int
    {
        $invoiceClass = \App\Models\Invoice::class;
        $invoices = $invoiceClass::where('has_late_fee', true)
            ->where('due_amount', '>', 0)
            ->get();

        $count = 0;
        $this->info("Checking " . $invoices->count() . " customer invoice(s) with late fee enabled...");

        foreach ($invoices as $invoice) {
            if ($invoice->isEligibleForLateFeeToday()) {
                $feeAmount = $invoice->calculateLateFeeFromConfig();

                if ($feeAmount > 0) {
                    $charge = $chargeService->applyCharge($invoice, [
                        'amount' => $feeAmount,
                        'fee_type' => $invoice->late_fee_config['fee_type'] ?? 'fixed',
                        'rate' => $invoice->late_fee_config['rate'] ?? $feeAmount,
                        'days_overdue' => $invoice->overdue_days ?? 0,
                        'note' => 'System auto-applied overdue late fee on ' . now()->toDateString(),
                    ]);

                    $config = $invoice->late_fee_config ?? [];
                    $config['last_applied_at'] = now()->toDateString();

                    $invoice->updateQuietly([
                        'late_fee_config' => $config,
                    ]);

                    $this->line("  ✓ Applied {$feeAmount} BDT late fee to Customer Invoice: <fg=cyan>{$invoice->invoice_no}</> (Charge No: <fg=yellow>{$charge->charge_no}</>)");
                    $count++;
                }
            }
        }

        return $count;
    }
}