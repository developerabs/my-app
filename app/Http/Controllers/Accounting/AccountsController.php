<?php

namespace App\Http\Controllers\Accounting;

use App\DataTables\AccountDataTable;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Currency;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\Accounting\AccountingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountsController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function index(AccountDataTable $dataTable)
    {
        // 🟢 Scoped to permitted branches for security
        $branches = function_exists('get_auth_permitted_branches')
            ? get_auth_permitted_branches()
            : Branch::active()->get();

        $currencies = Cache::tags([tenant_tag()])->remember('all_currencies_'.tenant('id'), 3600, function () {
            return Currency::select('id', 'name', 'code', 'symbol')->get();
        });

        return $dataTable->render('backend.accounting.accounts.index', compact('branches', 'currencies'));
    }

    public function store(Request $request, AccountingIntegrationService $accIntegration)
    {
        $validated = $request->validate([
            'account_type' => ['required', 'in:cash,bank,mobile,other'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'string'],
            'account_name' => ['required', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:100'],
            'routing_number' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'currency_id' => ['nullable', 'exists:currencies,id'], // 🟢 Added Currency Validation
        ]);

        DB::beginTransaction();
        try {
            $openingDate = $request->filled('opening_balance_date')
                ? Carbon::parse($request->opening_balance_date)->format('Y-m-d')
                : now()->toDateString();

            $validated['opening_balance_date'] = $openingDate;

            $account = $this->accountingService->createLedgerAccount($validated);
            $openingBalance = (float) ($validated['opening_balance'] ?? 0);

            // Clean 1-Line Accounting Sync Call
            $accIntegration->syncAccountOpeningBalance($account, $openingBalance, $openingDate);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Account created successfully.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating account: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error creating account: '.$e->getMessage()], 500);
        }
    }

    public function edit(Request $request, Account $account)
    {
        return response()->json([
            'success' => true,
            'data' => $account->load(['branch', 'currency']),
        ]);
    }

    public function update(Request $request, Account $account, AccountingIntegrationService $accIntegration)
    {
        $validated = $request->validate([
            'account_type' => ['required', 'in:cash,bank,mobile,other'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'string'],
            'account_name' => ['required', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:100'],
            'routing_number' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'currency_id' => ['nullable', 'exists:currencies,id'], // 🟢 Added Currency Validation
        ]);

        DB::beginTransaction();
        try {
            $newOpeningDate = $request->filled('opening_balance_date')
                ? Carbon::parse($request->opening_balance_date)->format('Y-m-d')
                : now()->toDateString();

            $validated['opening_balance_date'] = $newOpeningDate;

            $account = $this->accountingService->updateLedgerAccount($account, $validated);
            $newOpeningBalance = (float) ($validated['opening_balance'] ?? 0);

            // Re-sync Opening Balance Voucher
            $accIntegration->syncAccountOpeningBalance($account, $newOpeningBalance, $newOpeningDate);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Account updated successfully.'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating account: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error updating account: '.$e->getMessage()], 500);
        }
    }

    public function destroy(Account $account, AccountingIntegrationService $accIntegration)
    {
        try {
            if ($accIntegration->hasActiveTransactions($account)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete '{$account->account_name}' because it has active transaction records. Please deactivate (set active = false) the account instead.",
                ], 422);
            }

            DB::beginTransaction();

            $accIntegration->syncAccountOpeningBalance($account, 0, now()->toDateString());
            $account->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Account deleted successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting account: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error deleting account: '.$e->getMessage()], 500);
        }
    }
}
