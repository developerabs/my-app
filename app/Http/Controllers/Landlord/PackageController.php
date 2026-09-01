<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Jobs\Landlord\SyncPackageFeatureJob;
use App\Models\landlord\Feature;
use App\Models\landlord\Module;
use App\Models\landlord\Package;
use App\Models\landlord\PackageFeature;
use App\Models\landlord\PackageModule;
use App\Models\landlord\PackagePricing;
use App\Models\landlord\Tenant;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    use HasFiles;
    public function index()
    {
        $packages = Package::all();
        return view('landlord.dashboard.package_manager.packages', compact('packages'));
    }

    public function create()
    {
        $features = Feature::core()->active()->orderBy('key', 'ASC')->get();
        $modules = Module::active()->get();
        return view('landlord.dashboard.package_manager.create_package', compact('features', 'modules'));
    }

    public function store(Request $request)
    {
        // Step 1: Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name',
            'reseller_min_reg_fee' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',

            // Pricing validation
            'pricing.monthly.price' => 'nullable|numeric|min:0',
            'pricing.monthly.duration_days' => 'nullable|integer|min:1',
            'pricing.yearly.price' => 'nullable|numeric|min:0',
            'pricing.yearly.duration_days' => 'nullable|integer|min:1',
            'pricing.lifetime.price' => 'nullable|numeric|min:0',
            'pricing.lifetime.duration_days' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $imagePath = $this->uploadFiles($request, 'image', 'landlord/packages', 'public');

            $package = Package::create([
                'name' => $validated['name'],
                'reseller_min_reg_fee' => $validated['reseller_min_reg_fee'],
                'slug' => Str::slug($validated['name'], '-'),
                'description' => $request->description ?? null,
                'image' => $imagePath,
                'is_trial' => $request->has('is_trial'),
                'meta' => ['trial_period' => $request->is_trial ? (int) $request->trial_period : 0],
            ]);

            $packageId = $package->id;
            $now = now();
            $finalFeatures = []; // [feature_id => meta_json]

            // Collect selected modules
            if ($request->filled('modules')) {
                $moduleInserts = [];
                $selectedModuleIds = [];

                foreach ($request->modules as $moduleId => $moduleData) {
                    if (!empty($moduleData['enabled'])) {
                        $selectedModuleIds[] = $moduleId;
                        $moduleInserts[] = [
                            'package_id' => $packageId,
                            'module_id'  => $moduleId,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                }

                if (!empty($selectedModuleIds)) {
                    // Module Insert
                    PackageModule::insert($moduleInserts);

                    //Get all features of selected modules
                    $moduleFeatures = Feature::whereIn('module_id', $selectedModuleIds)->pluck('id');
                    foreach ($moduleFeatures as $fId) {
                        $finalFeatures[$fId] = null; // ডিফল্ট মেটা নাল
                    }
                }
            }

            // Manually selected features and their limits
            if ($request->filled('features')) {
                foreach ($request->features as $fId => $fData) {
                    if (!empty($fData['enabled'])) {
                        $meta = !empty($fData['limit']) ? json_encode(['limit' => (int) $fData['limit']]) : null;
                        //Overwrite if already exists
                        $finalFeatures[$fId] = $meta;
                    }
                }
            }

            // Insert features
            if (!empty($finalFeatures)) {
                $featureInserts = [];
                foreach ($finalFeatures as $fId => $meta) {
                    $featureInserts[] = [
                        'package_id' => $packageId,
                        'feature_id' => $fId,
                        'meta'       => $meta,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                PackageFeature::insert($featureInserts);
            }

            // Insert pricing
            if ($request->filled('pricing')) {
                $pricingInserts = [];
                foreach ($request->pricing as $type => $pricing) {
                    if (!empty($pricing['price'])) {
                        $pricingInserts[] = [
                            'package_id'    => $packageId,
                            'type'          => $type,
                            'price'         => $pricing['price'],
                            'duration_days' => $pricing['duration_days'] ?? null,
                            'created_at'    => $now,
                            'updated_at'    => $now
                        ];
                    }
                }
                PackagePricing::insert($pricingInserts);
            }

            DB::commit();

            return redirect()->route('landlord.packages')->with('success', 'Package created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(Package $package)
    {
        //return $package->features()->with('feature')->get();
        $features = Feature::core()->active()->orderBy('key', 'ASC')->get();
        $modules = Module::active()->get();
        return view('landlord.dashboard.package_manager.edit_package', compact('package', 'features', 'modules'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name,' . $package->id,
            'description' => 'nullable|string',
            'pricing.*.price' => 'nullable|numeric|min:0',
            'pricing.*.duration_days' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {
            // Update Image
            $imagePath = $this->updateFile($request, 'image', $package->image, 'landlord/packages', 'public');

            // Update Main Package
            $package->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name'], '-'),
                'description' => $validated['description'] ?? null,
                'image' => $imagePath,
                'is_trial' => $request->has('is_trial'),
                'meta' => [
                    'trial_period' => $request->is_trial ? (int) $request->trial_period : 0,
                ],
            ]);

            $now = now();
            $packageId = $package->id;

            // Module and feature handle

            $finalFeatures = []; // [feature_id => meta]

            // Modude handle
            $package->modules()->delete(); // Remove old modules
            if ($request->filled('modules')) {
                $moduleInserts = [];
                $selectedModuleIds = [];

                foreach ($request->modules as $moduleId => $moduleData) {
                    if (!empty($moduleData['enabled'])) {
                        $selectedModuleIds[] = $moduleId;
                        $moduleInserts[] = [
                            'package_id' => $packageId,
                            'module_id'  => $moduleId,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                }

                if (!empty($selectedModuleIds)) {
                    PackageModule::insert($moduleInserts);

                    // Collects all features of selected modules
                    $moduleFeatures = Feature::whereIn('module_id', $selectedModuleIds)->pluck('id');
                    foreach ($moduleFeatures as $fId) {
                        $finalFeatures[$fId] = null;
                    }
                }
            }

            // Manual feature and limit
            if ($request->filled('features')) {
                foreach ($request->features as $fId => $fData) {
                    if (!empty($fData['enabled'])) {
                        $meta = !empty($fData['limit']) ? json_encode(['limit' => (int) $fData['limit']]) : null;
                        $finalFeatures[$fId] = $meta;
                    }
                }
            }

            // Insert features
            $package->features()->delete(); // Remove old features
            if (!empty($finalFeatures)) {
                $featureInserts = [];
                foreach ($finalFeatures as $fId => $meta) {
                    $featureInserts[] = [
                        'package_id' => $packageId,
                        'feature_id' => $fId,
                        'meta'       => $meta,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
                PackageFeature::insert($featureInserts);
            }

            // handle pricing
            $package->pricing()->delete(); // Remove old pricing
            if ($request->filled('pricing')) {
                $pricingInserts = [];
                foreach ($request->pricing as $type => $pricing) {
                    if (!empty($pricing['price'])) {
                        $pricingInserts[] = [
                            'package_id'    => $packageId,
                            'type'          => $type,
                            'price'         => $pricing['price'],
                            'duration_days' => $pricing['duration_days'] ?? null,
                            'created_at'    => $now,
                            'updated_at'    => $now
                        ];
                    }
                }
                if (!empty($pricingInserts)) {
                    PackagePricing::insert($pricingInserts);
                }
            }

            // Sync features
            dispatch(new SyncPackageFeatureJob($package));

            DB::commit();
            return redirect()->route('landlord.packages')->with('success', 'Package updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('landlord.packages')
            ->with('success', 'Package deleted successfully!');
    }

    public function updateStatus(Package $package)
    {
        if ($package->is_active) {
            $package->update(['is_active' => false]);
        } else {
            $package->update(['is_active' => true]);
        }
        return back()->with('success', 'Package status updated successfully!');
    }

    public function getPackageInfo(Package $package)
    {
        $data = [
            'id' => $package->id,
            'is_trial' => $package->is_trial,
            'min_reg_fee' => $package->reseller_min_reg_fee,
            'trial_period' => $package->meta['trial_period'] ?? 0,
            'pricing' => $package->pricing()->get()->keyBy('type')->toArray(),
        ];
        return response()->json($data);
    }
}
