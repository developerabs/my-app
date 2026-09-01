@php
    $publicForm = $publicForm ?? null;
    $savedFields = $savedFields ?? collect();
    $fieldTypes = $fieldTypes ?? ['text', 'email', 'number', 'textarea', 'select', 'file', 'date'];
    // লিড এর জন্য হার্ডকোডেড সিস্টেম ডিফাইনড ফিল্ড, যেগুলো ডিলিট করা যাবে না (name/phone সবসময় required)
    $systemRequiredFieldNames = ['name', 'phone'];

    $systemDefinedFields = $systemDefinedFields ?? collect();
@endphp

@push('css')
<style>
    .public-form-builder .section-card {
        border: 1px solid #e5e7eb;
        border-radius: .75rem;
    }

    .public-form-builder .section-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: .25rem;
    }

    .public-form-builder .section-subtitle {
        color: #6c757d;
        font-size: .875rem;
    }

    .public-form-builder .form-control,
    .public-form-builder .form-select {
        min-height: 38px;
    }

    /* Field Card Styles */
    .field-card-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
    }
    .field-card-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    /* Drag & Drop Styles */
    .drag-handle {
        cursor: grab;
        color: #9ca3af;
        font-size: 1.1rem;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
    .ui-sortable-helper {
        background: #ffffff;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .ui-sortable-placeholder {
        background-color: #f3f4f6;
        border: 2px dashed #cbd5e1;
        border-radius: 0.5rem;
        visibility: visible !important;
        margin-bottom: 1rem;
    }

    .custom-logo-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 10px;
        background: #fdfdfd;
        border: 1px dashed #ddd;
        border-radius: 8px;
        min-height: 100px;
    }

    .img-preview-item {
        position: relative;
        width: 80px;
        height: 80px;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        border-radius: 6px;
        overflow: hidden;
        cursor: grab;
    }

    .img-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .img-preview-item .remove-img {
        position: absolute;
        top: 0;
        right: 0;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        font-size: 12px;
        font-weight: bold;
        width: 20px;
        height: 20px;
        text-align: center;
        cursor: pointer;
        line-height: 18px;
        z-index: 10;
    }

    .add-image-box {
        width: 80px;
        height: 80px;
        border: 2px dashed #007bff;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        cursor: pointer;
        color: #007bff;
        background: #f0f7ff;
        transition: 0.2s;
    }
    /* ফিল্ড জেনারেটর সেকশনের জন্য স্ক্রলবার ডিজাইন */
    #field-list {
        max-height: 480px; /* আপনার প্রয়োজন অনুযায়ী উচ্চতা বাড়াতে বা কমাতে পারেন (যেমন: 400px, 500px) */
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 8px; /* স্ক্রলবারের জন্য ডানপাশে সামান্য জায়গা রাখা */
    }

    /* স্ক্রলবারটি দেখতে সুন্দর করার জন্য (ঐচ্ছিক) */
    #field-list::-webkit-scrollbar {
        width: 6px;
    }
    #field-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    #field-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    #field-list::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endpush

