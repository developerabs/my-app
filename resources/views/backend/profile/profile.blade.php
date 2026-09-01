@extends('backend.layouts.main')

@section('title'){{__('file.title.my_profile')}} - {{ $general_settings['site_title'] ?? $general_settings['company_name'] ?? 'SheraziPOS'}} @endsection

@push('css')
@endpush

@section('content')

    @component('backend.layouts.partials.header')
        @slot('title'){{__('file.title.my_profile')}}@endslot
        @slot('subtitle'){{__('file.title.my_profile_desc')}}@endslot
        @slot('button')
            <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fa-solid fa-lock me-1"></i>
                {{ __('file.button.change_password') }}
            </a>
            <!-- Edit Profile Button -->
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="fa-solid fa-pencil me-1"></i>
                {{ __('file.button.edit') }} {{ __('file.profile') }}
            </a>
        @endslot
    @endcomponent

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

            <form action="{{ route('profile.update') }}" method="POST">
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
            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('file.field.current_password') }}</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <span onclick="togglePassword('#current_password', '#current-password-icon')" style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                                <i id="current-password-icon" class="fa-solid fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('file.field.new_password') }}</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="password" class="form-control" required>
                            <span onclick="togglePassword('#password', '#password-icon')" style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                                <i id="password-icon" class="fa-solid fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('file.field.confirm_password') }}</label>
                        <div class="position-relative">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                            <span onclick="togglePassword('#password_confirmation', '#confirm-password-icon')" style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                                <i id="confirm-password-icon" class="fa-solid fa-eye"></i>
                            </span>
                        </div>
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
