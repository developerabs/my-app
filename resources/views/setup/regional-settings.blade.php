@extends('layouts.setup')

@push('css')
    <style>
        /* Main page wrapper */
        html,
        body {
            height: 100%;
            margin: 0;
            background: #f4f7fb;
        }

        .setup-page {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Reduced height slightly to make it compact */
        .setup-card {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .08);
            width: 100%;
            max-width: 1000px;
            height: auto;
            min-height: 550px;
        }

        .left-panel {
            padding: 30px;
            background: #fff;
        }

        .right-panel {
            background: linear-gradient(135deg, #0d6efd 0%, #4d84ff 100%);
            color: #fff;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .right-panel p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Keep all original elements */
        .logo-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-circle i {
            font-size: 2rem;
        }

        .progress {
            height: 8px;
            border-radius: 30px;
        }

        .setup-steps {
            margin-top: 20px;
            flex: 0 1 auto;
        }

        .setup-step {
            display: flex;
            gap: 15px;
            position: relative;
            padding-bottom: 15px;
        }

        .setup-step:not(:last-child)::after {
            content: "";
            position: absolute;
            left: 15px;
            top: 20px;
            width: 2px;
            height: calc(100% - 15px);
            background: #d8dde7;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            color: #000000;
            flex-shrink: 0;
            z-index: 2;
        }

        .step-content h6 {
            margin-bottom: 2px;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: left;
        }

        .step-content p {
            margin: 0;
            color: #ffffff;
            font-size: 0.8rem;
            text-align: left;
        }

        .setup-step.active .step-icon {
            background: #fffb2a;
        }

        .setup-step.active .step-icon i {
            color: #000000;
        }

        .setup-step.active .step-content p {
            color: #fffb2a;
        }

        .setup-step.active .step-content h6 {
            color: #fffb2a;
        }

        .setup-step.complete .step-icon {
            background: #2aff58;
        }

        .setup-step.complete .step-icon i {
            color: #000000;
        }

        .setup-step.complete .step-content p {
            color: #2aff58;
        }

        .setup-step.complete .step-content h6 {
            color: #2aff58;
        }
        .form_section{
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        @media(max-width:991px) {
            .right-panel {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="setup-page">
        <div class="card setup-card">
            <div class="row g-0 h-100">
                {{-- Right Panel (As it was) --}}
                <div class="col-lg-5">
                    <div class="right-panel h-100">
                        <div class="logo-circle"><i class="bi bi-gear-wide-connected"></i></div>
                        <h4 class="fw-bold">ERP Initial Setup</h4>
                        <p class="opacity-75 mt-2">This wizard will guide you through the initial configuration of your ERP
                            system.</p>
                        <div class="w-100">
                            <div class="d-flex justify-content-between mb-1 small fw-semibold"><span>Setup Progress</span>
                                <span>25%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width:25%"></div>
                            </div>
                        </div>
                        <div class="setup-steps">
                            <div class="setup-step active">
                                <div class="step-icon"><i class="bi bi-globe2"></i></div>
                                <div class="step-content">
                                    <h6>Regional Settings</h6>
                                    <p>Configure your country, timezone, language and default currency.</p>
                                </div>
                            </div>
                            <div class="setup-step">
                                <div class="step-icon"><i class="bi bi-calculator"></i></div>
                                <div class="step-content">
                                    <h6>Accounting Settings</h6>
                                    <p>Configure accounting preferences, numbering and financial options.</p>
                                </div>
                            </div>
                            <div class="setup-step">
                                <div class="step-icon"><i class="bi bi-diagram-3"></i></div>
                                <div class="step-content">
                                    <h6>Branch Settings</h6>
                                    <p>Create your primary business branch.</p>
                                </div>
                            </div>
                            <div class="setup-step">
                                <div class="step-icon"><i class="bi bi-check2-circle"></i></div>
                                <div class="step-content">
                                    <h6>Complete Setup</h6>
                                    <p>Finish configuration and start using ERP.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Left Panel (As it was) --}}
                <div class="col-lg-7">
                    <div class="left-panel h-100">
                        <form action="{{ route('setup.regional.store') }}" method="POST" class="mt-auto pt-3 form_section">
                            <div>
                                <h4 class="fw-bold">Regional Settings</h4>
                            </div>
                            @csrf
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="country" class="form-label small fw-bold mb-1">Country <span
                                                class="text-danger">*</span></label>
                                        <select name="country" id="country" class="form-select shadow-sm select2"
                                            required>
                                            <option value="">Select Country</option>
                                            @foreach ($countries as $country)
                                                <option {{ $country->name == 'Bangladesh' ? 'selected' : '' }}
                                                    value="{{ $country->id .'-'. $country->name }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="language" class="form-label small fw-bold mb-1">Language <span
                                                class="text-danger">*</span></label>
                                                @php
                                                    $locales = config('locales');
                                                @endphp
                                        <select name="language" id="language" class="form-select shadow-sm select2"
                                            required>
                                            
                                            @foreach ($locales as $key => $locale)
                                                <option value="{{ $key }}">{{ $locale['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-2">
                                        <label for="timezone" class="form-label small fw-bold mb-1">Timezone <span
                                                class="text-danger">*</span></label>
                                        <select name="timezone" id="timezone" class="form-select shadow-sm select2"
                                            required>
                                            <option value="">Select Timezone</option>
                                            @foreach ($zones_array as $zone)
                                                <option {{ $zone['zone'] == 'Asia/Dhaka' ? 'selected' : '' }}
                                                    value="{{ $zone['zone'] }}">
                                                    {{ $zone['diff_from_GMT'] . ' - ' . $zone['zone'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="date_format" class="form-label small fw-bold mb-1">Date Format <span
                                                class="text-danger">*</span></label>
                                        <select name="date_format" id="date_format" class="form-select shadow-sm" required>
                                            @foreach (\App\Enums\DateFormat::cases() as $format)
                                                <option value="{{ $format->value }}">
                                                    {{ $format->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="date_format" class="form-label small fw-bold mb-1">Time Format <span
                                                class="text-danger">*</span></label>
                                        <select name="time_format" class="form-select">
                                            <option value="12"
                                                {{ old('time_format', $settings['time_format'] ?? '') == '12' ? 'selected' : '' }}>
                                                12 Hours ({{ now()->format('g:i A') }})</option>
                                            <option value="24"
                                                {{ old('time_format', $settings['time_format'] ?? '') == '24' ? 'selected' : '' }}>
                                                24 Hours ({{ now()->format('H:i') }})</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="currency" class="form-label small fw-bold mb-1">Default Currency <span
                                                class="text-danger">*</span></label>
                                        <select name="currency" id="currency" class="form-select shadow-sm select2"
                                            required>
                                            @foreach ($currencies as $currency)
                                                <option {{ $currency->code == 'BDT' ? 'selected' : '' }}
                                                    value="{{ $currency->id }}">
                                                    {{ $currency->name . ' - ' . $currency->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="currency_display_type" class="form-label small fw-bold mb-1">Display Type <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="currency_display_type" id="currency_display_type">
                                            <option value="symbol">
                                                Symbol ($/৳/€...)
                                            </option>
                                            <option value="code">
                                                Code (USD/BDT/EUR...)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="currency_position" class="form-label small fw-bold mb-1">Currency Position<span
                                                class="text-danger">*</span></label>
                                        <select name="currency_position" id="currency_position" class="form-select shadow-sm"
                                            required>
                                            <option value="left">
                                                Left ($ 1000.00)</option>
                                            <option value="right">
                                                Right (1000.00 $)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-2">
                                        <label for="decimal_digits" class="form-label small fw-bold mb-1">Decimal Digits <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="decimal_digits" id="decimal_digits" class="form-control shadow-sm"
                                            value="2" required>
                                    </div>
                                </div>

                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-2"><i class="bi bi-arrow-right-circle me-2"></i> Save & Next</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
