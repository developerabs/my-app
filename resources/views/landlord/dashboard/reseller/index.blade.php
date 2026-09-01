@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.reseller_management') }} - SheraziPOS Landlord
@endsection

@push('css')
    @include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.reseller_management') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.reseller_management_desc') }}</p>
        </div>
        <div>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createResellerModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.reseller') }}</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
        </div>
    </div>
@endsection

@section('modals')
    <!-- Create User Modal -->
    <div class="modal fade" id="createResellerModal" tabindex="-1" aria-labelledby="createResellerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createResellerModalLabel">{{ __('file.title.create_reseller') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('landlord.dashboard.reseller._form', ['isEdit' => false])
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editResellerModal" tabindex="-1" aria-labelledby="editResellerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="editResellerModalLabel">{{ __('file.title.edit_reseller') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('landlord.dashboard.reseller._form', ['isEdit' => true])
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('landlord.layouts.partials._datatable_bottom')
    <script>
        $(document).on('draw.dt', '#reseller-table', function() {
            // Master checkbox click
            $(document).off('click', '.select-all').on('click', '.select-all', function() {
                $('.row-checkbox').prop('checked', this.checked);
            });
            // Row checkbox click
            $(document).off('click', '.row-checkbox').on('click', '.row-checkbox', function() {
                if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                    $('.select-all').prop('checked', true);
                } else {
                    $('.select-all').prop('checked', false);
                }
            });
        });

        // Password Match Validation
        $('#createResellerForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let rawForm = form[0];

            // Password match check
            let password = form.find('[name="password"]').val();
            let confirmPassword = form.find('[name="password_confirmation"]').val();
            $('.password-error').remove();
            if (password !== confirmPassword) {
                form.find('[name="password_confirmation"]').after(
                    '<small class="text-danger password-error">{{ __('file.message.password_mismatch') }}</small>'
                    );
                return;
            }

            let formData = new FormData(rawForm);
            let url = '{{ route("landlord.resellers.store") }}';
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.creating') }}...");
                },
                success: function(res) {
                    $('#createResellerModal').modal('hide');
                    form.trigger('reset');
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }} {{ __('file.reseller') }}");
                    showFloatingAlert('success', res.message ||
                        "{{ __('file.message.reseller_created_successfully') }}");
                    $('#reseller-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }} {{ __('file.reseller') }}");
                    $('.text-danger').remove();
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            form.find('[name="' + key + '"]').after(
                                '<small class="text-danger">' + value[0] + '</small>');
                        });
                    }
                    showFloatingAlert('error', "{{ __('file.message.please_fix_errors') }}");
                }
            });
        });

        // Remove password error on typing
        $('#password, #password_confirmation').on('keyup', function() {
            $('.password-error').remove();
        });

        // Edit Reseller - Populate modal
        function editReseller(id) {
            let url = '{{ route('landlord.resellers.edit', ':id') }}'.replace(':id', id);
            $.get(url, function(response) {
                let r = response.reseller;
                $('#edit_id').val(r.id);
                $('#edit_name').val(r.name);
                $('#edit_name').attr('value', r.name); // Inspect cosmetic
                $('#edit_email').val(r.email);
                $('#edit_email').attr('value', r.email);
                $('#edit_phone').val(r.phone);
                $('#edit_phone').attr('value', r.phone);
                $('#edit_company_name').val(r.company_name);
                $('#edit_company_name').attr('value', r.company_name);
                $('#edit_address').val(r.address);
                $('#edit_address').attr('value', r.address);
                $('#edit_commission_per_registration').val(r.commission_per_registration);
                $('#edit_commission_per_subscription').val(r.commission_per_subscription);
                $('#edit_existingLogoPreview').attr('src', r.company_logo);
                $('#editResellerModal').modal('show');
            }).fail(function() {
                showFloatingAlert('error', "{{ __('file.message.unable_to_fetch_reseller_data') }}");
            });
        }

        // Edit Reseller AJAX submit
        $('#editResellerForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let rawForm = form[0];
            let resellerId = $('#edit_id').val();
            let url = '{{ route('landlord.resellers.update', ':id') }}'.replace(':id', resellerId);
            let formData = new FormData(rawForm);

            $.ajax({
                url: url,
                type: 'POST', // PATCH handled by @method('PATCH') in form
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.updating') }}...");
                },
                success: function(res) {
                    $('#editResellerModal').modal('hide');
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.update') }} {{ __('file.reseller') }}");
                    showFloatingAlert('success', res.message ||
                        "{{ __('file.message.reseller_updated_successfully') }}");
                    $('#reseller-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.update') }} {{ __('file.reseller') }}");
                    $('.text-danger').remove();
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            form.find('[name="' + key + '"]').after(
                                '<small class="text-danger">' + value[0] + '</small>');
                        });
                    }
                    showFloatingAlert('error', "{{ __('file.message.please_fix_errors') }}");
                }
            });
        });


        function deleteReseller(id) {
            $('#deleteConfirmModal').modal('show');
            let deleteButton = $('#deleteConfirm');

            deleteButton.off('click').on('click', function() {
                let url = '{{ route('landlord.resellers.destroy', ['reseller' => ':id']) }}';
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
                        showFloatingAlert('success', '{{ __('file.message.reseller_deleted_successfully') }}');
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#reseller-table').DataTable().ajax.reload();
                    },
                    error: function() {
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('error', "{{ __('file.message.unable_to_delete_reseller') }}");
                    }
                });
            });
        }
    </script>
@endpush
