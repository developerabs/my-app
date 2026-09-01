@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.payment_gateway') }} - SheraziPOS Landlord
@endsection

@push('css')
    @include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.payment_gateway') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.payment_gateway_desc') }}</p>
        </div>
        <div>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPaymentGatewayModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.title.payment_gateway') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
        </div>
    </div>
@endsection

@section('modals')
    <!-- Create Modal -->
    <div class="modal fade" id="createPaymentGatewayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('file.title.create_payment_gateway') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createPaymentGatewayForm" action="{{ route('landlord.payment-gateway.store') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.name') }}</label>
                                <select class="form-select" name="name" id="payment_name" required>
                                    <option value="bkash">bkash</option>
                                    <option value="nagad">nagad</option>
                                    <option value="sslcommerz">sslcommerz</option>
                                    <option value="stripe">stripe</option>
                                    <option value="paypal">paypal</option>
                                    <option value="razorpay">razorpay</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.display_name') }}</label>
                                <input type="text" class="form-control" name="display_name" required>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="5%"><i class="fa-solid fa-plus"></i></th>
                                        <th width="30%">{{ __('file.table.parameter') }}</th>
                                        <th>{{ __('file.table.value') }}</th>
                                        <th class="text-center" width="10%"><i class="fa-solid fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="payment_payment_gateway_table_body"></tbody>
                            </table>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.logo') }}</label>
                                <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg"
                                    onchange="previewImage(this,'logo_preview')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">{{ __('file.field.logo_preview') }}</label>
                                <img id="logo_preview" src="{{ asset('images/preview_image.png') }}" class="img-thumbnail"
                                    style="max-height:50px;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('file.button.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editPaymentGatewayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('file.title.edit_payment_gateway') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPaymentGatewayForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="id" id="edit_id">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.name') }}</label>
                                <select class="form-select" name="name" id="edit_name" required>
                                    <option value="bkash">bkash</option>
                                    <option value="nagad">nagad</option>
                                    <option value="sslcommerz">sslcommerz</option>
                                    <option value="stripe">stripe</option>
                                    <option value="paypal">paypal</option>
                                    <option value="razorpay">razorpay</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.display_name') }}</label>
                                <input type="text" class="form-control" name="display_name" id="edit_display_name"
                                    required>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center" width="5%"><i class="fa-solid fa-plus"></i></th>
                                        <th width="30%">{{ __('file.table.parameter') }}</th>
                                        <th>{{ __('file.table.value') }}</th>
                                        <th class="text-center" width="10%"><i class="fa-solid fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="edit_payment_gateway_table_body"></tbody>
                            </table>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('file.field.logo') }}</label>
                                <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg"
                                    onchange="previewImage(this,'edit_logo_preview')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">{{ __('file.field.logo_preview') }}</label>
                                <img id="edit_logo_preview" src="{{ asset('images/preview_image.png') }}"
                                    class="img-thumbnail" style="max-height:50px;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('file.button.update') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('landlord.layouts.partials._datatable_bottom')
    <script>
        $(document).ready(function() {

            const gatewayParameters = {
                "bkash": ["base_url", "username", "password", "app_key", "app_secret", ""],
                "nagad": ["base_url", "MERCHANT_NUMBER", "API_TOKEN"],
                "sslcommerz": ["base_url", "STORE_ID", "STORE_PASSWORD"],
                "stripe": ["PUBLISHABLE_KEY", "SECRET_KEY", "WEBHOOK_SECRET"],
                "paypal": ["base_url", "CLIENT_ID", "CLIENT_SECRET"],
                "razorpay": ["base_url", "KEY_ID", "KEY_SECRET"]
            };

            function populateTable(selectId, tbodyId) {
                const select = $(selectId);
                const tbody = $(tbodyId);

                function renderRows(params, values = {}) {
                    tbody.empty();
                    params.forEach((param, index) => {
                        const value = values[param] || '';
                        tbody.append(`
                            <tr class="payment-gateway-row">
                                <td class="text-center">${index === params.length-1 ? `<button type="button" class="btn btn-sm btn-success add-row-btn"><i class="fa-solid fa-plus"></i></button>` : ''}</td>
                                <td><input type="text" class="form-control form-control-sm" name="parameters[]" value="${param}" readonly required></td>
                                <td><input type="text" class="form-control form-control-sm" name="values[]" value="${value}" required></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row-btn" ${params.length===1?'disabled':''}><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        `);
                    });
                }

                select.on('change', function() {
                    const params = gatewayParameters[$(this).val()] || [''];
                    renderRows(params);
                });

                tbody.on('click', '.add-row-btn', function() {
                    tbody.append(`
                        <tr class="payment-gateway-row">
                            <td class="text-center"><button type="button" class="btn btn-sm btn-success add-row-btn"><i class="fa-solid fa-plus"></i></button></td>
                            <td><input type="text" class="form-control form-control-sm" name="parameters[]" required></td>
                            <td><input type="text" class="form-control form-control-sm" name="values[]" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row-btn"><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                    `);
                    updateButtons(tbody);
                });

                tbody.on('click', '.remove-row-btn', function() {
                    if (tbody.find('tr').length > 1) {
                        $(this).closest('tr').remove();
                        updateButtons(tbody);
                    }
                });

                function updateButtons(tableBody) {
                    const rows = tableBody.find('tr');
                    rows.find('.remove-row-btn').prop('disabled', rows.length <= 1);
                    rows.find('.remove-row-btn').show();
                    rows.last().find('.remove-row-btn').hide();
                    rows.find('.add-row-btn').remove();
                    rows.last().find('td:first').html(
                        `<button type="button" class="btn btn-sm btn-success add-row-btn"><i class="fa-solid fa-plus"></i></button>`
                    );
                }

                select.trigger('change');
            }

            populateTable('#payment_name', '#payment_payment_gateway_table_body');
        });

        function bindEditTableRows() {
            const tbody = $('#edit_payment_gateway_table_body');

            // Add row
            tbody.off('click', '.add-row-btn').on('click', '.add-row-btn', function() {
                tbody.append(`
            <tr class="payment-gateway-row">
                <td class="text-center"><button type="button" class="btn btn-sm btn-success add-row-btn"><i class="fa-solid fa-plus"></i></button></td>
                <td><input type="text" class="form-control form-control-sm" name="parameters[]" required></td>
                <td><input type="text" class="form-control form-control-sm" name="values[]" required></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row-btn"><i class="fa-solid fa-xmark"></i></button></td>
            </tr>
        `);
                updateEditButtons();
            });

            // Remove row
            tbody.off('click', '.remove-row-btn').on('click', '.remove-row-btn', function() {
                if (tbody.find('tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateEditButtons();
                }
            });

            function updateEditButtons() {
                const rows = tbody.find('tr');
                rows.find('.remove-row-btn').prop('disabled', rows.length <= 1);
                rows.find('.remove-row-btn').show();
                rows.last().find('.remove-row-btn').hide();

                // Remove all add buttons, only last row gets it
                rows.find('.add-row-btn').remove();
                rows.last().find('td:first').html(
                    `<button type="button" class="btn btn-sm btn-success add-row-btn"><i class="fa-solid fa-plus"></i></button>`
                );
            }

            updateEditButtons();
        }

        $('#createPaymentGatewayForm').on('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);

            $.ajax({
                url: $(form).attr('action'),
                method: $(form).attr('method'),
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $(form).find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.saving') }}...");
                },
                success: function(response) {
                    $('#createPaymentGatewayModal').modal('hide');
                    $(form).trigger('reset');
                    // Reset the logo preview after reset
                    $('#logo_preview').attr('src', "{{ asset('images/preview_image.png') }}");
                    // Re-initialize the dynamic table to remove added rows on success
                    $(form).find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.save') }}");
                    // show custom floating alert
                    showFloatingAlert('success', response.message ||
                        "{{ __('file.message.payment_gateway_created_successfully') }}");
                    $('#gateway-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    $(form).find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.save') }}");
                    // validation errors
                    $('.text-danger').remove(); // remove old validation messages
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            // Basic handling for nested array keys like 'parameters.0' etc. 
                            // For simplicity here, focusing on top-level fields.
                            let input = $('[name="' + key + '"]');
                            if (input.length === 0) {
                                // Handle nested/array errors by finding the nearest common ancestor or using the error key itself.
                                let generalErrorDiv = $('<small class="text-danger"></small>')
                                    .text(value[0]);
                                // For parameters/values, you might need a more complex mapping, 
                                // but for this fix, we assume other errors are simple fields.
                                if (key.startsWith('parameters.') || key.startsWith(
                                        'values.')) {
                                    // For dynamic table errors, just show a general message or skip if complex
                                    // In a real app, the server should return structured errors for dynamic rows.
                                } else {
                                    $(`[name="${key}"]`).after(generalErrorDiv);
                                }
                            } else {
                                input.after('<small class="text-danger">' + value[0] +
                                    '</small>');
                            }
                        });
                        showFloatingAlert('error', "{{ __('file.message.please_fix_errors') }}");
                    } else {
                        showFloatingAlert('error', "{{ __('file.message.something_went_wrong.') }}");
                    }
                }
            });
        });

        $('#editPaymentGatewayForm').on('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);

            $.ajax({
                url: "{{ route('landlord.payment-gateway.update') }}",
                method: $(form).attr('method'),
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $(form).find('button[type="submit"]')
                        .prop('disabled', true)
                        .html(
                            '<i class="fas fa-spinner fa-spin"></i> {{ __('file.button.updating') }}...'
                            );
                    $('.text-danger').remove(); // পুরনো error message ক্লিয়ার করো
                },
                success: function(response) {
                    // Reset and hide modal
                    $('#editPaymentGatewayModal').modal('hide');
                    $(form).trigger('reset');
                    $('#edit_logo_preview').attr('src', "{{ asset('images/preview_image.png') }}");
                    // Button reset
                    $(form).find('button[type="submit"]')
                        .prop('disabled', false)
                        .html('{{ __('file.button.update') }}');
                    // ✅ Floating success alert
                    showFloatingAlert('success', response.message ||
                        "{{ __('file.message.payment_gateway_updated_successfully') }}");
                    $('#gateway-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    $(form).find('button[type="submit"]')
                        .prop('disabled', false)
                        .html('{{ __('file.button.update') }}');

                    $('.text-danger').remove();

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;

                        // Loop through all validation errors
                        $.each(errors, function(key, messages) {
                            let fieldName = key.replace(/\.\d+/g, '[]'); // handle array keys

                            // Try to find input by name
                            let input = $('[name="' + fieldName + '"]', form);

                            if (input.length) {
                                input.after('<small class="text-danger d-block mt-1">' +
                                    messages[0] + '</small>');
                            } else {
                                // যদি ফিল্ড না পাওয়া যায়, তাহলে একটা সাধারণ error দেখাও
                                $(form).prepend(
                                    '<div class="alert alert-danger py-1 px-2 mb-2">' +
                                    messages[0] + '</div>');
                            }
                        });

                        showFloatingAlert('error', "{{ __('file.message.please_fix_errors') }}");
                    } else {
                        showFloatingAlert('error', "{{ __('file.message.something_went_wrong') }}");
                    }
                }
            });
        });

        function editGateway(id) {
            let url = '{{ route('landlord.payment-gateway.edit', ['gateway' => ':id']) }}';
            url = url.replace(':id', id);

            $.get(url, function(response) {
                if (response.status) {
                    const gateway = response.data;
                    // const imageSrc = gateway.logo ? `{{ asset('storage') }}/${gateway.logo}` :
                    //     `{{ asset('images/preview_image.png') }}`;
                    const imageSrc = response.image_url || `{{ asset('images/preview_image.png') }}`;
                    $('#edit_logo_preview').attr('src', imageSrc);
                    $('#edit_id').val(gateway.id);
                    const selectedOption = $('#edit_name').find(`option[value="${gateway.name}"]`);
                    $('#edit_name').find('option').not(selectedOption).remove();
                    selectedOption.prop('selected', true);
                    $('#edit_display_name').val(gateway.display_name);

                    const credentials = gateway.credentials;
                    const params = Object.keys(credentials);
                    const values = credentials;

                    const tbody = $('#edit_payment_gateway_table_body');
                    tbody.empty();
                    params.forEach((param, index) => {
                        const value = values[param] || '';
                        tbody.append(`
                            <tr class="payment-gateway-row">
                                <td class="text-center">${index === params.length-1 ? `<button type="button" class="btn btn-sm btn-success add-row-btn"><i class="fa-solid fa-plus"></i></button>` : ''}</td>
                                <td><input type="text" class="form-control form-control-sm" name="parameters[]" value="${param}" readonly required></td>
                                <td><input type="text" class="form-control form-control-sm" name="values[]" value="${value}" required></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row-btn" ${params.length===1?'disabled':''}><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        `);
                    });
                    bindEditTableRows();
                    $('#editPaymentGatewayModal').modal('show');
                } else {
                    alert('Failed to fetch gateway data');
                }
            });

        }

        // Image preview
        function previewImage(input, id) {
            const file = input.files[0];
            if (file) {
                document.getElementById(id).src = URL.createObjectURL(file);
            }
        }

        function deleteGateway(id) {
            $('#deleteConfirmModal').modal('show');
            let deleteButton = $('#deleteConfirm');

            deleteButton.off('click').on('click', function() {
                let url = '{{ route('landlord.payment-gateway.destroy', ['gateway' => ':id']) }}';
                url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    beforeSend: function() {
                        deleteButton.prop('disabled', true).text(
                            "{{ __('file.button.deleting') }}...");
                    },
                    success: function(response) {
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('success', response.message ||
                            'Gateway deleted successfully!');
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#gateway-table').DataTable().ajax.reload();
                    },
                    error: function() {
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('error', 'Unable to delete user. Please try again later.');
                    }
                });
            });
        }
    </script>
@endpush
