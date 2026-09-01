@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.supplier_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
    <link rel="stylesheet" href="{{ url('backend/assets/libs/flatpickr/flatpickr.min.css') }}">
    <style>
        table.dataTable.nowrap th[title="Balance"] {
            text-align: end !important;
        }

        .flatpickr-wrapper {
            display: block !important;
            width: 100%;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.supplier_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.supplier_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.supplier') }}
            </a>
        @endslot
    @endcomponent

    {{-- Filter Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <button class="btn btn-outline-primary d-md-none w-100 mb-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterCollapse">
                <i class="fa-solid fa-filter me-2"></i> {{ __('file.field.show_filters') }}
            </button>

            <div class="collapse d-md-block" id="filterCollapse">
                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">{{ __('file.field.filters') }}:</span>
                            </div>

                            <div class="col-12 col-md-auto" style="min-width: 180px;">
                                <select id="filter-status" data-dt-filter="supplier-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('supplier-table')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('file.button.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Section --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-hover table-striped nowrap w-100']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="createSupplierModal" tabindex="-1" aria-labelledby="createSupplierModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createSupplierModalLabel">{{ __('file.button.create') }}
                        {{ __('file.supplier') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('suppliers.store') }}" method="POST" id="createSupplierForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.suppliers._form', [
                            'isEdit' => false,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSupplierModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.supplier') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editSupplierForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.suppliers._form', [
                            'isEdit' => true,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="fa-solid fa-user-tie me-2"></i>Supplier Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="supplierDetailsBody">
                    <div class="text-center py-5" id="detailsLoader">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('backend.accounting.partials._quick_supplier_payment_modal')
@endsection

@push('js')
    <script src="{{ url('backend/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ url('backend/assets/js/date&time_pickers.js') }}"></script>
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        $(document).ready(function() {
            handleFormSubmit('#createSupplierForm', '#createSupplierModal', '#supplier-table', false);
            handleFormSubmit('#editSupplierForm', '#editSupplierModal', '#supplier-table', true);

            let timeout = null;

            // Universal Address Lookup
            $(document).on('input', '.address-input-field', function() {
                let $input = $(this);
                let $form = $input.closest('form');
                let $resultsContainer = $form.find('.address-results-container');
                let query = $input.val();

                clearTimeout(timeout);
                if (query.length < 3) {
                    $resultsContainer.hide();
                    return;
                }

                timeout = setTimeout(function() {
                    let url = "{{ route('address.lookup') }}?q=" + encodeURIComponent(query);
                    $.getJSON(url, function(data) {
                        let items = '';
                        if (data.length > 0) {
                            $.each(data, function(key, val) {
                                items += `
                                <a href="#" class="list-group-item list-group-item-action addr-item" 
                                   data-info='${JSON.stringify(val)}'>
                                    <i class="fa-solid fa-location-dot me-2 text-primary"></i>${val.display_name}
                                </a>`;
                            });
                            $resultsContainer.show().html(items);
                        } else {
                            $resultsContainer.hide();
                        }
                    });
                }, 500);
            });

            $(document).on('click', '.addr-item', function(e) {
                e.preventDefault();
                let d = $(this).data('info');
                let $form = $(this).closest('form');

                $form.find('input[name="full_address"]').val(d.display_name);
                $form.find('input[name="latitude"]').val(d.latitude);
                $form.find('input[name="longitude"]').val(d.longitude);
                $form.find('input[name="division"]').val(d.division);
                $form.find('input[name="district"]').val(d.district);
                $form.find('input[name="upazila"]').val(d.upazila);
                $form.find('input[name="country"]').val(d.country);
                $form.find('input[name="state"]').val(d.state);
                $form.find('input[name="city"]').val(d.city || '');
                $form.find('input[name="post_code"]').val(d.post_code || '');

                $form.find('.address-results-container').hide();
                $form.find('.manual-address-fields').removeClass('d-none');
            });

            $(document).on('click', '.toggle-manual-address', function() {
                $(this).closest('form').find('.manual-address-fields').toggleClass('d-none');
            });

            $(document).on('change', '.supplier-image-input', function(e) {
                const file = e.target.files[0];
                const $form = $(this).closest('form');
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        $form.find('.supplier-image-preview').attr('src', event.target.result);
                        $form.find('.preview-container').show();
                        $form.find('.upload-placeholder').hide();
                    }
                    reader.readAsDataURL(file);
                }
            });

            $(document).on('click', '.remove-preview', function() {
                const $form = $(this).closest('form');
                $form.find('.supplier-image-input').val('');
                $form.find('.preview-container').hide();
                $form.find('.upload-placeholder').show();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest(".address-input-field, .address-results-container").length) {
                    $(".address-results-container").hide();
                }
            });

            // Initialize Flatpickr with altInput
            flatpickr('.dob', {
                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                dateFormat: "Y-m-d",
                maxDate: 'today',
                static: true
            });

            console.log(getMinDateSafe());
            flatpickr('.date-picker', {
                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                dateFormat: "Y-m-d",
                defaultDate: 'today',
                minDate: getMinDateSafe(),
                static: true
            });
        });

        // Optimized editSupplier function
        function editSupplier(id) {
            let url = "{{ route('suppliers.edit', ':supplier') }}".replace(':supplier', id);
            let updateUrl = "{{ route('suppliers.update', ':supplier') }}".replace(':supplier', id);
            let $form = $('#editSupplierForm');

            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.attr('action', updateUrl);

            $('.preview-container').hide();
            $('.upload-placeholder').show();

            $.get(url, function(response) {
                if (response.success) {
                    const supplier = response.data;

                    // 1. Populate standard inputs & handle Flatpickr instances safely
                    Object.keys(supplier).forEach(key => {
                        let $field = $form.find(
                            `input[name="${key}"]:not([type="file"]), select[name="${key}"], textarea[name="${key}"]`
                        );

                        if ($field.length) {
                            let val = supplier[key];

                            if ($field[0] && $field[0]._flatpickr) {
                                setFlatpickrSafe($field, val);
                            } else {
                                $field.val(val === 'N/A' ? '' : val);
                            }
                        }
                    });

                    // 2. Populate Address Fields
                    if (supplier.address) {
                        const address = supplier.address;
                        $form.find('input[name="full_address"]').val(address.full_address || '');
                        ['country', 'division', 'district', 'upazila', 'state', 'city', 'post_code', 'latitude', 'longitude'].forEach(field => {
                            $form.find(`input[name="${field}"]`).val(address[field] || '');
                        });

                        if (address.district || address.upazila) {
                            $('.manual-address-fields').removeClass('d-none');
                        }
                    }

                    // 3. Populate Bank Details
                    if (supplier.bank_details) {
                        const bank = supplier.bank_details;
                        $form.find('input[name="bank_details[bank_name]"]').val(bank.bank_name || '');
                        $form.find('input[name="bank_details[account_name]"]').val(bank.account_name || '');
                        $form.find('input[name="bank_details[account_number]"]').val(bank.account_number || '');
                    }

                    // 4. Handle Supplier Image Preview
                    if (response.image_url) {
                        $('.supplier-image-preview').attr('src', response.image_url);
                        $('.preview-container').show();
                        $('.upload-placeholder').hide();
                    }

                    $form.find('#supplierMoreDetails').addClass('show');

                    // 5. Populate Custom Fields
                    if (supplier.custom_field_values && supplier.custom_field_values.length > 0) {
                        supplier.custom_field_values.forEach(item => {
                            let fieldName = `custom_fields[${item.custom_field_id}]`;
                            let $input = $form.find(`[name="${fieldName}"]`);

                            if ($input.length) {
                                if ($input.is(':checkbox')) {
                                    let values = item.value ? item.value.split(',').map(v => v.trim()) : [];
                                    $form.find(`[name="${fieldName}"]`).prop('checked', false);
                                    values.forEach(v => {
                                        $form.find(`[name="${fieldName}"][value="${v}"]`).prop('checked', true);
                                    });
                                } else if ($input.is(':radio')) {
                                    $form.find(`[name="${fieldName}"][value="${item.value}"]`).prop('checked', true);
                                } else {
                                    if ($input[0] && $input[0]._flatpickr) {
                                        setFlatpickrSafe($input, item.value);
                                    } else {
                                        $input.val(item.value && item.value !== 'N/A' ? item.value : '').trigger('change');
                                    }
                                }
                            }
                        });
                    } else {
                        $form.find('input[name^="custom_fields"]').val('').prop('checked', false);
                        $form.find('select[name^="custom_fields"]').val('').trigger('change');
                    }

                    if (typeof window.initPhoneInputs === "function") {
                        window.initPhoneInputs();
                    }

                    $('#editSupplierModal').modal('show');
                } else {
                    toastr.error('Failed to fetch supplier data');
                }
            }).fail(function() {
                toastr.error('Something went wrong!');
            });
        }

        function viewSupplier(id) {
            const modal = new bootstrap.Modal(document.getElementById('viewSupplierModal'));
            const body = $('#supplierDetailsBody');
            const loader = $('#detailsLoader');

            modal.show();
            body.html(loader.html());

            let url = "{{ route('suppliers.show', ':supplier') }}".replace(':supplier', id);
            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const c = response.data;
                        const bankDetails = c.bank_details || {};
                        const address = c.address || {};
                        const imageUrl = response.image_url || '/images/preview_image.png';

                        let customFieldsHtml = '';
                        if (c.custom_field_values && c.custom_field_values.length > 0) {
                            customFieldsHtml = `
                                <div class="mt-4 pt-3 border-top text-start">
                                    <h6 class="text-muted fw-bold small text-uppercase mb-2">Additional Info</h6>
                                    <table class="table table-sm table-borderless small">
                                        ${c.custom_field_values.map(v => `
                                            <tr>
                                                <th class="text-muted p-0" width="120">
                                                    ${v.custom_field ? v.custom_field.label : 'Field'}:
                                                </th>
                                                <td class="p-0 text-dark fw-semibold">
                                                    : ${v.value || 'N/A'}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </table>
                                </div>`;
                        }

                        let html = `
                        <div class="row">
                            <div class="col-md-5 text-center border-end">
                                <img src="${imageUrl}" class="img-fluid rounded shadow-sm mb-1" style="max-height: 120px; width: 120px; object-fit: cover;">
                                <h5 class="mb-0 text-primary fw-bold">${c.name}</h5>
                                <p class="small text-muted mb-1" style="font-size: 0.8rem;">${c.company_name || ''}</p>
                                
                                <table class="table table-sm table-borderless text-start mb-0" style="line-height: 1; font-size: 0.9rem;">
                                    <tbody> 
                                        <tr><th width="80" class="py-0">Phone</th><td class="py-0">: ${c.phone}</td></tr>
                                        <tr><th class="py-0">Email</th><td class="py-0">: ${c.email || 'N/A'}</td></tr>
                                        <tr><th class="py-0">Gender</th><td class="py-0">: ${c.gender || 'N/A'}</td></tr>
                                        <tr><th class="py-0">DOB</th><td class="py-0">: ${c.date_of_birth || 'N/A'}</td></tr>
                                    </tbody>
                                </table>
                                ${customFieldsHtml}
                            </div>

                            <div class="col-md-7">
                                <h6 class="text-uppercase border-bottom pb-0 mb-1 text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Bank Details</h6>
                                <table class="table table-sm table-borderless mb-2" style="line-height: 1; font-size: 0.9rem;">
                                    <tr><th width="140" class="py-0">Bank Name</th><td class="py-0">: ${bankDetails.bank_name || 'N/A'}</td></tr>
                                    <tr><th class="py-0">A/C Name</th><td class="py-0">: ${bankDetails.account_name || 'N/A'}</td></tr>
                                    <tr><th class="py-0">A/C Number</th><td class="py-0">: ${bankDetails.account_number || 'N/A'}</td></tr>
                                </table>

                                <h6 class="text-uppercase border-bottom pb-0 mb-1 text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Financial Overview</h6>
                                <table class="table table-sm table-borderless mb-2" style="line-height: 1; font-size: 0.9rem;">
                                    <tr>
                                        <th width="140" class="py-0">Opening Balance</th>
                                        <td class="py-0">: ${c.opening_balance ? formatMoney(parseFloat(c.opening_balance).toFixed(2)) : '0.00'}</td>
                                    </tr>
                                    <tr>
                                        <th class="py-0">Current Balance</th>
                                        <td class="py-0 fw-bold ${c.current_balance < 0 ? 'text-danger' : 'text-success'}">
                                            : ${c.current_balance ? formatMoney(parseFloat(c.current_balance).toFixed(2)) : '0.00'}
                                        </td>
                                    </tr>
                                </table>

                                <h6 class="text-uppercase border-bottom pb-0 mb-1 text-primary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Primary Address</h6>
                                <table class="table table-sm table-borderless mb-0" style="line-height: 1; font-size: 0.9rem;">
                                    <tr><th width="140" class="py-0">Full Address</th><td class="py-0">: ${address.full_address || 'N/A'}</td></tr>
                                    <tr><th class="py-0">Upazila</th><td class="py-0">: ${address.upazila || 'N/A'}</td></tr>
                                    <tr><th class="py-0">District</th><td class="py-0">: ${address.district || 'N/A'}</td></tr>
                                    <tr><th class="py-0">Division</th><td class="py-0">: ${address.division || 'N/A'}</td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-uppercase border-bottom pb-1 mb-2 text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Location Map</h6>
                                <div class="rounded overflow-hidden shadow-sm" style="height: 200px; width: 100%;">
                                    <iframe 
                                        width="100%" 
                                        height="100%" 
                                        frameborder="0" 
                                        style="border:0" 
                                        src="https://www.google.com/maps?q=${encodeURIComponent(address.full_address + ' ' + address.district)}&output=embed" 
                                        allowfullscreen>
                                    </iframe>
                                </div>
                            </div>
                        </div>`;
                        body.html(html);
                    }
                },
                error: function() {
                    body.html('<div class="alert alert-danger">Failed to load data.</div>');
                }
            });
        }
    </script>
@endpush