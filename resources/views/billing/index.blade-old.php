@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.billing') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <style>
        /* Gateway Card */
        .gateway-card {
            cursor: pointer;
        }

        .gateway-box {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all .25s ease;
            background: #fff;
        }

        /* Hover Effect */
        .gateway-card:hover .gateway-box {
            border-color: #0d6efd;
            box-shadow: 0 8px 20px rgba(13, 110, 253, .15);
            transform: translateY(-2px);
        }

        /* Selected State */
        .gateway-card input:checked+.gateway-box {
            border-color: #0d6efd;
            background: #f0f6ff;
            box-shadow: 0 10px 25px rgba(13, 110, 253, .25);
            position: relative;
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
        {{-- Current Plan --}}
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

                            @foreach ($current_package->features as $item)
                                @if ($item->meta != null)
                                    @php
                                        $meta = json_decode($item->meta, true);
                                    @endphp
                                    <tr>
                                        <th>{{ ucfirst($item->feature->name) }}</th>
                                        <td>
                                            @foreach ($meta as $key => $value)
                                                <span class="badge bg-primary me-1">{{ ucfirst($key) }}:
                                                    {{ $value }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Change Plan --}}
        <div class="col-lg-5 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-success d-flex justify-content-between align-items-center">
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
                                <label for="subscription_type"
                                    class="form-label">{{ __('file.field.subscription_type') }}</label>
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
                                            <option value="{{ $key }}">
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
                                        <div class="col-6 col-md-4 col-lg-3">
                                            <label class="gateway-card w-100">
                                                <input class="form-check-input d-none" type="radio" name="payment_gateway"
                                                    id="gateway_{{ $gateway->id }}" value="{{ $gateway->id }}">
                                                <div class="card gateway-box text-center p-3 h-100">
                                                    <img src="{{ url('storage/' . $gateway->logo) }}"
                                                        alt="{{ $gateway->display_name }}" class="img-fluid mx-auto"
                                                        style="max-height: 45px">
                                                    <small class="mt-2 d-block text-muted">
                                                        {{ $gateway->display_name }}
                                                    </small>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">{{ __('file.button.checkout') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">💰 {{ __('file.package_info') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th>{{ __('file.field.validity') }}</th>
                                <td id="validity_text">—</td>
                            </tr>
                            <tr>
                                <th>{{ __('file.field.expire_at') }}</th>
                                <td id="expiry_text">—</td>
                            </tr>
                            <tr>
                                <th>{{ __('file.field.price') }}</th>
                                <td id="price_display">
                                    {{ number_format(tenant()->subscription_fee ?? 0, 2) }} BDT
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
    <!-- Warning Modal -->
    <div class="modal fade" id="featureWarningModal" tabindex="-1" aria-labelledby="featureWarningModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="featureWarningModalLabel">⚠ Feature Limit Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('file.message.package_change_warning') }}</p>
                    <div id="featureWarningContent">

                    </div>
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
        const TENANT_EXPIRES_AT = "{{ tenant()->expires_at ? tenant()->expires_at->toDateString() : '' }}";

        function getBaseDate() {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (!TENANT_EXPIRES_AT) {
                return today;
            }

            const expiry = new Date(TENANT_EXPIRES_AT);
            expiry.setHours(0, 0, 0, 0);

            // If not expired yet → count from expiry
            if (expiry >= today) {
                return expiry;
            }

            // Expired → count from today
            return today;
        }

        let oldState = {};
        let confirmed = false;
        let warningModal = new bootstrap.Modal(
            document.getElementById('featureWarningModal')
        );

        /** --------------------
         * Helpers
         * -------------------- */
        function saveOldState() {
            oldState = {
                packageId: $("#package_id").val(),
                type: $("#subscription_type").val(),
                price: parseFloat($("#subscription_type option:selected").data("price")) || 0,
                days: $("#subscription_type option:selected").data("days") || null
            };
        }

        function updateValidity(days) {
            if (!days) {
                $("#validity_text").text("Lifetime");
                $("#expiry_text").text("Unlimited");
                return;
            }

            const baseDate = getBaseDate();
            const expiry = new Date(baseDate);
            expiry.setDate(baseDate.getDate() + parseInt(days));

            $("#validity_text").text(days + " days");
            $("#expiry_text").text(
                expiry.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                })
            );
        }

        function rollbackState() {
            $("#package_id").val(oldState.packageId);
            $("#subscription_type").val(oldState.type);
            $("#price_display").text(`${oldState.price.toFixed(2)} BDT`);
            updateValidity(oldState.days);
        }

        /** --------------------
         * Init
         * -------------------- */
        saveOldState();
        updateValidity(
            $("#subscription_type option:selected").data("days")
        );

        /** --------------------
         * Subscription type change
         * -------------------- */
        $("#subscription_type").on("change", function() {
            const opt = $(this).find("option:selected");
            const price = parseFloat(opt.data("price")) || 0;
            const days = opt.data("days");

            $("#price_display").text(`${price.toFixed(2)} BDT`);
            updateValidity(days);
        });

        /** --------------------
         * Package change
         * -------------------- */
        $("#package_id").on("change", function() {
            confirmed = false;

            const newPackageId = $(this).val();
            let url = "{{ route('billing.getPackageInfo', ':id') }}";
            url = url.replace(':id', newPackageId);

            $.get(url, function(response) {

                /** TEMP pricing update */
                let pricing = response.data || [];
                let options = '';

                pricing.forEach(p => {
                    options += `
                    <option value="${p.type}"
                            data-price="${p.price}"
                            data-days="${p.duration_days}">
                        ${p.type.charAt(0).toUpperCase() + p.type.slice(1)}
                    </option>`;
                });

                $("#subscription_type").html(options);

                if (pricing.length) {
                    $("#subscription_type").val(pricing[0].type).trigger('change');
                }

                /** WARNINGS */
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

        /** --------------------
         * Modal close handling
         * -------------------- */
        document.getElementById('featureWarningModal')
            .addEventListener('hidden.bs.modal', function() {
                if (!confirmed) {
                    rollbackState();
                }
            });

        $('#checkout-form').on('submit', function(e) {
            e.preventDefault();
            if (!$('input[name="payment_gateway"]:checked').length) {
                showFloatingAlert('warning', "{{ __('file.message.please_select_payment_gateway') }}");
                return false;
            } else {
                this.submit();
            }
        });
    </script>
@endpush
