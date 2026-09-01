@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.general_settings') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <style>
        .flatpickr-wrapper {
            display: block !important;
            width: 100% !important;
        }

        .flatpickr-time {
            height: 35px !important;
        }
    </style>
@endpush

@section('content')

    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.general_settings') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.general_settings_desc') }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            @php $firstActiveTab = null; @endphp
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="settingsTab" role="tablist">
                @can('manage_general_settings')
                    @php $firstActiveTab = $firstActiveTab ?? '#general'; @endphp
                    <li class="nav-item"><button class="nav-link {{ $firstActiveTab == '#general' ? 'active' : '' }}"
                            data-bs-toggle="tab" data-bs-target="#general">General</button></li>
                @endcan
                @can('manage_email_settings')
                    @php $firstActiveTab = $firstActiveTab ?? '#email'; @endphp
                    <li class="nav-item"><button class="nav-link {{ $firstActiveTab == '#email' ? 'active' : '' }}"
                            data-bs-toggle="tab" data-bs-target="#email">Email</button></li>
                @endcan
                @can('manage_currency_settings')
                    @php $firstActiveTab = $firstActiveTab ?? '#currency'; @endphp
                    <li class="nav-item"><button class="nav-link {{ $firstActiveTab == '#currency' ? 'active' : '' }}"
                            data-bs-toggle="tab" data-bs-target="#currency">Currency</button></li>
                @endcan
                @can('manage_analytics_settings')
                    @php $firstActiveTab = $firstActiveTab ?? '#analytics'; @endphp
                    <li class="nav-item"><button class="nav-link {{ $firstActiveTab == '#analytics' ? 'active' : '' }}"
                            data-bs-toggle="tab" data-bs-target="#analytics">Analytics</button></li>
                @endcan
                @can('manage_ai_settings')
                    @php $firstActiveTab = $firstActiveTab ?? '#ai'; @endphp
                    <li class="nav-item"><button class="nav-link {{ $firstActiveTab == '#ai' ? 'active' : '' }}"
                            data-bs-toggle="tab" data-bs-target="#ai">AI</button></li>
                @endcan
            </ul>

            <div class="tab-content" id="settingsTabContent">

                @can('manage_general_settings')
                    <div class="tab-pane fade {{ $firstActiveTab == '#general' ? 'show active' : '' }} " id="general">
                        <form action="{{ route('general-settings.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.company_name') }}</label>
                                    <input type="text" name="company_name"
                                        value="{{ old('company_name', $settings['company_name'] ?? '') }}"
                                        class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.site_title') }}</label>
                                    <input type="text" name="site_title"
                                        value="{{ old('site_title', $settings['site_title'] ?? '') }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.email') }}</label>
                                    <input type="email" name="company_email"
                                        value="{{ old('company_email', $settings['company_email'] ?? '') }}"
                                        class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.phone_number') }}</label>
                                    <input type="text" name="company_phone"
                                        value="{{ old('company_phone', $settings['company_phone'] ?? '') }}"
                                        class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.select_currency') }}</label>
                                    <input type="text" name="default_currency" class="form-control"
                                        value="{{ $default_currency['code'] . ' - ' . $default_currency['name'] }}" readonly
                                        disabled>
                                    {{-- <select name="default_currency" id="currency" class="form-select" >
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}"
                                                {{ old('default_currency', $settings['default_currency'] ?? '') == $currency->id ? 'selected' : '' }}>
                                                {{ $currency->name }} ({{ $currency->code }})</option>
                                        @endforeach
                                    {{-- <select name="default_currency" id="currency" class="form-select" >
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}"
                                                {{ old('default_currency', $settings['default_currency'] ?? '') == $currency->id ? 'selected' : '' }}>
                                                {{ $currency->name }} ({{ $currency->code }})</option>
                                        @endforeach
                                    </select> --}}
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.currency_position') }}</label>
                                    <select name="currency_position" class="form-select">
                                        <option value="left"
                                            {{ old('currency_position', $settings['currency_position'] ?? '') == 'left' ? 'selected' : '' }}>
                                            {{ __('file.option.left') }}</option>
                                        <option value="right"
                                            {{ old('currency_position', $settings['currency_position'] ?? '') == 'right' ? 'selected' : '' }}>
                                            {{ __('file.option.right') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.number_of_digits') }}</label>
                                    <input type="number" name="decimal_digits" class="form-control"
                                        value="{{ old('decimal_digits', $settings['decimal_digits'] ?? '2') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"
                                        for="currency_display_type">{{ __('file.field.currency_display_type') }}</label>
                                    <select class="form-select" name="currency_display_type" id="currency_display_type">
                                        <option value="symbol"
                                            {{ old('currency_display_type', $settings['currency_display_type'] ?? '') == 'symbol' ? 'selected' : '' }}>
                                            Symbol ($/৳/€...)
                                        </option>
                                        <option value="code"
                                            {{ old('currency_display_type', $settings['currency_display_type'] ?? '') == 'code' ? 'selected' : '' }}>
                                            Code (USD/BDT/EUR...)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"
                                        for="thousand_separator">{{ __('file.field.thousand_separator') }}</label>
                                    <select class="form-select" name="thousand_separator" id="thousand_separator">
                                        <option value=""
                                            {{ old('thousand_separator', $settings['thousand_separator'] ?? '') == '' ? 'selected' : '' }}>
                                            None (1000)
                                        </option>
                                        <option value=","
                                            {{ old('thousand_separator', $settings['thousand_separator'] ?? '') == ',' ? 'selected' : '' }}>
                                            Comma (1,000)
                                        </option>
                                        <option value="."
                                            {{ old('thousand_separator', $settings['thousand_separator'] ?? '') == '.' ? 'selected' : '' }}>
                                            Dot (1.000)
                                        </option>
                                        <option value="space"
                                            {{ old('thousand_separator', $settings['thousand_separator'] ?? '') == 'space' ? 'selected' : '' }}>
                                            Space (1 000)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.date_format') }}</label>
                                    <select name="date_format" class="form-select">
                                        @php
                                            $formats = [
                                                'd-m-Y' => 'd-m-Y',
                                                'd/m/Y' => 'd/m/Y',
                                                'd.m.y' => 'd.m.y',
                                                'm-d-Y' => 'm-d-Y',
                                                'm/d/y' => 'm/d/y',
                                                'm.d.y' => 'm.d.y',
                                                'Y-m-d' => 'Y-m-d',
                                                'Y/d/m' => 'Y/d/m',
                                                'Y.m.d' => 'Y.m.d',
                                                'd M, Y' => 'd M, Y',
                                                'd F, Y' => 'd F, Y',
                                                'l, d F Y' => 'l, d F Y',
                                                'D, d M Y' => 'D, d M Y',
                                            ];
                                        @endphp
                                        @foreach ($formats as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('date_format', $settings['date_format'] ?? '') == $value ? 'selected' : '' }}>
                                                {{ now()->format($value) }} ({{ $label }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.time_format') }}</label>
                                    <select name="time_format" class="form-select">
                                        <option value="12"
                                            {{ old('time_format', $settings['time_format'] ?? '') == '12' ? 'selected' : '' }}>
                                            12 Hours ({{ now()->format('g:i A') }})</option>
                                        <option value="24"
                                            {{ old('time_format', $settings['time_format'] ?? '') == '24' ? 'selected' : '' }}>
                                            24 Hours ({{ now()->format('H:i') }})</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('file.field.timezone') }}</label>
                                    <select name="timezone" class="selectpicker form-select" data-live-search="true">
                                        @foreach ($zones_array as $zone)
                                            <option
                                                {{ old('timezone', $settings['timezone'] ?? '') == $zone['zone'] ? 'selected' : '' }}
                                                value="{{ $zone['zone'] }}">
                                                {{ $zone['diff_from_GMT'] . ' - ' . $zone['zone'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('file.field.address') }}</label>
                                    <textarea name="company_address" class="form-control">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">{{ __('file.field.favicon') }}</label>
                                    <input type="file" name="favicon" class="form-control"
                                        accept="image/jpg, image/png, image/ico"
                                        onchange="previewImage(this, 'favicon_preview')">

                                    @if (!empty($settings['favicon']))
                                        <img id="favicon_preview" src="{{ file_url($settings['favicon']) }}" class="mt-2"
                                            height="50">
                                    @else
                                        <img id="favicon_preview" src="{{ url('images/preview_image.png') }}" class="mt-2"
                                            height="50">
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('file.field.company_logo') }}</label>
                                    <input type="file" name="company_logo" class="form-control"
                                        accept="image/jpg, image/jpeg, image/png"
                                        onchange="previewImage(this, 'logo_preview')">

                                    @if (!empty($settings['company_logo']))
                                        <img id="logo_preview" src="{{ file_url($settings['company_logo']) }}"
                                            class="mt-2" height="50">
                                    @else
                                        <img id="logo_preview" src="{{ url('images/preview_image.png') }}" class="mt-2"
                                            height="50">
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('file.field.toggle_logo') }}</label>
                                    <input type="file" name="toggle_logo" class="form-control"
                                        accept="image/jpg, image/jpeg, image/png"
                                        onchange="previewImage(this, 'toggle_logo_preview')">

                                    @if (!empty($settings['toggle_logo']))
                                        <img id="toggle_logo_preview" src="{{ file_url($settings['toggle_logo']) }}"
                                            class="mt-2" height="50">
                                    @else
                                        <img id="toggle_logo_preview" src="{{ url('images/preview_image.png') }}"
                                            class="mt-2" height="50">
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('file.field.white_logo') }}</label>
                                    <input type="file" name="white_logo" class="form-control"
                                        accept="image/jpg, image/jpeg, image/png"
                                        onchange="previewImage(this, 'white_logo_preview')">
                                    <div class="mt-2">
                                        @if (!empty($settings['white_logo']))
                                            <img id="white_logo_preview" src="{{ file_url($settings['white_logo']) }}"
                                                class="mt-2" height="50">
                                        @else
                                            <img id="white_logo_preview" src="{{ url('images/preview_image.png') }}"
                                                class="mt-2" height="50">
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('file.field.white_toggle_logo') }}</label>
                                    <input type="file" name="white_toggle_logo" class="form-control"
                                        accept="image/jpg, image/jpeg, image/png"
                                        onchange="previewImage(this, 'white_toggle_logo_preview')">

                                    <div class="mt-2">
                                        @if (!empty($settings['white_toggle_logo']))
                                            <img id="white_toggle_logo_preview"
                                                src="{{ file_url($settings['white_toggle_logo']) }}" class="mt-2"
                                                height="50">
                                        @else
                                            <img id="white_toggle_logo_preview" src="{{ url('images/preview_image.png') }}"
                                                class="mt-2" height="50">
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i>
                                    {{ __('file.button.save') }}</button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('manage_email_settings')
                    <div class="tab-pane fade {{ $firstActiveTab == '#email' ? 'show active' : '' }}" id="email">
                        <form action="{{ route('email-settings.update') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.mail_host') }}</label>
                                    <input type="text" name="mail_host" value="{{ $settings['mail_host'] ?? '' }}"
                                        class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.mail_port') }}</label>
                                    <input type="text" name="mail_port" value="{{ $settings['mail_port'] ?? '' }}"
                                        class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.username') }}</label>
                                    <input type="text" name="mail_username"
                                        value="{{ $settings['mail_username'] ?? '' }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.password') }}</label>
                                    <input type="password" name="mail_password"
                                        value="{{ $settings['mail_password'] ?? '' }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.encryption') }}</label>
                                    <select name="mail_encryption" class="form-select">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.from_address') }}</label>
                                    <input type="email" name="mail_from_address"
                                        value="{{ $settings['mail_from_address'] ?? '' }}" class="form-control">
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button class="btn btn-primary"><i class="bi bi-save"></i>
                                    {{ __('file.button.save_email_settings') }}</button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('manage_currency_settings')
                    <div class="tab-pane fade {{ $firstActiveTab == '#currency' ? 'show active' : '' }}" id="currency">
                        <form action="{{ route('currency-settings.update') }}" method="POST" id="currencySettingsForm">
                            @csrf
                            <div class="row g-3">

                                <!-- 1. Multi-Currency Enable/Disable Switch -->
                                <div class="col-12">
                                    <div
                                        class="form-check form-switch d-flex flex-wrap justify-content-between align-items-center bg-light p-3 rounded gap-3">
                                        <div class="form-check form-switch m-0 ps-0 d-flex align-items-center gap-2">
                                            <!-- সুইচটিকে ভেতরে ঠিকমতো বসাতে ফ্লো ঠিক করা হলো -->
                                            <input class="form-check-input m-0" type="checkbox" role="switch"
                                                id="multiCurrencySwitch" name="multi_currency_enabled" value="1"
                                                style="float: none; cursor: pointer;"
                                                {{ old('multi_currency_enabled', $settings['use_multi_currency'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold ms-2"
                                                for="multiCurrencySwitch">{{ __('file.label.enable_multi_currency') }}</label>
                                        </div>

                                        <!-- View Rates এবং Sync Now বাটন দুটি পাশাপাশি থাকবে এবং ডাটাবেজে এনাবল সেভ থাকলে বা ফর্মে অন করলে কাজ করবে -->
                                        @if(isset($settings['use_multi_currency']) && $settings['use_multi_currency'] == 1)
                                            <div id="currencyActionButtons" class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#viewRatesModal">
                                                    <i class="bi bi-eye"></i> {{ __('file.button.view_rates') ?? 'View Rates' }}
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm" id="syncRatesNowBtn">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                    {{ __('file.button.sync_rates_now') ?? 'Sync Rates Now' }}
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Dependent Container -->
                                <div id="multiCurrencyFieldsContainer" class="row g-3 mt-1">
                                    <!-- 2. Rate Source Option -->
                                    <div class="col-md-12">
                                        <label
                                            class="form-label fw-semibold">{{ __('file.label.exchange_rate_source') }}</label>
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input rate-source" type="radio" name="rate_source"
                                                    id="rateSourceSystem" value="system"
                                                    {{ old('rate_source', $settings['rate_source'] ?? 'system') == 'system' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="rateSourceSystem">
                                                    Get Rates from System
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input rate-source" type="radio" name="rate_source"
                                                    id="rateSourceApi" value="api"
                                                    {{ old('rate_source', $settings['rate_source'] ?? 'system') == 'api' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="rateSourceApi">Get Rates from API</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. API Providers -->
                                    <div class="col-md-6" id="apiProviderWrapper" style="display: none;">
                                        <label for="apiProviderSelect"
                                            class="form-label fw-semibold">{{ __('file.label.api_providers') }} <span
                                                class="text-danger">*</span></label>
                                        <select name="api_provider" id="apiProviderSelect" class="form-select">
                                            <option value="">{{ __('file.placeholder.select_provider') }}</option>
                                            <option value="exchangerate_api"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'exchangerate_api' ? 'selected' : '' }}>
                                                ExchangeRate-API</option>
                                            <option value="open_exchange"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'open_exchange' ? 'selected' : '' }}>
                                                Open Exchange Rates</option>
                                            <option value="fixer"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'fixer' ? 'selected' : '' }}>
                                                Fixer.io</option>
                                            <option value="currencylayer"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'currencylayer' ? 'selected' : '' }}>
                                                Currencylayer</option>
                                            <option value="coinlayer"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'coinlayer' ? 'selected' : '' }}>
                                                Coinlayer</option>
                                            <option value="free_currency_api"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'free_currency_api' ? 'selected' : '' }}>
                                                Free Currency API</option>
                                            <option value="alpha_vantage"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'alpha_vantage' ? 'selected' : '' }}>
                                                Alpha Vantage</option>
                                            <option value="ecb_api"
                                                {{ old('api_provider', $settings['api_provider'] ?? '') == 'ecb_api' ? 'selected' : '' }}>
                                                European Central Bank (ECB)</option>
                                        </select>
                                        <div class="invalid-feedback" id="apiProviderError">Please select an API provider.
                                        </div>
                                    </div>

                                    <!-- 4. Dynamic API Key Field with Password Type & Eye Icon -->
                                    <div class="col-md-6" id="apiDynamicFieldWrapper" style="display: none;">
                                        <label for="apiKeyInput" id="apiKeyLabel" class="form-label fw-semibold">API Key /
                                            Credentials <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" name="api_key" id="apiKeyInput" class="form-control"
                                                placeholder="Enter API Key or Token"
                                                value="{{ old('api_key', $settings['api_key'] ?? '') }}"
                                                autocomplete="new-password">
                                            <button class="btn btn-outline-secondary" type="button"
                                                id="toggleApiKeyVisibility" title="Toggle visibility">
                                                <i class="bi bi-eye" id="toggleIcon"></i>
                                            </button>
                                            <div class="invalid-feedback" id="apiKeyError">API Key or Token is required.</div>
                                        </div>
                                    </div>

                                    <!-- 5. Auto Sync Settings (Frequency & Time) -->
                                    <div class="col-12 mt-3">
                                        <hr>
                                        <h6 class="fw-bold mb-3">
                                            {{ __('file.label.auto_sync_settings') ?? 'Automatic Sync Settings' }}</h6>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="syncFrequency"
                                            class="form-label fw-semibold">{{ __('file.label.sync_frequency') ?? 'Sync Frequency' }}</label>
                                        <select name="sync_frequency" id="syncFrequency" class="form-select">
                                            <option value="manual"
                                                {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == 'manual' ? 'selected' : '' }}>
                                                Manual Only</option>
                                            <option value="daily"
                                                {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == 'daily' ? 'selected' : '' }}>
                                                Daily</option>
                                            <option value="weekly"
                                                {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == 'weekly' ? 'selected' : '' }}>
                                                Weekly</option>
                                            <option value="monthly"
                                                {{ old('sync_frequency', $settings['sync_frequency'] ?? '') == 'monthly' ? 'selected' : '' }}>
                                                Monthly</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2" id="syncTimeWrapper">
                                        <label for="syncTime"
                                            class="form-label fw-semibold">{{ __('file.label.sync_time') ?? 'Sync Time' }}</label>
                                        <input type="text" name="sync_time" id="syncTime"
                                            class="form-control flatpickr-time" placeholder="Select Hour"
                                            value="{{ old('sync_time', $settings['sync_time'] ?? '07:00 AM') }}">
                                    </div>
                                </div>

                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary" id="saveCurrencyBtn"><i
                                        class="bi bi-save"></i> {{ __('file.button.save_currency_settings') }}</button>
                            </div>
                        </form>
                    </div>

                    <!-- View Rates Modal -->
                    <div class="modal fade" id="viewRatesModal" tabindex="-1" aria-labelledby="viewRatesModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewRatesModalLabel">
                                        {{ __('file.title.current_exchange_rates') ?? 'Current Exchange Rates' }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted mt-2">Loading rates...</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('manage_analytics_settings')
                    <div class="tab-pane fade {{ $firstActiveTab == '#analytics' ? 'show active' : '' }}" id="analytics">
                        <form action="{{ route('analytics-settings.update') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('file.field.google_tag') }}</label>
                                    <textarea name="google_tag" class="form-control" rows="4">{{ $settings['google_tag'] ?? '' }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('file.field.facebook_pixel') }}</label>
                                    <textarea name="facebook_pixel" class="form-control" rows="4">{{ $settings['facebook_pixel'] ?? '' }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('file.field.chat_script') }}</label>
                                    <textarea name="chat_script" class="form-control" rows="4">{{ $settings['chat_script'] ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button class="btn btn-primary"><i class="bi bi-save"></i>
                                    {{ __('file.button.save_analytics') }}</button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('manage_ai_settings')
                    <div class="tab-pane fade {{ $firstActiveTab == '#ai' ? 'show active' : '' }}" id="ai"
                        style="pointer-events: none;">
                        <form action="#" method="POST" style="opacity: 0.5;">
                            @csrf
                            <p class="text-muted mb-3">AI Settings for future integration (e.g., OpenAI, ChatGPT, etc.)</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.ai_provider') }}</label>
                                    <select name="ai_provider" class="form-select" disabled>
                                        <option value="openai">{{ __('file.option.openai') }}</option>
                                        <option value="anthropic">{{ __('file.option.anthropic') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('file.field.api_key') }}</label>
                                    <input type="text" name="ai_api_key" class="form-control"
                                        value="{{ $settings['ai_api_key'] ?? '' }}" disabled>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">{{ __('file.field.default_model') }}</label>
                                    <input type="text" name="ai_model" class="form-control"
                                        value="{{ $settings['ai_model'] ?? 'gpt-4' }}" disabled>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button class="btn btn-primary" disabled><i
                                        class="bi bi-save"></i>{{ __('file.button.save_ai_setting') }}</button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // Persist last opened tab safely
            const activeTab = localStorage.getItem('activeTenantSettingsTab');
            if (activeTab) {
                const targetEl = document.querySelector(`[data-bs-target="${activeTab}"]`);
                if (targetEl) {
                    new bootstrap.Tab(targetEl).show();
                } else {
                    localStorage.removeItem('activeTenantSettingsTab'); // Invalid tab clear
                }
            }

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                localStorage.setItem('activeTenantSettingsTab', $(e.target).data('bs-target'));
            });

        });

        function previewImage(input, previewId) {
            const file = input.files[0];
            if (file) {
                document.getElementById(previewId).src = URL.createObjectURL(file);
            }
        }

        $('.selectpicker').select2();
    </script>
    @can('manage_currency_settings')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('currencySettingsForm');
                if (!form) return;

                const multiCurrencySwitch = document.getElementById('multiCurrencySwitch');
                const container = document.getElementById('multiCurrencyFieldsContainer');
                const rateSourceApi = document.getElementById('rateSourceApi');
                const rateSourceSystem = document.getElementById('rateSourceSystem');
                const apiProviderWrapper = document.getElementById('apiProviderWrapper');
                const apiDynamicFieldWrapper = document.getElementById('apiDynamicFieldWrapper');
                const apiProviderSelect = document.getElementById('apiProviderSelect');
                const apiKeyInput = document.getElementById('apiKeyInput');
                const apiKeyLabel = document.getElementById('apiKeyLabel');
                const toggleApiKeyBtn = document.getElementById('toggleApiKeyVisibility');
                const toggleIcon = document.getElementById('toggleIcon');
                const currencyActionButtons = document.getElementById('currencyActionButtons');

                function toggleFormStates() {
                    if (!multiCurrencySwitch || !container) return;
                    const isEnabled = multiCurrencySwitch.checked;

                    // Toggle action buttons visibility based on switch
                    if (currencyActionButtons) {
                        currencyActionButtons.style.display = isEnabled ? 'flex' : 'none';
                    }

                    container.querySelectorAll('input, select').forEach(el => {
                        if (el === rateSourceSystem && el.hasAttribute('data-restricted')) return;
                        el.disabled = !isEnabled;
                    });

                    if (isEnabled) {
                        container.style.opacity = '1';
                        container.style.pointerEvents = 'auto';
                        checkRateSource();
                    } else {
                        container.style.opacity = '0.5';
                        container.style.pointerEvents = 'none';
                        if (apiProviderWrapper) apiProviderWrapper.style.display = 'none';
                        if (apiDynamicFieldWrapper) apiDynamicFieldWrapper.style.display = 'none';
                    }
                }

                function checkRateSource() {
                    if (!rateSourceApi || !multiCurrencySwitch) return;
                    if (rateSourceApi.checked && multiCurrencySwitch.checked) {
                        if (apiProviderWrapper) apiProviderWrapper.style.display = 'block';
                        if (apiProviderSelect && apiProviderSelect.value) {
                            if (apiDynamicFieldWrapper) apiDynamicFieldWrapper.style.display = 'block';
                            updateApiKeyLabel(apiProviderSelect.value);
                        } else {
                            if (apiDynamicFieldWrapper) apiDynamicFieldWrapper.style.display = 'none';
                        }
                    } else {
                        if (apiProviderWrapper) apiProviderWrapper.style.display = 'none';
                        if (apiDynamicFieldWrapper) apiDynamicFieldWrapper.style.display = 'none';
                    }
                }

                function updateApiKeyLabel(provider) {
                    if (!apiKeyLabel) return;
                    if (provider === 'fixer') {
                        apiKeyLabel.innerHTML = 'Fixer API Key <span class="text-danger">*</span>';
                    } else if (provider === 'open_exchange') {
                        apiKeyLabel.innerHTML = 'Open Exchange App ID <span class="text-danger">*</span>';
                    } else if (provider === 'alpha_vantage') {
                        apiKeyLabel.innerHTML = 'Alpha Vantage API Key <span class="text-danger">*</span>';
                    } else {
                        apiKeyLabel.innerHTML = 'API Key / Token <span class="text-danger">*</span>';
                    }
                }

                if (multiCurrencySwitch) {
                    multiCurrencySwitch.addEventListener('change', toggleFormStates);
                }

                document.querySelectorAll('.rate-source').forEach(radio => {
                    radio.addEventListener('change', checkRateSource);
                });

                if (apiProviderSelect) {
                    apiProviderSelect.addEventListener('change', function() {
                        if (this.value) {
                            if (apiDynamicFieldWrapper) apiDynamicFieldWrapper.style.display = 'block';
                            updateApiKeyLabel(this.value);
                        } else {
                            if (apiDynamicFieldWrapper) apiDynamicFieldWrapper.style.display = 'none';
                        }
                    });
                }

                // Toggle Password Visibility (Eye Icon)
                if (toggleApiKeyBtn && apiKeyInput && toggleIcon) {
                    toggleApiKeyBtn.addEventListener('click', function() {
                        const type = apiKeyInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        apiKeyInput.setAttribute('type', type);
                        toggleIcon.classList.toggle('bi-eye');
                        toggleIcon.classList.toggle('bi-eye-slash');
                    });
                }

                // Form Submit Client-side Validation Check
                form.addEventListener('submit', function(e) {
                    if (multiCurrencySwitch && multiCurrencySwitch.checked && rateSourceApi && rateSourceApi
                        .checked) {
                        let isValid = true;

                        if (apiProviderSelect && !apiProviderSelect.value) {
                            apiProviderSelect.classList.add('is-invalid');
                            isValid = false;
                        } else if (apiProviderSelect) {
                            apiProviderSelect.classList.remove('is-invalid');
                        }

                        if (apiKeyInput && !apiKeyInput.value.trim()) {
                            apiKeyInput.classList.add('is-invalid');
                            isValid = false;
                        } else if (apiKeyInput) {
                            apiKeyInput.classList.remove('is-invalid');
                        }

                        if (!isValid) {
                            e.preventDefault();
                        }
                    }
                });

                toggleFormStates();

                if (typeof flatpickr !== 'undefined') {
                    flatpickr(".flatpickr-time", {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "h K", // শুধু ঘণ্টা এবং AM/PM দেখাবে (যেমন: 07 AM)
                        altInput: false,
                        time_24hr: false,
                        minuteIncrement: 60 // মিনিট ইনক্রিমেন্ট ৬০ মিনিট করে দেওয়া হলো
                    });
                }
            });

            $('#syncRatesNowBtn').on('click', function() {
                const $btn = $(this);
                const originalText = $btn.html();

                // Disable button and show loading state
                $btn.prop('disabled', true);
                $btn.html(
                    `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Syncing...`
                );

                // Send POST request using jQuery AJAX
                $.ajax({
                    url: "{{ route('currency-settings.sync-rate-now') }}",
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showFloatingAlert('success', response.message ||
                                'Rates synchronized successfully!');

                            // Optionally reload the page or update UI if needed
                            // location.reload();
                        } else {
                            showFloatingAlert('error', response.message ||
                                'Failed to sync rates. Please try again.');
                        }
                    },
                    error: function(xhr) {
                        // console.error('Error:', xhr.responseText);
                        showFloatingAlert('error', xhr.responseText ||
                            'An error occurred while syncing rates.');
                    },
                    complete: function() {
                        // Restore button state
                        $btn.prop('disabled', false);
                        $btn.html(originalText);
                    }
                });
            });

            $('#viewRatesModal').on('show.bs.modal', function () {
                const $modalBody = $(this).find('.modal-body');
                
                // Show loading state
                $modalBody.html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading current rates...</p>
                    </div>
                `);

                // Fetch rates via AJAX
                $.ajax({
                    url: "{{ route('currency-settings.get-rates') }}",
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.success && response.data && response.data.rates) {
                            const ratesData = response.data.rates;
                            const lastUpdated = formatedDate(response.data.last_updated_at, true) ?? 'N/A';
                            const baseCode = response.data.base_code ?? 'BDT';
                            
                            let html = `
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <small class="text-muted"><strong>Base:</strong> ${baseCode} | <strong>Updated:</strong> ${lastUpdated}</small>
                                </div>
                                
                                <!-- Search input for filtering currencies -->
                                <div class="mb-3">
                                    <input type="text" id="currencySearchInput" class="form-control form-control-sm" placeholder="Search currency code (e.g. USD, EUR)...">
                                </div>

                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <div class="row g-2" id="currencyGridContainer" style="margin-bottom: 10px;">
                            `;

                            // Loop through rates and display in a 4-column layout (col-lg-3 for 4 columns on large screens)
                            $.each(ratesData, function (code, rate) {
                                html += `
                                    <div class="col-lg-3 col-md-4 col-6 currency-item" data-code="${code.toLowerCase()}">
                                        <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center h-100">
                                            <span class="fw-bold text-dark">${code}</span>
                                            <span class="text-muted font-monospace small">${rate}</span>
                                        </div>
                                    </div>
                                `;
                            });

                            html += `
                                    </div>
                                </div>
                            `;

                            $modalBody.html(html);

                            // English comment: Implement live search filtering functionality
                            $('#currencySearchInput').on('keyup', function () {
                                let query = $(this).val().toLowerCase().trim();
                                
                                $('#currencyGridContainer .currency-item').each(function () {
                                    let currencyCode = $(this).data('code');
                                    if (currencyCode.includes(query)) {
                                        $(this).show();
                                    } else {
                                        $(this).hide();
                                    }
                                });
                            });

                        } else {
                            $modalBody.html(`<div class="alert alert-danger">Failed to load exchange rates data.</div>`);
                        }
                    },
                    error: function () {
                        $modalBody.html(`<div class="alert alert-danger">An unexpected error occurred while fetching rates.</div>`);
                    }
                });
            });
        </script>
    @endcan
@endpush
