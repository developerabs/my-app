<?php

namespace App\Http\Controllers\Accounting;

use App\DataTables\BillDataTable;
use App\Enums\LedgerAccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bills\StoreBillRequest;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Supplier;
use App\Services\Accounting\AccountingFormService;
use App\Services\Accounting\BillService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BillController extends Controller
{
    public function __construct(
        protected BillService $billService,
        protected AccountingFormService $accformservice
    ) {}

    public function index(BillDataTable $dataTable)
    {
        $paymentAccounts = Account::active()
            ->whereIn('account_type', LedgerAccountType::paymentAccounts())
            ->orderBy('account_name')
            ->get();

        return $dataTable->render('backend.accounting.bills.index', compact('paymentAccounts'));
    }

    public function create()
    {
        $data = $this->accformservice->getFormData();

        $expenseAccounts = Account::active()
            ->where('account_type', LedgerAccountType::EXPENSE)
            ->orderBy('account_code')
            ->get();

        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = [];

        return view('backend.accounting.bills.create', $data, compact('expenseAccounts', 'suppliers', 'projects'));
    }

    public function store(StoreBillRequest $request)
    {
        $validated = $request->validated();

        if (! user_can_access_all_branches() && ! in_array($validated['branch_id'], get_auth_permitted_branch_ids())) {
            throw ValidationException::withMessages([
                'branch_id' => 'Unauthorized: You do not have permission to create/update bills for this branch.',
            ]);
        }
        try {
            $bill = $this->billService->createBill($request->validated(), $request->file('attachment'));

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Vendor Bill {$bill->bill_no} created and posted successfully.",
                    'redirect' => route('bills.index'),
                ], 201);
            }

            return redirect()->route('bills.index')->with('success', "Vendor Bill {$bill->bill_no} created and posted successfully.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Bill Store Failed: '.$e->getMessage(), [
                'request' => $request->except(['attachment', '_token']),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating bill: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Error creating bill: '.$e->getMessage());
        }
    }

    /**
     * Show Bill Details in JSON for Preview Modal
     */
    public function show(Bill $bill)
    {
        $bill->load([
            'items.expenseAccount',
            'supplier',
            'branch',
            'currency',
            'creator',
            'payments.paymentAccount',
            'financeCharges',
        ]);

        return response()->json([
            'success' => true,
            'data' => $bill,
            'attachment_url' => $bill->attachment ? $this->billService->getFileUrl($bill->attachment, 's3') : null,
        ]);
    }

    public function edit(Bill $bill)
    {
        $data = $this->accformservice->getFormData();
        $bill->load('items.expenseAccount', 'supplier');

        $expenseAccounts = Account::active()
            ->where('account_type', LedgerAccountType::EXPENSE)
            ->orderBy('account_code')
            ->get();

        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = [];

        return view('backend.accounting.bills.edit', $data, compact('bill', 'expenseAccounts', 'suppliers', 'projects'));
    }

    public function update(StoreBillRequest $request, Bill $bill)
    {
        $validated = $request->validated();

        if (! user_can_access_all_branches() && ! in_array($validated['branch_id'], get_auth_permitted_branch_ids())) {
            throw ValidationException::withMessages([
                'branch_id' => 'Unauthorized: You do not have permission to create/update bills for this branch.',
            ]);
        }
        try {
            $updatedBill = $this->billService->updateBill(
                $bill,
                $request->validated(),
                $request->file('attachment')
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Vendor Bill {$updatedBill->bill_no} updated and re-posted successfully.",
                    'redirect' => route('bills.index'),
                ], 200);
            }

            return redirect()->route('bills.index')->with('success', "Vendor Bill {$updatedBill->bill_no} updated and re-posted successfully.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Bill Update Failed: '.$e->getMessage(), [
                'bill_id' => $bill->id,
                'request' => $request->except(['attachment', '_token']),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating bill: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Error updating bill: '.$e->getMessage());
        }
    }

    /**
     * Process Bill Settlement / Payment
     */
    public function pay(Request $request, Bill $bill)
    {
        $request->validate([
            'payment_date' => 'required|string',
            'amount' => 'required|numeric|min:0.01|max:'.$bill->due_amount,
            'payment_account_id' => 'required|exists:accounts,id',
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $payment = $this->billService->payBill($bill, $request->all(), $request->file('attachment'));

            return response()->json([
                'success' => true,
                'message' => "Payment {$payment->payment_no} processed. Bill due updated successfully.",
                'data' => $payment,
            ]);
        } catch (Exception $e) {
            Log::error('Bill Payment Failed: '.$e->getMessage(), [
                'bill_id' => $bill->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete / Void Vendor Bill & Reverse Accounting Voucher
     */
    public function destroy(Request $request, Bill $bill)
    {
        try {
            $reason = $request->input('reason', 'Vendor bill cancelled/deleted by user');

            $this->billService->deleteBill($bill, $reason, true);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Vendor Bill {$bill->bill_no} deleted and accounting entry reversed successfully.",
                ]);
            }

            return redirect()->route('bills.index')
                ->with('success', "Vendor Bill {$bill->bill_no} deleted and accounting entry reversed successfully.");

        } catch (Exception $e) {
            Log::error('Bill Deletion Failed: '.$e->getMessage(), [
                'bill_id' => $bill->id,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete bill: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete bill: '.$e->getMessage());
        }
    }
}
