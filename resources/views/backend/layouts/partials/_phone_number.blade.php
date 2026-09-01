@php
    $isRequired = $isRequired ?? false;
@endphp

<label class="form-label fw-bold">{{ __('file.field.phone_number') }} @if($isRequired == true)<span class="text-danger">*</span> @endif<span id="phone-error" class="text-danger small"></span></label>
<div class="input-group">
    <input type="tel" name="phone" id="phone" class="form-control phone-input" value="{{ old('phone') }}" {{ $isRequired == true ? 'required' : '' }} placeholder="017xxxxxxxx">
</div>