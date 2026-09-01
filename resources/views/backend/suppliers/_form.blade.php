<div class="row g-3">
    {{-- Primary Required Fields --}}
    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.field.supplier_name') }} <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fas fa-truck text-primary"></i></span>
            <input type="text" name="name" class="form-control" required placeholder="Enter supplier name">
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.field.phone_number') }} <span class="text-danger">*</span> <span
                class="text-danger small phone-error"></span></label>
        <div class="input-group">
            <input type="tel" name="phone" class="form-control phone-input" required placeholder="017xxxxxxxx">
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.field.email') }}</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fas fa-envelope text-primary"></i></span>
            <input type="email" name="email" class="form-control" placeholder="example@mail.com">
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-bold">{{ __('file.field.company_name') }}</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fas fa-building text-primary"></i></span>
            <input type="text" name="company_name" class="form-control" placeholder="Business name">
        </div>
    </div>

    @include('backend.layouts.partials._render_custom_fields', [
            'custom_fields' => $custom_fields,
            'model' => null,
        ])

    {{-- Expandable Section Divider --}}
    <div class="col-12 mt-4">
        <div class="d-flex align-items-center">
            <hr class="flex-grow-1 text-muted">
            <button type="button" class="btn btn-link btn-sm text-decoration-none fw-bold mx-2"
                data-bs-toggle="collapse" data-bs-target="#supplierMoreDetails">
                <i class="fas fa-sliders-h me-1"></i> {{ __('file.field.more_details') }}
            </button>
            <hr class="flex-grow-1 text-muted">
        </div>
    </div>

    {{-- Collapsible More Details --}}
    <div class="collapse row g-3 m-0 p-0" id="supplierMoreDetails">

        {{-- Financial Info --}}
        <div class="col-md-4">
            <label class="form-label fw-bold">Tax Number (BIN/TIN)</label>
            <input type="text" name="company_tax_id" class="form-control" placeholder="BIN/TIN">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Opening Balance</label>
            <input type="number" step="0.01" name="opening_balance" class="form-control" value="0">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Balance Date</label>
            <input type="text" name="opening_balance_date" class="form-control date-picker"
                value="">
        </div>

        {{-- Address Section (JSON Field Structure) --}}
        {{-- Address Section --}}
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-bold text-primary"><i class="fas fa-map-marker-alt me-1"></i>
                    {{ __('file.field.full_address') }}</label>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 toggle-manual-address"
                    style="font-size: 11px;">
                    <i class="fas fa-edit me-1"></i> Manual Entry
                </button>
            </div>
            <div class="position-relative mt-1">
                <input type="text" name="full_address" class="form-control address-input-field"
                    placeholder="Search address (e.g. Mirpur 10)..." autocomplete="off">
                <div class="list-group shadow position-absolute w-100 address-results-container"
                    style="z-index: 9999; display: none;"></div>
            </div>
        </div>

        {{-- Comprehensive Manual Address Fields --}}
        <div class="col-12 d-none manual-address-fields">
            <div class="card card-body bg-light border-0 shadow-sm">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="small fw-bold">Country</label>
                        <input type="text" name="country" class="form-control form-control-sm" value="Bangladesh">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Division</label>
                        <input type="text" name="division" class="form-control form-control-sm"
                            placeholder="Division">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">District</label>
                        <input type="text" name="district" class="form-control form-control-sm"
                            placeholder="District">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Upazila</label>
                        <input type="text" name="upazila" class="form-control form-control-sm"
                            placeholder="Upazila">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">State/Province</label>
                        <input type="text" name="state" class="form-control form-control-sm"
                            placeholder="State">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">City</label>
                        <input type="text" name="city" class="form-control form-control-sm"
                            placeholder="City">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Post Code</label>
                        <input type="text" name="post_code" class="form-control form-control-sm"
                            placeholder="Post Code">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Coordinates</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="latitude" class="form-control" placeholder="Lat" readonly>
                            <input type="text" name="longitude" class="form-control" placeholder="Lng" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank Details Section (JSON Field Structure) --}}
        <div class="col-12 mt-3">
            <label class="form-label fw-bold text-success"><i class="fas fa-university me-1"></i> Bank Details</label>
            <div class="card card-body border-dashed shadow-sm bg-light" style="border: 1px dashed #28a745;">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="small fw-bold">Bank Name</label>
                        <input type="text" name="bank_details[bank_name]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">A/C Name</label>
                        <input type="text" name="bank_details[account_name]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">A/C Number</label>
                        <input type="text" name="bank_details[account_number]"
                            class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Personal Info & Image --}}
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Date of Birth</label>
                    <input type="text" name="date_of_birth" class="form-control dob"
                        placeholder="YYYY-MM-DD" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Gender</label>
                    <select class="form-select" name="gender">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Notes about supplier..."></textarea>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold">{{ __('file.field.supplier_image') }}</label>
            <div class="rounded border-dashed d-flex flex-column align-items-center justify-content-center p-2 text-center bg-light"
                style="min-height: 160px; border: 2px dashed #dee2e6;">
                <div class="preview-container mb-3 position-relative" style="display: none;">
                    <img class="img-thumbnail shadow-sm supplier-image-preview " src="" data-default=""
                        style="width: 120px; height: 120px; object-fit: cover;">
                    <button type="button"
                        class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle remove-preview"
                        style="padding: 2px 6px;"><i class="fas fa-times"></i></button>
                </div>
                <div class="upload-placeholder">
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                    <p class="small text-muted mb-3">Upload Photo</p>
                </div>
                <input type="file" name="image" class="form-control form-control-sm supplier-image-input"
                    accept="image/*">
            </div>
        </div>
    </div>
</div>
