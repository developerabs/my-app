<?php

namespace App\Http\Controllers\Landlord;

use App\DataTables\Landlord\AddonsDataTable;
use App\Http\Controllers\Controller;
use App\Models\landlord\Addon;
use App\Models\landlord\Feature;
use App\Traits\HasFiles;
use Illuminate\Http\Request;

class AddonsController extends Controller
{
    use HasFiles;
    public function index(AddonsDataTable $dataTable)
    {
        $features = Feature::active()->select('id', 'name')->get();
        return $dataTable->render('landlord.dashboard.addons.addons', compact('features'));
    }

    public function store(Request $request)
    {
        // ১. ভ্যালিডেশন
        $rules = [
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:feature,limit',
            'reference_id'    => 'required|integer',
            'price'           => 'required|numeric|min:0',
            'duration_days'   => 'nullable|integer|min:1',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // ২ এমবি লিমিট
        ];

        // যদি টাইপ 'limit' হয়, তবে লিমিট সংক্রান্ত ফিল্ডগুলো রিকোয়ার্ড হবে
        if ($request->type === 'limit') {
            $rules['limit_mode']  = 'required|in:absolute,increment';
            $rules['limit_value'] = 'required|integer|min:1';
        }

        $validatedData = $request->validate($rules);

        // ২. ইমেজ আপলোড হ্যান্ডেল করা
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->uploadFiles($request, 'image', 'landlord/addons');
        }

        // ৩. মেটা (meta) ডাটা তৈরি করা (লিমিট অপশন এবং ইমেজ পাথ)
        $metaData = [
            'image'            => $imagePath,
            'limit_mode'       => $request->limit_mode ?? null,
            'limit_value'      => $request->limit_value ?? null,
            'reset_on_expiry'  => $request->has('reset_on_expiry') ? true : false,
        ];

        // ৪. ডেটাবেসে সেভ করা
        Addon::create([
            'name'           => $validatedData['name'],
            'type'           => $validatedData['type'],
            'reference_type' => 'feature', // আপনার কমেন্ট অনুযায়ী ফিক্সড
            'reference_id'   => $validatedData['reference_id'],
            'price'          => $validatedData['price'],
            'duration_days'  => $request->duration_days ?? 30,
            'is_active'      => true, // ডিফল্টভাবে একটিভ
            'meta'           => $metaData, // এটি অবশ্যই মডেলে 'json' হিসেবে কাস্ট করতে হবে
        ]);

        return response()->json(['status' => 'success', 'message' => 'Addon created successfully.']);
    }

    public function edit(Addon $addon)
    {
        return response()->json(['status' => 'success', 'data' => $addon]);
    }

    public function update(Request $request, Addon $addon)
    {
        $rules = [
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:feature,limit',
            'reference_id'    => 'required|integer',
            'price'           => 'required|numeric|min:0',
            'duration_days'   => 'nullable|integer|min:1',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        if ($request->type === 'limit') {
            $rules['limit_mode']  = 'required|in:absolute,increment';
            $rules['limit_value'] = 'required|integer|min:1';
        }

        $validatedData = $request->validate($rules);

        $imagePath = $addon->meta['image'] ?? [];
        if ($request->hasFile('image')) {
            $imagePath = $this->updateFile($request, 'image', $addon->meta['image'], 'landlord/addons');
        }

        $metaData = [
            'image'            => $imagePath,
            'limit_mode'       => $request->limit_mode ?? null,
            'limit_value'      => $request->limit_value ?? null,
            'reset_on_expiry'  => $request->has('reset_on_expiry') ? true : false,
        ];

        $addon->update([
            'name'           => $validatedData['name'],
            'type'           => $validatedData['type'],
            'reference_type' => 'feature', // আপনার কমেন্ট অনুযায়ী ফিক্সড
            'reference_id'   => $validatedData['reference_id'],
            'price'          => $validatedData['price'],
            'duration_days'  => $request->duration_days ?? 30,
            'meta'           => $metaData, // এটি অবশ্যই মডেলে 'json' হিসেবে কাস্ট করতে হবে
        ]);

        return response()->json(['status' => 'success', 'message' => 'Addon updated successfully.']);
    }

    public function destroy(Addon $addon)
    {
        if($addon->isUsedByTenant()){
            return response()->json([
                'status' => "error", 
                'message' => __('file.message.addon_is_used_by_tenant')
            ]);
        }

        $addon->delete();
        return response()->json(['status' => 'success', 'message' => 'Addon deleted successfully.']);
    }
}
