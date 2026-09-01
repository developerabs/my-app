@extends('landlord.layouts.main')

@section('title'){{__('file.title.user_management')}} - SheraziPOS Landlord @endsection

@push('css')
@include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{__('file.title.user_management')}}</h4>
            <p class="mb-0 text-muted">{{__('file.title.user_management_desc')}}</p>
        </div>
        <div>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="fa-solid fa-plus me-1"></i> {{__('file.button.create')}} {{__('file.user')}}</a>
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
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="margin-top: 80px;">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">{{ __('file.title.create_user') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('landlord.users.store') }}" method="POST" id="createUserForm">
                @csrf
                @include('landlord.dashboard.users._form', ['isEdit' => false])
                <button type="submit" class="btn btn-primary">{{ __('file.button.create') }} {{ __('file.user') }}</button>
                </form>
            </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">{{ __('file.title.edit_user') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="editUserForm">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="id" id="edit_id">
                    @include('landlord.dashboard.users._form', ['isEdit' => true])
                    <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.update') }} {{ __('file.user') }}</button>
                </form>
            </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('landlord.layouts.partials._datatable_bottom')
    <script>
        $('#user_role_menu').addClass('active open');
        $('#user_role_menu .main_menu').addClass('active');
        $('#user_role_menu .menu_list').css('display', 'block');
        $('#user_role_menu .menu_list #users a').addClass('active');

        $(document).on('draw.dt', '#users-table', function () {
            // Master checkbox click
            $(document).off('click', '.select-all').on('click', '.select-all', function () {
                $('.row-checkbox').prop('checked', this.checked);
            });
            // Row checkbox click
            $(document).off('click', '.row-checkbox').on('click', '.row-checkbox', function () {
                if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                    $('.select-all').prop('checked', true);
                } else {
                    $('.select-all').prop('checked', false);
                }
            });
        });

            // Password Match Validation
        $('#createUserModal #createUserForm').on('submit', function(e) {
            e.preventDefault(); // prevent default form submission



            let password = $('#password').val();
            let confirmPassword = $('#password_confirmation').val();

            // Clear previous error
            $('.password-error').remove();

            if(password !== confirmPassword) {
                // Show error message
                $('#password_confirmation').after('<small class="text-danger password-error">{{ __("file.message.password_mismatch") }}</small>');
                return false;
            }

            // If matched, submit via AJAX
            let form = $(this);
            $.ajax({
                url: form.attr('action'), // form action URL
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text("{{ __('file.button.creating') }}...");
                },
                success: function(response) {
                    // handle success: close modal, reset form
                    $('#createUserModal').modal('hide');
                    form.trigger('reset');
                    form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.create') }} {{ __('file.user') }}");

                    // show custom floating alert
                    showFloatingAlert('success', response.message || "{{ __('file.message.user_created_successfully') }}");

                    // refresh DataTable
                    $('#users-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.create') }} {{ __('file.user') }}");

                    // validation errors
                    $('.text-danger').remove(); // remove old validation messages
                    if(xhr.responseJSON && xhr.responseJSON.errors){
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value){
                            let input = $('[name="'+key+'"]');
                            input.after('<small class="text-danger">'+value[0]+'</small>');
                        });
                        showFloatingAlert('error', "{{ __('file.message.please_fix_errors') }}");
                    } else {
                        showFloatingAlert('error', "{{ __('file.message.something_went_wrong.') }}");
                    }
                }
            });
        });

        // Remove password error on typing
        $('#password, #password_confirmation').on('keyup', function() {
            $('.password-error').remove();
        });

        function editUser(id) {
            let url = '{{ route("landlord.users.edit", ["user" => ":id"]) }}';
            url = url.replace(':id', id);
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    $('#edit_id').val(response.user.id);
                    $('#edit_name').val(response.user.name);
                    $('#edit_username').val(response.user.username);
                    $('#edit_email').val(response.user.email);
                    $('#edit_phone_number').val(response.user.phone);
                    $('#edit_role option').each(function() {
                        if ($(this).val() === response.user.role) {
                            $(this).prop('selected', true);
                        }
                    });
                    $('#editUserModal').modal('show');
                },
                error: function() {
                    showFloatingAlert('error', "{{ __('file.message.unable_to_fetch_user_data') }}");
                }
            });
        }

        $('#editUserModal form').on('submit', function(e) {
            e.preventDefault();
            console.log('here');
            let form = $(this);
            let userId = $('#edit_id').val();
            let url = '{{ route("landlord.users.update", ["user" => ":id"]) }}';
            url = url.replace(':id', userId);
            $.ajax({
                url: url,
                type: 'PATCH',
                data: form.serialize(),
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text("{{ __('file.button.updating') }}...");
                },
                success: function(response) {
                    $('#editUserModal').modal('hide');
                    form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.update') }} {{ __('file.user') }}");

                    showFloatingAlert('success', response.message || "{{ __('file.message.user_updated_successfully') }}");

                    $('#users-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.update') }} {{ __('file.user') }}");

                    $('.text-danger').remove(); // remove old validation messages
                    if(xhr.responseJSON && xhr.responseJSON.errors){
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value){
                            let input = $('[name="'+key+'"]');
                            input.after('<small class="text-danger">'+value[0]+'</small>');
                        });
                        showFloatingAlert('error', "{{ __('file.message.please_fix_errors') }}");
                    } else {
                        showFloatingAlert('error', "{{ __('file.message.something_went_wrong.') }}");
                    }
                }
            });
        });

        $('.delete_btn').on('click', function() {
           // console.log('here');
            $('#deleteConfirmModal').modal('show');
        });

        function deleteUser(id) {
            $('#deleteConfirmModal').modal('show');
            let deleteButton = $('#deleteConfirm');

            deleteButton.off('click').on('click', function() {
                let url = '{{ route("landlord.users.destroy", ["user" => ":id"]) }}';
                url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    beforeSend: function() {
                        deleteButton.prop('disabled', true).text("{{ __('file.button.deleting') }}...");
                    },
                    success: function(response) {
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('success', response.message || 'User deleted successfully!');
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#users-table').DataTable().ajax.reload();
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
