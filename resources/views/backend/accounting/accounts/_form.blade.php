@php
    $isEdit = $isEdit ?? false;
    $isMultiCurrency =
        !empty($general_settings['use_multi_currency']) &&
        ($general_settings['use_multi_currency'] == '1' || $general_settings['use_multi_currency'] == true);
    $defaultBranch = $general_settings['default_branch'] ?? '';
@endphp

<div class="row mt-3">
    <!-- 1. Account Type -->
    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">Account Type <span class="text-danger">*</span></label>
        <select name="account_type" id="{{ $isEdit ? 'edit_account_type' : 'account_type' }}" class="form-select" required>
            <option value="cash">Cash</option>
            <option value="bank">Bank</option>
            <option value="mobile">Mobile Banking</option>
            <option value="other">Other</option>
        </select>
    </div>

    <!-- 2. Branch Selection (With data-currency-id for Auto-Selection) -->
    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">Select Branch</label>
        <select name="branch_id" id="{{ $isEdit ? 'edit_branch_id' : 'branch_id' }}" class="form-select branch_id">
            <option value="">{{ __('file.option.select_branch') ?? '-- Global / Head Office --' }}</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" {{ $defaultBranch == $branch->id ? 'selected' : '' }} data-currency-id="{{ $branch->currency_id }}">
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- 3. Currency Selection (NEW) -->
    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">Account Currency</label>
        @if ($isMultiCurrency)
            <select name="currency_id" id="{{ $isEdit ? 'edit_currency_id' : 'currency_id' }}" class="form-select select-picker currency_id" required>
                @forelse ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                        data-code="{{ $currency->code }}" {{ $default_currency['id'] == $currency->id ? 'selected' : '' }}>
                        {{ $currency->name . ' - ' . $currency->code }}
                    </option>
                @empty
                    <option value="">{{ __('file.option.no') }}</option>
                @endforelse
            </select>
        @else
            <select class="form-select select-picker" disabled>
                @foreach ($currencies as $currency)
                    @if ($default_currency['id'] == $currency->id)
                        <option selected>{{ $currency->name . ' - ' . $currency->code }}</option>
                    @endif
                @endforeach
            </select>
            <input type="hidden" name="currency_id" {{ $isEdit ? 'edit_currency_id' : 'currency_id' }} value="{{ $default_currency['id'] }}">
        @endif
    </div>

    <!-- 4. Opening Balance -->
    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">Opening Balance</label>
        <input type="number" min="0" step="any" name="opening_balance"
            id="{{ $isEdit ? 'edit_opening_balance' : 'opening_balance' }}" class="form-control text-end"
            value="0">
    </div>
</div>

<div class="row">
    <!-- 5. Account Name -->
    <div class="col-md-5 mb-3">
        <label class="form-label fw-bold">Account Name <span class="text-danger">*</span></label>
        <input type="text" name="account_name" id="{{ $isEdit ? 'edit_account_name' : 'account_name' }}"
            class="form-control" required placeholder="e.g. Dutch Bangla Bank USD A/C">
    </div>

    <!-- 6. Account Number -->
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Account Number</label>
        <input type="text" name="account_number" id="{{ $isEdit ? 'edit_account_number' : 'account_number' }}"
            class="form-control" placeholder="Account #">
    </div>

    <!-- 7. Opening Balance Date -->
    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">Opening Balance Date</label>
        <input type="text" placeholder="YYYY-MM-DD" name="opening_balance_date"
            id="{{ $isEdit ? 'edit_opening_balance_date' : 'opening_balance_date' }}" autocomplete="off"
            class="form-control date-picker">
    </div>
</div>

<div class="row">
    <!-- 8. Bank Details -->
    <div class="col-md-5 mb-3">
        <label class="form-label fw-bold">Bank Name</label>
        <input type="text" name="bank_name" id="{{ $isEdit ? 'edit_bank_name' : 'bank_name' }}" class="form-control"
            placeholder="e.g. City Bank">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label fw-bold">Branch Name</label>
        <input type="text" name="branch_name" id="{{ $isEdit ? 'edit_branch_name' : 'branch_name' }}"
            class="form-control" placeholder="e.g. Gulshan Branch">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label fw-bold">Routing Number</label>
        <input type="text" name="routing_number" id="{{ $isEdit ? 'edit_routing_number' : 'routing_number' }}"
            class="form-control" placeholder="Routing Number">
    </div>
</div>
