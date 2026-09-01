@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row g-3">
    {{-- Lead Subject Name --}}
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="name" required placeholder="e.g. Direct Call, Website, Facebook, Referral">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.order') }}</label>
        <input type="number" class="form-control shadow-none" name="order" min="0" placeholder="e.g. 1, 2, 3">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }}</label>
        <select class="form-select shadow-none" name="is_active">
            <option value="1">{{ __('file.option.active') }}</option>
            <option value="0">{{ __('file.option.inactive') }}</option>
        </select>
    </div>
</div>