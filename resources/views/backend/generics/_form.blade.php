@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row g-3">
    {{-- Brand Name --}}
    <div class="col-md-12">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="name" required placeholder="e.g. Azithromycin, Paracetamol, etc.">
    </div>
    {{-- Description --}}
    <div class="col-12">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.description') }}</label>
        <textarea class="form-control shadow-none" name="description" rows="2" placeholder="{{ __('Optional brief info...') }}"></textarea>
    </div>

    {{-- Status & Sorting --}}
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.sort_order') }}</label>
        <input type="number" class="form-control shadow-none" name="sort_order" value="0">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }}</label>
        <select class="form-select shadow-none" name="is_active">
            <option value="1">{{ __('file.option.active') }}</option>
            <option value="0">{{ __('file.option.inactive') }}</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1 invisible d-none d-md-block">{{ __('file.field.featured') }}</label>
        <div class="form-check form-switch border rounded p-0 d-flex align-items-center bg-light" style="height: 38px; padding-left: 10px !important;">
            <input class="form-check-input ms-2" type="checkbox" name="is_featured" value="1" id="generic_is_featured">
            <label class="form-check-label small fw-bold mb-0 ms-2" for="generic_is_featured">
                {{ __('file.field.mark_as_featured') }}
            </label>
        </div>
    </div>
</div>