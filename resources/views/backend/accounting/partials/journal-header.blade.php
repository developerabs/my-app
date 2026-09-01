@php
    // 1. Generic Model Entity
    $entity     = $model ?? ($entity ?? null);
    $isEditMode = isset($entity) && !empty($entity->id);

    // 2. Dynamic Field Names & Labels
    $dateFieldLabel = $dateLabel ?? __('file.field.voucher_date');
    $dateFieldName  = $dateName ?? 'voucher_date';

    // 🟢 ৩. পেমেন্ট অ্যাকাউন্ট ফ্ল্যাগ কনফিগারেশন
    $showPaymentAccount     = $showPaymentAccount ?? ($withPaymentAccount ?? false);
    $paymentAccountRequired = $paymentAccountRequired ?? true;
    $paymentAccountLabel    = $paymentAccountLabel ?? (__('file.field.payment_account') ?? 'Payment Account');
    $paymentAccountName     = $paymentAccountName ?? 'payment_account_id';
    $availablePaymentAccounts = $paymentAccounts ?? collect([]);

    // 4. Currency Engine Configurations
    $isMultiCurrency     = !empty($general_settings['use_multi_currency']) && ($general_settings['use_multi_currency'] == '1' || $general_settings['use_multi_currency'] == true);
    $currencyService     = app(\App\Services\CurrencyConversionService::class);
    $defaultCurrencyCode = $default_currency['code'] ?? 'BDT';

    // 5. Date Resolver
    $selectedDate = old($dateFieldName, 
        isset($entity) && $entity->{$dateFieldName} 
            ? $entity->{$dateFieldName}->format('Y-m-d') 
            : ($defaultDate ?? date('Y-m-d'))
    );

    // 6. Branch Resolver
    $selectedBranchId = old('branch_id', 
        $branchId ?? (
            $entity->branch_id ?? (
                session('branch_id') ?? (
                    auth()->user()->branch_id ?? (
                        $general_settings['default_branch'] ?? (
                            $branches->first()?->id ?? ''
                        )
                    )
                )
            )
        )
    );

    $selectedBranchObj = $branches->firstWhere('id', $selectedBranchId) ?? $branches->first();
    if ($selectedBranchObj) {
        $selectedBranchId = $selectedBranchObj->id;
    }

    $branchDefaultCurrencyId = $selectedBranchObj?->currency_id ?? null;
    
    // ব্রাঞ্চের ডিফল্ট একাউন্ট নির্ধারণ
    $branchDefaultAccountId = $selectedBranchObj?->defaultAccount?->id 
        ?? ($selectedBranchObj?->default_acc 
        ?? ($availablePaymentAccounts->firstWhere('branch_id', $selectedBranchId)?->id ?? null));

    // 7. Currency Resolver
    $selectedCurrencyId = old('currency_id', 
        $currencyId ?? (
            $entity->currency_id ?? (
                $branchDefaultCurrencyId ?? (
                    $default_currency['id'] ?? ''
                )
            )
        )
    );

    // 8. Payment Account Resolver
    $selectedPaymentAccountId = old($paymentAccountName, 
        $paymentAccountId ?? (
            $entity->{$paymentAccountName} ?? (
                $branchDefaultAccountId ?? (
                    $availablePaymentAccounts->first()?->id ?? ''
                )
            )
        )
    );

    // 9. Exchange Rate Resolver
    $resolvedInitialRate = 1;
    if ($selectedCurrencyId) {
        try {
            $resolvedInitialRate = $currencyService->getExchangeRate($selectedCurrencyId);
        } catch (\Throwable $e) {
            $resolvedInitialRate = 1;
        }
    }

    $selectedExchangeRate = old('exchange_rate', 
        $exchangeRate ?? ($entity->exchange_rate ?? $resolvedInitialRate)
    );
@endphp

<!-- 1. Dynamic Date Field -->
<div class="col-md-2 mb-3">
    <label class="form-label fw-bold">{{ $dateFieldLabel }} <span class="text-danger">*</span></label>
    <input type="date" name="{{ $dateFieldName }}" class="form-control journal_date"
        value="{{ $selectedDate }}" required>
</div>

