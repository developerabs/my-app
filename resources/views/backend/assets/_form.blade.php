@php
    $isEdit = $isEdit ?? false;
    $accounts = $accounts ?? [];
@endphp

<div class="row g-2">
    <!-- Account ID -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('file.field.account') }} <span class="text-danger">*</span></label>
        <select class="form-select form-select-sm account-picker" name="account_id" required>
            <option value="">{{ __('Select Account') }}</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}">
                    {{ $account->account_name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Asset Code -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Asset Code') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="asset_code" value="{{ old('asset_code') }}" required>
    </div>

    <!-- Asset Name -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Asset Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" name="asset_name" value="{{ old('asset_name') }}" required>
    </div>

    <!-- Unit -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Unit') }}</label>
        <input type="text" class="form-control shadow-none" name="unit" value="{{ old('unit') }}" placeholder="e.g. Pcs, Kg">
    </div>

    <!-- Depreciation Method -->
    <div class="col-md-6">
        <label class="form-label fw-bold small mb-1">{{ __('Depreciation Method') }}</label>
        <select class="form-select form-select-sm" name="depreciation_method">
            @foreach(\App\Enums\DepreciationMethod::cases() as $depreciationMethod)
                <option value="{{ $depreciationMethod->value }}">{{ ucfirst($depreciationMethod->value)}}</option>
            @endforeach
        </select>
    </div>

    <!-- Status / Is Active -->
    <div class="col-md-3">
        <label class="form-label fw-bold small mb-1 invisible d-none d-md-block">{{ __('Depreciable') }}</label>
        <div class="form-check form-switch border rounded p-0 d-flex align-items-center bg-light" style="height: 31px; padding-left: 10px !important;">
            <input type="hidden" name="is_depreciable" value="0">
            <input class="form-check-input ms-2" type="checkbox" name="is_depreciable" value="1" id="is_depreciable">
            <label class="form-check-label small fw-bold mb-0 ms-2" for="is_depreciable">
                {{ __('Is Depreciable?') }}
            </label>
        </div>
    </div>

    <!-- Is Active Switch -->
    <div class="col-md-3">
        <label class="form-label fw-bold small mb-1 invisible d-none d-md-block">{{ __('Status') }}</label>
        <div class="form-check form-switch border rounded p-0 d-flex align-items-center bg-light" style="height: 31px; padding-left: 10px !important;">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input ms-2" type="checkbox" checked name="is_active" value="1" id="is_active">
            <label class="form-check-label small fw-bold mb-0 ms-2" for="is_active">
                {{ __('Active Status') }}
            </label>
        </div>
    </div>

    <!-- Notes -->
    <div class="col-12">
        <label class="form-label fw-bold small mb-1">{{ __('Notes') }}</label>
        <textarea class="form-control" name="notes" rows="2" placeholder="{{ __('Optional notes...') }}">{{ old('notes', $asset->notes ?? '') }}</textarea>
    </div>
</div>