<div class="public-form-builder">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body p-3 p-lg-4">
                    <div class="section-card p-3 mb-3">
                        <div class="section-title">{{ __('file.title.basic_information') }}</div>
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label class="form-label fw-medium">{{ __('file.field.form_title') }} <span class="text-danger">*</span></label>
                                <input id="form-title" name="title" value="{{ old('title', $publicForm?->title) }}" class="form-control shadow-sm" placeholder="{{ __('file.placeholder.enter_title') }}" maxlength="255" required>
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label fw-medium">{{ __('file.field.subtitle') }}</label>
                                <input name="subtitle" value="{{ old('subtitle', $publicForm?->subtitle) }}" class="form-control shadow-sm" placeholder="{{ __('file.placeholder.enter_subtitle') }}" maxlength="1000">
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label fw-medium">{{ __('file.field.submitted_for') }} <span class="text-danger">*</span></label>
                                <select name="submitted_for" id="submitted_for" class="form-select shadow-sm">
                                    <option value="">-- {{ __('file.option.select') }}</option>
                                    <option value="lead" @selected(old('submitted_for', $publicForm?->submitted_for ?? 'lead') === 'lead')>{{ __('file.lead') }}</option>
                                    <option value="contact" @selected(old('submitted_for', $publicForm?->submitted_for) === 'contact')>{{ __('file.contact') }}</option>
                                    <option value="other" @selected(old('submitted_for', $publicForm?->submitted_for) === 'other')>{{ __('file.other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('file.field.category') }}</label>
                                <select class="form-select shadow-none select2" name="category_id">
                                    
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label fw-medium">{{ __('file.field.submission_mode') }}</label>
                                <select name="submission_mode" class="form-select shadow-sm">
                                    <option value="response_only" @selected(old('submission_mode', $publicForm?->submission_mode) === 'response_only')>{{ __('file.response_only') }}</option>
                                    <option value="auto_lead" @selected(old('submission_mode', $publicForm?->submission_mode) === 'auto_lead')>{{ __('file.auto_lead') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label fw-medium">{{ __('file.field.submit_button_text') }} <span class="text-danger">*</span></label>
                                <input name="submit_button_text" value="{{ old('submit_button_text', $publicForm?->submit_button_text ?? 'Submit Form') }}" class="form-control shadow-sm" placeholder="{{ __('file.placeholder.enter_submit_button_text') }}" maxlength="100" required>
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label fw-medium">{{ __('file.field.success_message') }} <span class="text-danger">*</span></label>
                                <textarea name="success_message" class="form-control shadow-sm" rows="1">{{ old('success_message', $publicForm?->success_message ?? 'Thank you for your submission') }}</textarea>
                            </div>
                            <!-- Lead Default Settings Wrapper (বাই ডিফল্ট d-none দিয়ে হাইড করা) -->
                            <div class="col-lg-12 d-none" id="lead-default-settings">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label fw-medium">{{ __('file.field.default_subject') }}</label>
                                        <select name="default_subject_id" class="form-select shadow-sm select2">
                                            <option value="">-- {{ __('file.option.select') }} --</option>
                                            @foreach($data['leadSubjects'] ?? [] as $subject)
                                                <option value="{{ $subject->id }}" @selected(old('default_subject_id', $publicForm?->default_subject_id) == $subject->id)>
                                                    {{ $subject->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label fw-medium">{{ __('file.field.default_source') }}</label>
                                        <select name="default_source_id" class="form-select shadow-sm select2">
                                            <option value="">-- {{ __('file.option.select') }} --</option>
                                            @foreach($data['leadSources'] ?? [] as $source)
                                                <option value="{{ $source->id }}" @selected(old('default_source_id', $publicForm?->default_source_id) == $source->id)>
                                                    {{ $source->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label fw-medium">{{ __('file.field.default_status') }}</label>
                                        <select name="default_status_id" class="form-select shadow-sm select2">

                                        </select>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label fw-medium">{{ __('file.field.default_manager') }}</label>
                                        <select name="default_manager_id" class="form-select shadow-sm select2">
                                            <option value="">-- {{ __('file.option.select') }} --</option>
                                            @foreach($data['users'] ?? [] as $user)
                                                <option value="{{ $user->id }}" @selected(old('default_manager_id', $publicForm?->default_manager_id) == $user->id)>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label fw-medium">{{ __('file.field.default_assigned_to') }}</label>
                                        <select name="default_assigned_to_id" class="form-select shadow-sm select2">
                                            <option value="">-- {{ __('file.option.select') }} --</option>
                                            @foreach($data['users'] ?? [] as $user)
                                                <option value="{{ $user->id }}" @selected(old('default_assigned_to_id', $publicForm?->default_assigned_to_id) == $user->id)>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- Lead Default Settings Wrapper End -->
                            <div class="col-lg-12" id="custom-logo-panel">
                                <label class="form-label fw-medium">{{ __('file.field.logo') }}</label>
                                <div class="custom-logo-gallery" id="custom-logo-gallery">
                                    @if($publicForm?->custom_logo)
                                        <div class="img-preview-item animate__animated animate__zoomIn" style="position: relative; display: inline-block; vertical-align: top; width: 80px; height: 80px; margin-right: 15px; margin-bottom: 15px;">
                                            <img src="{{ $publicForm->custom_logo_url }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                                            <span class="remove-img shadow-sm" onclick="removeCustomLogo(this)" 
                                                style="position: absolute; top: -8px; right: -8px; background: #ff4d4f; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; font-weight: bold; line-height: 1;">
                                                ×
                                            </span>
                                        </div>
                                    @endif

                                    <div class="add-image-box @if($publicForm?->custom_logo) d-none @endif" onclick="document.querySelector('.custom-logo-file-input').click()">
                                        <i class="ri-image-add-line"></i>
                                        <span>Add Photo</span>
                                    </div>
                                    <input type="file" name="custom_logo" class="d-none custom-logo-file-input" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body p-3 p-lg-4">
                    <div class="section-card p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <div class="section-title mb-0">{{ __('file.field.field_generator') }}</div>
                                <div class="section-subtitle">{{ __('file.field.field_generator_subtitle') }}</div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="add-field">
                                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.add_field') }}
                            </button>
                        </div>

                        <!-- টেবিল হেডার -->
                        <div class="d-none d-md-flex align-items-center gap-2 px-2 pb-2 mb-2 text-muted fw-bold border-bottom" style="font-size: 0.85rem;">
                            <div style="width: 25px; flex-shrink: 0;"></div>
                            <div class="row g-2 flex-grow-1">
                                <div class="col-md-3">{{ __('file.table.field_name') }}</div>
                                <div class="col-md-3">{{ __('file.table.label') }}</div>
                                <div class="col-md-2">{{ __('file.table.type') }}</div>
                                <div class="col-md-2">{{ __('file.table.placeholder') }}</div>
                                <div class="col-md-2">{{ __('file.table.columns') }}</div>
                            </div>
                            <div style="width: 35px; flex-shrink: 0;"></div>
                        </div>

                        <!-- ফিল্ড লিস্ট কন্টেইনার -->
                        <div id="field-list">
                            @php
                                // systemDefinedFields থেকে শুধু যেসব ফিল্ডের is_required = true আছে, সেগুলোর নাম ফিল্টার করে বের করা
                                $requiredSystemFieldNames = collect($systemDefinedFields)
                                    ->filter(fn($item) => (bool) data_get($item, 'is_required'))
                                    ->pluck('name')
                                    ->toArray();
                            @endphp
                            @foreach($savedFields as $index => $field)
                                @php
                                    $isSystemDefined = (bool) $field['is_system_defined'];
                                    // $isLockedRequiredField = $isSystemDefined && in_array($field['name'], $systemRequiredFieldNames, true);
                                    $isLockedRequiredField = $isSystemDefined && in_array($field['name'], $requiredSystemFieldNames, true);
                                @endphp
                                <div class="field-card-item" data-index="{{ $index }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="text-center" style="width: 25px; flex-shrink: 0;">
                                            <span class="drag-handle" title="Drag to reorder">
                                                <i class="fa-solid fa-grip-vertical"></i>
                                            </span>
                                        </div>

                                        <div class="row g-2 flex-grow-1">
                                            <div class="col-md-3">
                                                <input type="hidden" data-field="db_id" name="fields[{{ $index }}][db_id]" value="{{ $field['db_id'] }}">
                                                <!-- is_system_defined hidden field -->
                                                <input type="hidden" data-field="is_system_defined" name="fields[{{ $index }}][is_system_defined]" value="{{ $isSystemDefined ? 1 : 0 }}">
                                                
                                                <input type="text" data-field="name" name="fields[{{ $index }}][name]" value="{{ $field['name'] }}" class="form-control form-control-sm shadow-sm" placeholder="full_name" @if($isSystemDefined) readonly style="background-color: #e9ecef; cursor: not-allowed;" @endif required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" data-field="label" name="fields[{{ $index }}][label]" value="{{ $field['label'] }}" class="form-control form-control-sm shadow-sm" placeholder="Full Name" required>
                                            </div>
                                            <div class="col-md-2">
                                                <select data-field="type" name="fields[{{ $index }}][type]" class="form-select form-select-sm shadow-sm field-type" @disabled($isSystemDefined) required>
                                                    @foreach($fieldTypes as $type)
                                                        <option value="{{ $type }}" @selected($field['type'] === $type)>{{ ucfirst($type) }}</option>
                                                    @endforeach
                                                </select>
                                                @if($isSystemDefined)
                                                    <input type="hidden" data-field="type" name="fields[{{ $index }}][type]" value="{{ $field['type'] }}">
                                                @endif
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" data-field="placeholder" name="fields[{{ $index }}][placeholder]" value="{{ $field['placeholder'] }}" class="form-control form-control-sm shadow-sm" placeholder="Enter placeholder">
                                            </div>
                                            <div class="col-md-2">
                                                <select data-field="column_width" name="fields[{{ $index }}][column_width]" class="form-select form-select-sm shadow-sm">
                                                    <option value="1" @selected($field['column_width'] == 1)>1/3 width</option>
                                                    <option value="2" @selected($field['column_width'] == 2)>2/3 width</option>
                                                    <option value="3" @selected($field['column_width'] == 3)>Full width</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <!-- ডিলিট বাটন: সিস্টেম ডিফাইনড + রিকোয়ার্ড ফিল্ড (name/phone) হলে ডিলিট বাটন দেখাবে না -->
                                        <div class="text-end" style="width: 35px; flex-shrink: 0;">
                                            @if($isLockedRequiredField)
                                                <span class="text-muted" title="System defined field cannot be deleted"><i class="fa-solid fa-lock text-secondary"></i></span>
                                            @else
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-field py-1 px-2" title="Remove"><i class="fa-solid fa-trash"></i></button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="field-options mt-2 ps-4 @if($field['type'] !== 'select') d-none @endif">
                                        <label class="form-label small fw-medium mb-1">Options (comma separated)</label>
                                        <input type="text" data-field="options" name="fields[{{ $index }}][options]" value="{{ $field['options'] }}" class="form-control form-control-sm shadow-sm" placeholder="Option 1, Option 2" @disabled($field['type'] !== 'select')>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center gap-4 mt-2 pt-2 border-top text-muted ps-4" style="font-size: 0.8rem;">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="req_{{ $index }}" data-field="is_required" name="fields[{{ $index }}][is_required]" value="1" @checked($field['is_required']) @disabled($isLockedRequiredField) style="width: 2em; height: 1em;">
                                            <label class="form-check-label text-dark fw-medium" for="req_{{ $index }}" style="cursor: pointer;">Required</label>
                                        </div>
                                        {{-- @if($isSystemDefined)
                                            <input type="hidden" data-field="is_required" name="fields[{{ $index }}][is_required]" value="{{ $field['is_required'] ? 1 : 0 }}">
                                        @endif --}}
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="table_{{ $index }}" data-field="show_in_table" name="fields[{{ $index }}][show_in_table]" value="1" @checked($field['show_in_table']) style="width: 2em; height: 1em;">
                                            <label class="form-check-label text-dark" for="table_{{ $index }}" style="cursor: pointer;">Show in Data Table</label>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="search_{{ $index }}" data-field="searchable" name="fields[{{ $index }}][searchable]" value="1" @checked($field['searchable']) style="width: 2em; height: 1em;">
                                            <label class="form-check-label text-dark" for="search_{{ $index }}" style="cursor: pointer;">Searchable</label>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="filter_{{ $index }}" data-field="filterable" name="fields[{{ $index }}][filterable]" value="1" @checked($field['filterable']) style="width: 2em; height: 1em;">
                                            <label class="form-check-label text-dark" for="filter_{{ $index }}" style="cursor: pointer;">Filterable</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div id="empty-fields" class="small text-muted border rounded p-3 text-center mt-3 @if($savedFields->isNotEmpty()) d-none @endif">
                            No fields added yet. Click <strong>Add Field</strong> to start building the form.
                        </div>
                    </div>
                </div>
            </div>
        </div>            
    </div>
    <div class="card-footer bg-transparent border-top d-flex justify-content-end gap-2 p-3">
        <a href="{{ route('public-forms.index') }}" class="btn btn-light border">{{ __('file.button.cancel') }}</a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> {{ __('file.button.save_form') }}
        </button>
    </div>
</div>

@push('js')
<script>
    $('.select2').select2({
        width: '100%',
    });
    // গ্লোবাল ফাংশনসমূহ (যাতে AJAX রেসপন্স থেকে এগুলো কল করা যায়)
    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function typeOptions(selected, fieldTypes) {
        return $.map(fieldTypes, function (type) {
            return '<option value="' + type + '"' + (type === selected ? ' selected' : '') + '>' + type.charAt(0).toUpperCase() + type.slice(1) + '</option>';
        }).join('');
    }

    function fieldCardHtml(field, index, fieldTypes) {
        field = $.extend({
            db_id: '',
            name: '',
            label: '',
            type: 'text',
            placeholder: '',
            options: '',
            is_required: true,
            show_in_table: true,
            searchable: false,
            filterable: false,
            column_width: 1,
            is_system_defined: false
        }, field || {});

        var uniqueId = 'f_' + Math.random().toString(36).substring(2, 9);
        
        // সিস্টেম ডিফাইনড হলে ডিলিট বাটন লক থাকবে
        var deleteButtonHtml = field.is_system_defined && field.is_required
            ? '<span class="text-muted" title="System defined field cannot be deleted"><i class="fa-solid fa-lock text-secondary"></i></span>'
            : '<button type="button" class="btn btn-outline-danger btn-sm remove-field py-1 px-2" title="Remove"><i class="fa-solid fa-trash"></i></button>';

        // 🔥 সিস্টেম ডিফাইনড হলে name, type এবং is_required লক থাকবে
        var nameDisabled = field.is_system_defined ? 'readonly style="background-color: #e9ecef; cursor: not-allowed;"' : '';
        var typeDisabled = field.is_system_defined ? 'disabled' : '';
        var requiredDisabled = field.is_system_defined && field.is_required ? 'disabled' : '';

        return '' +
        '<div class="field-card-item" data-index="' + index + '">' +
            '<div class="d-flex align-items-center gap-2">' +
                '<div class="text-center" style="width: 25px; flex-shrink: 0;">' +
                    '<span class="drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>' +
                '</div>' +
                '<div class="row g-2 flex-grow-1">' +
                    '<div class="col-md-3">' +
                        '<input type="hidden" data-field="db_id" value="' + escapeHtml(field.db_id) + '">' +
                        '<input type="hidden" data-field="is_system_defined" value="' + (field.is_system_defined ? 1 : 0) + '">' +
                        // নাম পরিবর্তন করা যাবে না
                        '<input type="text" data-field="name" class="form-control form-control-sm shadow-sm" placeholder="full_name" value="' + escapeHtml(field.name) + '" ' + nameDisabled + ' required>' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<input type="text" data-field="label" class="form-control form-control-sm shadow-sm" placeholder="Full Name" value="' + escapeHtml(field.label) + '" required>' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        // টাইপ পরিবর্তন করা যাবে না (ডিসএবল করা)
                        '<select data-field="type" class="form-select form-select-sm shadow-sm field-type" ' + typeDisabled + ' required>' + typeOptions(field.type, fieldTypes) + '</select>' +
                        // ডিসএবল থাকলে ব্যাকএন্ডে টাইপ পাঠানোর জন্য হিডেন ফিল্ড
                        (field.is_system_defined ? '<input type="hidden" data-field="type" value="' + escapeHtml(field.type) + '">' : '') +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<input type="text" data-field="placeholder" class="form-control form-control-sm shadow-sm" placeholder="Enter placeholder" value="' + escapeHtml(field.placeholder) + '">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<select data-field="column_width" class="form-select form-select-sm shadow-sm">' +
                            '<option value="1"' + (field.column_width == 1 ? ' selected' : '') + '>1/3 width</option>' +
                            '<option value="2"' + (field.column_width == 2 ? ' selected' : '') + '>2/3 width</option>' +
                            '<option value="3"' + (field.column_width == 3 ? ' selected' : '') + '>Full width</option>' +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="text-end" style="width: 35px; flex-shrink: 0;">' + deleteButtonHtml + '</div>' +
            '</div>' +
            '<div class="field-options mt-2 ps-4' + (field.type === 'select' ? '' : ' d-none') + '">' +
                '<label class="form-label small fw-medium mb-1">Options (comma separated)</label>' +
                '<input type="text" data-field="options" class="form-control form-control-sm shadow-sm" placeholder="Option 1, Option 2" value="' + escapeHtml(field.options) + '"' + (field.type === 'select' ? '' : ' disabled') + '>' +
            '</div>' +
            '<div class="d-flex flex-wrap align-items-center gap-4 mt-2 pt-2 border-top text-muted ps-4" style="font-size: 0.8rem;">' +
                '<div class="form-check form-switch mb-0">' +
                    // Required সুইচ লক করা
                    '<input class="form-check-input" type="checkbox" role="switch" id="req_' + uniqueId + '" data-field="is_required" value="1"' + (field.is_required ? ' checked' : '') + ' ' + requiredDisabled + ' style="width: 2em; height: 1em;">' +
                    '<label class="form-check-label text-dark fw-medium" for="req_' + uniqueId + '" style="cursor: pointer;">Required</label>' +
                '</div>' +
                '<div class="form-check form-switch mb-0">' +
                    '<input class="form-check-input" type="checkbox" role="switch" id="table_' + uniqueId + '" data-field="show_in_table" value="1"' + (field.show_in_table ? ' checked' : '') + ' style="width: 2em; height: 1em;">' +
                    '<label class="form-check-label text-dark" for="table_' + uniqueId + '" style="cursor: pointer;">Show in Data Table</label>' +
                '</div>' +
                '<div class="form-check form-switch mb-0">' +
                    '<input class="form-check-input" type="checkbox" role="switch" id="search_' + uniqueId + '" data-field="searchable" value="1"' + (field.searchable ? ' checked' : '') + ' style="width: 2em; height: 1em;">' +
                    '<label class="form-check-label text-dark" for="search_' + uniqueId + '" style="cursor: pointer;">Searchable</label>' +
                '</div>' +
                '<div class="form-check form-switch mb-0">' +
                    '<input class="form-check-input" type="checkbox" role="switch" id="filter_' + uniqueId + '" data-field="filterable" value="1"' + (field.filterable ? ' checked' : '') + ' style="width: 2em; height: 1em;">' +
                    '<label class="form-check-label text-dark" for="filter_' + uniqueId + '" style="cursor: pointer;">Filterable</label>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function yourUniqueIdFallback(id) {
        return id;
    }

    function toggleEmptyState() {
        $('#empty-fields').toggleClass('d-none', $('#field-list').find('.field-card-item').length > 0);
    }

    function toggleOptions($card) {
        var isSelect = $card.find('.field-type').val() === 'select';
        $card.find('.field-options').toggleClass('d-none', !isSelect);
        $card.find('[data-field="options"]').prop('disabled', !isSelect);
    }

    function renumberFields() {
        $('#field-list').find('.field-card-item').each(function (index) {
            var $card = $(this);
            $card.attr('data-index', index);

            $card.find('[data-field]').each(function () {
                var $input = $(this);
                $input.attr('name', 'fields[' + index + '][' + $input.data('field') + ']');
            });

            toggleOptions($card);
        });

        toggleEmptyState();
    }

    $(document).on('change', '.custom-logo-file-input', function(e) {
        let files = e.target.files;
        let galleryWrapper = $('#custom-logo-gallery');
        let addButton = galleryWrapper.find('.add-image-box');

        if (files.length > 0) {
            let reader = new FileReader();
            reader.onload = function(event) {
                galleryWrapper.find('.img-preview-item').remove();
                let imgHtml = `
                <div class="img-preview-item animate__animated animate__zoomIn" style="position: relative; display: inline-block; vertical-align: top; width: 80px; height: 80px; margin-right: 15px; margin-bottom: 15px;">
                    <img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                    <span class="remove-img shadow-sm" onclick="removeCustomLogo(this)" 
                        style="position: absolute; top: -8px; right: -8px; background: #ff4d4f; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; font-weight: bold; line-height: 1;">
                        ×
                    </span>
                </div>`;
                addButton.before(imgHtml);
                addButton.addClass('d-none');
            };
            reader.readAsDataURL(files[0]);
        }
    });

    function removeCustomLogo(element) {
        let previewItem = $(element).closest('.img-preview-item');
        let galleryWrapper = previewItem.closest('#custom-logo-gallery');
        previewItem.remove(); 
        galleryWrapper.find('.custom-logo-file-input').val(''); 
        galleryWrapper.find('.add-image-box').removeClass('d-none'); 
    }

    $(function () {
        var fieldTypes = @json($fieldTypes);
        var $fieldList = $('#field-list');
        var $form = $fieldList.closest('form');

        // Sortable setup
        $fieldList.sortable({
            handle: '.drag-handle',
            axis: 'y',
            placeholder: 'ui-sortable-placeholder',
            update: function () {
                renumberFields();
            }
        });

    // যখন নতুন ফিল্ড অ্যাড করা হবে (বা Add Field বাটনে ক্লিক করা হবে)
        $('#add-field').on('click', function() {
            var $fieldList = $('#field-list');
            var index = $fieldList.children('.field-card').length; // বা আপনার ইনডেক্স কাউন্ট
            var fieldTypes = @json($fieldTypes);

            // নতুন ফিল্ড অ্যাপেন্ড করা
            $fieldList.append(fieldCardHtml(null, index, fieldTypes));
            renumberFields();

            // ─── নতুন ফিল্ডে অটো স্ক্রল করার কোড ───
            $fieldList.animate({
                scrollTop: $fieldList[0].scrollHeight
            }, 300); // ৩০০ মিলিসেকেন্ড অ্যানিমেশন স্পিড
        });

        $(document).on('click', '.remove-field', function () {
            $(this).closest('.field-card-item').remove();
            renumberFields();
        });

        $(document).on('change', '.field-type', function () {
            toggleOptions($(this).closest('.field-card-item'));
        });

        $('#form-title').on('input', function () {
            $('#form-slug').val($(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
        });

        $form.on('submit', function () {
            renumberFields();
        });

        renumberFields();
    });

    // একটি ফ্ল্যাগ ভেরিয়েবল যা দিয়ে বুঝবেন এটি এডিট মোড কি না
    var isEditMode = {{ isset($publicForm) && $publicForm?->id ? 'true' : 'false' }};
    var currentFormId = {{ isset($publicForm) && $publicForm?->id ? $publicForm->id : 'null' }};
    var savedCategoryId = @json(old('category_id', $publicForm?->category_id));
    var savedStatusId = @json(old('default_status_id', $publicForm?->default_status_id));

    // submitted_for চেঞ্জ হ্যান্ডলার
    $(document).on('change', 'select[name="submitted_for"]', function() {
        let type = $(this).val() || '';
        let $form = $(this).closest('form');
        let $categorySelect = $form.find('select[name="category_id"]');
        let $statusSelect = $form.find('select[name="default_status_id"]');
        var fieldTypes = @json($fieldTypes);
        // এডিট মোডে প্রথমবার হলে আগের সেভ করা মান পুনরায় সিলেক্ট করতে হবে
        var restoreCategoryId = isEditMode ? savedCategoryId : null;
        var restoreStatusId = isEditMode ? savedStatusId : null;

        // ১. ক্যাটাগরি লোড করার AJAX রিকোয়েস্ট
        if (type) {
            let url = "{{ route('categories.getCategoriesByStatusType', ':id') }}".replace(':id', type);
            $.get(url, function(response) {
                $categorySelect.empty();
                $categorySelect.append('<option value="">-- {{ __('file.option.select') }}</option>');
                response.categories.forEach(function(category) {
                    $categorySelect.append('<option value="' + category.id + '">' + category.name + '</option>');
                });
                if (restoreCategoryId) {
                    $categorySelect.val(restoreCategoryId);
                }
                if ($categorySelect.data('select2')) {
                    $categorySelect.trigger('change.select2');
                }
                // ক্যাটাগরি অনুযায়ী স্ট্যাটাস লোড এবং আগের সিলেক্ট করা স্ট্যাটাস পুনরায় সেট করা
                loadLeadStatuses($form, $categorySelect.val(), type, restoreStatusId);
            });
        } else {
            $categorySelect.empty();
            $categorySelect.append('<option value="">-- {{ __('file.option.select') }}</option>');
            $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>');
        }

        // লিড ডিফল্ট সেটিংস দেখানোর লজিক
        if (type === 'lead') {
            $('#lead-default-settings').removeClass('d-none');
        } else {
            $('#lead-default-settings').addClass('d-none');
        }

        // ২. এডিট মোডে প্রথমবার পেজ লোড হওয়ার সময় যেন ফিল্ডগুলো রিসেট হয়ে না যায়
        if (isEditMode) {
            // এডিট মোডে প্রথম চেঞ্জ ইগনোর করার পর ফ্ল্যাগ ফলস করে দেওয়া হচ্ছে যাতে পরবর্তীতে ইউজার চাইলে বদলাতে পারেন
            isEditMode = false; 
            return; 
        }

        // নতুন ফর্ম বা পরবর্তীতে ইউজার চেঞ্জ করলে ফিল্ডগুলো AJAX থেকে আনবে
        let fieldsUrl = "{{ route('public-forms.get-fields') }}";
        $.get(fieldsUrl, { submitted_for: type, form_id: currentFormId }, function(response) {
            var $fieldList = $('#field-list');
            $fieldList.empty(); // আগের ফিল্ডগুলো ক্লিয়ার করে দিচ্ছি

            if (response.fields && response.fields.length > 0) {
                response.fields.forEach(function(field, index) {
                    $fieldList.append(fieldCardHtml(field, index, fieldTypes));
                });
            }
            renumberFields(); // ইনপুট নেম এবং ইনডেক্স ঠিক করার জন্য
        });
    });

    // পেজ লোডে প্রথমবার ট্রিগার করা (যদি প্রয়োজন হয়)
    $('select[name="submitted_for"]').trigger('change');

    function loadLeadStatuses($form, categoryId, type, selectedStatusId = null) {
        let $statusSelect = $form.find('select[name="default_status_id"]');
        $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>');

        if (!categoryId || !type) {
            if (selectedStatusId) {
                $statusSelect.val(selectedStatusId);
                if ($statusSelect.data('select2')) {
                    $statusSelect.trigger('change.select2');
                }
            }
            return;
        }

        $.get("{{ route('statuses.by-category-and-type') }}", {
            category_id: categoryId,
            type: type
        }, function(res) {
            if (res.status && res.statuses) {
                res.statuses.forEach(function(st) {
                    $statusSelect.append('<option value="' + st.id + '">' + st.name + '</option>');
                });
            }

            if (selectedStatusId) {
                $statusSelect.val(selectedStatusId);
            }

            if ($statusSelect.data('select2')) {
                $statusSelect.trigger('change.select2');
            }
        });
    }

    // get lead status by category and type 
    $(document).on('change', 'select[name="category_id"]', function() {
        let categoryId = $(this).val();
        let $form = $(this).closest('form');
        let $type = $form.find('select[name="submitted_for"]').val();
        loadLeadStatuses($form, categoryId, $type);
    });

</script>
@endpush