<?php

namespace App\Services\Accounting;

use App\Enums\LedgerAccountType;
use App\Enums\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\FiscalYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AccountingFormService
{
    /**
     * Common Accounting Form Data
     */
    public function getFormData(): array
    {
        // dd($this->accounts());
        return [
            'branches' => $this->branches(),
            'currencies' => $this->currencies(),
            'fiscalYear' => $this->currentFiscalYear(),
            'accountingPeriod' => $this->currentAccountingPeriod(),
            'accounts' => $this->accounts(),
            'paymentAccounts' => $this->paymentAccounts(),
        ];
    }

    protected function accounts()
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('chart_of_accounts')) {
            return collect([]);
        }

        try {
            return Account::query()
                ->with('chartOfAccount')
                ->where('is_active', true)
                ->orderBy('account_type')
                ->get()
                ->groupBy(function ($account) {
                    return $account->chartOfAccount?->account_type ?? 'Others';
                });
        } catch (\Throwable $e) {
            return collect([]);
        }
    }

    /**
     * Active Branch List
     */
    protected function branches()
    {
        return get_auth_permitted_branches();
    }

    /**
     * Active Currency List
     */
    protected function currencies()
    {
        return $this->tenantCache()->remember(
            $this->cacheKey('all_currencies'),
            3600,
            fn () => Currency::orderBy('name')->get()
        );
    }

    /**
     * Current Fiscal Year
     */
    protected function currentFiscalYear()
    {
        return $this->tenantCache()->remember(
            $this->cacheKey('current_fiscal_year'),
            3600,
            fn () => FiscalYear::query()
                ->where('status', PeriodStatus::CURRENT)
                ->first()
        );
    }

    /**
     * Current Accounting Period
     */
    protected function currentAccountingPeriod()
    {
        return $this->tenantCache()->remember(
            $this->cacheKey('current_accounting_period'),
            3600,
            function () {

                $fiscalYear = $this->currentFiscalYear();

                if (! $fiscalYear) {
                    return null;
                }

                return AccountingPeriod::query()
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->where('status', PeriodStatus::CURRENT)
                    ->first();
            }
        );
    }

    public function paymentAccounts(): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('accounts')) {
            return collect([]);
        }

        $user = auth()->user();
        if (!$user) {
            return collect([]);
        }

        $query = Account::active()
            ->where('is_system', false)
            ->whereIn('account_type', LedgerAccountType::paymentAccounts())
            ->with(['currency', 'branch'])
            ->orderBy('account_name');

        // 🔒 ইউজার যদি সব ব্রাঞ্চের এক্সেস না পায়, তবে শুধু অনুমোদিত ব্রাঞ্চের একাউন্ট আসবে
        if (!user_can_access_all_branches($user)) {
            $permittedBranchIds = get_auth_permitted_branch_ids();
            $query->whereIn('branch_id', $permittedBranchIds);
        }

        return $query->get();
    }

    /**
     * Tenant Cache
     */
    protected function tenantCache()
    {
        return Cache::tags([tenant_tag()]);
    }

    /**
     * Tenant Cache Key
     */
    protected function cacheKey(string $key): string
    {
        return $key.'_'.tenant('id');
    }
}
