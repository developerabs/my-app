<?php

namespace App\Http\Controllers;

use App\Contracts\RestorableConflictInterface;
use App\DataTables\TrashDataTable;
use App\Models\Trash; // Import the interface
use App\Services\FeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GlobalTrashController extends Controller
{
    public function index(TrashDataTable $dataTable)
    {
        return $dataTable->render('backend.trash.index');
    }

    public function restore($id)
    {
        try {
            $response = DB::transaction(function () use ($id) {
                $trash = Trash::find($id);
                if (! $trash) {
                    return ['status' => true, 'message' => 'Item already restored successfully.'];
                }

                $item = $trash->trashable_type::withTrashed()->find($trash->trashable_id);

                if (! $item) {
                    return ['status' => false, 'message' => 'Original item not found!'];
                }

                // --- ১. ডাইনামিক লিমিট চেক ---
                if (method_exists($item, 'getFeatureLimitKey')) {
                    $limitKey = $item->getFeatureLimitKey();
                    $limit = FeatureService::getLimit($limitKey);

                    if (! is_null($limit) && $limit !== -1 && $limit !== 'unlimited') {
                        $modelName = strtolower(class_basename($item));
                        $cacheKey = "limit_count_{$modelName}_".tenant('id');

                        $currentCount = Cache::tags([tenant_tag()])->rememberForever($cacheKey, function () use ($item) {
                            return get_class($item)::count();
                        });

                        if ($currentCount >= (int) $limit) {
                            return [
                                'status' => false,
                                'message' => __('Restoration failed! You have reached your limit of :limit items.', ['limit' => $limit]),
                            ];
                        }
                    }
                }

                // --- ২. কনফ্লিক্ট চেক ---
                if ($item instanceof RestorableConflictInterface) {
                    if ($item->hasRestorationConflict()) {
                        return [
                            'status' => false,
                            'message' => 'Restoration failed! An active record with similar details already exists.',
                        ];
                    }
                }

                // Restore Model (Fires static::restored model event)
                $item->restore();

                // Safely delete Trash record if not already deleted by HasTrash trait
                if ($trash->exists) {
                    $trash->delete();
                }

                return ['status' => true, 'message' => 'Item restored successfully'];
            });

            return response()->json($response, $response['status'] ? 200 : 422);
        } catch (\Exception $e) {
            Log::error('Global Trash Restore Failed: '.$e->getMessage());

            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function permanentDelete($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $trash = Trash::findOrFail($id);
                $item = $trash->trashable_type::withTrashed()->find($trash->trashable_id);

                if ($item) {
                    // English: forceDelete will trigger file deletion traits if present
                    $item->forceDelete();
                }
                $trash->delete();
            });

            return response()->json(['status' => true, 'message' => 'Permanently deleted from system']);
        } catch (\Exception $e) {
            // Log the error internally for auditing
            Log::error('Permanent Delete Failed: '.$e->getMessage());

            // Intercept database constraint violations
            if (isset($e->errorInfo) && in_array($e->errorInfo[0], ['23503', '23001'])) {
                return response()->json([
                    'status' => false, // This triggers your frontend if (response.status === false)
                    'message' => 'This item cannot be permanently deleted because it has active transaction records (Sales, Purchases, or Stocks) attached to it.',
                ], 200); // Sending 200 keeps it inside jQuery success block safely
            }

            // Generic system fallback error
            return response()->json([
                'status' => false,
                'message' => 'System Error: '.$e->getMessage(),
            ], 200);
        }
    }

    public function bulkAction(Request $request)
    {
        $ids = $request->ids;
        $action = $request->action;

        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'No items selected!']);
        }

        try {
            $errors = [];
            $successCount = 0;
            $totalRequested = count($ids);

            // Fetching the target items from trash using the selected IDs
            $trashedItems = Trash::whereIn('id', $ids)->get();

            foreach ($trashedItems as $trash) {
                try {
                    DB::transaction(function () use ($trash, $action, &$errors, &$successCount) {
                        $item = $trash->trashable_type::withTrashed()->find($trash->trashable_id);
                        if (! $item) {
                            throw new \Exception('Item not found or already processed.');
                        }

                        if ($action === 'restore') {
                            // --- Feature Limit Check ---
                            if (method_exists($item, 'getFeatureLimitKey')) {
                                $limit = FeatureService::getLimit($item->getFeatureLimitKey());

                                if (! is_null($limit) && $limit !== -1 && $limit !== 'unlimited') {
                                    $modelName = strtolower(class_basename($item));
                                    $cacheKey = "limit_count_{$modelName}_".tenant('id');

                                    $currentCount = Cache::tags([tenant_tag()])->get($cacheKey);

                                    if (is_null($currentCount)) {
                                        $currentCount = get_class($item)::count();
                                        Cache::tags([tenant_tag()])->put($cacheKey, $currentCount, 3600);
                                    }

                                    if ((int) $currentCount >= (int) $limit) {
                                        throw new \Exception('Limit reached for '.($item->name ?? 'this item').'.');
                                    }
                                }
                            }

                            // --- Restoration Conflict Check ---
                            if ($item instanceof RestorableConflictInterface && $item->hasRestorationConflict()) {
                                throw new \Exception('Conflict: '.($item->name ?? 'item').' already exists.');
                            }

                            $item->restore();
                            $trash->delete();
                            $successCount++;

                        } elseif ($action === 'permanent-delete') {
                            $item->forceDelete();
                            $trash->delete();
                            $successCount++;
                        }
                    });
                } catch (\Exception $innerException) {
                    Log::error('Bulk Item Action Failed: '.$innerException->getMessage());

                    // Check for database foreign key constraint errors
                    if (isset($innerException->errorInfo) && in_array($innerException->errorInfo[0], ['23503', '23001'])) {
                        $errors[] = 'Some items cannot be deleted because they have active transactions attached.';
                    } else {
                        $errors[] = $innerException->getMessage();
                    }
                }
            }

            // Filter out duplicate error messages
            $uniqueErrors = array_unique($errors);

            // --- Handle Warnings and Errors based on success/failure ratio ---
            if (! empty($uniqueErrors)) {
                $errorMessage = implode(' | ', $uniqueErrors);

                // Scenario 1: Partial Success (Some succeeded, some failed)
                if ($successCount > 0) {
                    return response()->json([
                        'status' => 'warning', // You can check this status in frontend to show a yellow warning alert
                        'message' => "Processed with warnings! Succeeded: {$successCount}. Failed due to: {$errorMessage}",
                        'success_count' => $successCount,
                    ], 200);
                }

                // Scenario 2: Complete Failure (None of the items succeeded)
                return response()->json([
                    'status' => false,
                    'message' => 'Action failed! '.$errorMessage,
                    'success_count' => 0,
                ], 200);
            }

            // Scenario 3: Complete Success (All items processed successfully)
            return response()->json([
                'status' => true,
                'message' => 'All items processed successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Global Bulk Action Failed: '.$e->getMessage());

            return response()->json(['status' => false, 'message' => 'System Error: '.$e->getMessage()], 200);
        }
    }
}
