@php
    $isEdit = $isEdit ?? false;
    $categoryTypes = $categoryTypes ?? [];
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="name" value="{{ old('name') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.type') }} <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm category-type" name="category_type_id" required>
            <option value="">{{ __('Select Type') }}</option>
            @foreach($categoryTypes as $type)
                <option value="{{ $type->id }}" {{ (old('category_type_id') == $type->id) ? 'selected' : '' }}>
                    {{ $type->display_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.parent_category') }}</label>
        <select class="parent_category" name="parent_id">
            <option value=""></option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.sort_order') }}</label>
        <input type="number" class="form-control form-control" name="sort_order" value="{{ old('sort_order') }}">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.status') }}</label>
        <select class="form-select" name="is_active">
            <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>{{ __('Active') }}</option>
            <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.description') }}</label>
        <textarea class="form-control" name="description" rows="2" placeholder="{{ __('Optional brief info...') }}">{{ old('description') }}</textarea>
    </div>

    <div class="col-12 mt-1">
        <div class="p-2 border-start border-4 border-info bg-light">
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="meta_title" value="{{ old('meta_title') }}" placeholder="{{ __('file.field.meta_title') }}">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="meta_description" value="{{ old('meta_description') }}" placeholder="{{ __('file.field.meta_description') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.image') }}</label>
        <input type="file" class="form-control" name="image" id="categoryImageInput" onchange="document.getElementById('{{ $isEdit ? 'edit_image_preview' : 'image_preview' }}').src = window.URL.createObjectURL(this.files[0]);" accept="image/*">
        <div class="mt-2">
            <img id="{{ $isEdit ? 'edit_image_preview' : 'image_preview' }}" 
                 src="{{ url('images/preview_image.png') }}" 
                 data-default="{{ url('images/preview_image.png') }}"
                 alt="Preview" 
                 style="height: 60px;" 
                 class="rounded border shadow-sm image-preview-class">
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1 invisible d-none d-md-block">{{ __('file.field.featured') }}</label>
        <div class="form-check form-switch border rounded p-0 d-flex align-items-center bg-light" style="height: 31px; padding-left: 10px !important;">
            <input class="form-check-input ms-2" type="checkbox" name="is_featured" value="1" id="is_featured" 
                   {{ old('is_featured') ? 'checked' : '' }}>
            <label class="form-check-label small fw-bold mb-0 ms-2" for="is_featured">
                {{ __('file.field.mark_as_featured') }}
            </label>
        </div>
    </div>
</div>