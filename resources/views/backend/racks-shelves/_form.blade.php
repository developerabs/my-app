<div class="row g-2 mb-2">
    <div class="col-md-4">
        <label for="branch_id" class="form-label small fw-bold mb-1">{{ __('file.branch') ?? 'Branch' }} <span class="text-danger">*</span></label>
        <select name="branch_id" id="branch_id" class="form-control form-control-sm select2" required style="width: 100%;">
            <option value="">{{ __('file.dropdown.select_branch') ?? 'Select Branch' }}</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}"
                    {{ isset($rack) && $branch->id == ($rack->branch_id ?? '') ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
    </div>
    
    <div class="col-md-4">
        <label for="rack_name" class="form-label small fw-bold mb-1">{{ __('file.rack') }} {{ __('file.name') }} <span class="text-danger">*</span></label>
        <div class="input-group input-group-sm">
            <span class="input-group-text py-1 px-2"><i class="fa-solid fa-layer-group"></i></span>
            <input type="text" name="name" id="rack_name" class="form-control form-control-sm" placeholder="e.g., Rack A" required>
        </div>
    </div>
    
    <div class="col-md-4">
        <label for="rack_code" class="form-label small fw-bold mb-1">{{ __('file.rack') }} {{ __('file.code') }}</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text py-1 px-2"><i class="fa-solid fa-barcode"></i></span>
            <input type="text" name="code" id="rack_code" class="form-control form-control-sm" placeholder="e.g., RACK-A (Optional)">
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <label for="rack_description" class="form-label small fw-bold mb-1">{{ __('file.description') }}</label>
        <textarea name="description" id="rack_description" class="form-control form-control-sm" rows="2" placeholder="Enter rack details or location notes..."></textarea>
    </div>
</div>

<style>
    #shelf-rows-table th, #shelf-rows-table td {
        padding: 0.25rem 0.4rem !important;
        vertical-align: middle !important;
    }
    .shelf-input-field {
        font-size: 0.875rem !important;
        height: calc(1.5em + 0.5rem + 2px) !important;
    }
</style>

<div class="shelf-management-wrapper border rounded p-2 bg-light-subtle">
    <div class="d-flex align-items-center mb-2">
        <h6 class="fw-bold mb-0 text-primary" style="font-size: 0.9rem;">
            <i class="fa-solid fa-list-check me-1"></i> {{ __('file.shelf') }} {{ __('file.list') }}
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-2 bg-white" id="shelf-rows-table">
            <thead class="table-light">
                <tr class="text-muted">
                    <th style="width: 45%;">{{ __('file.shelf') }} {{ __('file.name') }} <span class="text-danger">*</span></th>
                    <th style="width: 45%;">{{ __('file.shelf') }} {{ __('file.code') }}</th>
                    <th style="width: 10%;" class="text-center">{{ __('file.action') }}</th>
                </tr>
            </thead>
            <tbody id="shelf-rows-container">
                </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-start">
        <button type="button" class="btn btn-sm btn-outline-success add-shelf-row-btn py-1 px-2" style="font-size: 0.8rem;">
            <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.add_row') ?? 'Add Row' }}
        </button>
    </div>
</div>