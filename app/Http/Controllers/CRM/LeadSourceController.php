<?php

namespace App\Http\Controllers\CRM;

use App\DataTables\LeadSourceDataTable;
use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LeadSourceController extends Controller
{
    public function index(LeadSourceDataTable $dataTable)
    {
        $leadSources = Cache::tags([tenant_tag()])->remember('all_lead_sources_' . tenant('id'), 3600, function () {
            return LeadSource::select('id', 'name', 'slug', 'sort_order', 'is_active')->get();
        });
        return $dataTable->render('backend.crm.lead_source.index', compact('leadSources'));
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $slug = LeadSource::generateUniqueSlug($request->name);

            $leadSource = LeadSource::create([
                'name' => $validatedData['name'],
                'slug' => $slug,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $validatedData['order'] ?? 0,
            ]);
            return response()->json([
                'status' => true, 
                'message' => __('file.message.lead_source_created_successfully'),
                'id' => $leadSource->id
                ]);
        } catch (\Exception $e) {
            Log::error('Error creating Lead Source: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.lead_source_creation_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function edit(LeadSource $leadSource)
    {
        return response()->json([
            'lead_source' => $leadSource,
        ]);
    }

    public function update(Request $request, LeadSource $leadSource)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $slug = LeadSource::generateUniqueSlug($request->name, $leadSource->id);

            $leadSource->update([
                'name' => $validatedData['name'],
                'slug' => $slug,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $validatedData['order'] ?? 0,
            ]);
            return response()->json([
                'status' => true, 
                'message' => __('file.message.lead_source_updated_successfully'),
                'id' => $leadSource->id
                ]);
        } catch (\Exception $e) {
            Log::error('Error updating Lead Source: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.lead_source_update_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(LeadSource $leadSource)
    {
        try {
            $leadSource->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.lead_source_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting Lead Source: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.lead_source_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        try {
            LeadSource::whereIn('id', $ids)->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.lead_source_bulk_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting Lead Sources: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.lead_source_bulk_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
