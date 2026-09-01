<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="light" data-toggled="close">

<head>
    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> @yield('title', 'SheraziPOS') </title>
    <meta name="Description" content="Bootstrap Responsive Admin Web Dashboard HTML5 Template">
    <meta name="Author" content="Spruko Technologies Private Limited">
    <meta name="keywords"
        content="admin dashboard template,admin panel html,bootstrap dashboard,admin dashboard,html template,template dashboard html,html css,bootstrap 5 admin template,bootstrap admin template,bootstrap 5 dashboard,admin panel html template,dashboard template bootstrap,admin dashboard html template,bootstrap admin panel,simple html template,admin dashboard bootstrap">

    <!-- Favicon -->
    <link rel="icon"
        href="{{ isset($general_settings['favicon']) && $general_settings['favicon'] ? $general_settings['favicon'] : url('backend/assets/images/brand-logos/favicon.ico') }}"
        type="image/x-icon">

    <!-- Choices JS -->
    <script src="{{ url('backend') }}/assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Main Theme Js -->
    <script src="{{ url('backend') }}/assets/js/main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="{{ url('backend') }}/assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Style Css -->
    <link href="{{ url('backend') }}/assets/css/styles.min.css" rel="stylesheet">

    <!-- Icons Css -->
    <link href="{{ url('backend') }}/assets/css/icons.css" rel="stylesheet">

    <!-- Node Waves Css -->
    <link href="{{ url('backend') }}/assets/libs/node-waves/waves.min.css" rel="stylesheet">

    <!-- Simplebar Css -->
    <link href="{{ url('backend') }}/assets/libs/simplebar/simplebar.min.css" rel="stylesheet">

    <!-- Color Picker Css -->
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/@simonwep/pickr/themes/nano.min.css">
    <link rel="stylesheet" href="{{ url('backend') }}/assets/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css" />

    <!-- Choices Css -->
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/choices.js/public/assets/styles/choices.min.css">

    @stack('css')

    <style>
        .copy-trigger {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .copy-trigger:hover {
            opacity: 0.8;
        }

        .copy-success {
            pointer-events: none;
            /* Disable click during feedback */
        }

        .iti {
            width: 100%;
            display: block;
        }

        .flatpickr-wrapper {
            display: block !important;
            width: 100%;
        }
    </style>

</head>

<body>

    @include('backend.layouts.partials.switchtheme')

    <div class="page">
        @include('backend.layouts.partials.topbar')
        <!-- Start::app-sidebar -->
        <aside class="app-sidebar sticky" id="sidebar">

            @include('backend.layouts.partials.sidebar')

        </aside>
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
        <!-- End::app-content -->

        @include('backend.layouts.partials.footer')

    </div>

    @yield('modals')
    <div class="modal fade" id="restoreConfirmModal" tabindex="-1" aria-labelledby="restoreConfirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreConfirmModalLabel">Restore Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="restoreMessage">Are you sure you want to restore this item?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="restoreConfirm">Restore</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Confirm Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Are you sure you want to delete this?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteConfirm">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="quickProductModal" tabindex="-1" aria-labelledby="quickProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content ">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickProductModalLabel">Quick Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @if ($unit_groups->count() === 0 || $unit_groups->sum('units_count') === 0)
                    <div class="alert shadow-sm d-flex align-items-center" role="alert"
                        style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-left: 4px solid #721c24; color: #721c24; padding: 10px 15px;">
                        <i class="fas fa-folder-plus me-2"></i>
                        <div class="flex-grow-1">
                            <strong>{{ __('file.warning') }}:</strong>
                            {!! __('file.message.unit_group_required_warning') !!}
                            <a href="{{ route('unit-groups.index') }}" class="fw-bold ms-1 text-decoration-underline"
                                style="color: #721c24;">
                                {{ __('file.message.create_unit_group') }}
                            </a>
                        </div>
                    </div>
                @else
                    <form id="quickProductForm">
                        <div class="modal-body">
                            <div class="row mb-2">
                                <div class="col-md-8">
                                    <label class="fw-bold" for="product_name">{{ __('file.field.name') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value=""
                                        required placeholder="{{ __('file.placeholder.product_name_hint') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="fw-bold" for="sku">{{ __('file.field.sku') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="sku" class="form-control" value=""
                                        required placeholder="SKU-TST001">
                                </div>
                            </div>
                            <div class="row mb-2" id="price_section">
                                <div class="col-md-3 col-12">
                                    <label class="fw-bold" for="profit_margin">
                                        {{ __('file.field.profit_margin') }} (%)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" name="profit_margin" id="profit_margin"
                                        class="form-control" value="" step="0.01" min="0">
                                </div>

                                <div class="col-md-3 col-12">
                                    <label class="fw-bold" for="product_cost">{{ __('file.field.cost') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="cost" id="product_cost" class="form-control"
                                        value="" step="0.01" required>
                                </div>

                                <div class="col-md-3 col-12">
                                    <label class="fw-bold" for="product_price">{{ __('file.field.price') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="price" id="product_price" class="form-control"
                                        value="" step="0.01" required>
                                </div>

                                <div class="col-md-3 col-12">
                                    <label class="fw-bold"
                                        for="wholesale_price">{{ __('file.field.wholesale_price') }}</label>
                                    <input type="number" name="wholesale_price" id="wholesale_price"
                                        class="form-control" value="" step="0.01">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <label class="fw-bold" for="unit_group">{{ __('file.field.unit_group') }}
                                        <span class="text-danger">*</span></label>
                                    <select name="unit_group_id" id="unit_group" class="form-control selectpicker"
                                        required data-live-search="true">
                                        <option value="">{{ __('file.option.select_unit_group') }}
                                        </option>
                                        @isset($unit_groups)
                                            @foreach ($unit_groups as $unitGroup)
                                                <option value="{{ $unitGroup->id }}">
                                                    {{ $unitGroup->name }}</option>
                                            @endforeach
                                        @endisset
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="fw-bold" for="base_unit_id">{{ __('file.field.base_unit') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="base_unit_id" id="base_unit_id" class="form-control" required>
                                        <option value="">{{ __('file.option.select_base_unit') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="fw-bold"
                                        for="purchase_unit_id">{{ __('file.field.purchase_unit') }} </label>
                                    <select name="purchase_unit_id" id="purchase_unit_id" class="form-control">
                                        <option value="">{{ __('file.option.select_purchase_unit') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="fw-bold" for="sale_unit_id">{{ __('file.field.sale_unit') }}
                                    </label>
                                    <select name="sale_unit_id" id="sale_unit_id" class="form-control">
                                        <option value="">{{ __('file.option.select_sale_unit') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <div id="unit_variables_container"></div>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="has_variants"
                                            id="has_variants" value="1">
                                        <label class="form-check-label fw-bold"
                                            for="has_variants">{{ __('file.field.has_variants') }}
                                            <span class="tooltip-wrapper" data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="{{ __('file.field.has_variants_tooltip') }}">
                                                <i class="fa-solid fa-info-circle"></i>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="has_expire_date"
                                            id="expire_date" value="1">
                                        <label class="form-check-label fw-bold"
                                            for="expire_date">{{ __('file.field.has_expire_date') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="has_imei"
                                            id="has_imei" value="1">
                                        <label class="form-check-label fw-bold"
                                            for="has_imei">{{ __('file.field.has_imei_or_serial') }}
                                            <span class="tooltip-wrapper" data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="{{ __('file.field.has_imei_tooltip') }}">
                                                <i class="fa-solid fa-info-circle"></i>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3" id="attribute_section" style="display:none;">
                                <div class="col-md-12" id="attribute-wrapper">

                                    <div class="row g-2 attribute-row mb-2">
                                        <div class="col-3">
                                            <label class="fw-bold" for="attribute_name_0">Attribute</label>
                                            <select name="attributes[0][name]"
                                                class="form-control form-control-sm attr-name-select"
                                                data-placeholder="Select Attribute">
                                                <option></option>
                                                @foreach ($allAttributes as $attr)
                                                    <option value="{{ $attr->name }}"
                                                        data-values="{{ json_encode($attr->values->pluck('value')) }}">
                                                        {{ $attr->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <label class="fw-bold" for="attribute_values_0">Values</label>
                                            <select name="attributes[0][values][]"
                                                class="form-control form-control-sm attr-values-select" multiple
                                                data-placeholder="Values"></select>
                                        </div>
                                        <div class="col-1 text-end align-content-end">
                                            <button type="button" class="btn btn-danger-light btn remove-attr-btn">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-primary btn-sm shadow-sm"
                                        style="width: auto;" id="add-attribute-btn">
                                        <i class="ri-add-circle-line align-middle"></i> Add Attribute
                                    </button>
                                </div>
                            </div>
                            <div id="variant-section" class="mt-3 d-none">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0 table-variant">
                                        <thead>
                                            <tr>
                                                <th style="width: 30%;" class="ps-3">Variant Details</th>
                                                <th style="width: 60%;">Pricing Settings</th>
                                                <th style="width: 10%;" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="variant-matrix-body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Product</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="forceSyncModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="fa-solid fa-database me-2"></i> {{ __('Reset & Re-Sync POS Database') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="fa-solid fa-triangle-exclamation text-warning" style="font-size: 42px;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">{{ __('Are you sure you want to reset local cache?') }}</h6>
                    <p class="text-muted small mb-3">
                        {{ __('This action will wipe all offline cached products, variants, barcodes, taxes, and stock lines stored in IndexedDB and perform a fresh full download from the server.') }}
                    </p>

                    <!-- Live Sync Progress Status Box -->
                    <div class="alert alert-light border py-2 px-3 mb-0" id="pos_sync_status_box"
                        style="display: none;">
                        <span id="pos_sync_progress_text" class="fw-bold text-warning small">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Ready to sync...
                        </span>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary"
                        data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" id="btn_confirm_force_sync" class="btn btn-sm btn-danger px-4">
                        <i class="fa-solid fa-rotate me-1"></i> {{ __('Confirm & Re-Sync') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('backend.layouts.partials.alerts')
    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="las la-angle-double-up"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    <!-- Scroll To Top -->
    <script>
        const baseUrl = "{{ url('/') }}";
        window.urls = {
            getBaseUnits: "{{ route('units.getBaseUnitsByGroup', ':id') }}",
            getSubUnits: "{{ route('units.getSubUnits', ':id') }}"
        };
        window.allAttributesData = @json($allAttributes);
    </script>
    <!-- Popper JS -->
    <script src="{{ url('backend') }}/assets/js/jquery-3.7.1.min.js"></script>
    <script src="{{ url('backend/assets/plugins/jquery-ui/jquery-ui.min.js') }}"></script>

    <script src="{{ url('backend') }}/assets/libs/@popperjs/core/umd/popper.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="{{ url('backend') }}/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Defaultmenu JS -->
    <script src="{{ url('backend') }}/assets/js/defaultmenu.min.js"></script>

    <!-- Node Waves JS-->
    <script src="{{ url('backend') }}/assets/libs/node-waves/waves.min.js"></script>

    <!-- Sticky JS -->
    <script src="{{ url('backend') }}/assets/js/sticky.js"></script>

    <!-- Simplebar JS -->
    <script src="{{ url('backend') }}/assets/libs/simplebar/simplebar.min.js"></script>
    <script src="{{ url('backend') }}/assets/js/simplebar.js"></script>

    <script src="{{ url('backend') }}/js/axios.min.js"></script>
    <script src="{{ url('backend') }}/js/dexie.js"></script>

    <!-- Color Picker JS -->
    <script src="{{ url('backend') }}/assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>
    <script src="{{ url('backend') }}/assets/js/select2.min.js"></script>
    <script src="{{ url('backend/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ url('backend/assets/js/date&time_pickers.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"></script>


    <script src="{{ url('backend') }}/js/product-sync.js"></script>
    <script src="{{ url('backend') }}/js/compound-unit-calculator.js"></script>
    <script src="{{ url('backend') }}/js/unit-manager.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // ১. ডাটাবেজ ওপেন করো
            await window.db.open();

            // ২. চেক করো ডাটাবেজে কোনো ডাটা আছে কি না
            const variantCount = await window.db.variants.count();

            // ৩. ডাটাবেজ খালি থাকলে শুধুমাত্র তখনই সিঙ্ক কল হবে
            if (variantCount === 0) {
                console.log("Database is empty. Initiating sync...");
                await window.globalSyncProducts('sync-status-display');
            } else {
                console.log("Database contains data. Sync skipped.");
            }

            $('#btn_open_force_sync_modal').off('click').on('click', function() {
                $('#pos_sync_status_box').hide();
                $('#btn_confirm_force_sync').prop('disabled', false).html(
                    '<i class="fa-solid fa-rotate me-1"></i> Confirm & Re-Sync');
                $('#forceSyncModal').modal('show');
            });

            // English Comment: Handle Confirmation & Force Reset Sync Flow
            $('#btn_confirm_force_sync').off('click').on('click', async function() {
                const progressElementId = 'pos_sync_progress_text';
                const $confirmBtn = $(this);
                const $modal = $('#forceSyncModal');

                try {
                    // Disable button and show loading state
                    $confirmBtn.prop('disabled', true).html(
                        '<i class="fa-solid fa-spinner fa-spin me-1"></i> Wiping Local DB...');
                    $('#pos_sync_status_box').slideDown(150);

                    // Step 1: Clear local IndexedDB tables & remove sync timestamp
                    if (typeof clearLocalPosDatabaseCache === 'function') {
                        await clearLocalPosDatabaseCache();
                    }

                    // Step 2: Trigger full sync orchestrator with progress callback
                    updateSyncUiProgress(progressElementId,
                        "Starting fresh full sync from server...");

                    if (typeof window.globalSyncProducts === 'function') {
                        await window.globalSyncProducts(progressElementId);
                    } else if (typeof syncProducts === 'function') {
                        await syncProducts(progressElementId);
                    }

                    // Step 3: Handle completion feedback
                    setTimeout(function() {
                        $modal.modal('hide');
                        $confirmBtn.prop('disabled', false).html(
                            '<i class="fa-solid fa-rotate me-1"></i> Confirm & Re-Sync');

                        if (typeof showFloatingAlert === 'function') {
                            showFloatingAlert('success',
                                'POS Database reset and re-synced successfully!');
                        }
                    }, 1000);

                } catch (error) {
                    console.error("Force Re-Sync Error:", error);
                    updateSyncUiProgress(progressElementId, `Re-Sync Failed: ${error.message}`,
                        false);
                    $confirmBtn.prop('disabled', false).html(
                        '<i class="fa-solid fa-rotate me-1"></i> Confirm & Re-Sync');

                    if (typeof showFloatingAlert === 'function') {
                        showFloatingAlert('error', 'Re-sync failed: ' + error.message);
                    }
                }
            });
        });
        /**
         * Global Phone Input Handler (Ajax & Static Support)
         * English: Initialize phone inputs with class 'phone-input'
         */
        window.initPhoneInputs = function(selector = ".phone-input") {
            const phoneInputs = document.querySelectorAll(selector);

            phoneInputs.forEach(function(input) {
                // English: Avoid double initialization
                if (input.dataset.itiInitialized) {
                    if (input.iti && input.value) {
                        input.iti.setNumber(input.value);
                    }
                    return;
                }

                // Find or Create Error Message Container
                let errorMsg = input.closest('div').querySelector(".phone-error");
                if (!errorMsg) {
                    errorMsg = document.createElement("span");
                    errorMsg.className = "phone-error text-danger small d-block mt-1";
                    input.parentNode.appendChild(errorMsg);
                }

                const form = input.closest("form");
                const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

                // Initialize intlTelInput
                const iti = window.intlTelInput(input, {
                    initialCountry: "auto",
                    separateDialCode: true,
                    countrySearch: true,
                    geoIpLookup: function(success, failure) {
                        fetch("https://ipapi.co/json")
                            .then(res => res.json())
                            .then(data => success(data.country_code))
                            .catch(() => success("bd"));
                    },
                    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"
                });

                // Store instance on element and mark as initialized
                input.iti = iti;
                input.dataset.itiInitialized = "true";

                // If field has value (from DB or Ajax), format it correctly
                if (input.value.trim()) {
                    iti.setNumber(input.value);
                }

                // 💡 Smart Conditional Validation Function
                const validate = () => {
                    const val = input.value.trim();
                    const isRequired = input.hasAttribute('required') || input.required;

                    // ১. খালি ঘরের ক্ষেত্রে (Empty Field Logic)
                    if (val === "") {
                        if (isRequired) {
                            errorMsg.textContent = "Phone number is required";
                            input.classList.add("is-invalid");
                            input.classList.remove("is-valid");
                            if (submitBtn) submitBtn.disabled = true;
                            return false;
                        } else {
                            // রিকোয়ার্ড না হলে খালি ঘরে কোনো এরর দেখাবে না এবং সাবমিট করতে দিবে
                            errorMsg.textContent = "";
                            input.classList.remove("is-invalid", "is-valid");
                            if (submitBtn) submitBtn.disabled = false;
                            return true;
                        }
                    }

                    // ২. ভ্যালু থাকলে ভ্যালিডেশন চেক (Filled Field Logic)
                    const isNumeric = /^\d+$/.test(val.replace(/[\s\-\(\)]/g, ''));
                    const isValid = iti.isValidNumber() && isNumeric;

                    if (isValid) {
                        errorMsg.textContent = "";
                        input.classList.remove("is-invalid");
                        input.classList.add("is-valid");
                        if (submitBtn) submitBtn.disabled = false;
                        return true;
                    } else {
                        errorMsg.textContent = "Invalid phone number";
                        input.classList.add("is-invalid");
                        input.classList.remove("is-valid");
                        // Only disable submit button for required fields
                        if (isRequired && submitBtn) {
                            submitBtn.disabled = true;
                        }
                        return isRequired ? false : true;
                    }
                };

                // Restrict input to digits only
                input.addEventListener('keypress', function(e) {
                    if (!/[0-9]/.test(e.key)) e.preventDefault();
                });

                // Event Listeners for Validation
                input.addEventListener("keyup", validate);
                input.addEventListener("change", validate);
                input.addEventListener("blur", validate);
                input.addEventListener("countrychange", validate); // ফ্ল্যাগ বদলালে ভ্যালিডেশন চেক

                // Form Submit Logic
                if (form) {
                    form.addEventListener("submit", function(e) {
                        if (!validate()) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            return false;
                        }
                        // Overwrite with full formatted number if filled
                        if (input.value.trim()) {
                            input.value = iti.getNumber();
                        }
                    });
                }
            });
        };

        // English: Auto-run on page load
        document.addEventListener("DOMContentLoaded", function() {
            window.initPhoneInputs();
        });
    </script>

    <script src="{{ url('js/passwordToggle.js') }}"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>




    <script>
        window.translations = {
            delete: "{{ __('file.button.delete') }}",
            deleting: "{{ __('file.button.deleting') }}",
            create: "{{ __('file.button.create') }}",
            creating: "{{ __('file.button.creating') }}",
            update: "{{ __('file.button.update') }}",
            updating: "{{ __('file.button.updating') }}",
        };
        window.trans = @json(__('file'));

        window.appSettings = {
            currency_symbol: "{{ $default_currency['symbol'] ?? '$' }}",
            currency_code: "{{ $default_currency['code'] ?? 'USD' }}",
            currency_display_type: "{{ $general_settings['currency_display_type'] ?? 'symbol' }}",
            currency_position: "{{ $general_settings['currency_position'] ?? 'left' }}",
            decimal_digits: {{ $general_settings['decimal_digits'] ?? 2 }},
            thousand_separator: "{{ $general_settings['thousand_separator'] ?? '' }}",
            date_format: "{{ $general_settings['date_format'] ?? 'd/m/Y' }}",
            time_format: "{{ $general_settings['time_format'] ?? '24' }}",
            timezone: "{{ $general_settings['timezone'] ?? 'UTC' }}",
            fy_start_date: "{{ $currentFiscalYear ? formatDate($currentFiscalYear->start_date) : '2000-01-01' }}",
        };

        function setFlatpickrSafe($field, val) {
            if ($field.length && $field[0] && $field[0]._flatpickr) {
                if (val && val !== 'N/A' && val !== 'null' && val !== 'undefined') {
                    try {
                        // Extract raw YYYY-MM-DD part
                        let rawDateStr = (typeof val === 'string') ? val.split('T')[0] : val;

                        // Convert to JS Native Date Object to avoid string format parsing conflicts
                        let dateObj = new Date(rawDateStr + 'T00:00:00');

                        if (!isNaN(dateObj.getTime())) {
                            $field[0]._flatpickr.setDate(dateObj, true);
                        } else {
                            $field[0]._flatpickr.clear();
                        }
                    } catch (e) {
                        $field[0]._flatpickr.clear();
                    }
                } else {
                    $field[0]._flatpickr.clear();
                }
            }
        }

        function parseAnyDateToJsDate(dateStr) {
            if (!dateStr || dateStr === 'N/A' || dateStr === 'null' || dateStr === 'undefined') {
                return null;
            }

            dateStr = String(dateStr).trim();

            // 1. Format: YYYY-MM-DD or YYYY/MM/DD (ISO Format: 2026-07-01)
            if (/^\d{4}[-\/. ]\d{1,2}[-\/. ]\d{1,2}/.test(dateStr)) {
                let parts = dateStr.split(/[-\/. T]/);
                let year = parseInt(parts[0], 10);
                let month = parseInt(parts[1], 10) - 1; // JS Months are 0-indexed (0 = Jan, 6 = Jul)
                let day = parseInt(parts[2], 10);
                return new Date(year, month, day);
            }

            // 2. Format: DD/MM/YYYY or DD-MM-YYYY or DD.MM.YYYY (European Format: 01/07/2026)
            if (/^\d{1,2}[-\/. ]\d{1,2}[-\/. ]\d{4}/.test(dateStr)) {
                let parts = dateStr.split(/[-\/. ]/);
                let day = parseInt(parts[0], 10);
                let month = parseInt(parts[1], 10) - 1; // JS Months are 0-indexed
                let year = parseInt(parts[2], 10);
                return new Date(year, month, day);
            }

            // 3. Textual Format (e.g. "01 July, 2026" or "July 01, 2026")
            let parsedTimestamp = Date.parse(dateStr);
            if (!isNaN(parsedTimestamp)) {
                return new Date(parsedTimestamp);
            }

            return null;
        }

        // Global Safe Helper to parse minDate for Flatpickr
        function getMinDateSafe() {
            if (window.appSettings && window.appSettings.fy_start_date) {
                return parseAnyDateToJsDate(window.appSettings.fy_start_date);
            }
            return null;
        }

        /**
         * Universal Multi-Currency Money Formatter for JavaScript.
         * Supports: formatMoney(amount, currencyObj), formatMoney(amount, 'USD'), formatMoney(amount, '$'), or formatMoney(amount)
         *
         * @param {number|string} amount - The amount to format
         * @param {object|string|null} currency - Optional Currency Object {symbol: '$', code: 'USD'}, Code string 'USD', Symbol string '$', or null
         * @param {number|null} customDecimals - Optional override for decimal places
         * @returns {string} Formatted currency string
         */
        function formatMoney(amount, currency = null, customDecimals = null) {
            const settings = window.appSettings || {};
            const decimals = (customDecimals !== null && !isNaN(customDecimals)) ? customDecimals : (settings
                .decimal_digits ?? 2);

            let num = Number(amount);
            if (isNaN(num)) num = 0.00;

            let formatted = num.toFixed(decimals);
            let separator = (settings.thousand_separator === 'space') ? ' ' : (settings.thousand_separator ?? '');

            // 🟢 Resolve Currency Symbol and Code dynamically
            let symbol = settings.currency_symbol || '$';
            let code = settings.currency_code || 'USD';

            if (currency) {
                if (typeof currency === 'object' && currency !== null) {
                    symbol = currency.symbol || currency.code || symbol;
                    code = currency.code || symbol;
                } else if (typeof currency === 'string' && currency.trim() !== '') {
                    currency = currency.trim();
                    if (currency.length === 3 && /^[A-Za-z]+$/.test(currency)) {
                        code = currency.toUpperCase();
                        symbol = code;
                    } else {
                        symbol = currency;
                        code = currency;
                    }
                }
            }

            let displayUnit = (settings.currency_display_type === 'code') ? code : symbol;

            if (separator !== "") {
                let parts = formatted.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, separator);
                formatted = parts.join('.');
            }

            if (settings.currency_position === 'left') {
                return displayUnit + ' ' + formatted;
            } else {
                return formatted + ' ' + displayUnit;
            }
        }

        function formatedDate(date, showTime = false) {
            if (!date) return 'N/A';

            const settings = window.appSettings || {};

            let format = settings.date_format || 'd/m/Y';

            if (showTime) {
                format += (settings.time_format === '12') ?
                    ' g:i:s A' :
                    ' H:i:s';
            }

            const d = new Date(date);

            if (isNaN(d.getTime())) {
                return 'N/A';
            }

            const pad = (n) => String(n).padStart(2, '0');

            const monthsFull = [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];

            const monthsShort = [
                "Jan", "Feb", "Mar", "Apr", "May", "Jun",
                "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
            ];

            const weekdaysFull = [
                "Sunday", "Monday", "Tuesday", "Wednesday",
                "Thursday", "Friday", "Saturday"
            ];

            const weekdaysShort = [
                "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"
            ];

            const hours24 = d.getHours();
            const hours12 = hours24 % 12 || 12;

            const replacements = {
                d: pad(d.getDate()),
                j: d.getDate(),

                m: pad(d.getMonth() + 1),
                n: d.getMonth() + 1,

                F: monthsFull[d.getMonth()],
                M: monthsShort[d.getMonth()],

                Y: d.getFullYear(),
                y: String(d.getFullYear()).slice(-2),

                l: weekdaysFull[d.getDay()],
                D: weekdaysShort[d.getDay()],

                H: pad(hours24),
                G: hours24,

                h: pad(hours12),
                g: hours12,

                i: pad(d.getMinutes()),
                s: pad(d.getSeconds()),

                A: hours24 >= 12 ? 'PM' : 'AM',
                a: hours24 >= 12 ? 'pm' : 'am',
            };

            let output = '';

            for (let i = 0; i < format.length; i++) {

                const char = format[i];

                // Escape character (\)
                if (char === '\\') {
                    i++;
                    if (i < format.length) {
                        output += format[i];
                    }
                    continue;
                }

                output += replacements.hasOwnProperty(char) ?
                    replacements[char] :
                    char;
            }

            return output;
        }

        $(document).on('wheel', 'input[type="number"]', function(e) {
            if ($(this).is(':focus')) {
                $(this).blur();
            }
        });
    </script>

    @stack('js')

    <!-- Custom-Switcher JS -->
    <script src="{{ url('backend') }}/assets/js/custom-switcher.min.js"></script>
    <!-- Custom JS -->
    <script src="{{ url('backend') }}/assets/js/custom.js"></script>
    <script src="{{ url('backend') }}/assets/js/customalerts.js"></script>
    <script src="{{ url('backend') }}/assets/js/form-submit.js"></script>
    <script src="{{ url('js/sw-register.js') }}"></script>
    <script src="{{ url('js/network-status.js') }}"></script>

    <script>
        function imageHandler() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = function() {
                const file = input.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('image', file);

                    $.ajax({
                        url: "{{ route('upload.quill.image') }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            if (data.url) {
                                const range = quill.getSelection();
                                quill.insertEmbed(range.index, 'image', data.url);
                            } else {
                                alert('Upload failed');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Upload error:', error);
                            alert('Image upload failed!');
                        }
                    });
                }
            };
        }

        $(document).on('click', '.copy-trigger', function() {
            const $this = $(this);
            const textToCopy = $this.data('copy') || $this.text()
                .trim(); // Priority: data-copy attribute, then element text
            const originalHtml = $this.html();

            if (!textToCopy) return;

            navigator.clipboard.writeText(textToCopy).then(() => {
                // Visual feedback
                $this.addClass('copy-success').html(`
                    <span class="text-success small anim-fade-in" style="font-size: 11px;">
                        <i class="fa-solid fa-check-circle"></i> Copied!
                    </span>
                `);

                // Reset after 1.5s
                setTimeout(() => {
                    $this.removeClass('copy-success').html(originalHtml);
                }, 1500);
            }).catch(err => {
                console.error('Copy failed:', err);
            });
        });



        function initCustomFieldsDatePicker() {
            /* English Comment: 
            Initializes Flatpickr for dynamically rendered custom fields.
            Using 'altInput: true' for user-friendly UI display while submitting ISO Y-m-d to backend.
            Prevents double initialization and works seamlessly inside Bootstrap modals.
            */
            if (typeof flatpickr !== 'undefined') {
                $('.custom-datepicker').each(function() {
                    if (!this._flatpickr) {
                        flatpickr(this, {
                            altInput: true,
                            altFormat: (window.appSettings && window.appSettings.date_format) ? window
                                .appSettings.date_format : "Y-m-d",
                            dateFormat: "Y-m-d",
                            static: true,
                            allowInput: true,
                        });
                    }
                });
            }
        }

        // ডকুমেন্ট রেডি হলে কল করুন
        $(document).ready(function() {
            initCustomFieldsDatePicker();
        });

        $('.select-picker').select2();
    </script>

</body>

</html>
