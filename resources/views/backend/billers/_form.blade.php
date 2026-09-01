@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row g-3">
    <!-- Biller Name -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Biller Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="name" value="{{ old('name') }}" required>
    </div>

    <!-- Company Name -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Company Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="company_name" value="{{ old('company_name') }}" required>
    </div>

    <!-- Proprietor Name -->
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('Proprietor Name') }}</label>
        <input type="text" class="form-control shadow-none" name="propiter_name" value="{{ old('propiter_name') }}">
    </div>

    <!-- Email -->
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('Email') }} <span class="text-danger">*</span></label>
        <input type="email" class="form-control shadow-none" name="email" value="{{ old('email') }}" required>
    </div>

    <!-- Phone -->
    <div class="col-md-4">
        <label class="form-label fw-bold small mb-1">{{ __('Phone') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="phone" value="{{ old('phone') }}" required>
    </div>

    <!-- Website URL -->
    <div class="col-md-5">
        <label class="form-label fw-bold small mb-1">{{ __('Website URL') }}</label>
        <input type="url" class="form-control shadow-none" name="website_url" value="{{ old('website_url') }}" placeholder="https://example.com">
    </div>

    <!-- BIN Number -->
    <div class="col-md-5">
        <label class="form-label fw-bold small mb-1">{{ __('BIN Number') }}</label>
        <input type="text" class="form-control shadow-none" name="bin" value="{{ old('bin') }}">
    </div>

    <!-- Status (Active/Inactive Switch) -->
    <div class="col-md-2">
        <label class="form-label fw-bold small mb-1">{{ __('Status') }}</label>
        <div class="form-check form-switch border rounded p-0 d-flex align-items-center bg-light" style="height: 38px; padding-left: 10px !important;">
            <input class="form-check-input ms-2" type="checkbox" name="is_active" value="1" id="is_active" 
                   {{ old('is_active', 1) ? 'checked' : '' }}>
            <label class="form-check-label small fw-bold mb-0 ms-2" for="is_active">
                {{ __('Active') }}
            </label>
        </div>
    </div>

    <!-- Address -->
    <div class="col-12">
        <label class="form-label fw-bold small mb-1">{{ __('Address') }}</label>
        <textarea class="form-control shadow-none" name="address" rows="1" placeholder="{{ __('Full address...') }}">{{ old('address') }}</textarea>
    </div>

    <!-- Terms and Conditions -->
    <div class="col-12">
        <label class="form-label fw-bold small mb-1">{{ __('Terms and Conditions') }}</label>
        <textarea class="form-control shadow-none" name="tnc" rows="2" placeholder="{{ __('Special notes or T&C...') }}">{{ old('tnc') }}</textarea>
    </div>

    <!-- Meta Info (Compact Box) -->
    <div class="col-12">
        <div class="p-2 border-start border-4 border-info bg-light">
            <label class="form-label fw-bold small mb-1">{{ __('Meta Information') }}</label>
            <input type="text" class="form-control shadow-none" name="meta" value="{{ old('meta') }}" placeholder="{{ __('Additional meta data...') }}">
        </div>
    </div>

    <!-- Logo Upload with Preview -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Company Logo') }} (Image)</label>
        <input type="file" class="form-control shadow-none" name="logo" id="billerLogoInput" 
               onchange="document.getElementById('{{ $isEdit ? 'edit_logo_preview' : 'logo_preview' }}').src = window.URL.createObjectURL(this.files[0]);" accept="image/*">
        <div class="mt-2">
            <img id="{{ $isEdit ? 'edit_logo_preview' : 'logo_preview' }}" 
                 src="{{ url('images/preview_image.png') }}" 
                 data-default="{{ url('images/preview_image.png') }}"
                 alt="Logo Preview" 
                 style="height: 60px;" 
                 class="rounded border shadow-sm">
        </div>
    </div>

    <!-- Certificate Upload (Image/PDF) -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Certificate') }} (Image/PDF)</label>
        <input type="file" class="form-control shadow-none" name="certificate" accept="image/*,application/pdf">
        <small class="text-muted" style="font-size: 10px;">{{ __('Accepted: JPG, PNG, PDF') }}</small>
        @if($isEdit)
            <div id="edit_certificate_status" class="mt-2"></div>
        @endif
    </div>
</div>