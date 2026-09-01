<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.label.model_type') }} <span class="text-danger">*</span></label>
        <select name="model_type" class="form-select select2" required>
            <option value="">{{ __('file.placeholder.select_module') }}</option>

            @foreach (config('sherazipos.model_mappings') as $key => $fullClass)
                <option value="{{ $fullClass }}" {{ old('model_type') == $fullClass ? 'selected' : '' }}>
                    {{ __("file.$key") }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.label.field_type') }} <span class="text-danger">*</span></label>
        <select name="type" id="field_type" class="form-select shadow-sm" required>
            @foreach (['text', 'number', 'date', 'email', 'textarea', 'select', 'radio', 'checkbox'] as $type)
                <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.label.field_label') }} <span class="text-danger">*</span></label>
        <input type="text" name="label" class="form-control shadow-sm" placeholder="e.g. Warranty Period"
            value="{{ old('label') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.label.placeholder') }}</label>
        <input type="text" name="placeholder" class="form-control shadow-sm" placeholder="e.g. Enter warranty"
            value="{{ old('placeholder') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.label.default_value') }}</label>
        <input type="text" name="default_value" class="form-control shadow-sm" value="{{ old('default_value') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.label.sort_order') }}</label>
        <input type="number" name="order" class="form-control shadow-sm" value="{{ old('order') }}">
    </div>

    <div class="col-md-12 d-none" id="options_container">
        <div class="card bg-light border-dashed p-3">
            <label class="form-label fw-bold text-primary">{{ __('file.label.options') }}
                ({{ __('file.note.comma_separated') }})</label>
            <textarea name="options" class="form-control shadow-sm" rows="2" placeholder="Option 1, Option 2, Option 3">{{ old('options') }}</textarea>
            <small class="text-muted mt-1"><i class="fa-solid fa-circle-info me-1"></i>
                {{ __('file.help.options_format') }}</small>
        </div>
    </div>

    <div class="col-md-12">
        <div class="row bg-white rounded p-2 border mx-0 mt-2">
            <div class="col-md-4">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" name="is_required" id="is_required" value="1"
                        {{ old('is_required') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="is_required">{{ __('file.label.required') }}</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" name="show_in_list" id="show_in_list" value="1"
                        {{ old('show_in_list') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold"
                        for="show_in_list">{{ __('file.label.show_in_datatable') }}</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="is_active">{{ __('file.label.active') }}</label>
                </div>
            </div>
        </div>
    </div>
</div>