<!-- 2. Universal Branch Selection -->
<div class="col-md-2 mb-3">
    <label class="form-label fw-bold">Branch <span class="text-danger">*</span></label>
    <select name="branch_id" class="form-select select-picker journal-branch-select" required>
        @forelse ($branches as $branch)
            @php
                $bDefaultAccId = $branch->defaultAccount?->id ?? ($branch->default_acc ?? ($availablePaymentAccounts->firstWhere('branch_id', $branch->id)?->id ?? ''));
            @endphp
            <option value="{{ $branch->id }}" 
                    data-currency-id="{{ $branch->currency_id ?? '' }}"
                    data-default-account-id="{{ $bDefaultAccId }}"
                    {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>
                {{ $branch->name }}
            </option>
        @empty
            <option value="">{{ __('file.option.no') }}</option>
        @endforelse
    </select>
</div>

<!-- 3. Dynamic Multi-Currency Dropdown -->
<div class="col-md-2 mb-3">
    <label class="form-label fw-bold">Currency <span class="text-danger">*</span></label>
    @if ($isMultiCurrency)
        <select name="currency_id" class="form-select select-picker journal-currency-select" required>
            @forelse ($currencies as $currency)
                @php
                    $rate = 1;
                    try {
                        $rate = $currencyService->getExchangeRate($currency->id);
                    } catch (\Throwable $e) {
                        $rate = 1;
                    }
                @endphp
                <option value="{{ $currency->id }}" 
                        data-rate="{{ $rate }}"
                        data-code="{{ $currency->code }}"
                        {{ $selectedCurrencyId == $currency->id ? 'selected' : '' }}>
                    {{ $currency->name . ' - ' . $currency->code }}
                </option>
            @empty
                <option value="">{{ __('file.option.no') }}</option>
            @endforelse
        </select>
    @else
        <select class="form-select select-picker" disabled>
            @foreach ($currencies as $currency)
                @if ($selectedCurrencyId == $currency->id)
                    <option selected>{{ $currency->name . ' - ' . $currency->code }}</option>
                @endif
            @endforeach
        </select>
        <input type="hidden" name="currency_id" class="journal-currency-hidden" value="{{ $selectedCurrencyId }}">
    @endif
</div>

<!-- 4. Exchange Rate Input -->
<div class="col-md-2 mb-3">
    <label class="form-label fw-bold d-flex align-items-center justify-content-between mb-1">
        <span>Exchange Rate</span>
        <span class="badge bg-primary-subtle text-primary border journal-rate-instruction" style="font-size: 10px; display: none;"></span>
    </label>
    @if ($isMultiCurrency)
        <input type="number" step="0.00000001" name="exchange_rate" class="form-control journal-exchange-rate"
            value="{{ $selectedExchangeRate }}" required>
    @else
        <input type="number" step="0.00000001" class="form-control" value="1" readonly>
        <input type="hidden" name="exchange_rate" value="1">
    @endif
</div>

<!-- 6. Fiscal Year Info -->
<div class="col-md-2 mb-3">
    <label class="form-label fw-bold">Fiscal Year</label>
    <input type="text" class="form-control bg-light" value="{{ $fiscalYear?->name ?? 'N/A' }}" readonly>
</div>

<!-- 7. Accounting Period Info -->
<div class="col-md-2 mb-3">
    <label class="form-label fw-bold">Accounting Period</label>
    <input type="text" class="form-control bg-light" value="{{ $accountingPeriod?->name ?? 'N/A' }}" readonly>
</div>

<!-- 🟢 5. Configurable Payment Source Account (Conditional Flag) -->
@if ($showPaymentAccount)
    <div class="col-md-2 mb-3">
        <label class="form-label fw-bold">{{ $paymentAccountLabel }} @if($paymentAccountRequired)<span class="text-danger">*</span>@endif</label>
        <select name="{{ $paymentAccountName }}" 
                class="form-select select-picker payment-account-select" 
                {{ $paymentAccountRequired ? 'required' : '' }}>
            @forelse ($availablePaymentAccounts as $account)
                <option value="{{ $account->id }}" 
                        data-branch-id="{{ $account->branch_id ?? '' }}"
                        data-currency-id="{{ $account->currency_id ?? '' }}"
                        {{ $selectedPaymentAccountId == $account->id ? 'selected' : '' }}>
                    {{ $account->account_name }} ({{ $account->currency->code ?? $defaultCurrencyCode }})
                </option>
            @empty
                <option value="">No Payment Accounts Found</option>
            @endforelse
        </select>
    </div>
@endif

<!-- Auto-Rate & Bi-Directional Context Sync Script -->
@push('js')
    <script>
        $(document).ready(function() {
            const baseCurrencyCode = "{{ $defaultCurrencyCode }}";
            const isEditMode       = {{ $isEditMode ? 'true' : 'false' }};
            const $journalDateInput = $('.journal_date');
            const initialDateVal   = "{{ $selectedDate }}";

            // ইনফিনিট ইভেন্ট লুপ আটকানোর ফ্ল্যাগ
            let isContextSyncing = false;

            // ১. Flatpickr ইনিশিয়ালাইজেশন
            if ($journalDateInput.length && typeof flatpickr !== 'undefined') {
                flatpickr('.journal_date', {
                    altInput: true,
                    altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                    dateFormat: "Y-m-d",
                    defaultDate: initialDateVal ? initialDateVal : "today",
                    static: true,
                    allowInput: true,
                });
            }

            // ২. রেট ইনফো ব্যাজ আপডেট ফাংশন
            function updateRateInstruction($form) {
                let $currencySelect = $form.find('.journal-currency-select');
                let $selectedOption = $currencySelect.find(':selected');
                let targetCode      = $selectedOption.data('code') || '';
                let $rateInput      = $form.find('.journal-exchange-rate');
                let currentRate     = parseFloat($rateInput.val()) || 0;
                let $instruction    = $form.find('.journal-rate-instruction');

                if (targetCode && baseCurrencyCode && targetCode !== baseCurrencyCode && currentRate > 0) {
                    $instruction.html(`1 ${targetCode} = ${currentRate} ${baseCurrencyCode}`).show();
                } else {
                    $instruction.html('').hide();
                }
            }

            // 🟢 ৩. ব্রাঞ্চ পরিবর্তন হলে -> কারেন্সি ও পেমেন্ট অ্যাকাউন্ট সিঙ্ক
            function syncFromBranch($form, forceUpdate = false) {
                let $branchSelect   = $form.find('select[name="branch_id"], .journal-branch-select');
                let $selectedBranch = $branchSelect.find(':selected');
                
                let branchId         = $selectedBranch.val();
                let branchCurrencyId = $selectedBranch.data('currency-id');
                let defaultAccountId = $selectedBranch.data('default-account-id');

                // ক. কারেন্সি সিঙ্ক
                if (branchCurrencyId) {
                    let $currencySelect = $form.find('.journal-currency-select');
                    let $currencyHidden = $form.find('.journal-currency-hidden');

                    if ($currencySelect.length) {
                        let currentCurrencyVal = $currencySelect.val();
                        if (forceUpdate || !isEditMode || !currentCurrencyVal) {
                            $currencySelect.val(branchCurrencyId);
                            if ($.fn.selectpicker && $currencySelect.hasClass('select-picker')) {
                                $currencySelect.selectpicker('val', branchCurrencyId);
                                $currencySelect.selectpicker('refresh');
                            } else if ($currencySelect.hasClass('select2-hidden-accessible')) {
                                $currencySelect.trigger('change.select2');
                            }

                            let $selectedOption = $currencySelect.find(':selected');
                            let rate = $selectedOption.data('rate') || 1;
                            let $rateInput = $form.find('.journal-exchange-rate');
                            if ($rateInput.length) {
                                $rateInput.val(rate);
                                updateRateInstruction($form);
                            }
                        }
                    }

                    if ($currencyHidden.length && (forceUpdate || !isEditMode)) {
                        $currencyHidden.val(branchCurrencyId);
                    }
                }

                // খ. পেমেন্ট অ্যাকাউন্ট ফিল্টারিং ও ডিফল্ট সিঙ্ক
                let $paymentAccountSelect = $form.find('select[name="payment_account_id"], .payment-account-select');
                if ($paymentAccountSelect.length) {
                    let firstBranchAccountId = null;

                    $paymentAccountSelect.find('option').each(function() {
                        let accBranchId = $(this).data('branch-id');
                        if (!accBranchId || accBranchId == branchId) {
                            $(this).prop('disabled', false).show();
                            if (!firstBranchAccountId && $(this).val()) {
                                firstBranchAccountId = $(this).val();
                            }
                        } else {
                            $(this).prop('disabled', true).hide();
                        }
                    });

                    let targetAccountId = defaultAccountId || firstBranchAccountId;

                    if (targetAccountId && (forceUpdate || !isEditMode || !$paymentAccountSelect.val())) {
                        $paymentAccountSelect.val(targetAccountId);
                        if ($.fn.selectpicker && $paymentAccountSelect.hasClass('select-picker')) {
                            $paymentAccountSelect.selectpicker('val', targetAccountId);
                            $paymentAccountSelect.selectpicker('refresh');
                        } else if ($paymentAccountSelect.hasClass('select2-hidden-accessible')) {
                            $paymentAccountSelect.trigger('change.select2');
                        }
                    } else if ($.fn.selectpicker && $paymentAccountSelect.hasClass('select-picker')) {
                        $paymentAccountSelect.selectpicker('refresh');
                    }
                }
            }

            // 🟢 ৪. পেমেন্ট অ্যাকাউন্ট পরিবর্তন হলে -> ব্রাঞ্চ ও কারেন্সি সিঙ্ক
            function syncFromPaymentAccount($form) {
                let $paymentAccountSelect = $form.find('select[name="payment_account_id"], .payment-account-select');
                let $selectedAcc = $paymentAccountSelect.find(':selected');
                
                let accBranchId   = $selectedAcc.data('branch-id');
                let accCurrencyId = $selectedAcc.data('currency-id');

                // ক. ব্রাঞ্চ সিঙ্ক (যদি একাউন্ট নির্দিষ্ট ব্রাঞ্চের হয়)
                if (accBranchId) {
                    let $branchSelect = $form.find('select[name="branch_id"], .journal-branch-select');
                    if ($branchSelect.length && $branchSelect.val() != accBranchId) {
                        $branchSelect.val(accBranchId);
                        if ($.fn.selectpicker && $branchSelect.hasClass('select-picker')) {
                            $branchSelect.selectpicker('val', accBranchId);
                            $branchSelect.selectpicker('refresh');
                        } else if ($branchSelect.hasClass('select2-hidden-accessible')) {
                            $branchSelect.trigger('change.select2');
                        }
                    }
                }

                // খ. কারেন্সি সিঙ্ক (একাউন্টের কারেন্সি ড্রপডাউনে সেট করা)
                if (accCurrencyId) {
                    let $currencySelect = $form.find('.journal-currency-select');
                    let $currencyHidden = $form.find('.journal-currency-hidden');

                    if ($currencySelect.length && $currencySelect.val() != accCurrencyId) {
                        $currencySelect.val(accCurrencyId);
                        if ($.fn.selectpicker && $currencySelect.hasClass('select-picker')) {
                            $currencySelect.selectpicker('val', accCurrencyId);
                            $currencySelect.selectpicker('refresh');
                        } else if ($currencySelect.hasClass('select2-hidden-accessible')) {
                            $currencySelect.trigger('change.select2');
                        }

                        let $selectedOption = $currencySelect.find(':selected');
                        let rate = $selectedOption.data('rate') || 1;
                        let $rateInput = $form.find('.journal-exchange-rate');
                        if ($rateInput.length) {
                            $rateInput.val(rate);
                            updateRateInstruction($form);
                        }
                    }

                    if ($currencyHidden.length) {
                        $currencyHidden.val(accCurrencyId);
                    }
                }
            }

            // ৫. ইভেন্ট লিসেনারস (লুপ-প্রটেক্টেড)
            $(document).on('change', 'select[name="branch_id"], .journal-branch-select', function() {
                if (isContextSyncing) return;
                isContextSyncing = true;
                try {
                    let $form = $(this).closest('form');
                    syncFromBranch($form, true);
                } finally {
                    isContextSyncing = false;
                }
            });

            $(document).on('change', 'select[name="payment_account_id"], .payment-account-select', function() {
                if (isContextSyncing) return;
                isContextSyncing = true;
                try {
                    let $form = $(this).closest('form');
                    syncFromPaymentAccount($form);
                } finally {
                    isContextSyncing = false;
                }
            });

            $(document).on('change', '.journal-currency-select', function() {
                let $selectedOption = $(this).find(':selected');
                let rate = $selectedOption.data('rate') || 1;
                let $form = $(this).closest('form');
                let $rateInput = $form.find('.journal-exchange-rate');
                
                if ($rateInput.length) {
                    $rateInput.val(rate);
                    updateRateInstruction($form);
                }
            });

            $(document).on('input change', '.journal-exchange-rate', function() {
                let $form = $(this).closest('form');
                updateRateInstruction($form);
            });

            // ৬. পেজ লোড হওয়ামাত্র ইনিশিয়াল সিঙ্ক
            $('form').has('.journal-branch-select').each(function() {
                let $form = $(this);
                if (!isEditMode) {
                    syncFromBranch($form, false);
                } else {
                    updateRateInstruction($form);
                }
            });
        });
    </script>
@endpush