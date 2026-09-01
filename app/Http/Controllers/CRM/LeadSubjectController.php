<?php

namespace App\Http\Controllers\CRM;

use App\DataTables\LeadSubjectDataTable;
use App\Http\Controllers\Controller;
use App\Models\LeadSubject;
use App\Rules\UniqueWithTrashCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LeadSubjectController extends Controller
{

    public function index(LeadSubjectDataTable $dataTable)
    {
        $leadSubjects = Cache::tags([tenant_tag()])->remember('all_lead_subjects_' . tenant('id'), 3600, function () {
            return LeadSubject::select('id', 'name', 'slug', 'sort_order', 'is_active')->get();
        });
        return $dataTable->render('backend.crm.lead_subject.index', compact('leadSubjects'));
    }
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name' => ['required', 'string', 'max:255', new UniqueWithTrashCheck(LeadSubject::class, 'name')],
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $slug = LeadSubject::generateUniqueSlug($request->name);

            $leadSubject = LeadSubject::create([
                'name' => $validateData['name'],
                'slug' => $slug,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $validateData['order'] ?? 0,
            ]);
            return response()->json([
                'status' => true, 
                'message' => __('file.message.lead_subject_created_successfully'),
                'id' => $leadSubject->id
                ]);
        } catch (\Exception $e) {
            Log::error('Error creating Lead Subject: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => __('file.message.lead_subject_creation_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }

    }

    public function edit(LeadSubject $leadSubject)
    {
        return response()->json([
            'lead_subject' => $leadSubject,
        ]);
    }

    public function update(Request $request, LeadSubject $leadSubject)
    {
        $validateData = $request->validate([
            'name' => ['required', 'string', 'max:255', new UniqueWithTrashCheck(LeadSubject::class, 'name', $leadSubject->id)],
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $slug = LeadSubject::generateUniqueSlug($request->name, $leadSubject->id);

            $leadSubject->update([
                'name' => $validateData['name'],
                'slug' => $slug,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $validateData['order'] ?? 0,
            ]);
            return response()->json([
                'status' => true, 
                'message' => __('file.message.lead_subject_updated_successfully'),
                'id' => $leadSubject->id
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => __('file.message.lead_subject_update_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(LeadSubject $leadSubject)
    {
        try {
            $leadSubject->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.lead_subject_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('file.message.lead_subject_deletion_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:lead_subjects,id',
        ]);

        try {
            LeadSubject::whereIn('id', $request->ids)->delete();
            return response()->json([
                'status' => true,
                'message' => __('file.message.lead_subject_bulk_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('file.message.lead_subject_bulk_deletion_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
