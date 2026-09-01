@extends('layouts.setup')

@push('css')
    <style>
        /* Main page wrapper */
        html,
        body {
            height: 100%;
            margin: 0;
            background: #f4f7fb;
        }

        .setup-page {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Reduced height slightly to make it compact */
        .setup-card {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .08);
            width: 100%;
            max-width: 1000px;
            height: auto;
            min-height: 550px;
        }

        .left-panel {
            padding: 30px;
            background: #fff;
        }

        .right-panel {
            background: linear-gradient(135deg, #0d6efd 0%, #4d84ff 100%);
            color: #fff;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .right-panel p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Keep all original elements */
        .logo-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-circle i {
            font-size: 2rem;
        }

        .progress {
            height: 8px;
            border-radius: 30px;
        }

        .setup-steps {
            margin-top: 20px;
            flex: 0 1 auto;
        }

        .setup-step {
            display: flex;
            gap: 15px;
            position: relative;
            padding-bottom: 15px;
        }

        .setup-step:not(:last-child)::after {
            content: "";
            position: absolute;
            left: 15px;
            top: 20px;
            width: 2px;
            height: calc(100% - 15px);
            background: #d8dde7;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            color: #000000;
            flex-shrink: 0;
            z-index: 2;
        }

        .step-content h6 {
            margin-bottom: 2px;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: left;
        }

        .step-content p {
            margin: 0;
            color: #ffffff;
            font-size: 0.8rem;
            text-align: left;
        }

        .setup-step.active .step-icon {
            background: #fffb2a;
        }

        .setup-step.active .step-icon i {
            color: #000000;
        }

        .setup-step.active .step-content p {
            color: #fffb2a;
        }

        .setup-step.active .step-content h6 {
            color: #fffb2a;
        }

        .setup-step.complete .step-icon {
            background: #2aff58;
        }

        .setup-step.complete .step-icon i {
            color: #000000;
        }

        .setup-step.complete .step-content p {
            color: #2aff58;
        }

        .setup-step.complete .step-content h6 {
            color: #2aff58;
        }

        .form_section {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        @media(max-width:991px) {
            .right-panel {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="setup-page">
        <div class="card setup-card">
            <div class="row g-0 h-100">
                {{-- Right Panel (As it was) --}}
                <div class="col-lg-5">
                    <div class="right-panel h-100">
                        <div class="logo-circle"><i class="bi bi-gear-wide-connected"></i></div>
                        <h4 class="fw-bold">ERP Initial Setup</h4>
                        <p class="opacity-75 mt-2">This wizard will guide you through the initial configuration of your ERP
                            system.</p>
                        <div class="w-100">
                            <div class="d-flex justify-content-between mb-1 small fw-semibold"><span>Setup Progress</span>
                                <span>50%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width:50%; background-color: #d8ff2a"></div>
                            </div>
                        </div>
                        <div class="setup-steps">
                            <div class="setup-step complete">
                                <div class="step-icon"><i class="bi bi-check"></i></div>
                                <div class="step-content">
                                    <h6>Regional Settings</h6>
                                    <p>Configure your country, timezone, language and default currency.</p>
                                </div>
                            </div>
                            <div class="setup-step active">
                                <div class="step-icon"><i class="bi bi-calculator"></i></div>
                                <div class="step-content">
                                    <h6>Accounting Settings</h6>
                                    <p>Configure accounting preferences, numbering and financial options.</p>
                                </div>
                            </div>
                            <div class="setup-step">
                                <div class="step-icon"><i class="bi bi-diagram-3"></i></div>
                                <div class="step-content">
                                    <h6>Branch Settings</h6>
                                    <p>Create your primary business branch.</p>
                                </div>
                            </div>
                            <div class="setup-step">
                                <div class="step-icon"><i class="bi bi-check2-circle"></i></div>
                                <div class="step-content">
                                    <h6>Complete Setup</h6>
                                    <p>Finish configuration and start using ERP.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Left Panel (As it was) --}}
                <div class="col-lg-7">
                    <div class="left-panel h-100">
                        <form action="{{ route('setup.accounting.store') }}" method="POST"
                            class="mt-auto pt-3 form_section">
                            <div>
                                <h4 class="fw-bold">Accounting Settings</h4>
                            </div>
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label for="fiscal_start_from" class="form-label">Fiscal Start From
                                        <span>*</span></label>
                                    <input type="text" class="form-control" name="fiscal_start_from"
                                        id="fiscal_start_from" placeholder="MM-YYYY" value="">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="period_start_from" class="form-label">Period Start From
                                        <span>*</span></label>
                                    <select name="current_period" class="form-control" id="period_start_from">
                                        @for ($month = 1; $month <= 12; $month++)
                                            <option value="{{ $month }}" @selected(now()->month == $month)>
                                                {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="fiscal_year_name" class="form-label">Fiscal Year Name <span>*</span></label>
                                    <input type="text" class="form-control" name="fiscal_year_name" id="fiscal_year_name"
                                        placeholder="E.g 2026-2027">
                                </div>
                                <div class="col-md-12 mb-2">
                                    <label for="account_type" class="form-label">Account Type</label>
                                    <select name="account_type" id="account_type" class="form-select" required>
                                        @foreach (\App\Enums\LedgerAccountType::cases() as $accountType)
                                            @if (in_array($accountType->value, ['cash', 'mobile', 'bank', 'other']))
                                                <option value="{{ $accountType->value }}">
                                                    {{ $accountType->value }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="branch_id" class="form-label">Select Branch</label>
                                    <select name="branch_id" id="branch_id" class="form-select" required>
                                        @foreach ($branches as $branch)
                                           <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="currency_id" class="form-label">Select Currency</label>
                                    <select name="currency" id="currency" class="form-select shadow-sm select2" disabled required>
                                            @foreach ($currencies as $currency)
                                                <option {{ $currency->id == $defaultCurrencyId ? 'selected' : '' }}
                                                    value="{{ $currency->id }}">
                                                    {{ $currency->name . ' - ' . $currency->code }}</option>
                                            @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12 mb-2">
                                    <label for="account_name" class="form-label">Account Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="account_name" id="account_name" class="form-control"
                                        required placeholder="E.g. Hand Cash (Office)">
                                </div>
                                <div class="col-md-12 mb-2">
                                    <label for="account_number" class="form-label">Account Number</label>
                                    <input type="text" name="account_number" id="account_number" class="form-control"
                                        placeholder="E.g. 012365498745">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="opening_balance" class="form-label">Opening Balance</label>
                                    <input type="text" name="opening_balance" id="opening_balance" value="0" class="form-control"
                                        placeholder="E.g 100000">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="opening_balance_date" class="form-label">Opening Date</label>
                                    <input type="text" name="opening_balance_date" id="opening_balance_date"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="row" id="bank_section" style="display: none;">
                                <div class="col-md-12 mb-2">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" name="bank_name" id="bank_name" class="form-control">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="branch_name" class="form-label">Branch Name</label>
                                    <input type="text" name="branch_name" id="branch_name" class="form-control">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="routing_number" class="form-label">Routing Number</label>
                                    <input type="text" name="routing_number" id="routing_number"
                                        class="form-control">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-2">Save & Next <i
                                    class="bi bi-arrow-right-circle me-2"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            const now = new Date();
            const currentMonth = now.getMonth();
            const currentYear = now.getFullYear(); // getFullYear() ব্যবহার করতে হবে
            const targetYear = currentMonth >= 6 ? currentYear : currentYear - 1;
            const defaultFiscalDate = `Jul-${targetYear}`;

            const openingBalancePicker = flatpickr("#opening_balance_date", {
                dateFormat: "Y-m-d",
            });

            // Fiscal Start From
            const fiscalStartPicker = flatpickr("#fiscal_start_from", {
                defaultDate: defaultFiscalDate,

                plugins: [
                    new monthSelectPlugin({
                        shorthand: false,
                        dateFormat: "M-Y",
                        altFormat: "F Y",
                    })
                ],

                onChange: function(selectedDates) {
                    updateOpeningBalanceDate(selectedDates);
                }
            });

            // Fiscal Start অনুযায়ী Opening Balance Date update
            function updateOpeningBalanceDate(selectedDates) {
                if (!selectedDates || !selectedDates.length) {
                    return;
                }

                const fiscalStartDate = selectedDates[0];

                // Fiscal month-এর প্রথম দিন
                const minDate = new Date(
                    fiscalStartDate.getFullYear(),
                    fiscalStartDate.getMonth(),
                    1
                );

                // Minimum date সেট
                openingBalancePicker.set('minDate', minDate);

                // Default হিসেবে Minimum Date select
                openingBalancePicker.setDate(minDate, true);
            }

            // Page load হওয়ার সময়
            updateOpeningBalanceDate(fiscalStartPicker.selectedDates);

            // চেঞ্জ ইভেন্ট লিসেনার
            $('#fiscal_start_from').on('change', function() {
                let fiscalStart = $(this).val();

                if (!fiscalStart) return;
                let parts = fiscalStart.split('-');
                if (parts.length !== 2) return;

                let month = parts[0];
                let year = parseInt(parts[1]);

                let fiscalYear = '';
                if (month === 'Jan') {
                    fiscalYear = year;
                } else {
                    // ফিসকাল ইয়ারের শেষ ২ ডিজিট নিতে চাইলে (year + 1).toString().slice(-2) দিতে পারেন
                    fiscalYear = year + '-' + (year + 1);
                }

                $('#fiscal_year_name').val(fiscalYear);
            });

            // পেজ লোড হওয়ার সাথে সাথে নাম ফিল্ডটি ডিফল্ট ভ্যালু দিয়ে পূরণ করার জন্য ইভেন্টটি ট্রিগার করা হলো
            $('#fiscal_start_from').trigger('change');

            $('#account_type').on('change', function() {
                let type = $(this).find(":selected").val();
                if (type === 'bank') {
                    $('#bank_section').show();
                } else {
                    $('#bank_section').hide();
                }
            })
        });
    </script>
@endpush
