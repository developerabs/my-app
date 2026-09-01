@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.customer_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
    <link rel="stylesheet" href="{{ url('backend/assets/libs/flatpickr/flatpickr.min.css') }}">
    <style>
        table.dataTable.nowrap th[title="Balance"] {
            text-align: end !important;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.customer_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.customer_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.customer') }}
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
                                <select id="filter-status" data-dt-filter="customer-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto" style="min-width: 180px;">
                                <select id="filter-group" data-dt-filter="customer-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_groups') }}</option>
                                    @forelse ($customer_groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @empty
                                        <option value="" disabled>{{ __('file.option.no_groups') }}</option>
                                    @endforelse
                                </select>
                            </div>

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('customer-table')">
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
    <div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCustomerModalLabel">{{ __('file.button.create') }}
                        {{ __('file.customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('customers.store') }}" method="POST" id="createCustomerForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.customers._form', [
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

    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editCustomerForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.customers._form', [
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

    <div class="modal fade" id="viewCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="fa-solid fa-user-tie me-2"></i>Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="customerDetailsBody">
                    <div class="text-center py-5" id="detailsLoader">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        $(document).ready(function() {
            handleFormSubmit('#createCustomerForm', '#createCustomerModal', '#customer-table', false);
            handleFormSubmit('#editCustomerForm', '#editCustomerModal', '#customer-table', true);

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

            $(document).on('change', '.customer-image-input', function(e) {
                const file = e.target.files[0];
                const $form = $(this).closest('form');
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        $form.find('.customer-image-preview').attr('src', event.target.result);
                        $form.find('.preview-container').show();
                        $form.find('.upload-placeholder').hide();
                    }
                    reader.readAsDataURL(file);
                }
            });

            $(document).on('click', '.remove-preview', function() {
                const $form = $(this).closest('form');
                $form.find('.customer-image-input').val('');
                $form.find('.preview-container').hide();
                $form.find('.upload-placeholder').show();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest(".address-input-field, .address-results-container").length) {
                    $(".address-results-container").hide();
                }
            });

            // Initialize Flatpickr with altInput for UI display & Y-m-d form submission
            flatpickr('.dob', {
                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                dateFormat: "Y-m-d",
                maxDate: 'today',
                static: true
            });

            flatpickr('.date-picker', {
                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                dateFormat: "Y-m-d",
                defaultDate: 'today',
                minDate: getMinDateSafe(),
                static: true
            });
        });

        // Optimized editCustomer function
        function editCustomer(id) {
            let url = "{{ route('customers.edit', ':customer') }}".replace(':customer', id);
            let updateUrl = "{{ route('customers.update', ':customer') }}".replace(':customer', id);
            let $form = $('#editCustomerForm');

            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.attr('action', updateUrl);

            $.get(url, function(response) {
                if (response.success) {
                    let customer = response.data;
                    let details = customer.details || {};
                    let address = customer.primary_address || customer.primaryAddress || (customer.addresses ? customer.addresses[0] : {});

                    // 1. Populate Primary Info safely (Raw DB dates passed to setFlatpickrSafe)
                    Object.keys(customer).forEach(key => {
                        let $field = $form.find(`input[name="${key}"]:not([type="file"]), select[name="${key}"], textarea[name="${key}"]`);

                        if ($field.length) {
                            let val = customer[key];

                            if ($field[0] && $field[0]._flatpickr) {
                                setFlatpickrSafe($field, val);
                            } else {
                                $field.val(val === 'N/A' ? '' : val);
                            }
                        }
                    });

                    // 2. Populate Details & Date of Birth
                    $form.find('input[name="company_name"]').val(details.company_name && details.company_name !== 'N/A' ? details.company_name : '');
                    $form.find('input[name="tax_number"]').val(details.tax_number && details.tax_number !== 'N/A' ? details.tax_number : '');
                    $form.find('select[name="gender"]').val(details.gender || 'male').trigger('change');
                    $form.find('textarea[name="description"]').val(details.description && details.description !== 'N/A' ? details.description : '');

                    // Handle DOB Flatpickr Instance safely
                    let $dobField = $form.find('input[name="date_of_birth"]');
                    setFlatpickrSafe($dobField, details.date_of_birth);

                    // 3. Map Address Fields
                    const addrFields = ['full_address', 'division', 'district', 'upazila', 'state', 'city', 'post_code', 'country', 'latitude', 'longitude'];
                    addrFields.forEach(f => {
                        let addrVal = address[f];
                        $form.find(`input[name="${f}"]`).val(addrVal && addrVal !== 'N/A' ? addrVal : (f === 'country' ? 'Bangladesh' : ''));
                    });

                    if (address.full_address || address.district) {
                        $form.find('.manual-address-fields').removeClass('d-none');
                    }

                    // 4. Image logic
                    if (response.image_url && !response.image_url.includes('default')) {
                        $form.find('.customer-image-preview').attr('src', response.image_url);
                        $form.find('.preview-container').show();
                        $form.find('.upload-placeholder').hide();
                    } else {
                        $form.find('.preview-container').hide();
                        $form.find('.upload-placeholder').show();
                    }

                    $form.find('#moreDetailsEdit').addClass('show');

                    // 5. Handle Custom Fields Data Populating Safely
                    if (customer.custom_field_values && customer.custom_field_values.length > 0) {
                        customer.custom_field_values.forEach(item => {
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

                    $('#editCustomerModal').modal('show');
                }
            });
        }

        function viewCustomer(id) {
            const modal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
            const body = $('#customerDetailsBody');
            const loader = $('#detailsLoader');

            modal.show();
            body.html(loader.html());

            let url = "{{ route('customers.show', ':customer') }}".replace(':customer', id);
            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const c = response.data;
                        const details = c.details || {};
                        const address = (c.addresses && c.addresses.length > 0) ? c.addresses[0] : {};
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
                            <div class="col-md-4 text-center border-end">
                                <img src="${imageUrl}" class="img-fluid rounded shadow-sm mb-3" style="max-height: 150px;">
                                <h4 class="mb-1">${c.name}</h4>
                                <span class="badge bg-info">${c.customer_group ? c.customer_group.name : 'No Group'}</span>

                                ${customFieldsHtml}
                            </div>
                            <div class="col-md-8">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="150">Phone</th><td>: ${c.phone}</td></tr>
                                    <tr><th>Email</th><td>: ${c.email || 'N/A'}</td></tr>
                                    <tr><th>Company</th><td>: ${details.company_name || 'N/A'}</td></tr>
                                    <tr><th>Gender</th><td>: ${details.gender || 'N/A'}</td></tr>
                                    <tr><th>Balance</th><td class="fw-bold">: ${c.current_balance}</td></tr>
                                    <tr><th class="border-top mt-2 pt-2 text-primary text-uppercase" colspan="2">Primary Address</th></tr>
                                    <tr><th>Full Address</th><td>: ${address.full_address || 'N/A'}</td></tr>
                                    <tr><th>Upazila</th><td>: ${address.upazila || 'N/A'}</td></tr>
                                    <tr><th>District</th><td>: ${address.district || 'N/A'}</td></tr>
                                    <tr><th>Division</th><td>: ${address.division || 'N/A'}</td></tr>
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