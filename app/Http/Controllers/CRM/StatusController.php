<?php

namespace App\Http\Controllers\CRM;

use App\DataTables\StatusDataTable;
use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StatusController extends Controller
{
    public function index(StatusDataTable $dataTable)
    {
        $statuses = Cache::tags([tenant_tag()])->remember('all_statuses_' . tenant('id'), 3600, function () {
            return Status::select('id', 'name', 'slug', 'progress', 'color', 'sort_order', 'is_active')->get();
        });
        return $dataTable->render('backend.crm.status.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:lead,deal,meeting',
            'category_id' => 'nullable|exists:categories,id',
            'progress' => 'required|integer|min:0|max:100',
            'color' => 'required|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $slug = Status::generateUniqueSlug($request->name);

            $status = Status::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'category_id' => $validated['category_id'] ?? null,
                'slug' => $slug,
                'progress' => $validated['progress'],
                'color' => $validated['color'] ?? '#000000',
                'sort_order' => $validated['order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'status' => true,
                'message' => __('file.message.status_created_successfully'),
                'id' => $status->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Status: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.status_creation_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Status $status)
    {
        return response()->json([
            'status' => $status,
        ]);
    }

    public function update(Request $request, Status $status)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:lead,deal,meeting',
            'category_id' => 'nullable|exists:categories,id',
            'progress' => 'required|integer|min:0|max:100',
            'color' => 'required|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $slug = Status::generateUniqueSlug($request->name, $status->id);

            $status->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'category_id' => $validated['category_id'] ?? null,
                'slug' => $slug,
                'progress' => $validated['progress'],
                'color' => $validated['color'] ?? '#000000',
                'sort_order' => $validated['order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'status' => true,
                'message' => __('file.message.status_updated_successfully'),
                'id' => $status->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating Status: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.status_update_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Status $status)
    {
        
        try {
            $status->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.status_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting Status: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.status_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        try {
            Status::whereIn('id', $ids)->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.statuses_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting Statuses: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.statuses_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getStatusesByCategoryAndType(Request $request)
    {
        $categoryId = $request->input('category_id');
        $statuses = Status::select('id', 'name')->active()->where('category_id', $categoryId)->where('type', $request->input('type'))->get();

        return response()->json([
            'status' => true,
            'statuses' => $statuses,
        ]);
    }
    // public function getStatusesByType(Request $request)
    // {
    //     $validated = $request->validate([
    //         'type' => ['required', 'string'],
    //         'category_id' => ['nullable'],
    //     ]);
        
    //     $statuses = Status::query()
    //         ->select('id', 'name')
    //         ->active()
    //         ->where('type', $validated['type'])
    //         ->when(
    //             !empty($validated['category_id']),
    //             fn ($query) => $query->where('category_id', $validated['category_id'])
    //         )
    //         ->get();

    //     return response()->json([
    //         'status' => true,
    //         'statuses' => $statuses,
    //     ]);
    // }
}
