@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_name' : 'name' }}" class="form-label">{{ __('file.field.name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="{{ $isEdit ? 'edit_name' : 'name' }}" name="name"
            value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_username' : 'username' }}"
            class="form-label">{{ __('file.field.username') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="{{ $isEdit ? 'edit_username' : 'username' }}" name="username"
            value="{{ old('username', $user->username ?? '') }}" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_phone_number' : 'phone_number' }}"
            class="form-label">{{ __('file.field.phone_number') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control phone-input"
            id="{{ $isEdit ? 'edit_phone_number' : 'phone_number' }}" name="phone_number"
            value="{{ old('phone_number', $user->phone ?? '') }}" required>
    </div>

    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_email' : 'email' }}" class="form-label">{{ __('file.field.email') }} <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="{{ $isEdit ? 'edit_email' : 'email' }}" name="email"
            value="{{ old('email', $user->email ?? '') }}" required>
    </div>
</div>

{{-- শুধুমাত্র Create ফর্মে password ফিল্ড দেখাবে --}}
@if (!$isEdit)
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="password" class="form-label">{{ __('file.field.password') }} <span class="text-danger">*</span></label>
            <div style="position: relative;">
                <input type="password" class="form-control" id="password" name="password" required
                    autocomplete="new-password">
                <span onclick="togglePassword('#password', '#password-icon')"
                    style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                    <i id="password-icon" class="fa-solid fa-eye"></i>
                </span>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="password_confirmation" class="form-label">{{ __('file.field.password_confirmation') }} <span class="text-danger">*</span></label>
            <div style="position: relative;">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                    required autocomplete="new-password">
                <span onclick="togglePassword('#password_confirmation', '#password-icon-confirm')"
                    style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                    <i id="password-icon-confirm" class="fa-solid fa-eye"></i>
                </span>
            </div>
        </div>
    </div>
@endif

<div class="row mb-3">
    <!-- Multiple Role Select -->
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_role' : 'role' }}" class="form-label">{{ __('file.field.role') }} <span class="text-danger">*</span></label>
        <select name="roles[]" id="{{ $isEdit ? 'edit_role' : 'role' }}" class="form-select" multiple="multiple" required>
            @forelse ($roles as $role)
                <option value="{{ $role->name }}" @if (isset($user) && $user->hasRole($role->name)) selected @endif>
                    {{ $role->name }}
                </option>
            @empty
                <option value="" disabled>{{ __('file.field.no_role_found') }}</option>
            @endforelse
        </select>
    </div>

    <!-- Multiple Branch Select -->
    <div class="col-md-6 mb-3">
        <label for="{{ $isEdit ? 'edit_branch' : 'branch' }}" class="form-label">{{ __('file.field.branch') }} <span class="text-danger">*</span></label>
        <select name="branch_ids[]" id="{{ $isEdit ? 'edit_branch' : 'branch' }}" class="form-select" multiple="multiple" required>
            @forelse ($branches as $branch)
                <option value="{{ $branch->id }}" @if (isset($user) && $user->branches->contains($branch->id)) selected @endif>
                    {{ $branch->name }}
                </option>
            @empty
                <option value="" disabled>{{ __('file.field.no_branch_found') }}</option>
            @endforelse
        </select>
    </div>
</div>