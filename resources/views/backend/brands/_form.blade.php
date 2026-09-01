@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row g-3">
    {{-- Brand Name --}}
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="name" required placeholder="e.g. Apple, Samsung">
    </div>

    {{-- Website URL --}}
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.website_url') }}</label>
        <input type="url" class="form-control shadow-none" name="website_url" placeholder="https://example.com">
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
            <input class="form-check-input ms-2" type="checkbox" name="is_featured" value="1" id="brand_is_featured">
            <label class="form-check-label small fw-bold mb-0 ms-2" for="brand_is_featured">
                {{ __('file.field.mark_as_featured') }}
            </label>
        </div>
    </div>

    {{-- Brand Logo & Cover Image --}}
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.brand_logo') }}</label>
        <input type="file" class="form-control shadow-none" name="brand_logo" 
               onchange="document.getElementById('{{ $isEdit ? 'edit_logo_preview' : 'logo_preview' }}').src = window.URL.createObjectURL(this.files[0]);" accept="image/*">
        <div class="mt-2 text-center border rounded p-1" style="width: fit-content;">
            <img id="{{ $isEdit ? 'edit_logo_preview' : 'logo_preview' }}" 
                 src="{{ url('images/preview_image.png') }}" 
                 data-default="{{ url('images/preview_image.png') }}"
                 alt="Logo" style="height: 50px;" class="rounded image-preview-class">
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.cover_image') }}</label>
        <input type="file" class="form-control shadow-none" name="cover_image" 
               onchange="document.getElementById('{{ $isEdit ? 'edit_cover_preview' : 'cover_preview' }}').src = window.URL.createObjectURL(this.files[0]);" accept="image/*">
        <div class="mt-2 text-center border rounded p-1" style="width: fit-content;">
            <img id="{{ $isEdit ? 'edit_cover_preview' : 'cover_preview' }}" 
                 src="{{ url('images/preview_image.png') }}" 
                 data-default="{{ url('images/preview_image.png') }}"
                 alt="Cover" style="height: 50px;" class="rounded image-preview-class">
        </div>
    </div>

    {{-- SEO Section --}}
    <div class="col-12 mt-2">
        <div class="p-3 border-start border-4 border-info bg-light rounded-end">
            <h6 class="fw-bold mb-3 small text-info text-uppercase"><i class="fas fa-search me-1"></i> {{ __('file.field.seo_details') }}</h6>
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm shadow-none" name="meta_title" placeholder="{{ __('file.field.meta_title') }}">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm shadow-none" name="meta_keywords" placeholder="{{ __('file.field.meta_keywords') }}">
                </div>
                <div class="col-12">
                    <textarea class="form-control form-control-sm shadow-none" name="meta_description" rows="2" placeholder="{{ __('file.field.meta_description') }}"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>