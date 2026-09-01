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
                                <span>80%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width:80%; background-color: #75d818"></div>
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
                            <div class="setup-step complete">
                                <div class="step-icon"><i class="bi bi-check"></i></div>
                                <div class="step-content">
                                    <h6>Accounting Settings</h6>
                                    <p>Configure accounting preferences, numbering and financial options.</p>
                                </div>
                            </div>
                            <div class="setup-step complete">
                                <div class="step-icon"><i class="bi bi-diagram-3"></i></div>
                                <div class="step-content">
                                    <h6>Branch Settings</h6>
                                    <p>Create your primary business branch.</p>
                                </div>
                            </div>
                            <div class="setup-step active">
                                <div class="step-icon"><i class="bi bi-calendar-week"></i></div>
                                <div class="step-content">
                                    <h6>Opening Balance</h6>
                                    <p>Create your opening balance.</p>
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
                        <form action="{{ route('setup.branch.store') }}" method="POST" class="mt-auto pt-3 form_section">
                            <div>
                                <h4 class="fw-bold">Opening Balance</h4>
                            </div>
                            @csrf
                            {{-- Opening Date --}}
                            <div class="card shadow-sm mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Opening Information</h6>
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Opening Date <span class="text-danger">*</span>
                                            </label>

                                            <input type="date" name="opening_date" class="form-control" required>
                                        </div>

                                    </div>

                                </div>
                            </div>


                            {{-- Cash / Bank / Mobile --}}
                            <div class="card shadow-sm mb-3">

                                <div class="card-header">
                                    <h6 class="mb-0">
                                        Cash, Bank & Mobile Balances
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        @foreach ($cashAccounts as $account)
                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">
                                                    {{ $account->account_name }}
                                                </label>

                                                <input type="number" step="0.01" min="0"
                                                    class="form-control text-end"
                                                    name="opening_balance[{{ $account->id }}]" value="0.00">

                                            </div>
                                        @endforeach

                                    </div>

                                </div>

                            </div>


                            {{-- Accounts Receivable / Payable --}}
                            <div class="card shadow-sm mb-3">

                                <div class="card-header">
                                    <h6 class="mb-0">
                                        Receivables & Payables
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        <div class="col-md-6 mb-3">

                                            <label class="form-label">

                                                Accounts Receivable

                                            </label>

                                            <input type="number" step="0.01" min="0"
                                                class="form-control text-end" name="accounts_receivable" value="0.00">

                                            <small class="text-muted">
                                                Total customer due. Allocate customer-wise later.
                                            </small>

                                        </div>

                                        <div class="col-md-6 mb-3">

                                            <label class="form-label">

                                                Accounts Payable

                                            </label>

                                            <input type="number" step="0.01" min="0"
                                                class="form-control text-end" name="accounts_payable" value="0.00">

                                            <small class="text-muted">
                                                Total supplier payable. Allocate supplier-wise later.
                                            </small>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            {{-- Fixed Assets --}}
                            <div class="card shadow-sm mb-3">

                                <div class="card-header">
                                    <h6 class="mb-0">
                                        Fixed Assets
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        @foreach ($fixedAssetAccounts as $account)
                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">
                                                    {{ $account->account_name }}
                                                </label>

                                                <input type="number" step="0.01" min="0"
                                                    class="form-control text-end"
                                                    name="opening_balance[{{ $account->id }}]" value="0.00">

                                            </div>
                                        @endforeach

                                    </div>

                                </div>

                            </div>


                            {{-- Other Assets --}}
                            <div class="card shadow-sm mb-3">

                                <div class="card-header">
                                    <h6 class="mb-0">
                                        Other Assets
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        @foreach ($otherAssetAccounts as $account)
                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">
                                                    {{ $account->account_name }}
                                                </label>

                                                <input type="number" step="0.01" min="0"
                                                    class="form-control text-end"
                                                    name="opening_balance[{{ $account->id }}]" value="0.00">

                                            </div>
                                        @endforeach

                                    </div>

                                </div>

                            </div>


                            {{-- Other Liabilities --}}
                            <div class="card shadow-sm mb-3">

                                <div class="card-header">
                                    <h6 class="mb-0">
                                        Other Liabilities
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <div class="row">

                                        @foreach ($otherLiabilityAccounts as $account)
                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">
                                                    {{ $account->account_name }}
                                                </label>

                                                <input type="number" step="0.01" min="0"
                                                    class="form-control text-end"
                                                    name="opening_balance[{{ $account->id }}]" value="0.00">

                                            </div>
                                        @endforeach

                                    </div>

                                </div>

                            </div>


                            {{-- Summary --}}
                            <div class="card shadow-sm">

                                <div class="card-header">
                                    <h6 class="mb-0">
                                        Opening Balance Summary
                                    </h6>
                                </div>

                                <div class="card-body">

                                    <table class="table table-bordered mb-0">

                                        <tr>
                                            <th>Total Assets</th>
                                            <td class="text-end" id="totalAssets">
                                                0.00
                                            </td>
                                        </tr>

                                        <tr>
                                            <th>Total Liabilities</th>
                                            <td class="text-end" id="totalLiabilities">
                                                0.00
                                            </td>
                                        </tr>

                                        <tr class="table-primary">

                                            <th>Calculated Opening Balance Equity</th>

                                            <td class="text-end fw-bold" id="openingEquity">

                                                0.00

                                            </td>

                                        </tr>

                                    </table>

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
            flatpickr("#opening_date", {
                defaultDate: 'today',
                dateFormat: 'd-m-Y',
            });
        })
    </script>
@endpush
