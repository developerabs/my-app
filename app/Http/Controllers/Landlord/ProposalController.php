<?php

namespace App\Http\Controllers\Landlord;
use App\DataTables\Landlord\ProposalDataTable;
use App\Models\landlord\Package;
use App\Models\landlord\Proposal;
use App\Models\landlord\Reseller;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposalController extends Controller
{
    public function index(ProposalDataTable $dataTable)
    {
        $packages = Package::active()->get();

        return $dataTable->render('landlord.dashboard.proposals.index', [
            'packages' => $packages,
        ]);
    }
    public function create()
    {
        $packages = Package::all();
            return view('landlord.dashboard.proposals.create_proposal', compact('packages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reseller_id'       => 'nullable|integer',
            'tenant_id'         => 'nullable|string|max:255',
            'company_name'      => 'required|string|max:255',
            'customer_name'     => 'required|string|max:255',
            'customer_email'    => 'required|email|max:255',
            'customer_phone'    => 'required|string|max:50',
            'customer_address'  => 'nullable|string|max:500',
            'proposal_details'  => 'nullable|string',
            'package'           => 'required|exists:packages,id',
            'registration_fee'  => 'required|numeric|min:0',
            'subscription_fee'  => 'required|numeric|min:0',
            'monthly'           => 'nullable|numeric|min:0',
            'yearly'            => 'nullable|numeric|min:0',
            'lifetime'          => 'nullable|numeric|min:0',
            'validity'          => 'nullable|integer|min:1',
            'demo_link'         => 'nullable|string|max:255',
            'username'          => 'nullable|string|max:255',
            'password'          => 'nullable|string|max:255',
            'special_note'      => 'nullable|string',
            'status'            => 'nullable|in:pending,sent,rejected,completed',
            'discount'          => 'nullable|numeric|min:0',
            'discount_type'     => 'nullable|in:flat,percentage',
        ]);

        $proposal_number = "PRP-" . date("Ymd") . '-' . date("His");

        Proposal::create([
            'reseller_id'       => $request->reseller_id,
            'tenant_id'         => $request->tenant_id,
            'proposal_number'   => $proposal_number,
            'company_name'      => $request->company_name,
            'customer_name'     => $request->customer_name,
            'customer_email'    => $request->customer_email,
            'customer_phone'    => $request->customer_phone,
            'customer_address'  => $request->customer_address,
            'proposal_details'  => $request->proposal_details,
            'package'           => $request->package, // package_id
            'registration_fee'  => $request->registration_fee ?? 10000,
            'subscription_fee'  => $request->subscription_fee ?? 500,
            'monthly'           => $request->monthly ?? 0,
            'yearly'            => $request->yearly ?? 0,
            'lifetime'          => $request->lifetime ?? 0,
            'validity'          => $request->validity,
            'demo_link'         => $request->demo_link,
            'username'          => $request->username,
            'password'          => $request->password,
            'special_note'      => $request->special_note,
            'added_by'          => Auth::id(),
            'discount'          => $request->discount ?? 0,
            'discount_type'     => $request->discount_type ?? 'flat',
            'status'            => $request->status ?? 'pending',
        ]);

        return redirect()->route('landlord.proposals')
            ->with('success', 'Proposal created successfully!');
    }



    public function edit(Proposal $proposal)
    {
        $packages = Package::all();

        return view('landlord.dashboard.proposals.edit_proposal', compact('proposal', 'packages'));
    }

    public function update(Request $request, Proposal $proposal)
    {
        $request->validate([
            'reseller_id'       => 'nullable|integer',
            'tenant_id'         => 'nullable|string|max:255',
            'company_name'      => 'required|string|max:255',
            'customer_name'     => 'nullable|string|max:255',
            'customer_email'    => 'nullable|email|max:255',
            'customer_phone'    => 'nullable|string|max:50',
            'customer_address'  => 'nullable|string|max:500',
            'proposal_details'  => 'nullable|string',
            'package'           => 'required|exists:packages,id',
            'registration_fee'  => 'nullable|numeric|min:0',
            'subscription_fee'  => 'nullable|numeric|min:0',
            'monthly'           => 'nullable|numeric|min:0',
            'yearly'            => 'nullable|numeric|min:0',
            'lifetime'          => 'nullable|numeric|min:0',
            'validity'          => 'nullable|integer|min:1',
            'demo_link'         => 'nullable|string|max:255',
            'username'          => 'nullable|string|max:255',
            'password'          => 'nullable|string|max:255',
            'special_note'      => 'nullable|string',
            'status'            => 'nullable|in:pending,sent,rejected,completed',
            'discount'          => 'nullable|numeric|min:0',
            'discount_type'     => 'nullable|in:flat,percentage',
        ]);

        $proposal->update([
            'reseller_id'       => $request->reseller_id,
            'tenant_id'         => $request->tenant_id,
            'company_name'      => $request->company_name,
            'customer_name'     => $request->customer_name,
            'customer_email'    => $request->customer_email,
            'customer_phone'    => $request->customer_phone,
            'customer_address'  => $request->customer_address,
            'proposal_details'  => $request->proposal_details,
            'package'           => $request->package,
            'registration_fee'  => $request->registration_fee ?? 10000,
            'subscription_fee'  => $request->subscription_fee ?? 500,
            'monthly'           => $request->monthly ?? 0,
            'yearly'            => $request->yearly ?? 0,
            'lifetime'          => $request->lifetime ?? 0,
            'validity'          => $request->validity,
            'demo_link'         => $request->demo_link,
            'username'          => $request->username,
            'password'          => $request->password,
            'special_note'      => $request->special_note,
            'discount'          => $request->discount ?? 0,
            'discount_type'     => $request->discount_type ?? 'flat',
            'status'            => $request->status ?? 'pending',
        ]);

        return redirect()->route('landlord.proposals')
            ->with('success', 'Proposal updated successfully!');
    }

    public function destroy(Proposal $proposal)
    {
        try {
            $proposal->delete();
            return response()->json(['message' => 'Proposal deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete proposal.'], 500);
        }
    }

    public function show(Proposal $proposal)
    {
        $reseller = Reseller::first();

        return view('landlord.dashboard.proposals.view_proposal', compact('proposal', 'reseller'));
    }


}
