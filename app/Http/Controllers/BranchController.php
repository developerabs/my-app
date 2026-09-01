<?php

namespace App\Http\Controllers;

use App\DataTables\BranchDataTable;
use App\Enums\LedgerAccountType;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Currency;
use App\Rules\UniqueWithTrashCheck;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    use HasFiles;
    public function index(BranchDataTable $dataTable)
    {
        $accounts = Cache::tags([tenant_tag()])->remember('payment_accounts_' . tenant('id'), 3600, function () {
            return Account::active()->whereIn('account_type', LedgerAccountType::paymentAccounts())->select('id', 'account_name')->get();
        });
        $currencies = Cache::tags([tenant_tag()])->remember('all_currencies_' . tenant('id'), 3600, function () {
            return Currency::select('id', 'name', 'code')->get();
        });
        return $dataTable->render('backend.branches.branches', compact('accounts', 'currencies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                new UniqueWithTrashCheck(Branch::class, 'name'),
            ],
            'address' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'default_acc' => 'nullable|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'bin_number' => 'nullable',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $branchLogo = null;
        if ($request->hasFile('image')) {
            $branchLogo = $this->processImage($request->file('image'), 'branches', [
                'width' => 500,
            ]);
        }
        $slug = Branch::generateUniqueSlug($validated['name']);
        $validated['slug'] = $slug;
        $validated['branch_logo'] = $branchLogo;
        try{
            Branch::create($validated);
            return response()->json(['status' => true, 'message' => 'Branch created successfully!']);
        }catch(\Exception $e){
            return response()->json(['status' => false, 'message' => 'Failed to create branch.', 'error' => $e->getMessage()], 500);
        }
    }

    public function edit(Branch $branch)
    {
        return response()->json([
            'branch_logo_url' => $branch->branch_logo_url,
            'branch' => $branch
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                new UniqueWithTrashCheck(Branch::class, 'name', $branch->id),
            ],
            'address' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'default_acc' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'bin_number' => 'nullable',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $branchLogo = $branch->branch_logo;
        if ($request->hasFile('image')) {
            $branchLogo = $this->processImage($request->file('image'), 'branches', [
                'width' => 500,
            ], $branch->branch_logo);
        }
        $slug = Branch::generateUniqueSlug($validated['name'], $branch->id);
        $validated['slug'] = $slug;
        $validated['branch_logo'] = $branchLogo;
        try{
            $branch->update($validated);
            return response()->json(['status' => true, 'message' => 'Branch updated successfully!']);
        }catch(\Exception $e){
            return response()->json(['status' => false, 'message' => 'Failed to update branch.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Branch $branch)
    {
        try{
            $branch->delete();
            return response()->json(['status' => true, 'message' => 'Branch deleted successfully!']);
        }catch(\Exception $e){
            return response()->json(['status' => false, 'message' => 'Failed to delete branch.', 'error' => $e->getMessage()], 500);
        }
    }
}
