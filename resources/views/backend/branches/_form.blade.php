@php
    $isEdit = $isEdit ?? false;
    $accounts = $accounts ?? [];
    $currencies = $currencies ?? [];
    $isMultiCurrency =
        !empty($general_settings['use_multi_currency']) &&
        ($general_settings['use_multi_currency'] == '1' || $general_settings['use_multi_currency'] == true);
@endphp


<div class="row">
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_name' : 'name' }}" class="form-label">Name *</label>
        <input type="text" class="form-control" id="{{ $isEdit ? 'edit_name' : 'name' }}" name="name" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_phone' : 'phone' }}" class="form-label">Phone Number</label>
        <input type="text" class="form-control" id="{{ $isEdit ? 'edit_phone' : 'phone' }}" name="phone" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_email' : 'email' }}" class="form-label">Email</label>
        <input type="email" class="form-control" id="{{ $isEdit ? 'edit_email' : 'email' }}" name="email" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_bin_number' : 'bin_number' }}" class="form-label">Bin Number</label>
        <input type="text" class="form-control" id="{{ $isEdit ? 'edit_bin_number' : 'bin_number' }}" name="bin_number">
    </div>
    <div class="col-md-12 mb-3">
        <label for="{{ $isEdit ? 'edit_address' : 'address' }}" class="form-label">Address</label>
        <input type="text" class="form-control" id="{{ $isEdit ? 'edit_address' : 'address' }}" name="address" required>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="{{ $isEdit ? 'edit_currency' : 'currency' }}" class="form-label">Currency</label>
        @if ($isMultiCurrency)
            <select name="currency_id" id="{{ $isEdit ? 'edit_currency_id' : 'currency_id' }}" class="form-select select-currency" required>
                @forelse ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                        data-code="{{ $currency->code }}" {{ $default_currency['id'] == $currency->id ? 'selected' : '' }}>
                        {{ $currency->name . ' - ' . $currency->code }}
                    </option>
                @empty
                    <option value="">{{ __('file.option.no') }}</option>
                @endforelse
            </select>
        @else
            <select class="form-select select-picker" disabled>
                @foreach ($currencies as $currency)
                    @if ($default_currency['id'] == $currency->id)
                        <option selected>{{ $currency->name . ' - ' . $currency->code }}</option>
                    @endif
                @endforeach
            </select>
            <input type="hidden" id="{{ $isEdit ? 'edit_currency_id' : 'currency_id' }}" name="currency_id" value="{{ $default_currency['id'] }}">
        @endif
    </div>
    <div class="col-md-2 mb-3">
        <label for="{{ $isEdit ? 'edit_is_active' : 'is_active' }}" class="form-label">Is Active</label>
        <select name="is_active" id="{{ $isEdit ? 'edit_is_active' : 'is_active' }}" class="form-select" required>
            <option value="1" selected>Yes</option>
            <option value="0">No</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_image' : 'image' }}" class="form-label">Image</label>
        <div class="input-group">
            <input type="file" name="image" class="form-control" id="{{ $isEdit ? 'edit_image' : 'image' }}" accept="image/jpeg,image/png" onchange="document.getElementById('{{ $isEdit ? 'edit_image_preview' : 'image_preview' }}').src = window.URL.createObjectURL(this.files[0]);">
            <label class="input-group-text" for="{{ $isEdit ? 'edit_image' : 'image' }}">
                <i class="fa-solid fa-upload"></i>
            </label>
        </div>

        <img id="{{ $isEdit ? 'edit_image_preview' : 'image_preview' }}" alt="Preview"
             src="{{ url('images/preview_image.png') }}"
             data-default="{{ url('images/preview_image.png') }}"
             class="img-thumbnail mt-2 image-preview-class" style="max-height: 50px;">
    </div>
</div>
