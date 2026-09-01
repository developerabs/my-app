<div class="row g-4">
    {{-- CLIENT INFO --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ __('file.title.client_information') }}</h5>
            </div>
            <div class="card-body row g-1">

                <style>
                    .required-star::after {
                        content: " *";
                        color: #dc3545;
                    }
                </style>

                <div class="col-12">
                    <label class="form-label fw-bold required-star">{{ __('file.field.company_name') }}</label>
                   <input type="text" name="company_name"
                    value="{{ old('company_name', $proposal->company_name ?? '') }}"
                    class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold required-star">{{ __('file.field.customer_name') }}</label>
                    <input type="text" name="customer_name"
                           value="{{ old('customer_name', $proposal->customer_name ?? '') }}"
                           class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold required-star">{{ __('file.field.customer_email') }}</label>
                    <input type="email" name="customer_email"
                           value="{{ old('customer_email', $proposal->customer_email ?? '') }}"
                           class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold required-star">{{ __('file.field.customer_phone') }}</label>
                    <input type="text" name="customer_phone"
                           value="{{ old('customer_phone', $proposal->customer_phone ?? '') }}"
                           class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">{{ __('file.field.customer_address') }}</label>
                    <textarea name="customer_address" class="form-control"
                              rows="2">{{ old('customer_address', $proposal->customer_address ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- PACKAGE & FEES --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">{{ __('file.title.package_fees') }}</h5>
            </div>
            <div class="card-body row g-3">

                {{-- Package Select --}}
                    <div class="col-12">
                <label class="form-label fw-bold required-star">{{ __('file.field.package') }}</label>

                <select name="package" id="package" class="form-select" required>
                    <option value="" disabled {{ empty($proposal?->package) ? 'selected' : '' }}>
                        {{ __('file.option.select_package') }}
                    </option>

                    @foreach ($packages as $package)
                        @php
                            $pricing = $package->pricing->keyBy('type');
                        @endphp

                        <option value="{{ $package->id }}"
                            {{ (($proposal?->package ?? '') == $package->id) ? 'selected' : '' }}
                            data-registration="{{ $package->registration_fee ?? 0 }}"
                            data-subscription="{{ $package->subscription_fee ?? 0 }}"
                            data-monthly="{{ $pricing['monthly']->price ?? 0 }}"
                            data-yearly="{{ $pricing['yearly']->price ?? 0 }}"
                            data-lifetime="{{ $pricing['lifetime']->price ?? 0 }}">
                            {{ $package->name }}
                        </option>
                    @endforeach
                </select>
            </div>
                <div class="col-12">
                    <label class="form-label fw-bold required-star">{{ __('file.field.registration_fee') }}</label>
                    <input type="number" step="0.01" name="registration_fee"
                           value="{{ old('registration_fee', $proposal->registration_fee ?? 10000) }}"
                           class="form-control" required>
                </div>
                {{-- Pricing Fields --}}
                <div class="col-12">
                    <div class="d-flex gap-3">
                        <div class="flex-fill">
                            <label class="form-label fw-bold required-star">{{ __('file.field.subscription_fee') }}</label>
                            <input type="number" step="0.01" name="subscription_fee"
                                   value="{{ old('subscription_fee', $proposal->subscription_fee ?? 500) }}"
                                   class="form-control" required>
                        </div>
                        <div class="flex-fill">
                            <label class="form-label fw-bold">{{ __('file.field.monthly') }}</label>
                            <input type="number" step="0.01" name="monthly"
                                   value="{{ old('monthly', $proposal->monthly ?? '') }}"
                                   class="form-control  bg-light" readonly>
                        </div>
                        <div class="flex-fill">
                            <label class="form-label fw-bold">{{ __('file.field.yearly') }}</label>
                            <input type="number" step="0.01" name="yearly"
                                   value="{{ old('yearly', $proposal->yearly ?? '') }}"
                                   class="form-control  bg-light" readonly>
                        </div>

                        <div class="flex-fill">
                            <label class="form-label fw-bold">{{ __('file.field.lifetime') }}</label>
                            <input type="number" step="0.01" name="lifetime"
                                   value="{{ old('lifetime', $proposal->lifetime ?? '') }}"
                                   class="form-control  bg-light" readonly>
                        </div>
                    </div>
                </div>

                {{-- Discount --}}
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('file.field.discount') }}</label>
                    <input type="number" step="0.01" name="discount"
                           value="{{ old('discount', $proposal->discount ?? '0') }}"
                           class="form-control">
                </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">{{ __('file.field.discount_type') }}</label>
                <select name="discount_type" class="form-select">
                    <option value="flat" {{ (($proposal?->discount_type ?? '') == 'flat') ? 'selected' : '' }}>{{ __('file.option.flat') }}</option>
                    <option value="percentage" {{ (($proposal?->discount_type ?? '') == 'percentage') ? 'selected' : '' }}>{{ __('file.option.percentage') }}</option>
                </select>
            </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold required-star">{{ __('file.field.validity_days') }}</label>
                    <input type="number" name="validity"value="{{ old('validity', $proposal->validity ?? '') }}"class="form-control" required>
                </div>
            </div>
        </div>
    </div>

    {{-- DEMO INFO --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-white"> <h5 class="mb-0">{{ __('file.title.demo_information') }}</h5> </div>
            <div class="card-body">
                <div class="col-12">
                    <label class="form-label fw-bold">{{ __('file.field.demo_link') }}</label>
                    <input type="text" name="demo_link"value="{{ old('demo_link', $proposal->demo_link ?? '') }}"class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">{{ __('file.field.username') }}</label>
                    <input type="text" name="username" value="{{ old('username', $proposal->username ?? '') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">{{ __('file.field.password') }}</label>
                    <input type="text" name="password" value="{{ old('password', $proposal->password ?? '') }}"class="form-control">
                </div>
            </div>
        </div>
    </div>

    {{-- NOTES --}}
    <div class="col-12">
        <div class="card shadow-sm">
             <div class="card-header bg-warning text-dark">
                 <h5 class="mb-0">{{ __('file.title.proposal_special_notes') }}</h5>
             </div>
             <div class="card-body row g-3">

                 <div class="col-md-6">
                     <label class="form-label fw-bold">{{ __('file.field.proposal_details') }}</label>
                     <textarea name="proposal_details" class="form-control"rows="5">{{ old('proposal_details', $proposal->proposal_details ?? '') }}</textarea>
                 </div>
                 <div class="col-md-6">
                     <label class="form-label fw-bold">{{ __('file.field.special_note') }}</label>
                     <textarea name="special_note" class="form-control" rows="5">{{ old('special_note', $proposal->special_note ?? '') }}</textarea>
                 </div>

             </div>
        </div>
    </div>

</div>

@push('js')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const packageSelect = document.getElementById('package');
    const monthlyInput = document.querySelector('input[name="monthly"]');
    const yearlyInput = document.querySelector('input[name="yearly"]');
    const lifetimeInput = document.querySelector('input[name="lifetime"]');
    function updatePrices() {
        const selected = packageSelect.options[packageSelect.selectedIndex];
        monthlyInput.value = selected?.getAttribute('data-monthly') ?? 0;
        yearlyInput.value = selected?.getAttribute('data-yearly') ?? 0;
        lifetimeInput.value = selected?.getAttribute('data-lifetime') ?? 0;
    }
    packageSelect.addEventListener('change', updatePrices);
    updatePrices();
});


</script>

@endpush
