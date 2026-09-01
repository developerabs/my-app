@php
    $prefix = $isEdit ? 'edit' : 'create';
@endphp

@if($isEdit)
    <input type="hidden" id="addon_id" name="id" value="">
@endif

<div class="mb-3">
    <label class="form-label">Addon Name *</label>
    <input type="text" name="name" id="{{ $prefix }}_name" class="form-control" required>
</div>

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <label class="form-label">Addon Type</label>
        <select name="type" id="{{ $prefix }}_addon_type" class="form-select addon-type" required>
            <option value="feature">Feature</option>
            <option value="limit">Limit</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Feature</label>
        <select name="reference_id" class="form-select feature-select" required>
            <option value="">-- Select Feature --</option>
            @foreach ($features as $feature)
                <option value="{{ $feature->id }}">{{ $feature->name }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Limit fields --}}
<div class="limit-fields d-none">
    <div class="mb-3">
        <label class="form-label">Limit Mode *</label>
        <select name="limit_mode" class="form-select">
            <option value="">-- Select --</option>
            <option value="absolute">Set Final Limit</option>
            <option value="increment">Increase Limit</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Limit Value *</label>
        <input type="number" name="limit_value" class="form-control" min="1">
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="reset_on_expiry" class="form-check-input" value="1" checked>
        <label class="form-check-label">Reset on expiry</label>
    </div>
</div>

<div class="row align-items-center mb-3">
    <div class="col-md-6">
        <label class="form-label">Price</label>
        <input type="number" name="price" step="0.01" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Duration (days)</label>
        <input type="number" name="duration_days" class="form-control" value="30">
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Image</label>
    <div class="input-group">
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png"
            onchange="document.getElementById('{{ $prefix }}_image_preview').src = window.URL.createObjectURL(this.files[0]);">
        <label class="input-group-text" for="image">
            <i class="fa-solid fa-upload"></i>
        </label>
    </div>

    <img id="{{ $prefix }}_image_preview"
    src="{{ asset('images/preview_image.png') }}"
    class="img-thumbnail mt-2" style="max-height: 100px;">
</div>
