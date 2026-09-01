@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.user_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.user_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.user_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.user') }}</a>
        @endslot
    @endcomponent

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
                    <form action="{{ route('users.store') }}" method="POST" id="createUserForm">
                        @csrf
                        @include('backend.users._form', ['isEdit' => false])
                        <button type="submit" class="btn btn-primary">{{ __('file.button.create') }}
                            {{ __('file.user') }}</button>
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
                        @include('backend.users._form', ['isEdit' => true])
                        <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.update') }}
                            {{ __('file.user') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    <script>
        $('#user_role_menu').addClass('active open');
        $('#user_role_menu .main_menu').addClass('active');
        $('#user_role_menu .menu_list').css('display', 'block');
        $('#user_role_menu .menu_list #users a').addClass('active');

        // Initialize Select2 on Create Modal Show
        $('#createUserModal').on('shown.bs.modal', function() {
            $('#branch').select2({
                placeholder: "Select branch(es)",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createUserModal')
            });
            $('#role').select2({
                placeholder: "Select role(s)",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createUserModal')
            });
        });

        // Initialize Select2 on Edit Modal Show
        $('#editUserModal').on('shown.bs.modal', function() {
            $('#edit_branch').select2({
                placeholder: "Select branch(es)",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editUserModal')
            });
            $('#edit_role').select2({
                placeholder: "Select role(s)",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editUserModal')
            });
        });

        // Password Match Validation
        $('#createUserModal form').on('submit', function(e) {
            e.preventDefault();

            let password = $('#password').val();
            let confirmPassword = $('#password_confirmation').val();

            $('.password-error').remove();

            if (password !== confirmPassword) {
                $('#password_confirmation').after(
                    '<small class="text-danger password-error">{{ __('file.message.password_mismatch') }}</small>'
                );
                return false;
            }

            let form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.creating') }}...");
                },
                success: function(response) {
                    $('#createUserModal').modal('hide');
                    form.trigger('reset');
                    $('#role').val(null).trigger('change');
                    $('#branch').val(null).trigger('change');
                    
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }} {{ __('file.user') }}");

                    showFloatingAlert('success', response.message ||
                        "{{ __('file.message.user_created_successfully') }}");

                    $('#user-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }} {{ __('file.user') }}");

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        try {
                            let firstErrorKey = Object.keys(errors)[0];
                            let firstErrorMessage = errors[firstErrorKey][0];

                            if (
                                firstErrorMessage.startsWith("{") &&
                                firstErrorMessage.endsWith("}")
                            ) {
                                let errorData = JSON.parse(firstErrorMessage);

                                if (errorData.is_trashed) {
                                    let modal = $("#restoreConfirmModal");
                                    let restoreBtn = $("#restoreConfirm");

                                    modal.find("#restoreMessage").html(
                                        `Username or email is already in use for another user <br>
                                        <b>${errorData.name}</b> is in trash. <br> Do you want to restore it?`
                                    );

                                    modal.modal("show");

                                    restoreBtn.off("click").one("click", function() {
                                        $.ajax({
                                            url: "/trashes/restore/" + errorData.id,
                                            type: "POST",
                                            data: {},
                                            beforeSend: function() {
                                                restoreBtn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Restoring...');
                                            },
                                            success: function(response) {
                                                modal.modal("hide");
                                                $('#createUserModal').modal("hide");
                                                showFloatingAlert("success", response.message);
                                                $('#user-table').DataTable().ajax.reload(null, false);
                                            },
                                            error: function(res) {
                                                showFloatingAlert("error", res.responseJSON?.message || "Restore failed!");
                                            },
                                            complete: function() {
                                                restoreBtn.prop("disabled", false).html("Restore");
                                            }
                                        });
                                    });
                                    return;
                                }
                            }
                        } catch (e) {
                            console.error("JSON parse error:", e);
                        }

                        handleValidationErrors(form, errors);
                    } else {
                        showFloatingAlert(
                            "error",
                            xhr.responseJSON?.message || "Server Error!"
                        );
                    }
                }
            });
        });

        // Remove password error on typing
        $('#password, #password_confirmation').on('keyup', function() {
            $('.password-error').remove();
        });

        function editUser(id) {
            let url = '{{ route('users.edit', ['user' => ':id']) }}';
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
                    
                    // Populate Multiple Roles
                    $('#edit_role').val(response.user_roles).trigger('change');
                    
                    // Populate Multiple Branches
                    $('#edit_branch').val(response.user_branches).trigger('change');
                    
                    $('#editUserModal').modal('show');
                },
                error: function() {
                    showFloatingAlert('error', "{{ __('file.message.unable_to_fetch_user_data') }}");
                }
            });
        }

        $('#editUserModal form').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let userId = $('#edit_id').val();
            let url = '{{ route('users.update', ['user' => ':id']) }}';
            url = url.replace(':id', userId);

            $.ajax({
                url: url,
                type: 'PATCH',
                data: form.serialize(),
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.updating') }}...");
                },
                success: function(response) {
                    $('#editUserModal').modal('hide');
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.update') }} {{ __('file.user') }}");

                    showFloatingAlert('success', response.message ||
                        "{{ __('file.message.user_updated_successfully') }}");

                    $('#user-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.update') }} {{ __('file.user') }}");

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        handleValidationErrors(form, errors);
                    } else {
                        showFloatingAlert(
                            "error",
                            xhr.responseJSON?.message || "Server Error!"
                        );
                    }
                }
            });
        });

        function handleValidationErrors(form, errors) {
            form.find(".invalid-feedback").remove();
            form.find(".is-invalid").removeClass("is-invalid");

            $.each(errors, function(field, messages) {
                let fieldName = field.replace(/\./g, "_");
                let inputField = form.find(`[name="${field}"], [name="${field}[]"]`);

                inputField.addClass("is-invalid");
                
                // If Select2 element, append error message after the select2-container
                if (inputField.hasClass("select2-hidden-accessible")) {
                    inputField.next('.select2-container').after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
                } else {
                    inputField.after(`<div class="invalid-feedback">${messages[0]}</div>`);
                }
            });
        }
    </script>
@endpush