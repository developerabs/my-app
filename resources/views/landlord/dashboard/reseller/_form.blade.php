@php
    $isEdit = $isEdit ?? false;
@endphp

<form id="{{ $isEdit ? 'editResellerForm' : 'createResellerForm' }}"
      method="POST"
      enctype="multipart/form-data">
    @csrf
    @if($isEdit)
        @method('PATCH')
        <input type="hidden" name="id" id="edit_id" value="">
    @endif

    {{-- Name --}}
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="{{ $isEdit ? 'edit_name' : 'name' }}" class="form-label">{{ __('file.field.name') }}</label>
            <input type="text"
                class="form-control"
                id="{{ $isEdit ? 'edit_name' : 'name' }}"
                name="name"
                value=""
                required>
        </div>

        <div class="col-md-4 mb-3">
            <label for="{{ $isEdit ? 'edit_username' : 'username' }}" class="form-label">{{ __('file.field.username') }}</label>
            <input type="text"
                class="form-control"
                id="{{ $isEdit ? 'edit_username' : 'username' }}"
                name="username"
                value=""
                required>
        </div>

        <div class="col-md-4 mb-3">
            <label for="{{ $isEdit ? 'edit_reseller_type' : 'reseller_type' }}" class="form-label">{{ __('file.field.reseller_type') }}</label>
            <select name="type" id="{{ $isEdit ? 'edit_reseller_type' : 'reseller_type' }}" class="form-select" required>
                <option value="external" @if(isset($reseller) && $reseller->type == 'external') selected @endif>{{ __('file.option.external') }}</option>
                <option value="internal" @if(isset($reseller) && $reseller->type == 'internal') selected @endif>{{ __('file.option.internal') }}</option>
            </select>
        </div>

    </div>

    {{-- Phone & Email --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="{{ $isEdit ? 'edit_phone' : 'phone' }}" class="form-label">{{ __('file.field.phone_number') }}</label>
            <input type="text"
                   class="form-control"
                   id="{{ $isEdit ? 'edit_phone' : 'phone' }}"
                   name="phone"
                   value=""
                   required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="{{ $isEdit ? 'edit_email' : 'email' }}" class="form-label">{{ __('file.field.email') }}</label>
            <input type="email"
                   class="form-control"
                   id="{{ $isEdit ? 'edit_email' : 'email' }}"
                   name="email"
                   value=""
                   required
                   autocomplete="off"
                   {{ $isEdit ? 'readonly' : '' }}>
        </div>
    </div>

    {{-- Company Name & Logo --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="{{ $isEdit ? 'edit_company_name' : 'company_name' }}" class="form-label">{{ __('file.field.company_name') }}</label>
            <input type="text"
                   class="form-control"
                   id="{{ $isEdit ? 'edit_company_name' : 'company_name' }}"
                   name="company_name"
                   value="">
        </div>
        <div class="col-md-6 mb-3">
            <div class="row">
                <div class="col-8">
                    <label for="{{ $isEdit ? 'edit_company_logo' : 'company_logo' }}" class="form-label">{{ __('file.field.company_logo') }}</label>
                    <input type="file"
                           class="form-control"
                           id="{{ $isEdit ? 'edit_company_logo' : 'company_logo' }}"
                           name="company_logo"
                           onchange="document.getElementById('{{ $isEdit ? 'edit_existingLogoPreview' : 'existingLogoPreview'}}').src = window.URL.createObjectURL(this.files[0]);">
                </div>
                <div class="col-4">
                    <label class="form-label">{{ __('file.field.preview') }}</label>
                    <div class="mb-1">
                        <img id="{{ $isEdit ? 'edit_existingLogoPreview' : 'existingLogoPreview'}}"
                             src="{{ asset('images/preview_image.png') }}"
                             alt="Logo" class="rounded" height="40">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Address --}}
    <div class="mb-3">
        <label for="{{ $isEdit ? 'edit_address' : 'address' }}" class="form-label">{{ __('file.field.address') }}</label>
        <input type="text"
               class="form-control"
               id="{{ $isEdit ? 'edit_address' : 'address' }}"
               name="address"
               value="">
    </div>

    {{-- Commission --}}
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="{{ $isEdit ? 'edit_commission_per_registration' : 'commission_per_registration' }}" class="form-label">{{ __('file.field.commission_per_registration') }}</label>
            <input type="number"
                   step="0.01"
                   class="form-control"
                   id="{{ $isEdit ? 'edit_commission_per_registration' : 'commission_per_registration' }}"
                   name="commission_per_registration"
                   value="">
        </div>
        <div class="col-md-6 mb-3">
            <label for="{{ $isEdit ? 'edit_commission_per_subscription' : 'commission_per_subscription' }}" class="form-label">{{ __('file.field.commission_per_subscription') }}</label>
            <input type="number"
                   step="0.01"
                   class="form-control"
                   id="{{ $isEdit ? 'edit_commission_per_subscription' : 'commission_per_subscription' }}"
                   name="commission_per_subscription"
                   value="">
        </div>
    </div>

    {{-- Password only for create --}}
    @if(!$isEdit)
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label">{{ __('file.field.password') }}</label>
                <div style="position: relative;">
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                    <span onclick="togglePassword('#password', '#password-icon')" style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                            <i id="password-icon" class="fa-solid fa-eye"></i>
                        </span>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="password_confirmation" class="form-label">{{ __('file.field.password_confirmation') }}</label>
                <div style="position: relative;">
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    <span onclick="togglePassword('#password_confirmation', '#password-icon-confirm')" style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                            <i id="password-icon-confirm" class="fa-solid fa-eye"></i>
                        </span>
                </div>
            </div>
        </div>
    @endif

    {{-- Submit button --}}
    <button type="submit" class="btn btn-primary">{{ $isEdit ? __('file.button.update').' '.__('file.reseller') : __('file.button.create').' '.__('file.reseller') }}</button>
</form>
