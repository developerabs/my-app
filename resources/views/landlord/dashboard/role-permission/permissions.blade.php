@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.assign_permissions') }} - SheraziPOS Landlord
@endsection

@push('css')
<style>
    /* Permission Table Styling */
    .permissions-table td,
    .permissions-table th {
        vertical-align: middle;
    }

    /* Permission Grid (responsive flex layout) */
    .permission-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.5rem;
        align-items: center;
    }

    .permission-grid .form-check {
        margin: 0;
        padding-left: 1.75rem;
    }

    /* Checkbox alignment */
    .form-check-input {
        cursor: pointer;
        width: 1.15em;
        height: 1.15em;
    }

    .form-check-label {
        cursor: pointer;
        user-select: none;
    }

    /* Hover & transition */
    .permissions-table tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease-in-out;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .permission-grid {
            flex-direction: column;
            align-items: flex-start;
        }

        .permissions-table th:first-child {
            width: 35%;
        }
    }

    @media (max-width: 576px) {
        .permissions-table th:first-child {
            width: 100%;
        }
        .permission-grid {
            gap: 0.5rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.assign_permissions') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.assign_permission_desc') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h6 class="card-title mb-0">{{ __('file.role_name') }} : {{ $role->name }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('landlord.assign-permissions', $role->id) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <!-- Check All -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check-all">
                                <label class="form-check-label fw-semibold ms-2" for="check-all">{{ __('file.field.check_all') }}</label>
                            </div>
                        </div>

                        <!-- Permissions Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle permissions-table">
                                <thead>
                                    <tr class="text-center">
                                        <th scope="col">{{ __('file.table.group') }}</th>
                                        <th scope="col">{{ __('file.table.permission') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permissions as $group => $perms)
                                        <tr>
                                            <td class="fw-semibold text-capitalize bg-light">
                                                {{ str_replace('_', ' ', $group) }}
                                            </td>
                                            <td>
                                                <div class="row g-2">
                                                    @foreach ($perms as $permission)
                                                        <div class="col-12 col-md-3 col-lg-2">
                                                            <div class="form-check">
                                                                <input 
                                                                    class="form-check-input" 
                                                                    type="checkbox" 
                                                                    name="permissions[]" 
                                                                    value="{{ $permission->name }}" 
                                                                    id="perm-{{ $permission->id }}" 
                                                                    {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                                                <label class="form-check-label ms-2" for="perm-{{ $permission->id }}">
                                                                    {{ Str::title(str_replace('_', ' ', $permission->name)) }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 text-start">
                            <button type="submit" class="btn btn-primary px-4 me-2">
                                <i class="bi bi-save me-1"></i>{{ __('file.button.update') }}
                            </button>
                            <a href="{{ route('landlord.roles-permissions') }}" class="btn btn-secondary px-4">
                                <i class="bi bi-x-circle me-1"></i>{{ __('file.button.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        // Check All Logic
        $('#check-all').on('click', function() {
            $('input[name="permissions[]"]').prop('checked', this.checked);
        });

        // Update Check All status dynamically
        $('input[name="permissions[]"]').on('change', function() {
            let total = $('input[name="permissions[]"]').length;
            let checked = $('input[name="permissions[]"]:checked').length;
            $('#check-all').prop('checked', total === checked);
        });

        // On load - sync check all
        let total = $('input[name="permissions[]"]').length;
        let checked = $('input[name="permissions[]"]:checked').length;
        $('#check-all').prop('checked', total === checked);
    });
</script>
@endpush
