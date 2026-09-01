<?php

namespace App\Http\Controllers;

use App\DataTables\RackDataTable;
use App\Models\Branch;
use App\Models\Rack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RackShelfController extends Controller
{
    public function index(RackDataTable $dataTable)
    {
        $branches = Cache::tags([tenant_tag()])->remember('branches_list_' . tenant('id'), 3600, fn() => Branch::active()->get());
        return $dataTable->render('backend.racks-shelves.index', compact('branches'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'branch_id'      => 'required|exists:branches,id', // Ensure branch UUID exists
            'name'           => 'required|string|max:191',
            'code'           => 'nullable|string|max:191',
            'description'    => 'nullable|string',
            'shelves'        => 'required|array|min:1',
            'shelves.*.name' => 'required|string|distinct|max:191', // Stops inline duplicate names
            'shelves.*.code' => 'nullable|string|distinct|max:191', // Stops inline duplicate codes
        ]);

        DB::beginTransaction();

        try {
            // Step 3: Create the master record inside racks table
            $rack = Rack::create([
                'branch_id'   => $request->branch_id,
                'name'        => $request->name,
                'code'        => $request->code,
                'description' => $request->description,
            ]);

            // Step 4: Loop through the shelves array payload and insert rows safely
            foreach ($request->shelves as $shelfData) {
                
                // 🔥 Step 5: Double check database uniqueness to avoid collision with historical data
                $isDuplicate = $rack->shelves()
                    ->where(function ($query) use ($shelfData) {
                        $query->where('name', $shelfData['name']);
                        if (!empty($shelfData['code'])) {
                            $query->orWhere('code', $shelfData['code']);
                        }
                    })->exists();

                if ($isDuplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => "The shelf name or code already exists in this rack context."
                    ], 422);
                }

                // Step 6: Insert child shelf records via relation
                $rack->shelves()->create([
                    'name' => $shelfData['name'],
                    'code' => $shelfData['code'],
                ]);
            }

            // Step 7: Commit transaction if everything passes cleanly
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('file.message.rack_created_successfully') ?? 'Rack and shelves created successfully.'
            ], 200);

        } catch (\Exception $e) {
            // Step 8: Something went wrong, rollback every single query executed above safely
            DB::rollBack();

            Log::error("Rack Shelf Store Error: " . $e->getMessage(), [
                'payload' => $request->all(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong on the server. Please try again.'
            ], 500);
        }
    }

    public function edit(Rack $rack)
    {
        try {
            // Eager load related child shelves using load() since the Rack model is already injected
            $rack->load(['shelves' => function ($query) {
                $query->orderBy('id', 'asc');
            }]);

            return response()->json([
                'success' => true,
                'rack'    => $rack
            ], 200);

        } catch (\Exception $e) {
            Log::error("Rack Shelf Edit Fetch Error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Rack details could not be found.'
            ], 404);
        }
    }

    public function update(Request $request, Rack $rack)
    {
        // Step 1: Validate incoming update payload data
        $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'name'           => 'required|string|max:191',
            'code'           => 'nullable|string|max:191',
            'description'    => 'nullable|string',
            'shelves'        => 'required|array|min:1',
            'shelves.*.id'   => 'nullable', // English Comment: Null means new row added during edit mode
            'shelves.*.name' => 'required|string|distinct|max:191',
            'shelves.*.code' => 'nullable|string|distinct|max:191',
        ]);

        // Step 2: Start transaction to protect historical stock linkages
        DB::beginTransaction();

        try {
            // 🔥 Step 3: Directly update the injected master rack model (No findOrFail needed)
            $rack->update([
                'branch_id'   => $request->branch_id,
                'name'        => $request->name,
                'code'        => $request->code,
                'description' => $request->description,
            ]);

            // Step 4: This tracker will collect active shelf IDs to understand what to keep and what to delete
            $activeShelfIds = [];

            // Step 5: Process children rows sent via request array
            foreach ($request->shelves as $shelfData) {
                
                // Step 6: Validate uniqueness, ignore current shelf row ID if we are modifying an existing row
                $duplicateCheck = $rack->shelves()
                    ->where(function ($query) use ($shelfData) {
                        $query->where('name', $shelfData['name']);
                        if (!empty($shelfData['code'])) {
                            $query->orWhere('code', $shelfData['code']);
                        }
                    });

                if (!empty($shelfData['id'])) {
                    $duplicateCheck->where('id', '!=', $shelfData['id']); // English Comment: Ignore myself
                }

                if ($duplicateCheck->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => "The shelf name or code '{$shelfData['name']}' already exists elsewhere in this rack."
                    ], 422);
                }

                // Step 7: Update or Create execution branch logic
                if (!empty($shelfData['id'])) {
                    // English Comment: Shelf already exists in database, let's update its fields
                    $shelf = $rack->shelves()->findOrFail($shelfData['id']);
                    $shelf->update([
                        'name' => $shelfData['name'],
                        'code' => $shelfData['code'],
                    ]);
                    $activeShelfIds[] = $shelf->id; 
                } else {
                    // English Comment: No ID found, means user clicked 'Add Row' inside the edit popup
                    $newShelf = $rack->shelves()->create([
                        'name' => $shelfData['name'],
                        'code' => $shelfData['code'],
                    ]);
                    $activeShelfIds[] = $newShelf->id; 
                }
            }

            // Step 8: Wipe out any shelves that were removed by the user from the frontend modal screen
            $rack->shelves()->whereNotIn('id', $activeShelfIds)->delete();

            // Step 9: Commit transaction safely
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('file.message.rack_updated_successfully') ?? 'Rack and shelves updated successfully.'
            ], 200);

        } catch (\Exception $e) {
            // Step 10: Fail-safe cancellation
            DB::rollBack();

            Log::error("Rack Shelf Update Error: " . $e->getMessage(), [
                'rack_id' => $rack->id,
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong on the server while updating. Please try again.'
            ], 500);
        }
    }

    public function show(Rack $rack)
    {
        try {
            // English Comment: Load related branch metadata along with sorted child shelves for single resource view
            $rack->load(['branch', 'shelves' => function ($query) {
                $query->orderBy('id', 'asc');
            }]);

            return response()->json([
                'success' => true,
                'rack'    => $rack
            ], 200);

        } catch (\Exception $e) {
            Log::error("Rack Shelf Show Fetch Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Data could not be loaded.'], 404);
        }
    }

    public function destroy(Rack $rack)
    {
        try {
            $rack->delete();
            return response()->json([
                'success' => true,
                'message' => __('file.message.rack_deleted_successfully') ?? 'Rack and its shelves deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Rack Shelf Deletion Error: " . $e->getMessage(), ['rack_id' => $rack->id]);
            return response()->json([
                'success' => false,
                'message' => 'Rack could not be deleted. It may be linked to other records.'
            ], 500);
        }
    }
}
