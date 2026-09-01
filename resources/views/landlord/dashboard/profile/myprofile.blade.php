@extends('landlord.layouts.main')

@section('title'){{__('file.title.my_profile')}} - SheraziPOS Landlord @endsection

@push('css')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{__('file.title.my_profile')}}</h4>
            <p class="mb-0 text-muted">{{__('file.title.my_profile_desc')}}</p>
        </div>
        <div class="d-flex gap-2">
        <!-- Change Password Button -->
        <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="fa-solid fa-lock me-1"></i>
            {{ __('file.button.change_password') }}
        </a>
        <!-- Edit Profile Button -->
        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <i class="fa-solid fa-pencil me-1"></i>
            {{ __('file.button.edit') }} {{ __('file.profile') }}
        </a>

    </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" style="font-size: 20px; font-weight: 500; width:600px">
                <tbody>
                    <tr>
                        <th>{{__('file.table.name')}}</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th>{{__('file.table.email')}}</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th>{{__('file.table.phone_number')}}</th>
                        <td>{{ $user->phone }}</td>
                    </tr>
                    <tr>
                        <th>{{__('file.table.company_name')}}</th>
                        <td>{{ $user->company_name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Profile Modal -->


@endsection

@section('modals')
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg ">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">{{ __('file.button.edit') }} {{ __('file.profile') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('landlord.myprofile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('file.table.name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ $user->name }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('file.table.email') }}</label>
                            <input type="email" class="form-control" name="email" value="{{ $user->email }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('file.table.phone_number') }}</label>
                            <input type="text" class="form-control" name="phone" value="{{ $user->phone }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('file.table.company_name') }}</label>
                            <input type="text" class="form-control" name="company_name" value="{{ $user->company_name }}">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('file.button.save') }}</button>
                </div>

            </form>

        </div>
    </div>
</div>
<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">{{ __('file.title.change_password') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('landlord.myprofile.changePassword') }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('file.field.current_password') }}</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('file.field.new_password') }}</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('file.field.confirm_password') }}</label>
                        <input type="password" name="new_password_confirmation" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                    <button type="submit" class="btn btn-warning">{{ __('file.button.update') }}</button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
@push('js')
@endpush
