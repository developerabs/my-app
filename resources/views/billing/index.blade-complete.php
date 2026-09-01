@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.billing') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <style>
        /* Gateway Card Style */
        .gateway-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .gateway-box {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all .25s ease;
            background: #fff;
            position: relative;
        }

        /* Hover Effect */
        .gateway-card:hover:not(.disabled-gateway) .gateway-box {
            border-color: #0d6efd;
            box-shadow: 0 8px 20px rgba(13, 110, 253, .15);
            transform: translateY(-2px);
        }

        /* Selected State */
        .gateway-card input:checked+.gateway-box {
            border-color: #0d6efd;
            background: #f0f6ff;
            box-shadow: 0 10px 25px rgba(13, 110, 253, .25);
        }

        /* Check Icon */
        .gateway-card input:checked+.gateway-box::after {
            content: "✔";
            position: absolute;
            top: 8px;
            right: 10px;
            background: #0d6efd;
            color: #fff;
            font-size: 12px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        /* Disabled Gateway Style */
        .gateway-card.disabled-gateway {
            cursor: not-allowed !important;
        }

        .gateway-card.disabled-gateway .gateway-box {
            opacity: 0.4;
            filter: grayscale(1);
            background: #f8f9fa;
            border-color: #dee2e6;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.billing_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.billing_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary"><i class="fa-solid fa-list me-1"></i> {{ __('file.button.list') }}
                {{ __('file.billing') }}</a>
        @endslot
    @endcomponent

    <div class="row g-4">
        {{-- Current Plan Details --}}
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📦 {{ __('file.current_plan') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <th>{{ __('file.field.tenant') }}</th>
                                <td>{{ ucfirst(tenant()->id) }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('file.field.phone_number') }}</th>
                                <td>{{ tenant()->phone }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('file.field.status') }}</th>
                                <td>
                                    @switch(tenant()->status)
                                        @case('active')
                                            <span class="badge bg-success">{{ __('file.table.active') }}</span>
                                        @break
                                        @case('expired')
                                            <span class="badge bg-warning">{{ __('file.table.expired') }}</span>
                                        @break
                                        @default
                                            <span class="badge bg-danger">{{ __('file.table.inactive') }}</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('file.field.package') }}</th>
                                <td><strong>{{ $current_package->name }}</strong></td>
                            </tr>
                            <tr>
                                <th>{{ __('file.field.subscription_type') }}</th>
                                <td>{{ tenant()->subscription_type }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('file.field.expires_at') }}</th>
                                <td>{{ tenant()->expires_at->format($general_settings['date_format'] ?? 'd, M Y') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Change Plan Form --}}
        <div class="col-lg-5 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">🔄 {{ __('file.change_plan') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('billing.checkout') }}" id="checkout-form" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="package_id" class="form-label">{{ __('file.field.package') }}</label>
                                <select name="package_id" id="package_id" class="form-select">
                                    @foreach ($packages as $package)
                                        <option value="{{ $package->id }}"
                                            {{ $package->id == $current_package->id ? 'selected' : '' }}>
                                            {{ $package->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="subscription_type" class="form-label">{{ __('file.field.subscription_type') }}</label>
                                <select name="subscription_type" id="subscription_type" class="form-select">
                                    @foreach ($current_package->pricing as $pricing)
                                        <option value="{{ $pricing->type }}" data-price="{{ $pricing->price }}"
                                            data-days="{{ $pricing->duration_days }}"
                                            {{ $pricing->type == tenant()->subscription_type ? 'selected' : '' }}>
                                            {{ ucfirst($pricing->type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">{{ __('file.field.currency') }}</label>
                                <select name="currency" id="currency" class="form-select">
                                    @php
                                        $landlordCurrencyCodes = array_column($landlordCurrencies, 'code');
                                    @endphp
                                    @foreach ($currencyRates['rates'] as $key => $rate)
                                        @if (in_array($key, $landlordCurrencyCodes))
                                            <option value="{{ $key }}" {{ $key == 'BDT' ? 'selected' : ''}} data-rate="{{ $rate }}">
                                                {{ $key }} - ({{ $rate }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">{{ __('file.field.payment_gateway') }}</label>
                                <div class="row g-3">
                                    @foreach ($payments_gateways as $gateway)
                                        <div class="col-6 col-md-4">
                                            {{-- data-name provides the unique identifier for filtering --}}
                                            <label class="gateway-card w-100" data-name="{{ strtolower($gateway->name) }}">
                                                <input class="form-check-input d-none" type="radio" name="payment_gateway"
                                                    value="{{ $gateway->id }}">
                                                <div class="card gateway-box text-center p-3 h-100">
                                                    <img src="{{ url('storage/' . $gateway->logo) }}"
                                                        alt="{{ $gateway->display_name }}" class="img-fluid mx-auto"
                                                        style="max-height: 40px">
                                                    <small class="mt-2 d-block text-muted text-truncate">
                                                        {{ $gateway->display_name }}
                                                    </small>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2">{{ __('file.button.checkout') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Dynamic Package Info / Summary --}}
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">💰 {{ __('file.package_info') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="ps-0">{{ __('file.field.validity') }}</th>
                                <td id="validity_text" class="text-end">—</td>
                            </tr>
                            <tr>
                                <th class="ps-0">{{ __('file.field.expire_at') }}</th>
                                <td id="expiry_text" class="text-end">—</td>
                            </tr>
                            <tr>
                                <th class="ps-0">{{ __('file.field.base_price') }}</th>
                                <td id="price_display" class="text-end">0.00 BDT</td>
                            </tr>
                            <tr class="border-top">
                                <th class="ps-0 pt-3">{{ __('file.field.total_payable') }}</th>
                                <td id="converted_price_display" class="text-end pt-3 fw-bold text-primary">
                                    0.00 —
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="featureWarningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">⚠ Feature Limit Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('file.message.package_change_warning') }}</p>
                    <div id="featureWarningContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmPackageChange">Proceed Anyway</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        /** * 1. Manual Gateway Currency Map
         * keys should match your $gateway->name in lowercase
         */
        const GATEWAY_CURRENCY_SUPPORT = {
            'sslcommerz':  ['BDT', 'INR'],
            'stripe':      ['USD', 'EUR', 'GBP', 'INR', 'AUD', 'CAD'],
            'paypal':      ['USD', 'EUR', 'GBP', 'CAD'],
            'bkash':       ['BDT'],
            'nagad':       ['BDT'],
            'razorpay':    ['INR', 'AUD', 'CAD'],
        };

        const TENANT_EXPIRES_AT = "{{ tenant()->expires_at ? tenant()->expires_at->toDateString() : '' }}";
        const SYSTEM_BASE_CURRENCY = "BDT"; 

        let oldState = {};
        let confirmed = false;
        let warningModal = new bootstrap.Modal(document.getElementById('featureWarningModal'));

        /** Helpers **/
        function getBaseDate() {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (!TENANT_EXPIRES_AT) return today;
            const expiry = new Date(TENANT_EXPIRES_AT);
            expiry.setHours(0, 0, 0, 0);
            return (expiry >= today) ? expiry : today;
        }

        function saveOldState() {
            oldState = {
                packageId: $("#package_id").val(),
                type: $("#subscription_type").val(),
                price: parseFloat($("#subscription_type option:selected").data("price")) || 0,
                days: $("#subscription_type option:selected").data("days") || null
            };
        }

        /** * 2. Core Logic: Currency, Price Conversion & Gateway Filtering
         */
        function refreshBillingCalculations() {
            const selectedCurrency = $("#currency").val();
            const rate = parseFloat($("#currency option:selected").data("rate")) || 1;
            const basePrice = parseFloat($("#subscription_type option:selected").data("price")) || 0;

            // Price Calculation (Base Price * Selected Rate)
            const convertedTotal = basePrice * rate;
            
            $("#price_display").text(`${basePrice.toFixed(2)} ${SYSTEM_BASE_CURRENCY}`);
            $("#converted_price_display").text(`${convertedTotal.toFixed(2)} ${selectedCurrency}`);

            // Gateway Filtering
            $(".gateway-card").each(function() {
                const gatewayName = $(this).data('name');
                const supportedList = GATEWAY_CURRENCY_SUPPORT[gatewayName] || [];
                const input = $(this).find('input');

                if (supportedList.includes(selectedCurrency)) {
                    $(this).removeClass('disabled-gateway');
                    input.prop('disabled', false);
                } else {
                    $(this).addClass('disabled-gateway');
                    input.prop('disabled', true);
                    if (input.is(':checked')) input.prop('checked', false);
                }
            });
        }

        function updateValidityDisplay(days) {
            if (!days) {
                $("#validity_text").text("Lifetime");
                $("#expiry_text").text("Unlimited");
                return;
            }
            const baseDate = getBaseDate();
            const expiry = new Date(baseDate);
            expiry.setDate(baseDate.getDate() + parseInt(days));
            
            $("#validity_text").text(days + " days");
            $("#expiry_text").text(expiry.toLocaleDateString('en-GB', { 
                day: '2-digit', month: 'short', year: 'numeric' 
            }));
        }

        function rollbackToPreviousState() {
            $("#package_id").val(oldState.packageId);
            $("#subscription_type").val(oldState.type);
            refreshBillingCalculations();
            updateValidityDisplay(oldState.days);
        }

        /** Initialize **/
        saveOldState();
        updateValidityDisplay($("#subscription_type option:selected").data("days"));
        refreshBillingCalculations();

        /** Event Handlers **/

        // Currency Change
        $("#currency").on("change", refreshBillingCalculations);

        // Subscription Type Change
        $(document).on("change", "#subscription_type", function() {
            refreshBillingCalculations();
            updateValidityDisplay($(this).find("option:selected").data("days"));
        });

        // Click on Gateway
        $(document).on("click", ".gateway-card", function(e) {
            const input = $(this).find('input');
            if (input.prop('disabled')) {
                const currency = $("#currency").val();
                showFloatingAlert('warning', `The selected gateway does not support payments in ${currency}.`);
                e.preventDefault();
                return false;
            }
        });

        // Package Change (Ajax)
        $("#package_id").on("change", function() {
            confirmed = false;
            const newPackageId = $(this).val();
            let url = "{{ route('billing.getPackageInfo', ':id') }}".replace(':id', newPackageId);

            $.get(url, function(response) {
                let pricing = response.data || [];
                let options = '';
                pricing.forEach(p => {
                    options += `<option value="${p.type}" data-price="${p.price}" data-days="${p.duration_days}">
                        ${p.type.charAt(0).toUpperCase() + p.type.slice(1)}</option>`;
                });
                
                $("#subscription_type").html(options).trigger('change');

                if (response.warnings && response.warnings.length) {
                    let html = '<ul class="mb-0">';
                    response.warnings.forEach(w => html += `<li>${w}</li>`);
                    html += '</ul>';
                    $("#featureWarningContent").html(html);
                    warningModal.show();

                    $("#confirmPackageChange").off().on("click", function() {
                        confirmed = true;
                        saveOldState();
                        warningModal.hide();
                    });
                } else {
                    saveOldState();
                }
            });
        });

        // Modal closed without confirming
        document.getElementById('featureWarningModal').addEventListener('hidden.bs.modal', function() {
            if (!confirmed) rollbackToPreviousState();
        });

        // Form Submit Validation
        $('#checkout-form').on('submit', function(e) {
            if (!$('input[name="payment_gateway"]:checked').length) {
                e.preventDefault();
                showFloatingAlert('warning', "{{ __('file.message.please_select_payment_gateway') }}");
                return false;
            }
        });
    </script>
@endpush