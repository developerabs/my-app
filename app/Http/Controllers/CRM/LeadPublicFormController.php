<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadPublicFormController extends Controller
{
    public function index()
    {
        // return "hello";
        return view('backend.crm.leads.public_form');
    }

    /**
     * Store a newly created lead from public form.
     */
    public function submitLead(Request $request)
    {
        $validated = $this->validateLead($request);

        // phone, email or username mandatory check
        if (empty($validated['phone']) && empty($validated['email']) && empty($validated['username'])) {
            return response()->json([
                'status'  => false, 
                'message' => __('file.message.phone_email_username_required')
            ], 422);
        }

        return DB::transaction(function () use ($request, $validated) {
            try {
                if ($request->hasFile('attachment')) {
                    $validated['attachment'] = $this->uploadFiles($request, 'attachment', 'leads');
                }

                if (!empty($validated['address'])) {
                    $validated['address'] = ['address' => $validated['address']];
                }

                $lead = Lead::create($validated);

                if (!empty($validated['note'])) {
                    Note::create([
                        'noteable_type'     => Lead::class,
                        'noteable_id'       => $lead->id,
                        'note'              => $validated['note'],
                        'note_type'          => $validated['type'],
                        'status_id'         => $validated['status_id'] ?? null,
                        'next_follow_up_at' => $validated['follow_up_date'] ?? null,
                    ]);
                }

                return response()->json([
                    'status'  => true, 
                    'message' => __('file.message.lead_created_successfully'), 
                    'id'      => $lead->id
                ], 201);

            } catch (\Exception $e) {
                Log::error('Error creating public lead: ' . $e->getMessage());
                return response()->json([
                    'status'  => false, 
                    'message' => __('file.message.lead_creation_failed'), 
                    'error'   => $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Validate public lead request (Store Only)
     */
    private function validateLead(Request $request): array
    {
        return $request->validate([
            'type'            => 'required|string',
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string',
            'username'        => 'nullable|string|max:255|unique:leads,username,NULL,id,deleted_at,NULL',
            'company_name'    => 'nullable|string|max:255',
            'note'            => 'nullable|string',
            'description'     => 'nullable|string',
            'address'         => 'nullable|string',
            'website'         => 'nullable|string|max:255',
            'priority'        => 'nullable|string|in:low,medium,high',
            'expected_value'  => 'nullable|numeric',
            'follow_up_date'  => 'nullable|date',
            'category_id'     => 'required|string',
            'lead_subject_id' => 'required|integer',
            'lead_source_id'  => 'required|integer',
            'status_id'       => 'required|integer',
            'manager_id'      => 'required|integer',
            'assigned_to_id'  => 'required|integer',
            'attachment'      => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);
    }
}
