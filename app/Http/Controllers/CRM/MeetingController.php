<?php

namespace App\Http\Controllers\CRM;

use App\Enums\MeetingType;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    public function index()
    {
        $users = User::query()->select('id', 'name')->orderBy('name')->get();

        return view('backend.crm.meetings.index', compact('users'));
    }

    public function statuses()
    {
        return response()->json([
            'statuses' => Status::query()
                ->where('type', 'meeting')
                ->active()
                ->select('id', 'name', 'color')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function events(Request $request)
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        // FullCalendar's month grid can start up to six days before the visible month.
        $month = Carbon::parse($validated['start'])->addWeek();
        $monthRange = [
            'start' => $month->startOfMonth(),
            'end' => $month->copy()->startOfMonth()->addMonth(),
        ];

        $events = $this->meetingEvents($monthRange)->values();

        return response()->json($events);
    }

    public function show(Meeting $meeting)
    {
        abort_unless($this->canAccess($meeting), 403);

        $meeting->load(['meetingable', 'status', 'assignedTo', 'category', 'creator', 'completedBy']);

        return response()->json(['meeting' => $this->serialize($meeting)]);
    }

    public function update(Request $request, Meeting $meeting)
    {
        abort_unless($this->canAccess($meeting), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'type' => ['required', Rule::enum(MeetingType::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'status_id' => ['required', 'exists:statuses,id'],
            'assigned_to_id' => ['nullable', 'exists:users,id'],
            'reminder_at' => ['nullable', 'date'],
        ]);

        abort_unless(Status::whereKey($validated['status_id'])->where('type', 'meeting')->exists(), 422, 'Invalid meeting status.');

        try {
            $meeting->update(array_merge($validated, ['updated_by' => Auth::id()]));
            $meeting->load(['meetingable', 'status', 'assignedTo', 'category', 'creator', 'completedBy']);

            return response()->json(['status' => true, 'meeting' => $this->serialize($meeting), 'message' => __('file.message.meeting_updated')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('file.message.meeting_update_failed')], 422);
        }
        
    }

    private function meetingEvents(array $dateRange)
    {
        $userId = Auth::id();

        $meetings = Meeting::query()
            ->with('status')
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)->orWhere('assigned_to_id', $userId);
            })
            ->where('start_at', '<', $dateRange['end'])
            ->where(function ($query) use ($dateRange) {
                $query->whereNull('end_at')->where('start_at', '>=', $dateRange['start'])
                    ->orWhere(function ($query) use ($dateRange) {
                        $query->whereNotNull('end_at')->where('end_at', '>=', $dateRange['start']);
                    });
            })
            ->orderBy('start_at')
            ->get();

        return $meetings->map(function (Meeting $meeting) {
            return [
                'id' => 'meeting-' . $meeting->id,
                'title' => $meeting->title,
                'start' => $meeting->start_at->toIso8601String(),
                'end' => $meeting->end_at?->toIso8601String(),
                'backgroundColor' => $meeting->status?->color,
                'is_completed' => $meeting->is_completed,
                'extendedProps' => [
                    'event_type' => 'meeting',
                    'model_id' => $meeting->id,
                    'status' => $meeting->status?->name,
                ],
            ];
        });
    }

    private function canAccess(Meeting $meeting): bool
    {
        return $meeting->created_by == Auth::id() || $meeting->assigned_to_id == Auth::id();
    }

    private function serialize(Meeting $meeting): array
    {
        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'description' => $meeting->description,
            'start_at' => formatDate($meeting->start_at, true),
            'end_at' => formatDate($meeting->end_at, true),
            'type' => $meeting->type?->value,
            'type_label' => $meeting->type?->label(),
            'location' => $meeting->location,
            'meeting_link' => $meeting->meeting_link,
            'status_id' => $meeting->status_id,
            'status' => $meeting->status?->name,
            'assigned_to_id' => $meeting->assigned_to_id,
            'assigned_to' => $meeting->assignedTo?->name,
            'lead' => $meeting->meetingable?->name,
            'lead_type' => $meeting->meetingable?->type,
            'created_at' => formatDate($meeting->created_at, true),
            'is_completed' => $meeting->is_completed,
            'completion_notes' => $meeting->completion_notes,
            'completed_at' => formatDate($meeting->completed_at, true),
            'completed_by' => $meeting->completedBy?->name,
        ];
    }

    public function complete(Request $request, Meeting $meeting)
    {
        abort_unless($this->canAccess($meeting), 403);

        $validated = $request->validate([
            'completion_notes' => ['required', 'string'],
        ]);

        try {
            $meeting->update([
                'is_completed' => true,
                'completed_at' => now(),
                'completed_by' => Auth::id(),
                'completion_notes' => $validated['completion_notes'],
            ]);

            return response()->json(['status' => true, 'message' => __('file.message.meeting_completed')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('file.message.meeting_complete_failed')], 422);
        }
    }
}
