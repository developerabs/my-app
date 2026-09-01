@php
    $isEdit = $isEdit ?? false;
@endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="name" required placeholder="{{ __('file.placeholder.full_name') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.type') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" name="type" required>
            <option value="">-- {{ __('file.option.select') }}</option>
            <option value="lead" selected>Lead</option>
            <option value="deal">Deal</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.category') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" name="category_id" required>
            
        </select>
    </div>
    <div class="col-md-4">
        @include('backend.layouts.partials._phone_number', ['isRequired' => false])
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.email') }}</label>
        <input type="email" class="form-control shadow-none" name="email" placeholder="{{ __('file.placeholder.email_example') }}">
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.username') }}</label>
        <input type="text" class="form-control shadow-none" name="username" placeholder="{{ __('file.placeholder.username') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.company') }}</label>
        <input type="text" class="form-control shadow-none" name="company_name" placeholder="{{ __('file.placeholder.company') }}">
    </div>
    <div class="col-md-8 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.address') }}</label>
        <input type="text" class="form-control shadow-none" name="address" placeholder="{{ __('file.placeholder.address') }}">
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.website') }}</label>
        <input type="text" class="form-control shadow-none" name="website" placeholder="{{ __('file.placeholder.website') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.subject') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" name="lead_subject_id" required>
            <option value="">-- {{ __('file.option.select') }}</option>
            @if(isset($leadSubjects))
                @foreach($leadSubjects ?? [] as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.source') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" name="lead_source_id" required>
            <option value="">-- {{ __('file.option.select') }}</option>
            @if(isset($leadSources))
                @foreach($leadSources ?? [] as $src)
                    <option value="{{ $src->id }}">{{ $src->name }}</option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" name="status_id" required>
            
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.manager') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none selectnew2" name="manager_id" required>
            <option value="">-- {{ __('file.option.select') }}</option>
            @if(isset($users))
                @foreach($users ?? [] as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.assigned_to') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none selectnew2" name="assigned_to_id" required>
            <option value="">-- {{ __('file.option.select') }}</option>
            @if(isset($users))
                @foreach($users ?? [] as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.priority') }}</label>
        <select class="form-select shadow-none" name="priority">
            <option value="low">{{ __('file.option.low') }}</option>
            <option value="medium" selected>{{ __('file.option.medium') }}</option>
            <option value="high">{{ __('file.option.high') }}</option>
        </select>
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.expected_value') }}</label>
        <input type="number" step="0.01" class="form-control shadow-none" name="expected_value" placeholder="0.00">
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.follow_up_date') }}</label>
        <input type="text" class="form-control shadow-none date-picker" name="follow_up_date" placeholder="DD-MM-YYYY">
    </div>
    <div class="col-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.note') }}</label>
        <textarea name="note" class="form-control shadow-none" rows="2" placeholder="Add a note..."></textarea>
    </div>
    <div class="col-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.description') }}</label>
        <textarea name="description" class="form-control shadow-none" rows="2" placeholder="{{ __('file.placeholder.optional_description') }}"></textarea>
    </div>
    <div class="col-md-4 mt-2">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.attachment') }} (Image/PDF)</label>
        <input type="file" class="form-control shadow-none" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <small class="text-muted" style="font-size: 10px;">{{ __('Accepted: JPG, PNG, PDF') }}</small>
        <div class="small mt-1 current-attachment"></div>
    </div>
</div>