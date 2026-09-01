@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row">
    <div class="col-md-5 mb-3">
        <label for="{{ $isEdit ? 'edit_name' : 'name' }}" class="form-label">Attribute Name *</label>
        <input type="text" class="form-control" id="{{ $isEdit ? 'edit_name' : 'name' }}" name="name" placeholder="e.g. Color or Size" required>
    </div>

    <div class="col-md-4 mb-3">
        <label for="{{ $isEdit ? 'edit_is_active' : 'is_active' }}" class="form-label">Status</label>
        <select name="is_active" id="{{ $isEdit ? 'edit_is_active' : 'is_active' }}" class="form-select">
            <option value="1" selected>Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>

    <div class="col-md-3 mb-3 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input is-color-check" type="checkbox" name="is_color" value="1" id="{{ $isEdit ? 'edit_is_color' : 'is_color' }}">
            <label class="form-check-label fw-bold" for="{{ $isEdit ? 'edit_is_color' : 'is_color' }}">Is Color?</label>
        </div>
    </div>
    <div class="col-md-12 mb-3">
        <textarea name="description" id="{{ $isEdit ? 'edit_description' : 'description' }}" class="form-control" rows="1" placeholder="Optional description about the attribute"></textarea>
    </div>
</div>

<div class="card border shadow-none mb-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
        <h6 class="mb-0 fs-13 text-uppercase fw-bold">Attribute Values</h6>
        <button type="button" class="btn btn-sm btn-primary add-value-btn">
            <i class="fa-solid fa-plus me-1"></i> Add Value
        </button>
    </div>
    
    <div class="card-body p-3" style="max-height: 400px; overflow-y: auto; overflow-x: hidden;">
        <div class="row g-3" id="{{ $isEdit ? 'edit_value_wrapper' : 'value_wrapper' }}">
            
            <div class="col-md-6 value-row animate__animated animate__fadeIn">
                <div class="border p-2 rounded bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <input type="text" name="values[]" class="form-control form-control-sm" placeholder="Value (e.g. XL)" required>
                            <input type="hidden" name="value_ids[]" class="value-id" value="">
                        </div>
                        
                        <div class="color-input-col" style="display: none; min-width: 150px;">
                            <div class="input-group input-group-sm">
                                <input type="color" class="form-control form-control-color p-0 color-picker" value="#34c38f" style="width: 35px; height: 31px;">
                                <input type="text" name="color_codes[]" class="form-control hex-input" value="#34c38f" placeholder="#Hex" style="font-size: 11px;">
                            </div>
                        </div>

                        <button type="button" class="btn btn-link text-danger p-0 remove-value-btn d-none">
                            <i class="fa-solid fa-circle-xmark fs-5"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>