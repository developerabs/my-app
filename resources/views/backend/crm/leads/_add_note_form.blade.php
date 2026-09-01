<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.note') }} <span class="text-danger">*</span></label>
        <textarea name="note" class="form-control" rows="2"
            placeholder="{{ __('file.placeholder.add_note') }}" required></textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none selectnew2" name="status_id" required>
            
        </select>
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.follow_up_date') }}</label>
        <input type="text" class="form-control shadow-none date-picker" name="follow_up_date" placeholder="{{ __('file.placeholder.select_date_and_time') }}" autocomplete="off">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.assigned_to') }}</label>
        <select class="form-select shadow-none selectnew2" name="assigned_to_id">
            <option value="">-- {{ __('file.option.select') }}</option>
            @if(isset($users))
                @foreach($users ?? [] as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-4">
        @include('backend.layouts.partials._phone_number', ['isRequired' => false])
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.attachment') }} (Image/PDF)</label>
        <input type="file" class="form-control shadow-none" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <small class="text-muted" style="font-size: 10px;">{{ __('Accepted: JPG, PNG, PDF') }}</small>
        <div class="small mt-1 current-attachment"></div>
    </div>
    <div class="col-md-12 mt-2">
        <input type="hidden" name="meeting_category_id" value="">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="schedule_meeting" value="1" id="scheduleMeeting">
            <label class="form-check-label fw-bold small" for="scheduleMeeting">{{ __('file.field.schedule_a_meeting') }}</label>
        </div>
    </div>
    <div class="col-md-12" id="meetingFields" style="display: none;">
        <hr>
        <h6 class="mb-3">{{ __('file.field.meeting_details') }}</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.title') }} <span class="text-danger">*</span></label>
                <input type="text" name="meeting_title" class="form-control meeting-field">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.type') }} <span class="text-danger">*</span></label>
                <select name="meeting_type" class="form-select meeting-field">
                    <option value="">-- {{ __('file.option.select') }} --</option>
                    @foreach(\App\Enums\MeetingType::cases() as $meetingType)
                        <option value="{{ $meetingType->value }}">{{ $meetingType->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.meeting_date_time') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control meeting-field meeting-date-picker" autocomplete="off" placeholder="{{ __('file.placeholder.select_meeting_date_and_time') }}">
                <input type="hidden" name="meeting_start_at" class="meeting-field">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.location') }}</label>
                <input type="text" name="meeting_location" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }} <span class="text-danger">*</span></label>
                <select name="meeting_status_id" class="form-select shadow-none selectnew2">
                    
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.assigned_to') }}</label>
                <select name="meeting_assigned_to_id" class="form-select shadow-none selectnew2">
                    <option value="">-- {{ __('file.option.select') }} --</option>
                    @foreach($users ?? [] as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.meeting_link') }}</label>
                <input type="url" name="meeting_link" class="form-control">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold small mb-1">{{ __('file.field.description') }}</label>
                <textarea name="meeting_description" class="form-control" rows="1"></textarea>
            </div>
        </div>
    </div>
</div>