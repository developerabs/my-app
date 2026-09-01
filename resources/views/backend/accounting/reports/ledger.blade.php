@extends('backend.layouts.main')

@section('title', __('file.title.ledger'))

@push('css')
    <style>
        .ledger-header-card {
            background: #fff;
            border: 1px solid #e3e6ef;
            border-radius: 6px;
        }

        .table-ledger th {
            background-color: #f8f9fc;
            color: #6e707e;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .table-ledger td {
            vertical-align: middle;
            font-size: 0.875rem;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .tr-reversed {
            background-color: #fef2f2 !important;
        }

        .tr-reversal {
            background-color: #fffbeb !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .table-ledger th {
                background-color: #f1f3f9 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
@endpush

@section('content')

    @component('backend.layouts.partials.header')
        @slot('title')
            @if (!empty($reportData['sub_ledger_id']))
                Sub-Ledger: <span
                    class="text-primary">{{ $reportData['sub_ledger']->name ?? ($reportData['sub_ledger']->company_name ?? 'Detail Ledger') }}</span>
            @else
                Account Ledger
            @endif
        @endslot
        @slot('subtitle')
            Control Account: <strong>{{ $reportData['account']->account_code }}</strong> -
            {{ $reportData['account']->account_name }}
            @if (!empty($reportData['account']->currency))
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">{{ $reportData['account']->currency->code }}</span>
            @endif
            @if (!empty($reportData['sub_ledger_id']))
                | Sub-Ledger: <strong>{{ $reportData['sub_ledger']->name ?? ($reportData['sub_ledger']->company_name ?? $reportData['sub_ledger_id']) }}</strong>
            @endif
        @endslot
        @slot('button')
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-print me-1"></i> Print / PDF
                </button>
            </div>
        @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-md-12">

            <!-- 🖨️ Audit Print Header (Visible only on Print/PDF) -->
            <div class="d-none d-print-block mb-3 border-bottom pb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $general_settings['company_name'] ?? config('app.name') }}</h4>
                        <div class="small text-muted">
                            Branch: <strong>{{ !empty($branchId) ? ($branches->firstWhere('id', $branchId)?->name ?? 'Selected Branch') : 'Consolidated (All Branches)' }}</strong>
                        </div>
                    </div>
                    <div class="text-end small text-muted">
                        <div>Period: <strong>{{ formatDate($fromDate) }}</strong> to <strong>{{ formatDate($toDate) }}</strong></div>
                        <div>Printed: {{ now()->format('d-M-Y h:i A') }} by {{ auth()->user()->name }}</div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card ledger-header-card mb-4 no-print shadow-sm">
                <div class="card-body py-3">
                    <form
                        action="{{ !empty($reportData['sub_ledger_id']) ? route('reports.subledger', ['account_id' => $reportData['account']->id, 'sub_ledger_id' => $reportData['sub_ledger_id']]) : route('reports.ledger', ['account_id' => $reportData['account']->id]) }}"
                        method="GET" class="row g-3 align-items-center">

                        @if (!empty($reportData['sub_ledger_id']))
                            <input type="hidden" name="sub_ledger_id" value="{{ $reportData['sub_ledger_id'] }}">
                        @endif

                        <div class="col-auto">
                            <label for="from_date" class="col-form-label fw-semibold small">From:</label>
                        </div>
                        <div class="col-auto">
                            <input type="text" id="from_date" name="from_date"
                                class="form-control form-control-sm flatpickr-range" value="{{ $fromDate }}">
                        </div>

                        <div class="col-auto">
                            <label for="to_date" class="col-form-label fw-semibold small">To:</label>
                        </div>
                        <div class="col-auto">
                            <input type="text" id="to_date" name="to_date"
                                class="form-control form-control-sm flatpickr-range" value="{{ $toDate }}">
                        </div>

                        <div class="col-auto">
                            <label for="branch_id" class="col-form-label fw-semibold small">Branch:</label>
                        </div>
                        <div class="col-auto">
                            <select id="branch_id" name="branch_id" class="form-select form-select-sm">
                                <option value="">{{ user_can_access_all_branches() ? 'Consolidated (All Branches)' : 'All My Permitted Branches' }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name ?? $branch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-filter me-1"></i> Run Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ledger Table Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-ledger table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="py-3 ps-3">Date</th>
                                    <th class="py-3">Type / Status</th>
                                    <th class="py-3">No. / Ref</th>
                                    <th class="py-3">Description / Narration</th>
                                    <th class="py-3 text-end">Debit</th>
                                    <th class="py-3 text-end">Credit</th>
                                    <th class="py-3 text-end pe-3">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 🟢 1. Opening Balance Row (Multi-Currency Safe) -->
                                <tr class="fw-semibold bg-light">
                                    <td class="ps-3">{{ formatDate($fromDate) }}</td>
                                    <td><span class="badge bg-secondary">Opening</span></td>
                                    <td>-</td>
                                    <td>
                                        Balance brought forward
                                        {{ $reportData['entries']->currentPage() > 1 ? '(Page ' . $reportData['entries']->currentPage() . ')' : '' }}
                                    </td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end pe-3">
                                        @if(!empty($reportData['is_account_foreign']))
                                            <div class="fw-bold text-dark">
                                                {{ format_currency($reportData['page_opening_native_balance'], $reportData['account']->currency) }}
                                            </div>
                                            <small class="text-muted d-block fw-normal" style="font-size: 10px;">
                                                ≈ {{ format_currency($reportData['page_opening_balance']) }}
                                            </small>
                                        @else
                                            <div class="fw-bold text-dark">
                                                {{ format_currency($reportData['page_opening_balance']) }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>

                                <!-- 🟢 2. Transaction Lines Loop -->
                                @forelse($reportData['entries'] as $entry)
                                    @php
                                        $voucher = $entry->voucher;
                                        $voucherCurrency = $voucher?->currency;
                                        $accountCurrency = $reportData['account']->currency;
                                        
                                        $isReversed =
                                            $entry->status === 'reversed' ||
                                            $voucher?->status === \App\Enums\JournalVoucherStatus::REVERSED ||
                                            $voucher?->status?->value === 'reversed' ||
                                            !empty($voucher?->reversed_by_voucher);

                                        $isReversal =
                                            !empty($voucher?->reversal_of) ||
                                            str_contains(strtolower($entry->narration ?? ''), 'reversal of');

                                        // চেক করা হচ্ছে লেনদেনের কারেন্সি এবং ব্যাংক একাউন্টের কারেন্সি ভিন্ন কি না (e.g. SAR vs USD)
                                        $isCrossCurrency = $voucherCurrency && $accountCurrency && ($voucherCurrency->id !== $accountCurrency->id);
                                        $voucherRate = (float) ($voucher?->exchange_rate ?? 1);
                                    @endphp

                                    <tr class="{{ $isReversed ? 'tr-reversed' : ($isReversal ? 'tr-reversal' : '') }}">
                                        <td class="ps-3 text-nowrap">{{ formatDate($entry->transaction_date) }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $entry->voucher_type->value ?? $entry->voucher_type }}
                                            </span>
                                            @if ($isReversed)
                                                <span class="badge bg-danger text-white ms-1" title="This entry was reversed">
                                                    <i class="fa-solid fa-ban me-1"></i>Reversed
                                                </span>
                                            @elseif($isReversal)
                                                <span class="badge bg-warning text-dark ms-1" title="This is a reversal entry">
                                                    <i class="fa-solid fa-rotate-left me-1"></i>Reversal
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-primary">{{ $entry->voucher_no }}</span>
                                            @if (!empty($entry->reference_no))
                                                <br><small class="text-muted">Ref: {{ $entry->reference_no }}</small>
                                            @endif
                                        </td>

                                        <!-- 🟢 Description Column: আসল ভাউচারের মুদ্রা ও রেট স্পষ্ট ব্যাজে প্রদর্শন (যেমন: 50.00 SAR @ 32.43) -->
                                        <td>
                                            <span class="{{ $isReversed ? 'text-decoration-line-through text-muted' : '' }}">
                                                {{ $entry->narration ?? '-' }}
                                            </span>

                                            @if($isCrossCurrency && $voucher)
                                                <div class="mt-1">
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 10px;" title="Original Voucher Transaction Source">
                                                        <i class="fa-solid fa-coins me-1"></i>Original: {{ format_currency($voucher->total_debit > 0 ? $voucher->total_debit : $voucher->total_credit, $voucherCurrency) }} (@ {{ number_format($voucherRate, 2) }})
                                                    </span>
                                                </div>
                                            @endif

                                            @if($entry->branch)
                                                <div class="small text-muted mt-1">
                                                    <i class="fa-solid fa-code-branch me-1 text-secondary"></i>{{ $entry->branch->name }}
                                                </div>
                                            @endif

                                            @if ($isReversed && $voucher?->reversedVoucher)
                                                <div class="small text-danger fst-italic mt-1">
                                                    Cancelled by {{ $voucher->reversedVoucher->voucher_no }}
                                                </div>
                                            @elseif($isReversal && $voucher?->reversalOf)
                                                <div class="small text-muted fst-italic mt-1">
                                                    Reversal of {{ $voucher->reversalOf->voucher_no }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- 🟢 Debit Column: অ্যাকাউন্টের নিজস্ব কারেন্সিতে ($) এবং অ্যাকাউন্টের কার্যকর রেট (@ 121.60) -->
                                        <td class="text-end {{ $isReversed ? 'text-muted' : '' }}">
                                            @if((float)$entry->base_debit > 0)
                                                <div class="fw-bold text-dark">
                                                    {{ format_currency($entry->debit, $accountCurrency ?? $entry->currency) }}
                                                </div>
                                                @if(!empty($reportData['is_account_foreign']))
                                                    <small class="text-muted d-block" style="font-size: 10px;">
                                                        ≈ {{ format_currency($entry->base_debit) }} <span class="text-secondary">(@ {{ number_format($entry->row_account_rate ?? $entry->row_exchange_rate, 2) }})</span>
                                                    </small>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <!-- 🟢 Credit Column: অ্যাকাউন্টের নিজস্ব কারেন্সিতে ($) এবং অ্যাকাউন্টের কার্যকর রেট (@ 121.60) -->
                                        <td class="text-end {{ $isReversed ? 'text-muted' : '' }}">
                                            @if((float)$entry->base_credit > 0)
                                                <div class="fw-bold text-dark">
                                                    {{ format_currency($entry->credit, $accountCurrency ?? $entry->currency) }}
                                                </div>
                                                @if(!empty($reportData['is_account_foreign']))
                                                    <small class="text-muted d-block" style="font-size: 10px;">
                                                        ≈ {{ format_currency($entry->base_credit) }} <span class="text-secondary">(@ {{ number_format($entry->row_account_rate ?? $entry->row_exchange_rate, 2) }})</span>
                                                    </small>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <!-- 🟢 Running Balance Column -->
                                        <td class="text-end pe-3 fw-semibold">
                                            @if(!empty($reportData['is_account_foreign']))
                                                <div class="text-primary">
                                                    {{ format_currency($entry->row_running_balance, $accountCurrency) }}
                                                </div>
                                                <small class="text-muted d-block fw-normal" style="font-size: 10px;">
                                                    ≈ {{ format_currency($entry->row_running_base_balance) }}
                                                </small>
                                            @else
                                                <div class="text-primary">
                                                    {{ format_currency($entry->row_running_balance) }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No transactions found for this date range.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            
                            <!-- 🟢 3. Table Footer Summary (Fixed USD $374.07 vs BDT 45,484.29) -->
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="4" class="text-end py-3">Total / Ending Balance:</td>
                                    <td class="text-end py-3">{{ format_currency($reportData['total_debit']) }}</td>
                                    <td class="text-end py-3">{{ format_currency($reportData['total_credit']) }}</td>
                                    <td class="text-end py-3 pe-3 text-primary">
                                        @if(!empty($reportData['is_account_foreign']))
                                            <div>{{ format_currency($reportData['ending_native_balance'], $reportData['account']->currency) }}</div>
                                            <small class="text-muted d-block fw-normal" style="font-size: 11px;">
                                                ≈ {{ format_currency($reportData['ending_balance']) }}
                                            </small>
                                        @else
                                            <div>{{ format_currency($reportData['ending_balance']) }}</div>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Server-Side Pagination Links -->
                    <div class="px-3 py-3 border-top no-print d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Showing {{ $reportData['entries']->firstItem() ?? 0 }} to
                            {{ $reportData['entries']->lastItem() ?? 0 }} of {{ $reportData['entries']->total() }} entries
                        </div>
                        <div>
                            {{ $reportData['entries']->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(".flatpickr-range", {
                    disableMobile: true,
                    altInput: true,
                    altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                    dateFormat: "Y-m-d",
                    allowInput: true
                });
            }
        });
    </script>
@endpush