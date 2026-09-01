<?php

namespace App\Http\Controllers\CRM;

use App\DataTables\CRMTodayFollowUPDataTable;
use App\DataTables\DealDataTable;
use App\DataTables\LeadDataTable;
use App\Enums\MeetingType;
use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadSubject;
use App\Models\Note;
use App\Models\Status;
use App\Models\User;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    use HasFiles;

    public function index(LeadDataTable $dataTable)
    {
        $data = $this->getCommonFormData('lead');
        return $dataTable->render('backend.crm.leads.index', $data);
    }

    public function deals(DealDataTable $dataTable)
    {
        $data = $this->getCommonFormData('deal');
        return $dataTable->render('backend.crm.leads.index', $data);
    }

    public function todaysFollowUp(CRMTodayFollowUPDataTable $dataTable)
    {
        $data = $this->getCommonFormDataLeadDeal('leadDeal');
        $data['isTodayFollowUpPage'] = true;
        return $dataTable->render('backend.crm.leads.index', $data);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLead($request);

        // phone or emain or username must be inserted
        if (empty($validated['phone']) && empty($validated['email']) && empty($validated['username'])) {
            return response()->json(['status' => false, 'message' => __('file.message.phone_email_username_required')]);
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
                        'noteable_type'    => Lead::class,
                        'noteable_id'      => $lead->id,
                        'note'             => $validated['note'],
                        'note_type'        => $validated['type'],
                        'status_id'        => $validated['status_id'],
                        'next_follow_up_at' => $validated['follow_up_date'] ?? null,
                    ]);
                }

                return response()->json(['status' => true, 'message' => __('file.message.lead_created_successfully'), 'id' => $lead->id]);
            } catch (\Exception $e) {
                Log::error('Error creating lead: ' . $e->getMessage());
                return response()->json(['status' => false, 'message' => __('file.message.lead_creation_failed'), 'error' => $e->getMessage()], 500);
            }
        });
    }

    public function edit(Lead $lead)
    {
        $lead->load(['category', 'leadSubject', 'leadSource', 'leadStatus']);
        $lead->follow_up_date = $lead->follow_up_date ? formatDate($lead->follow_up_date, true) : null;

        return response()->json(['lead' => $lead]);
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $this->validateLead($request, isUpdate: true);

        if (empty($validated['phone']) && empty($validated['email']) && empty($validated['username'])) {
            return response()->json(['status' => false, 'message' => __('file.message.phone_email_username_required')], 422);
        }

        return DB::transaction(function () use ($request, $lead, $validated) {
            try {
                if ($request->hasFile('attachment')) {
                    $validated['attachment'] = $this->updateFile($request, 'attachment', $lead->attachment, 'leads');
                }

                if (!empty($validated['address'])) {
                    $validated['address'] = ['address' => $validated['address']];
                }

                $lead->update($validated);

                if (!empty($validated['note'])) {
                    Note::create([
                        'noteable_type'    => Lead::class,
                        'noteable_id'      => $lead->id,
                        'note'             => $validated['note'],
                        'note_type'        => $validated['type'],
                        'status_id'        => $validated['status_id'] ?? $lead->status_id,
                        'next_follow_up_at' => $validated['follow_up_date'] ?? null,
                    ]);
                }

                return response()->json(['status' => true, 'message' => __('file.message.lead_updated_successfully'), 'id' => $lead->id]);
            } catch (\Exception $e) {
                Log::error('Error updating lead: ' . $e->getMessage());
                return response()->json(['status' => false, 'message' => __('file.message.lead_update_failed'), 'error' => $e->getMessage()], 500);
            }
        });
    }

    public function destroy(Lead $lead)
    {
        try {
            $lead->delete();
            return response()->json(['status' => true, 'message' => __('file.message.lead_deleted_successfully')]);
        } catch (\Exception $e) {
            Log::error('Error deleting lead: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => __('file.message.lead_delete_failed'), 'error' => $e->getMessage()], 500);
        }
    }

    public function addNote(Request $request, Lead $lead)
    {
        $validated = $this->validateNote($request);

        return DB::transaction(function () use ($request, $lead, $validated) {
            try {
                if ($request->hasFile('attachment')) {
                    $validated['attachment'] = $this->uploadFiles($request, 'attachment', 'lead_notes');
                }

                $note = Note::create([
                    'noteable_type'    => Lead::class,
                    'noteable_id'      => $lead->id,
                    'note'             => $validated['note'],
                    'note_type'        => $lead->type,
                    'effective_phone'  => $validated['phone'] ?? null,
                    'status_id'        => $validated['status_id'],
                    'next_follow_up_at' => $validated['follow_up_date'] ?? null,
                    'attachment'       => $validated['attachment'] ?? null,
                ]);

                if ($request->boolean('schedule_meeting')) {
                    $lead->meetings()->create([
                        'title'          => $validated['meeting_title'],
                        'description'    => $validated['meeting_description'] ?? null,
                        'start_at'       => $validated['meeting_start_at'],
                        'end_at'         => $validated['meeting_end_at'] ?? null,
                        'type'           => $validated['meeting_type'],
                        'location'       => $validated['meeting_location'] ?? null,
                        'meeting_link'   => $validated['meeting_link'] ?? null,
                        'category_id'    => $validated['meeting_category_id'] ?? null,
                        'status_id'      => $validated['meeting_status_id'],
                        'reminder_at'    => $validated['meeting_reminder_at'] ?? null,
                        'assigned_to_id' => $validated['meeting_assigned_to_id'] ?? null,
                        'created_at'     => $note->created_at,
                        'updated_at'     => $note->created_at,
                    ]);
                    $note->update(['is_meeting_set' => true]);
                }

                $lead->update([
                    'status_id'       => $validated['status_id'],
                    'effective_phone' => $validated['phone'] ?? $lead->effective_phone,
                    'follow_up_date'  => $validated['follow_up_date'] ?? $lead->follow_up_date,
                    'assigned_to_id'  => $validated['assigned_to_id'] ?? $lead->assigned_to_id,
                ]);

                if ($request->ajax()) {
                    return response()->json(['status' => true, 'message' => __('file.message.lead_note_added'), 'note' => $note]);
                }
                return redirect()->back()->with('success', __('file.message.lead_note_added'));
            } catch (\Exception $e) {
                Log::error('Error adding lead note: ' . $e->getMessage());
                return response()->json(['status' => false, 'message' => __('file.message.lead_note_failed'), 'error' => $e->getMessage()], 500);
            }
        });
    }

    public function convertToDeal(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'note'           => 'required|string',
            'type'           => 'required|string',
            'category_id'    => 'required|string',
            'status_id'      => 'required|integer',
            'follow_up_date' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($request, $lead, $validated) {
            try {
                $note = Note::create([
                    'noteable_type'    => Lead::class,
                    'noteable_id'      => $lead->id,
                    'note'             => $validated['note'],
                    'note_type'        => $validated['type'],
                    'status_id'        => $validated['status_id'],
                    'next_follow_up_at' => $validated['follow_up_date'] ?? null,
                ]);

                $lead->update([
                    'type'           => $validated['type'],
                    'category_id'    => $validated['category_id'],
                    'status_id'      => $validated['status_id'],
                    'follow_up_date' => $validated['follow_up_date'] ?? $lead->follow_up_date,
                ]);

                if ($request->ajax()) {
                    return response()->json(['status' => true, 'message' => __('file.message.lead_converted_successfully'), 'note' => $note]);
                }
                return redirect()->back()->with('success', __('file.message.lead_converted_successfully'));
            } catch (\Exception $e) {
                Log::error('Error converting lead: ' . $e->getMessage());
                return redirect()->back()->with('error', __('file.message.lead_conversion_failed'));
            }
        });
    }

    public function convertToCustomer(Request $request, Lead $lead)
    {
        return DB::transaction(function () use ($lead) {
            try {
                $customer = Customer::create([
                    'name'       => $lead->name,
                    'email'      => $lead->email,
                    'phone'      => $lead->phone,
                    'created_by' => Auth::id(),
                ]);

                $lead->update([
                    'customer_id'  => $customer->id,
                    'converted_at' => now(),
                    'updated_by'   => Auth::id(),
                ]);

                Note::create([
                    'noteable_type' => Lead::class,
                    'noteable_id'   => $lead->id,
                    'note'          => 'Converted to customer id: ' . $customer->id,
                    'created_by_id' => Auth::id(),
                    'status_id'     => $lead->status_id,
                    'note_type'     => $lead->type,
                ]);

                return redirect()->back()->with('success', __('file.message.lead_converted_successfully'));
            } catch (\Exception $e) {
                Log::error('Error converting lead: ' . $e->getMessage());
                return redirect()->back()->with('error', __('file.message.lead_conversion_failed'));
            }
        });
    }

    public function markFailed(Request $request, Lead $lead)
    {
        return $this->toggleFailedStatus($request, $lead, isFailed: true);
    }

    public function removeFromFailed(Request $request, Lead $lead)
    {
        return $this->toggleFailedStatus($request, $lead, isFailed: false);
    }

    public function show(Lead $lead)
    {
        $lead->load(['category', 'leadSubject', 'leadSource', 'leadStatus', 'manager', 'assignedTo', 'updatedBy']);

        $tenantId = tenant('id');
        $cacheKey = "crm_lead_statuses_{$tenantId}_cat_{$lead->category_id}";

        $leadStatuses = Cache::tags([tenant_tag()])->remember($cacheKey, 3600, function () use ($lead) {
            return Status::select('id', 'name')->active()->where('type', 'lead')->where('category_id', $lead->category_id)->latest()->get();
        });

        $leadNotes = $lead->notes()->with(['creator', 'status'])->latest()->paginate(10);
        $leadMeetingsByCreatedAt = $lead->meetings()->with(['status', 'assignedTo'])->get()->groupBy(fn ($meeting) => formatDate($meeting->created_at, true));

        return view('backend.crm.leads.show', compact('lead', 'leadStatuses', 'leadNotes', 'leadMeetingsByCreatedAt'));
    }

    public function history(Lead $lead)
    {
        $meetingsByCreatedAt = $lead->meetings()->with(['status', 'assignedTo'])->get()->groupBy(fn ($meeting) => formatDate($meeting->created_at, true));

        $notes = $lead->notes()->with(['creator', 'status'])->latest()->take(10)->get()->map(function ($note) use ($meetingsByCreatedAt) {
            $formattedDate = formatDate($note->created_at, true);
            $meeting = $meetingsByCreatedAt->get($formattedDate, collect())->first();

            return [
                'id'                => $note->id,
                'note'              => $note->note,
                'note_type'         => $note->note_type,
                'status'            => $note->status?->name,
                'color'             => $note->status?->color,
                'next_follow_up_at' => $note->next_follow_up_at ? formatDate($note->next_follow_up_at, true) : null,
                'attachment_url'    => $note->attachment_url ?? null,
                'created_by'        => $note->creator?->name,
                'created_at'        => $formattedDate,
                'effective_phone'   => $note->effective_phone,
                'meeting'           => $meeting ? [
                    'id'          => $meeting->id,
                    'title'       => $meeting->title,
                    'start_at'    => formatDate($meeting->start_at, true),
                    'end_at'      => $meeting->end_at ? formatDate($meeting->end_at, true) : null,
                    'type'        => $meeting->type?->label(),
                    'status'      => $meeting->status?->name,
                    'location'    => $meeting->location,
                    'assigned_to' => $meeting->assignedTo?->name,
                ] : null,
            ];
        });

        return response()->json(['history' => $notes]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        try {
            Lead::whereIn('id', $ids)->delete();
            return response()->json(['status' => true, 'message' => __('file.message.lead_bulk_deleted_successfully')]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting leads: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => __('file.message.lead_bulk_delete_failed'), 'error' => $e->getMessage()], 500);
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                               PRIVATE HELPERS                              */
    /* -------------------------------------------------------------------------- */

    private function getCommonFormData(string $type): array
    {
        $tenantId = tenant('id');
        $tag = tenant_tag();

        $categories = Cache::tags([$tag])->remember("all_{$type}_categories_{$tenantId}", 3600, function () use ($type) {
            $categoryType = CategoryType::where('name', $type)->first();
            return $categoryType ? $categoryType->categories()->active()->select('id', 'name')->latest()->get() : collect();
        });

        $leadSubjects = Cache::tags([$tag])->remember("all_lead_subjects_{$tenantId}", 3600, fn () => LeadSubject::select('id', 'name')->active()->latest()->get());
        $leadSources  = Cache::tags([$tag])->remember("all_lead_sources_{$tenantId}", 3600, fn () => LeadSource::select('id', 'name')->active()->latest()->get());
        $users        = Cache::tags([$tag])->remember("all_users_{$tenantId}", 3600, fn () => User::select('id', 'name')->latest()->get());
        $statuses     = Cache::tags([$tag])->remember("all_crm_{$type}_statuses_{$tenantId}", 3600, fn () => Status::whereIn('type', [$type])->active()->select('id', 'name')->latest()->get());

        return compact('categories', 'leadSources', 'leadSubjects', 'users', 'statuses');
    }
    private function getCommonFormDataLeadDeal(string $type): array
    {
        $tenantId = tenant('id');
        $tag = tenant_tag();

        $categories = Cache::tags([$tag])->remember("all_{$type}_categories_{$tenantId}", 3600, function () use ($type) {
            $categoryType = CategoryType::whereIn('name', ['lead', 'deal'])->get();
            return $categoryType ? $categoryType->flatMap(fn($ct) => $ct->categories()->active()->select('id', 'name')->latest()->get()) : collect();
        });

        $leadSubjects = Cache::tags([$tag])->remember("all_lead_subjects_{$tenantId}", 3600, fn () => LeadSubject::select('id', 'name')->active()->latest()->get());
        $leadSources  = Cache::tags([$tag])->remember("all_lead_sources_{$tenantId}", 3600, fn () => LeadSource::select('id', 'name')->active()->latest()->get());
        $users        = Cache::tags([$tag])->remember("all_users_{$tenantId}", 3600, fn () => User::select('id', 'name')->latest()->get());
        $statuses     = Cache::tags([$tag])->remember("all_crm_{$type}_statuses_{$tenantId}", 3600, fn () => Status::whereIn('type', ['lead', 'deal'])->active()->select('id', 'name')->latest()->get());

        return compact('categories', 'leadSources', 'leadSubjects', 'users', 'statuses');
    }

    private function toggleFailedStatus(Request $request, Lead $lead, bool $isFailed)
    {
        $validated = $request->validate([
            'note'      => 'required|string',
            'status_id' => 'required|integer',
        ]);

        $defaultNote = $isFailed ? 'Marked as failed' : 'Removed from failed';
        $successMsg  = $isFailed ? __('file.message.lead_marked_failed_successfully') : __('file.message.lead_removed_from_failed_successfully');
        $failedMsg   = $isFailed ? __('file.message.lead_mark_failed') : __('file.message.lead_remove_from_failed');

        return DB::transaction(function () use ($request, $lead, $validated, $isFailed, $defaultNote, $successMsg, $failedMsg) {
            try {
                Note::create([
                    'noteable_type' => Lead::class,
                    'noteable_id'   => $lead->id,
                    'note'          => $validated['note'] ?? $defaultNote,
                    'status_id'     => $validated['status_id'],
                    'note_type'     => $lead->type,
                ]);

                $lead->update([
                    'status_id' => $validated['status_id'],
                    'is_failed' => $isFailed,
                    'failed_at' => $isFailed ? now() : null,
                ]);

                if ($request->ajax()) {
                    return response()->json(['status' => true, 'message' => $successMsg]);
                }
                return redirect()->back()->with('success', $successMsg);
            } catch (\Exception $e) {
                Log::error("Error updating failed status: " . $e->getMessage());
                if ($request->ajax()) {
                    return response()->json(['status' => false, 'message' => $failedMsg, 'error' => $e->getMessage()], 500);
                }
                return redirect()->back()->with('error', $failedMsg);
            }
        });
    }

    private function validateLead(Request $request, bool $isUpdate = false): array
    {
        $nullableOnUpdate = $isUpdate ? 'nullable' : 'required';

        return $request->validate([
            'type'            => 'required|string',
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string',
            'username'        => 'nullable|string|max:255|unique:leads,username,' . ($isUpdate ? $request->route('lead')->id : 'NULL') . ',id,deleted_at,NULL',
            'company_name'    => 'nullable|string|max:255',
            'note'            => 'nullable|string',
            'description'     => 'nullable|string',
            'address'         => 'nullable|string',
            'website'         => 'nullable|string|max:255',
            'priority'        => 'nullable|string|in:low,medium,high',
            'expected_value'  => 'nullable|numeric',
            'follow_up_date'  => 'nullable|date',
            'category_id'     => "{$nullableOnUpdate}|string",
            'lead_subject_id' => "{$nullableOnUpdate}|integer",
            'lead_source_id'  => "{$nullableOnUpdate}|integer",
            'status_id'       => "{$nullableOnUpdate}|integer",
            'manager_id'      => "{$nullableOnUpdate}|integer",
            'assigned_to_id'  => "{$nullableOnUpdate}|integer",
            'attachment'      => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);
    }

    private function validateNote(Request $request): array
    {
        return $request->validate([
            'note'                   => 'required|string',
            'phone'                  => 'nullable|string',
            'status_id'              => 'required|integer',
            'follow_up_date'         => 'nullable|date',
            'assigned_to_id'         => 'nullable|integer',
            'attachment'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'schedule_meeting'       => 'nullable|boolean',
            'meeting_title'          => 'nullable|required_if:schedule_meeting,1|string|max:255',
            'meeting_description'    => 'nullable|string',
            'meeting_start_at'       => 'nullable|required_if:schedule_meeting,1|date',
            'meeting_end_at'         => 'nullable|date|after_or_equal:meeting_start_at',
            'meeting_type'           => ['nullable', 'required_if:schedule_meeting,1', Rule::enum(MeetingType::class)],
            'meeting_location'       => 'nullable|string|max:255',
            'meeting_link'           => 'nullable|url|max:255',
            'meeting_category_id'    => 'nullable|exists:categories,id',
            'meeting_status_id'      => [
                'nullable',
                'required_if:schedule_meeting,1',
                Rule::exists('statuses', 'id')->where(fn ($query) => $query->where('type', 'meeting')->where('is_active', true)),
            ],
            'meeting_reminder_at'    => 'nullable|date',
            'meeting_assigned_to_id' => 'nullable|exists:users,id',
        ], [
            'meeting_title.required_if'     => 'Please enter a meeting title when scheduling a meeting.',
            'meeting_start_at.required_if' => 'Please select a meeting date and time when scheduling a meeting.',
            'meeting_type.required_if'     => 'Please select a meeting type when scheduling a meeting.',
            'meeting_status_id.required_if'   => 'Please select a meeting status when scheduling a meeting.',
        ]);
    }
}