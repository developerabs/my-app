@extends('backend.layouts.main')

@section('title', __('Profit & Loss Statement'))

@push('css')
    <style>
        .pnl-table th, .pnl-table td { padding: 6px 12px !important; font-size: 13px; vertical-align: middle; }
        .pnl-section-header { background-color: #f1f5f9 !important; font-weight: 700; font-size: 13.5px; }
        .pnl-subtotal-row { background-color: #f8fafc !important; font-weight: 700; }
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .pnl-table th, .pnl-table td { padding: 4px 8px !important; font-size: 11px; }
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('Profit & Loss Statement') }}
        @endslot
        @slot('subtitle')
            {{ __('Income and expense performance statement according to IAS 1 standards.') }}
        @endslot
        @slot('button')
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print me-1">
                <i class="fa-solid fa-print me-1"></i> {{ __('Print') }}
            </button>
        @endslot
    @endcomponent

    {{-- Professional Smart Filter Section --}}
    <div class="card border-0 shadow-sm mb-3 no-print">
        <div class="card-body p-3">
            <form action="{{ route('reports.profit-loss') }}" method="GET" id="pnl_filter_form">
                <div class="row g-2 align-items-end">
                    <!-- Fiscal Year Picker -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small mb-1">{{ __('Fiscal Year') }}</label>
                        <select name="fiscal_year_id" id="fiscal_year_id" class="form-select form-select-sm">
                            @foreach ($fiscalYears as $fy)
                                <option value="{{ $fy->id }}" {{ ($selectedFiscalYear?->id == $fy->id) ? 'selected' : '' }}>
                                    {{ $fy->title ?? $fy->name }} ({{ $fy->start_date->format('d M Y') }} - {{ $fy->end_date->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Quick Period Preset Picker -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold small mb-1">{{ __('Quick Period') }}</label>
                        <select name="period_preset" id="period_preset" class="form-select form-select-sm fw-semibold">
                            <option value="this_fiscal_year" {{ $period_preset === 'this_fiscal_year' ? 'selected' : '' }}>{{ __('This Fiscal Year (To Date)') }}</option>
                            <option value="full_fiscal_year" {{ $period_preset === 'full_fiscal_year' ? 'selected' : '' }}>{{ __('Full Fiscal Year') }}</option>
                            <option value="this_month" {{ $period_preset === 'this_month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                            <option value="last_month" {{ $period_preset === 'last_month' ? 'selected' : '' }}>{{ __('Last Month') }}</option>
                            <option value="q1" {{ $period_preset === 'q1' ? 'selected' : '' }}>{{ __('Q1 (Months 1-3)') }}</option>
                            <option value="q2" {{ $period_preset === 'q2' ? 'selected' : '' }}>{{ __('Q2 (Months 4-6)') }}</option>
                            <option value="q3" {{ $period_preset === 'q3' ? 'selected' : '' }}>{{ __('Q3 (Months 7-9)') }}</option>
                            <option value="q4" {{ $period_preset === 'q4' ? 'selected' : '' }}>{{ __('Q4 (Months 10-12)') }}</option>
                            <option value="custom" {{ $period_preset === 'custom' ? 'selected' : '' }}>-- {{ __('Custom Date Range') }} --</option>
                        </select>
                    </div>

                    <!-- Custom Date Inputs -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold small mb-1">{{ __('From Date') }}</label>
                        <input type="text" name="from_date" id="from_date" class="form-control form-control-sm flatpickr-single"
                            value="{{ $from_date }}" placeholder="YYYY-MM-DD" {{ $period_preset !== 'custom' ? 'readonly style=background-color:#f1f5f9;' : '' }}>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small mb-1">{{ __('To Date (As of)') }} <span class="text-danger">*</span></label>
                        <input type="text" name="to_date" id="to_date" class="form-control form-control-sm flatpickr-single"
                            value="{{ $to_date }}" required placeholder="YYYY-MM-DD" {{ $period_preset !== 'custom' ? 'readonly style=background-color:#f1f5f9;' : '' }}>
                    </div>

                    <!-- Branch Filter -->
                    <div class="col-md-2">
                        <label class="form-label fw-bold small mb-1">{{ __('Branch') }}</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">-- {{ __('All Branches') }} --</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branch_id == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Button -->
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Net Profit / Loss Banner --}}
    <div class="mb-3">
        @if ($is_profitable)
            <div class="alert alert-success border-success-subtle d-flex justify-content-between align-items-center py-2 px-3 mb-0 shadow-sm">
                <div>
                    <i class="fa-solid fa-circle-check fs-5 me-2 text-success"></i>
                    <strong class="me-1">{{ __('NET PROFIT') }}:</strong> 
                    {{ __('Company has generated net operational profit for the selected period:') }}
                    <span class="fw-bold text-dark">[{{ $from_date ? formatDate($from_date) : 'Start' }} - {{ formatDate($to_date) }}]</span>
                </div>
                <div class="fs-5 fw-bold text-success">{{ format_currency($net_profit) }}</div>
            </div>
        @else
            <div class="alert alert-danger border-danger-subtle d-flex justify-content-between align-items-center py-2 px-3 mb-0 shadow-sm">
                <div>
                    <i class="fa-solid fa-circle-minus fs-5 me-2 text-danger"></i>
                    <strong class="me-1">{{ __('NET LOSS') }}:</strong> 
                    {{ __('Company has incurred net operational loss for the selected period:') }}
                    <span class="fw-bold text-dark">[{{ $from_date ? formatDate($from_date) : 'Start' }} - {{ formatDate($to_date) }}]</span>
                </div>
                <div class="fs-5 fw-bold text-danger">{{ format_currency($net_profit) }}</div>
            </div>
        @endif
    </div>

    {{-- Main P&L Statement Table --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0 pnl-table">
                    <thead>
                        <tr>
                            <th width="15%">{{ __('ACCOUNT CODE') }}</th>
                            <th width="65%">{{ __('ACCOUNT NAME / HIERARCHY') }}</th>
                            <th width="20%" class="text-end">{{ __('AMOUNT (BDT)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- 1. OPERATING REVENUE SECTION --}}
                        <tr class="text-success">
                            <td colspan="2"><i class="fa-solid fa-chart-line me-1"></i> {{ __('1. OPERATING REVENUE / INCOME') }}</td>
                            <td class="text-end">{{ format_currency($total_revenue) }}</td>
                        </tr>
                        @if(!empty($revenue_tree))
                            @include('backend.accounting.partials._pnl_row', ['node' => $revenue_tree, 'level' => 1])
                        @endif

                        {{-- 2. COST OF GOODS SOLD SECTION --}}
                        <tr class="text-danger mt-2">
                            <td colspan="2"><i class="fa-solid fa-boxes-packing me-1"></i> {{ __('2. COST OF GOODS SOLD (COGS)') }}</td>
                            <td class="text-end">({{ format_currency($total_cogs) }})</td>
                        </tr>
                        @if(!empty($cogs_tree))
                            @include('backend.accounting.partials._pnl_row', ['node' => $cogs_tree, 'level' => 1])
                        @endif

                        {{-- GROSS PROFIT SUMMARY BANNER --}}
                        <tr class="fw-bold fs-6">
                            <td colspan="2" class="text-end py-2 text-primary">{{ __('GROSS PROFIT') }} (1 - 2):</td>
                            <td class="text-end py-2 text-primary">{{ format_currency($gross_profit) }}</td>
                        </tr>

                        {{-- 3. OPERATING EXPENSES SECTION --}}
                        <tr class="text-warning-emphasis">
                            <td colspan="2"><i class="fa-solid fa-wallet me-1"></i> {{ __('3. OPERATING EXPENSES') }}</td>
                            <td class="text-end">({{ format_currency($total_expense) }})</td>
                        </tr>
                        @if(!empty($expense_tree))
                            @include('backend.accounting.partials._pnl_row', ['node' => $expense_tree, 'level' => 1])
                        @endif
                    </tbody>
                    <tfoot class="fw-bold">
                        <tr class="fs-6 {{ $is_profitable ? 'table-success-subtle text-success' : 'table-danger-subtle text-danger' }}">
                            <th colspan="2" class="text-end py-3">{{ __('NET PROFIT / (LOSS) BEFORE TAX') }}:</th>
                            <th class="text-end py-3 fs-5">{{ format_currency($net_profit) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(".flatpickr-single", {
                    disableMobile: true,
                    altInput: true,
                    altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                    dateFormat: "Y-m-d",
                    static: true
                });
            }

            $('#period_preset').change(function() {
                let preset = $(this).val();
                if (preset === 'custom') {
                    $('#from_date, #to_date').prop('readonly', false).css('background-color', '#ffffff');
                } else {
                    $('#from_date, #to_date').prop('readonly', true).css('background-color', '#f1f5f9');
                    $('#pnl_filter_form').submit();
                }
            });

            $('#fiscal_year_id').change(function() {
                $('#pnl_filter_form').submit();
            });
        });
    </script>
@endpush