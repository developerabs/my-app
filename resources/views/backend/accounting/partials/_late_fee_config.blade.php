@php
    $model = $model ?? null;
    $hasLateFee = old('has_late_fee', $model?->has_late_fee ?? false);
    $config = old('late_fee_config', $model?->late_fee_config ?? []);
    
    $graceDays = $config['grace_days'] ?? 5;
    $feeType = $config['fee_type'] ?? 'fixed';
    $rate = $config['rate'] ?? '0.00';
    $calcMethod = $config['calculation_method'] ?? 'simple';
    $frequency = $config['frequency'] ?? 'one_time';
    $maxFeeLimit = $config['max_fee_limit'] ?? '';
@endphp

<div class="card border shadow-none mb-3">
    <div class="card-body p-3 bg-light-subtle">
        <div class="form-check form-switch mb-0 d-flex align-items-center">
            <input class="form-check-input me-2" type="checkbox" name="has_late_fee" id="has_late_fee_toggle" value="1" 
                {{ $hasLateFee ? 'checked' : '' }} style="cursor: pointer; width: 2.2em; height: 1.2em;">
            <div>
                <label class="form-check-label fw-bold text-dark me-2" for="has_late_fee_toggle" style="cursor: pointer;">
                    <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> {{ __('Enable Overdue Late Fee / Finance Charge') }}
                </label>
                <small class="text-muted d-block">{{ __('Automatically or manually charge an additional fee if payment is delayed past the due date.') }}</small>
            </div>
        </div>

        <!-- Expandable Config Box -->
        <div class="mt-3 pt-3 border-top" id="late_fee_config_box" style="{{ $hasLateFee ? '' : 'display: none;' }}">
            <div class="row g-3">
                <!-- 1. Grace Days -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">{{ __('Grace Period (Days)') }} <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="late_fee_config[grace_days]" class="form-control form-control-sm late-fee-req"
                        value="{{ $graceDays }}" placeholder="e.g. 5" {{ $hasLateFee ? 'required' : '' }}>
                    <small class="text-muted" style="font-size: 11px;">{{ __('Days after due date.') }}</small>
                </div>

                <!-- 2. Fee Type -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">{{ __('Fee Type') }} <span class="text-danger">*</span></label>
                    <select name="late_fee_config[fee_type]" class="form-select form-select-sm late-fee-req" {{ $hasLateFee ? 'required' : '' }}>
                        <option value="fixed" {{ $feeType === 'fixed' ? 'selected' : '' }}>{{ __('Fixed Amount') }}</option>
                        <option value="percentage" {{ $feeType === 'percentage' ? 'selected' : '' }}>{{ __('Percentage (%)') }}</option>
                    </select>
                </div>

                <!-- 3. Rate / Amount -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">{{ __('Fee Rate / Amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="late_fee_config[rate]" class="form-control form-control-sm text-end fw-bold late-fee-req"
                        value="{{ $rate }}" placeholder="0.00" {{ $hasLateFee ? 'required' : '' }}>
                </div>

                <!-- 4. Calculation Method -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">{{ __('Calculation Method') }} <span class="text-danger">*</span></label>
                    <select name="late_fee_config[calculation_method]" class="form-select form-select-sm late-fee-req" {{ $hasLateFee ? 'required' : '' }}>
                        <option value="simple" {{ $calcMethod === 'simple' ? 'selected' : '' }}>{{ __('Simple (Principal)') }}</option>
                        <option value="compound" {{ $calcMethod === 'compound' ? 'selected' : '' }}>{{ __('Compound (Interest)') }}</option>
                    </select>
                </div>

                <!-- 5. Frequency -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">{{ __('Charge Frequency') }} <span class="text-danger">*</span></label>
                    <select name="late_fee_config[frequency]" class="form-select form-select-sm late-fee-req" {{ $hasLateFee ? 'required' : '' }}>
                        <option value="one_time" {{ $frequency === 'one_time' ? 'selected' : '' }}>{{ __('One Time Only') }}</option>
                        <option value="monthly" {{ $frequency === 'monthly' ? 'selected' : '' }}>{{ __('Monthly Recurring') }}</option>
                    </select>
                </div>

                <!-- 6. Max Fee Limit (Optional) -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small mb-1">{{ __('Max Fee Limit') }} <small class="text-muted fw-normal">(Optional)</small></label>
                    <input type="number" step="0.01" min="0" name="late_fee_config[max_fee_limit]" class="form-control form-control-sm text-end"
                        value="{{ $maxFeeLimit }}" placeholder="{{ __('No Limit') }}">
                    <small class="text-muted" style="font-size: 11px;">{{ __('Maximum total cap limit.') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        function toggleLateFeeInputs(isChecked) {
            if (isChecked) {
                $('#late_fee_config_box').slideDown(200);
                $('.late-fee-req').prop('required', true);
            } else {
                $('#late_fee_config_box').slideUp(200);
                $('.late-fee-req').prop('required', false);
            }
        }

        $('#has_late_fee_toggle').off('change').on('change', function() {
            toggleLateFeeInputs($(this).is(':checked'));
        });

        // Initialize state on load
        toggleLateFeeInputs($('#has_late_fee_toggle').is(':checked'));
    });
</script>
@endpush