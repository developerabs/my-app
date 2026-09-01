@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.meeting_calendar') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection
@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<style>
    .fc-header-toolbar {
        flex-wrap: wrap;
        gap: 10px;
    }
    .calendar-select-wrapper {
        display: inline-flex;
        gap: 6px;
        align-items: center;
    }
    .calendar-select-wrapper select {
        padding: 4px 8px;
        font-size: 0.875rem;
        border-radius: 4px;
        border: 1px solid #ced4da;
    }
</style>
@endpush
@section('content')
    @component('backend.layouts.partials.header')
        @slot('title') {{ __('file.title.meeting_calendar') }} @endslot
        @slot('subtitle') {{ __('file.subtitle.scheduled_meetings') }} @endslot
    @endcomponent

    <div class="card shadow-sm">
        <div class="card-body">
            <div id="meetingCalendar"></div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="meetingDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="meetingDetailsTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="meetingDetailsBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                <button type="button" class="btn btn-info" id="completeMeetingButton">{{ __('file.button.complete_meeting') }}</button>
                <button type="button" class="btn btn-primary" id="editMeetingButton">{{ __('file.button.edit_meeting') }}</button>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="editMeetingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('file.title.edit_meeting') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editMeetingForm"><div class="modal-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">{{ __('file.label.title') }}</label><input class="form-control" name="title" required></div>
                <div class="col-md-4"><label class="form-label">{{ __('file.label.type') }}</label><select class="form-select" name="type" required>@foreach(\App\Enums\MeetingType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">{{ __('file.label.meeting_date_time') }}</label><input class="form-control" type="text" name="start_at" id="editMeetingDate" required><input type="hidden" name="end_at" value=""></div>
                <div class="col-md-4"><label class="form-label">{{ __('file.label.status') }}</label><select class="form-select" name="status_id" id="editMeetingStatus" required></select></div>
                <div class="col-md-4"><label class="form-label">{{ __('file.label.location') }}</label><input class="form-control" name="location"></div>
                <div class="col-md-4"><label class="form-label">{{ __('file.label.meeting_link') }}</label><input class="form-control" type="url" name="meeting_link"></div>
                <div class="col-md-4"><label class="form-label">{{ __('file.label.assigned_to') }}</label><select class="form-select" name="assigned_to_id"><option value="">-- Select user --</option>@foreach($users ?? [] as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-md-8"><label class="form-label">{{ __('file.label.description') }}</label><textarea class="form-control" name="description" rows="3"></textarea></div>
            </div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button><button class="btn btn-primary">{{ __('file.button.save_changes') }}</button></div></form>
        </div></div>
    </div>

    <div class="modal fade" id="completeMeetingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('file.title.complete_meeting') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="completeMeetingForm"><div class="modal-body">
                <div class="mb-3"><label class="form-label">{{ __('file.label.completion_notes') }}</label><textarea class="form-control" id="completeMeetingNotes" name="completion_notes" rows="3"></textarea></div>
            </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button><button type="submit" class="btn btn-primary">{{ __('file.button.complete_meeting') }}</button></div></form>
        </div>
    </div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
$(function () {
    let selectedMeetingId = null;
    let editMeetingDatePicker = flatpickr('#editMeetingDate', {
        enableTime: true,
        time_24hr: false,
        dateFormat: 'Y-m-d H:i:S',
        altInput: true,
        altFormat: 'd M Y H:i',
        static: true,
        allowInput: true
    });

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    // Generate Month Options with Current Month Selected
    let monthOptions = monthNames.map((month, index) => 
        `<option value="${index}" ${index === currentMonth ? 'selected' : ''}>${month}</option>`
    ).join('');

    // Generate Year Options with Current Year Selected
    let yearOptions = '';
    for (let y = currentYear - 10; y <= currentYear + 10; y++) {
        yearOptions += `<option value="${y}" ${y === currentYear ? 'selected' : ''}>${y}</option>`;
    }

    const dropdownHtml = `
        <div class="calendar-select-wrapper">
            <select id="calendarMonthSelect" class="form-select form-select-sm">${monthOptions}</select>
            <select id="calendarYearSelect" class="form-select form-select-sm">${yearOptions}</select>
        </div>
    `;

    const calendarEl = document.getElementById('meetingCalendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        dayMaxEvents: 3,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: { url: '{{ route('meetings.events') }}', method: 'GET' },
        datesSet: function(info) {
            // Sync Dropdown Value on Navigation (Prev / Next)
            const viewDate = calendar.getDate();
            $('#calendarMonthSelect').val(viewDate.getMonth());
            $('#calendarYearSelect').val(viewDate.getFullYear());
        },
        eventClick: function (info) {
            if (info.event.extendedProps.event_type !== 'meeting') {
                return;
            }

            selectedMeetingId = info.event.extendedProps.model_id;
            $.get('{{ url('/crm/meetings') }}/' + selectedMeetingId, function (response) {
                const meeting = response.meeting;
                const leadType = meeting.lead_type ? meeting.lead_type : 'lead';
                $('#meetingDetailsTitle').text(meeting.title);
                $('#meetingDetailsBody').html('<dl class="row mb-0">' +
                    '<dt class="col-sm-4">' + (leadType == 'lead' ? '{{ __('file.label.lead') }}' : '{{ __('file.label.deal') }}') + '</dt><dd class="col-sm-8">' + (meeting.lead || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.date_and_time') }}</dt><dd class="col-sm-8">' + meeting.start_at + (meeting.end_at ? ' - ' + meeting.end_at : '') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.title') }}</dt><dd class="col-sm-8">' + (meeting.title || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.type') }}</dt><dd class="col-sm-8">' + (meeting.type_label || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.status') }}</dt><dd class="col-sm-8">' + (meeting.status || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.assigned_to') }}</dt><dd class="col-sm-8">' + (meeting.assigned_to || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.location') }}</dt><dd class="col-sm-8">' + (meeting.location || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.meeting_link') }}</dt><dd class="col-sm-8">' + (meeting.meeting_link || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.is_completed') }}</dt><dd class="col-sm-8">' + (meeting.is_completed ? '{{ __('file.label.yes') }}' : '{{ __('file.label.no') }}') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.completed_by') }}</dt><dd class="col-sm-8">' + (meeting.completed_by || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.completed_at') }}</dt><dd class="col-sm-8">' + (meeting.completed_at || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.completion_notes') }}</dt><dd class="col-sm-8">' + (meeting.completion_notes || '-') + '</dd>' +
                    '<dt class="col-sm-4">{{ __('file.label.description') }}</dt><dd class="col-sm-8">' + (meeting.description || '-') + '</dd></dl>');
                $('#meetingDetailsModal').modal('show');
            });
        }
    });

    calendar.render();

    // Insert Dropdowns directly into headerToolbar right side
    $('.fc-toolbar-chunk:last-child').html(dropdownHtml);

    // Event listener for Month/Year changes
    $(document).on('change', '#calendarMonthSelect, #calendarYearSelect', function () {
        const selectedMonth = parseInt($('#calendarMonthSelect').val());
        const selectedYear = parseInt($('#calendarYearSelect').val());
        calendar.gotoDate(new Date(selectedYear, selectedMonth, 1));
    });

    $('#editMeetingButton').on('click', function () {
        $.get('{{ url('/crm/meetings') }}/' + selectedMeetingId, function (response) {
            const meeting = response.meeting;
            const form = $('#editMeetingForm');
            Object.keys(meeting).forEach(function (key) {
                if (key !== 'start_at' && key !== 'end_at') {
                    form.find('[name="' + key + '"]').val(meeting[key] || '');
                }
            });
            editMeetingDatePicker.setDate(meeting.start_at || null, false, 'Y-m-d H:i:S');
            form.find('[name="end_at"]').val('');
            $.get('{{ route('meetings.statuses') }}', function (statuses) {
                $('#editMeetingStatus').html((statuses.statuses || []).map(function (status) { return '<option value="' + status.id + '">' + status.name + '</option>'; }).join('')).val(meeting.status_id);
                $('#meetingDetailsModal').modal('hide'); $('#editMeetingModal').modal('show');
            });
        });
    });

    $('#editMeetingForm').on('submit', function (event) {
        event.preventDefault();
        $.ajax({ url: '{{ url('/crm/meetings') }}/' + selectedMeetingId, type: 'PATCH', data: $(this).serialize() + '&_token={{ csrf_token() }}' })
            .done(function (response) { 
                $('#editMeetingModal').modal('hide'); 
                calendar.refetchEvents(); 
                showFloatingAlert("success", response.message);
            })
            .fail(function (xhr) { showFloatingAlert("error", xhr.responseJSON?.message || 'Unable to update meeting.'); });
    });

    // Complete Meeting Button
    $('#completeMeetingButton').on('click', function () {
        $('#meetingDetailsModal').modal('hide');
        $('#completeMeetingModal').modal('show');
    });
    $('#completeMeetingModal').on('show.bs.modal', function () {
        $('#completeMeetingNotes').val('');
    });
    $('#completeMeetingForm').on('submit', function (event) {
        event.preventDefault();
        $.ajax({ url: '{{ url('/crm/meetings') }}/' + selectedMeetingId + '/complete', type: 'POST', data: $(this).serialize() + '&_token={{ csrf_token() }}' })
            .done(function (response) { 
                $('#completeMeetingModal').modal('hide'); 
                calendar.refetchEvents(); 
                showFloatingAlert("success", response.message);
            })
            .fail(function (xhr) { showFloatingAlert("error", xhr.responseJSON?.message || 'Unable to complete meeting.'); });
    });
});
</script>
@endpush