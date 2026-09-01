@extends('backend.layouts.main')

@section('title', __('Trial Balance Report'))

@push('css')
    <style>
        /* 🚀 ERP Compact Table & Tree Styling */
        .tb-table th, .tb-table td { 
            padding: 5px 10px !important; 
            font-size: 12.5px; 
            vertical-align: middle;
        }
        .tb-group-root { background-color: #f1f5f9 !important; font-weight: 700; }
        .tb-group-sub { background-color: #f8fafc !important; }
        .hover-bg-light:hover { background-color: #f1f5f9 !important; }
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .tb-table th, .tb-table td { padding: 3px 6px !important; font-size: 11px; }
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('Trial Balance') }}
        @endslot
        @slot('subtitle')
            {{ __('Verification report for debit and credit equality across all general ledger accounts.') }}
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
            <form action="{{ route('reports.trial-balance') }}" method="GET" id="tb_filter_form">
                <div class="row g-2 align-items-end">
                    <!-- 1. Fiscal Year Picker -->
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

                    <!-- 2. Quick Period Preset Picker -->
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

                    <!-- 3. Custom Date Inputs (Auto Disabled unless 'custom' is selected) -->
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

                    <!-- 4. Branch Filter -->
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

                    <!-- 5. Action Buttons -->
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Audit Verification Banner --}}
    <div class="mb-3">
        @if ($is_balanced)
            <div class="alert alert-success border-success-subtle d-flex align-items-center py-2 px-3 mb-0 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check fs-5 me-2 text-success"></i>
                <div class="small">
                    <strong class="me-1">{{ __('BALANCED') }}:</strong> 
                    {{ __('Total Debit matches Total Credit. Mathematical accuracy verified for the period:') }} 
                    <span class="fw-bold text-dark">[{{ $from_date ? formatDate($from_date) : 'Start' }} - {{ formatDate($to_date) }}]</span>
                </div>
            </div>
        @else
            <div class="alert alert-danger border-danger-subtle d-flex align-items-center py-2 px-3 mb-0 shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation fs-5 me-2 text-danger"></i>
                <div class="small">
                    <strong class="me-1">{{ __('UNBALANCED') }}:</strong> 
                    {{ __('Discrepancy detected! Difference:') }} 
                    <span class="fw-bold text-dark">{{ format_currency($difference) }}</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Main Report Table --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0 tb-table">
                    <thead>
                        <tr>
                            <th width="15%">{{ __('ACCOUNT CODE') }}</th>
                            <th width="45%">{{ __('ACCOUNT NAME / GROUP HIERARCHY') }}</th>
                            <th width="20%" class="text-end">{{ __('DEBIT BALANCE (BDT)') }}</th>
                            <th width="20%" class="text-end">{{ __('CREDIT BALANCE (BDT)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report_tree as $group)
                            @include('backend.accounting.partials._trial_balance_row', ['node' => $group, 'level' => 0])
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fs-3 d-block mb-1"></i>
                                    {{ __('No general ledger transactions found for the selected period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <th colspan="2" class="text-end py-2">{{ __('GRAND TOTAL') }}:</th>
                            <th class="text-end py-2 text-dark fs-6">{{ format_currency($grand_total_debit) }}</th>
                            <th class="text-end py-2 text-dark fs-6">{{ format_currency($grand_total_credit) }}</th>
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
            // Flatpickr Init
            if (typeof flatpickr !== 'undefined') {
                flatpickr(".flatpickr-single", {
                    disableMobile: true,
                    altInput: true,
                    altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                    dateFormat: "Y-m-d",
                    static: true
                });
            }

            // 💡 Quick Period Preset Listener
            $('#period_preset').change(function() {
                let preset = $(this).val();
                if (preset === 'custom') {
                    $('#from_date, #to_date').prop('readonly', false).css('background-color', '#ffffff');
                } else {
                    $('#from_date, #to_date').prop('readonly', true).css('background-color', '#f1f5f9');
                    $('#tb_filter_form').submit(); // প্রিসেট বদলালে অটো-ফিল্টার হবে
                }
            });

            // Fiscal Year বদলালে অটো-ফিল্টার
            $('#fiscal_year_id').change(function() {
                $('#tb_filter_form').submit();
            });
        });
    </script>
@endpush