@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.sherazipos_store') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <style>
        .clickable-card:hover {
            cursor: pointer;
            box-shadow: 0 0px 8px rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        @media (min-width: 576px) {
            .modal-sm-custom {
                max-width: 400px;
            }
        }

        /* মোবাইল টাচ অপ্টিমাইজেশন */
        .gateway-card {
            cursor: pointer;
        }

        .gateway-box {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            background: #fff;
            transition: all 0.2s;
        }

        /* চেকড হলে হাইলাইট */
        .gateway-card input:checked+.gateway-box {
            border-color: #0d6efd;
            background-color: #f0f7ff;
            box-shadow: 0 0 0 1px #0d6efd;
        }

        /* ডিজেবল গেটওয়ে গ্রাফিক্স */
        .gateway-card.disabled {
            opacity: 0.3;
            pointer-events: none;
            filter: grayscale(1);
        }

        .form-select-sm {
            font-size: 13px;
            padding: 0.4rem 0.5rem;
        }

        /* হোভার ইফেক্ট শুধু ডেস্কটপে */
        @media (min-width: 992px) {
            .gateway-box:hover {
                border-color: #0d6efd;
                transform: translateY(-2px);
            }
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.sherazipos_store') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.sherazipos_store_desc') }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            @php $firstActiveTab = null; @endphp
            <ul class="nav nav-tabs mb-3" id="storeTab" role="tablist">
                @php $firstActiveTab = $firstActiveTab ?? '#addons'; @endphp
                <li class="nav-item"><button class="nav-link {{ $firstActiveTab == '#addons' ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#addons">{{ __('file.addons') }}</button></li>
                @php $firstActiveTab = $firstActiveTab ?? '#modules'; @endphp
                <li class="nav-item"><button class="nav-link {{ $firstActiveTab == '#modules' ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#modules">{{ __('file.modules') }}</button></li>
            </ul>

            <div class="tab-content" id="storeTabContent">
                <div class="tab-pane fade {{ $firstActiveTab == '#addons' ? 'show active' : '' }}" id="addons">
                    <div class="row">
                        @foreach ($alladdons as $addon)
                            @include('backend.store_purchase.addon_card', ['addon' => $addon])
                        @endforeach
                    </div>
                </div>
                <div class="tab-pane fade {{ $firstActiveTab == '#modules' ? 'show active' : '' }}" id="modules">
                    <div class="row g-3">
                        @foreach ($modules as $module)
                            @include('backend.store_purchase.module_card', ['item' => $module])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="moduleDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div id="moduleDetailsContent">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light w-100 fw-bold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="makePaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm-custom">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">

                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold"><i class="fas fa-shield-alt text-primary me-2"></i>Secure Checkout</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="font-size: 10px;"></button>
                </div>

                <div class="modal-body pt-2 pb-4 px-3">
                    <form action="{{ route('store-purchase.make-payment') }}" method="POST" id="makePaymentForm">
                        @csrf
                        <input type="hidden" name="item_id" id="payment_item_id">
                        <input type="hidden" name="item_type" id="payment_item_type">
                        <input type="hidden" name="is_renewal" id="payment_is_renewal" value="0">
                        <input type="hidden" name="tenant" value="{{ tenant('id') }}">

                        <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light rounded-3 border">
                            <div>
                                <small class="text-muted d-block" style="font-size: 11px;">Item Name</small>
                                <span class="fw-bold text-dark" id="payment_item_name" style="font-size: 14px;">---</span>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block" style="font-size: 11px;">Total Payable</small>
                                <span class="fw-bold text-primary" id="payment_item_base_price_value"
                                    style="font-size: 14px;">0.00 BDT</span>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Frequency</label>
                                <select name="payment_frequency" id="payment_frequency"
                                    class="form-select form-select-sm shadow-none border-secondary-subtle"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Currency</label>
                                <select name="payment_currency" id="payment_currency"
                                    class="form-select form-select-sm shadow-none border-secondary-subtle">
                                    @foreach ($currencyRates['rates'] as $key => $rate)
                                        @if (in_array($key, array_column($landlordCurrencies, 'code')))
                                            <option value="{{ $key }}" {{ $key == 'BDT' ? 'selected' : '' }}
                                                data-rate="{{ $rate }}">{{ $key }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-2">Select Gateway</label>
                            <div class="row gx-2 gy-2">
                                @foreach ($paymentGateways as $gateway)
                                    <div class="col-4"> <label class="gateway-card w-100"
                                            data-name="{{ strtolower($gateway->name) }}">
                                            <input class="form-check-input d-none" type="radio" name="payment_gateway"
                                                value="{{ $gateway->id }}">
                                            <div class="gateway-box card h-100 border text-center p-2 transition-all">
                                                <img src="{{ $gateway->logo_url_path }}"
                                                    alt="{{ $gateway->display_name }}" class="img-fluid mx-auto mb-1"
                                                    style="max-height: 25px; object-fit: contain;">
                                                <span class="d-block text-truncate"
                                                    style="font-size: 9px; font-weight: 600;">{{ $gateway->display_name }}</span>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm"
                            style="font-size: 14px;" id="submit_payment_btn">
                            Pay <span id="button_payable_amount">0.00 BDT</span> <i class="fas fa-lock ms-1"
                                style="font-size: 12px;"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        $(document).ready(function() {
            // Persist last opened tab
            const activeTab = localStorage.getItem('activeStoreTab');
            if (activeTab) new bootstrap.Tab(document.querySelector(`[data-bs-target="${activeTab}"]`)).show();

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                localStorage.setItem('activeStoreTab', $(e.target).data('bs-target'));
            });


        });



        function viewDetials(id) {
            let url = "{{ route('store-purchase.get-module-details', ':id') }}".replace(':id', id);

            $.get(url, function(response) {
                if (response.status) {
                    let module = response.data;

                    // হেডার তথ্য সাজানো
                    let html = `
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="${module.icon} fa-3x text-primary p-3 bg-light rounded-circle"></i>
                                </div>
                                <h4 class="fw-bold">${module.name}</h4>
                                <p class="text-muted small">${module.description}</p>
                                <div class="d-flex justify-content-center gap-3">
                                    <span class="badge bg-soft-primary text-primary px-3 py-2">Monthly: ৳${module.meta.pricing.monthly}</span>
                                    <span class="badge bg-soft-success text-success px-3 py-2">Yearly: ৳${module.meta.pricing.yearly}</span>
                                </div>
                            </div>
                            
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Included Features:</h6>
                            <div class="row">
                        `;

                    // ফিচারগুলো লুপ চালিয়ে অ্যাড করা
                    module.features.forEach(feature => {
                        html += `
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="${feature.icon} text-info mt-1" style="font-size: 24px; width: 40px;"></i>
                                    <div>
                                        <div class="fw-bold" style="line-height: 1.2;">${feature.name}</div>
                                        <div class="text-muted" style="font-size: 12px;">${feature.key.replace('_', ' ')}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div>`; // Row closing

                    // মডাল কন্টেন্ট আপডেট এবং ওপেন করা
                    $('#moduleDetailsContent').html(html);
                    $('#moduleDetailsModal').modal('show');
                }
            });
        }
        const GATEWAY_CURRENCY_SUPPORT = {
            'sslcommerz': ['BDT', 'INR'],
            'stripe': ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'AUD', 'CAD'],
            'paypal': ['USD', 'EUR', 'GBP', 'CAD'],
            'bkash': ['BDT'],
            'nagad': ['BDT'],
            'razorpay': ['INR', 'AUD', 'CAD'],
        };

        $(document).on("click", ".gateway-card", function(e) {
            const input = $(this).find('input');
            if (input.prop('disabled')) {
                const currency = $("#currency").val();
                showFloatingAlert('warning', `The selected gateway does not support payments in ${currency}.`);
                e.preventDefault();
                return false;
            }
        });

        function makePayment(id, type, isRenew = false) {
            $('#makePaymentForm')[0].reset();
            if (type == 'module') {
                let url = "{{ route('store-purchase.get-module-details', ':id') }}".replace(':id', id);
                $.get(url, function(response) {
                    let module = response.data;
                    $('#payment_item_id').val(id);
                    $('#payment_item_type').val(type);
                    $('#payment_item_name').text(module.name);
                    $('#payment_is_renewal').val(isRenew ? '1' : '0');

                    let paymentFrequencies = '';
                    Object.keys(module.meta.pricing).forEach(key => {
                        let price = module.meta.pricing[key];
                        let label = key.replace('_', ' ').toLowerCase();
                        label = label.charAt(0).toUpperCase() + label.slice(1);

                        paymentFrequencies +=
                            `<option value="${key}" data-price="${price}">${label} - (${price})</option>`;
                    });
                    $('#payment_frequency').html(paymentFrequencies);
                    updateConvertedPrice();
                });
            }
            $('#makePaymentModal').modal('show');
        }

        // Price Conversion Logic
        function updateConvertedPrice() {
            // ১. সিলেক্ট করা ফ্রিকোয়েন্সি থেকে বেজ প্রাইস নিন
            let selectedFrequency = $('#payment_frequency option:selected');
            let basePrice = parseFloat(selectedFrequency.data('price')) || 0;

            // ২. সিলেক্ট করা কারেন্সি থেকে রেট নিন
            let selectedCurrency = $('#payment_currency option:selected');
            let rate = parseFloat(selectedCurrency.data('rate')) || 1;
            let currencyCode = selectedCurrency.val();

            // ৩. ফাইনাল ক্যালকুলেশন
            let convertedPrice = (basePrice * rate).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // ৪. উপরের মেইন ডিসপ্লে আপডেট (BDT এবং Converted উভয়ই দেখানো হচ্ছে সচ্ছতার জন্য)
            if (currencyCode !== 'BDT') {
                $('#payment_item_base_price_value').html(
                    `<span style="font-size:12px; color:#6c757d;">(${basePrice} BDT)</span> ${convertedPrice} ${currencyCode}`
                    );
            } else {
                $('#payment_item_base_price_value').text(`${basePrice} BDT`);
            }

            // ৫. বাটনের টেক্সট আপডেট
            $('#button_payable_amount').text(`${convertedPrice} ${currencyCode}`);
        }

        function filterGateways() {
            const selectedCurrency = $('#payment_currency').val();

            $('.gateway-card').each(function() {
                const gatewayName = $(this).data('name');
                const supportedCurrencies = GATEWAY_CURRENCY_SUPPORT[gatewayName] || [];
                const input = $(this).find('input');

                if (supportedCurrencies.includes(selectedCurrency)) {
                    $(this).removeClass('disabled');
                    input.prop('disabled', false);
                } else {
                    $(this).addClass('disabled');
                    input.prop('disabled', true);
                    input.prop('checked', false);
                }
            });
        }

        // Event listener: whenever frequency or currency changes
        $(document).on('change', '#payment_frequency, #payment_currency', function() {
            updateConvertedPrice();
            filterGateways();
        });

        $('#makePaymentForm').on('submit', function(e) {
            if (!$('input[name="payment_gateway"]:checked').length) {
                e.preventDefault();
                showFloatingAlert('warning', "{{ __('file.message.please_select_payment_gateway') }}");
                return false;
            }
        });
    </script>
@endpush
