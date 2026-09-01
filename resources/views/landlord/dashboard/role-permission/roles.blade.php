@extends('landlord.layouts.main')

@section('title'){{__('file.title.role_management')}} - SheraziPOS Landlord @endsection

@push('css')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{__('file.title.role_management')}}</h4>
            <p class="mb-0 text-muted">{{__('file.title.role_management_desc')}}</p>
        </div>
        <div>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal"><i class="fa-solid fa-plus me-1"></i>{{__('file.button.create')}} {{__('file.role')}}</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-hover table-striped border mb-0" id="roles-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('file.table.no') }}</th>
                                <th scope="col">{{ __('file.table.name') }}</th>
                                <th scope="col" class="text-end">{{ __('file.table.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                @if ($role->name != 'Super-Admin')
                                    <tr>
                                        <th scope="row">{{ $loop->index + 1 }}</th>
                                        <td>{{ $role->name }}</td>
                                        <td>
                                            @can('manage_role')
                                                <div class="d-flex justify-content-end">
                                                    <a href="{{ route('landlord.manage-permissions', $role->id)}}" class="btn btn-sm btn-info me-2"><i class="fa-solid fa-eye me-1"></i> {{ __('file.button.change_permission') }}</a>
                                                    @if($role->name != 'Accounts' && $role->name != 'Super-Admin' && $role->name != 'Editor' && $role->name != 'Reseller')
                                                        <button type="button" class="btn btn-sm btn-primary me-2" onclick="editRole({{ $role->id }} , '{{ $role->name }}');"><i class="fa-solid fa-pen me-1"></i> {{ __('file.button.edit') }}</button>
                                                        <form id="deleteForm{{ $role->id }}" action="{{ route('landlord.roles-permissions.destroy', $role->id) }}" method="POST">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="button" class="btn btn-sm btn-danger delete_btn" data-id="{{ $role->id }}"><i class="fa-solid fa-trash me-1"></i> {{ __('file.button.delete') }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <!-- Create Role Modal -->
    <div class="modal modal-lg fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRoleModalLabel">{{ __('file.title.create_role') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('landlord.roles-permissions.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('file.field.name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="permissions" class="form-label">{{ __('file.field.permissions') }}</label>
                            <div class="form-check">
                                <div class="row">
                                    @foreach ($permissions as $permission)
                                        <div class="col-md-4">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="{{ $permission->id }}">
                                                <label class="form-check-label" for="{{ $permission->id }}">{{ Str::ucfirst($string = str_replace('_', ' ', $permission->name)) }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.storeandassign') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('landlord.roles-permissions.update') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="role_id" id="role_id">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')

    <script>
        $('#user_role_menu').addClass('active open');
        $('#user_role_menu .main_menu').addClass('active');
        $('#user_role_menu .menu_list').css('display', 'block');
        $('#user_role_menu .menu_list #roles-permissions a').addClass('active');

        $(document).ready(function() {
            let roleId = '';

            $(document).on('click', '.delete_btn', function(e) {
                e.preventDefault();
                roleId = $(this).data('id');
                $('#deleteConfirmModal').modal('show');
            });

            $('#deleteConfirm').on('click', function() {
                 if(roleId) {
                     $('#deleteForm' + roleId).submit();
                     roleId = '';
                 }
            });
        })

        function editRole(id, name) {
            $('#role_id').val(id);
            $('#editRoleModal #name').val(name);
            $('#editRoleModal').modal('show');
        }
    </script>
@endpush
