@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row g-3">
    {{-- Lead Subject Name --}}
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="name" required placeholder="{{ __('file.placeholder.name') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.type') }} <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" name="type" required>
            <option value="">-- {{ __('file.option.select') }}</option>
            <option value="lead">Lead</option>
            <option value="deal">Deal</option>
            <option value="meeting">Meeting</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.category') }}</label>
        <select class="form-select shadow-none" name="category_id">
            
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.order') }}</label>
        <input type="number" class="form-control shadow-none" name="order" min="0" placeholder="e.g. 1, 2, 3">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.progress') }} <span class="text-danger">*</span></label>
        <input type="number" class="form-control shadow-none" name="progress" min="0" max="100" placeholder="e.g. 0, 25, 50, 75, 100">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.color') }} <span class="text-danger">*</span></label>
        <div class="color-input-col" style="min-width: 150px;">
            <div class="input-group input-group-sm">
                <input type="color" class="form-control form-control-color p-0 color-picker" value="#34c38f" style="height: 31px;">
                <input type="text" name="color" class="form-control hex-input" value="#34c38f" placeholder="#Hex" style="font-size: 11px;">
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }}</label>
        <select class="form-select shadow-none" name="is_active">
            <option value="1">{{ __('file.option.active') }}</option>
            <option value="0">{{ __('file.option.inactive') }}</option>
        </select>
    </div>
</div>