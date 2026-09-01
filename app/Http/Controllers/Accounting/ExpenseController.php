<?php

namespace App\Http\Controllers\Accounting;

use App\DataTables\ExpenseDataTable;
use App\Enums\LedgerAccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Supplier;
use App\Services\Accounting\AccountingFormService;
use App\Services\Accounting\ExpenseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
        protected AccountingFormService $accformservice
    ) {}

    public function index(ExpenseDataTable $dataTable)
    {
        return $dataTable->render('backend.accounting.expenses.index');
    }

    public function create()
    {
        // getFormData() passes paymentAccounts, branches, currencies, fiscalYear, etc. to view
        $data = $this->accformservice->getFormData();

        // Expense Category Accounts
        $expenseAccounts = Account::active()
            ->where('account_type', LedgerAccountType::EXPENSE)
            ->orderBy('account_code')
            ->get();

        $suppliers = Cache::tags([tenant_tag()])->remember('all_suppliers_'.tenant('id'), 3600, fn () => Supplier::active()->get());
        // Future Project Scope
        $projects = [];

        return view('backend.accounting.expenses.create', $data, compact('expenseAccounts', 'projects', 'suppliers'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $validated = $request->validated();

        if (! user_can_access_all_branches() && ! in_array($validated['branch_id'], get_auth_permitted_branch_ids())) {
            throw ValidationException::withMessages([
                'branch_id' => 'Unauthorized: You cannot post expenses to a branch you do not have access to.',
            ]);
        }
        try {
            $expense = $this->expenseService->createExpense(
                $request->validated(),
                $request->file('attachment')
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Expense {$expense->expense_no} created and posted successfully.",
                    'data' => $expense,
                    'redirect' => route('expenses.index'),
                ], 201);
            }

            return redirect()->route('expenses.index')
                ->with('success', "Expense {$expense->expense_no} created and posted successfully.");

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Expense Store Failed: '.$e->getMessage(), [
                'request' => $request->except(['attachment', '_token']),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating expense: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Error creating expense: '.$e->getMessage());
        }
    }

    public function show(Expense $expense)
    {
        $expense->load([
            'items.expenseAccount',
            'paymentAccount',
            'branch',
            'currency',
            'creator',
        ]);

        return response()->json([
            'success' => true,
            'data' => $expense,
            'attachment_url' => $expense->attachment ? $this->expenseService->getFileUrl($expense->attachment, 's3') : null,
        ]);
    }

    public function edit(Expense $expense)
    {
        $data = $this->accformservice->getFormData();
        $expense->load('items.expenseAccount', 'paymentAccount');

        $expenseAccounts = Account::active()
            ->where('account_type', LedgerAccountType::EXPENSE)
            ->orderBy('account_code')
            ->get();

        $suppliers = Cache::tags([tenant_tag()])->remember('all_suppliers_'.tenant('id'), 3600, fn () => Supplier::active()->get());
        $projects = [];

        return view('backend.accounting.expenses.edit', $data, compact('expense', 'expenseAccounts', 'projects', 'suppliers'));
    }

    public function update(StoreExpenseRequest $request, Expense $expense)
    {

        $validated = $request->validated();

        if (! user_can_access_all_branches() && ! in_array($validated['branch_id'], get_auth_permitted_branch_ids())) {
            throw ValidationException::withMessages([
                'branch_id' => 'Unauthorized: You cannot post expenses to a branch you do not have access to.',
            ]);
        }
        try {
            $updatedExpense = $this->expenseService->updateExpense(
                $expense,
                $request->validated(),
                $request->file('attachment')
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Expense {$updatedExpense->expense_no} updated and re-posted successfully.",
                    'redirect' => route('expenses.index'),
                ], 200);
            }

            return redirect()->route('expenses.index')
                ->with('success', "Expense {$updatedExpense->expense_no} updated and re-posted successfully.");

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Expense Update Failed: '.$e->getMessage(), [
                'expense_id' => $expense->id,
                'request' => $request->except(['attachment', '_token']),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating expense: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Error updating expense: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, Expense $expense)
    {
        try {
            $reason = $request->input('reason', 'Expense deleted by user');

            $this->expenseService->deleteExpense($expense, $reason);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Expense {$expense->expense_no} deleted and accounting entry reversed successfully.",
                ]);
            }

            return redirect()->route('expenses.index')
                ->with('success', "Expense {$expense->expense_no} deleted and accounting entry reversed successfully.");

        } catch (Exception $e) {
            Log::error('Expense Deletion Failed: '.$e->getMessage(), [
                'expense_id' => $expense->id,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete expense: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete expense: '.$e->getMessage());
        }
    }
}
