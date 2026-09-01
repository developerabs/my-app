@extends('backend.layouts.main')

@section('title', __('file.title.balance_sheet'))

@push('css')
<style>
    .qb-report-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .qb-table { font-size: 13.5px; color: #2d3748; margin-bottom: 0; }
    .qb-table th { background-color: #f8fafc; color: #4a5568; font-weight: 600; text-transform: uppercase; font-size: 11.5px; border-bottom: 2px solid #e2e8f0; padding: 10px 14px; }
    .qb-section-header { background-color: #f1f5f9; font-weight: 700; color: #0f172a; cursor: pointer; user-select: none; }
    .qb-section-header:hover { background-color: #e2e8f0; }
    .qb-row td { padding: 8px 14px; border-bottom: 1px solid #f1f5f9; }
    .qb-sub-row td { padding: 7px 14px 7px 28px; color: #334155; border-bottom: 1px solid #f8fafc; }
    .qb-sub-row a { color: #334155; text-decoration: none; }
    .qb-sub-row a:hover { color: #2563eb; text-decoration: underline; }
    .qb-total-row { font-weight: 700; background-color: #f8fafc; border-top: 1px solid #cbd5e0; border-bottom: 2px solid #cbd5e0; }
    
    .toggle-icon { transition: transform 0.2s ease; display: inline-block; width: 14px; font-size: 10px; color: #64748b; transform: rotate(0deg); }
    .qb-section-header.collapsed .toggle-icon,
    .qb-row.collapsed .toggle-icon { transform: rotate(-90deg); }

    @media print {
        body * { visibility: hidden; }
        #printable-balance-sheet, #printable-balance-sheet * { visibility: visible; }
        #printable-balance-sheet { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }
        .no-print { display: none !important; }
    }
</style>
@endpush

@section('content')

    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.balance_sheet') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.balance_sheet_desc') }}
        @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-6">

            <!-- Filter and Print Section -->
            <div class="qb-report-card p-3 mb-3 no-print">
                <form action="{{ route('reports.balance-sheet') }}" method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="as_of_date" class="col-form-label fw-semibold small">As Of Date:</label>
                    </div>
                    <div class="col-auto">
                        <!-- Value passes ISO Y-m-d, Flatpickr altInput displays formatted date in UI -->
                        <input type="text" id="as_of_date" name="as_of_date"
                            class="form-control form-control-sm flatpickr-single" value="{{ $asOfDate }}">
                    </div>

                    <div class="col-auto">
                        <label for="branch_id" class="col-form-label fw-semibold small">Branch:</label>
                    </div>
                    <div class="col-auto">
                        <select id="branch_id" name="branch_id" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name ?? $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fa-solid fa-filter me-1"></i> Run Report</button>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" onclick="handlePrint()" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-print me-1"></i> Print / PDF</button>
                    </div>
                </form>
            </div>

            <!-- Report Main Card -->
            <div class="qb-report-card p-4" id="printable-balance-sheet">
                <div class="text-center mb-4 pb-3 border-bottom">
                    <h4 class="fw-bold text-dark mb-1">{{ $general_settings['company_name'] ?? 'Company Name' }}</h4>
                    <h5 class="text-secondary fw-semibold mb-1">Balance Sheet</h5>
                    <p class="text-muted small mb-0">
                        As of {{ formatDate($asOfDate) }} 
                        @if($branchId && isset($branches))
                            | Branch: <strong>{{ $branches->firstWhere('id', $branchId)?->name ?? 'Selected Branch' }}</strong>
                        @else
                            | <strong>All Branches</strong>
                        @endif
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table qb-table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 75%;">Account</th>
                                <th class="text-end" style="width: 25%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- ASSETS SECTION --}}
                            <tr class="qb-section-header collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseAssets" aria-expanded="false" style="cursor: pointer;">
                                <td><i class="fa-solid fa-chevron-down toggle-icon me-1"></i> ASSETS</td>
                                <td></td>
                            </tr>
                            <tr class="collapse" id="collapseAssets">
                                <td colspan="2" class="p-0 border-0">
                                    <table class="table mb-0">
                                        @foreach ($reportData['assets'] as $assetGroup)
                                            @include('backend.accounting.partials.balance-sheet-node', ['node' => $assetGroup, 'level' => 1])
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                            <tr class="qb-total-row">
                                <td class="ps-4">Total Assets</td>
                                <td class="text-end">{{ number_format($reportData['total_asset'], 2) }}</td>
                            </tr>

                            {{-- LIABILITIES SECTION --}}
                            <tr class="qb-section-header collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseLiabilities" aria-expanded="false" style="cursor: pointer;">
                                <td><i class="fa-solid fa-chevron-down toggle-icon me-1"></i> LIABILITIES</td>
                                <td></td>
                            </tr>
                            <tr class="collapse" id="collapseLiabilities">
                                <td colspan="2" class="p-0 border-0">
                                    <table class="table mb-0">
                                        @foreach ($reportData['liabilities'] as $liabilityGroup)
                                            @include('backend.accounting.partials.balance-sheet-node', ['node' => $liabilityGroup, 'level' => 1])
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                            <tr class="qb-total-row">
                                <td class="ps-4">Total Liabilities</td>
                                <td class="text-end">{{ number_format($reportData['total_liability'], 2) }}</td>
                            </tr>

                            {{-- EQUITY SECTION --}}
                            <tr class="qb-section-header collapsed" data-bs-toggle="collapse"
                                data-bs-target="#collapseEquity" aria-expanded="false" style="cursor: pointer;">
                                <td><i class="fa-solid fa-chevron-down toggle-icon me-1"></i> EQUITY</td>
                                <td></td>
                            </tr>
                            <tr class="collapse" id="collapseEquity">
                                <td colspan="2" class="p-0 border-0">
                                    <table class="table mb-0">
                                        @foreach ($reportData['equities'] as $equityGroup)
                                            @include('backend.accounting.partials.balance-sheet-node', ['node' => $equityGroup, 'level' => 1])
                                        @endforeach

                                        @if ($reportData['prior_years_retained_earnings'] != 0)
                                            <tr class="qb-sub-row">
                                                <td style="width: 75%; padding-left: 28px;">Prior Years Retained Earnings</td>
                                                <td class="text-end" style="width: 25%;">{{ number_format($reportData['prior_years_retained_earnings'], 2) }}</td>
                                            </tr>
                                        @endif

                                        @if ($reportData['net_income'] != 0)
                                            <tr class="qb-sub-row">
                                                <td style="width: 75%; padding-left: 28px;">Current Year Net Income</td>
                                                <td class="text-end" style="width: 25%;">{{ number_format($reportData['net_income'], 2) }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                            <tr class="qb-total-row">
                                <td class="ps-4">Total Equity</td>
                                <td class="text-end">{{ number_format($reportData['total_equity'], 2) }}</td>
                            </tr>

                            {{-- TOTAL LIABILITIES & EQUITY --}}
                            <tr class="qb-total-row table-active" style="background-color: #edf2f7; font-size: 14.5px;">
                                <td>TOTAL LIABILITIES & EQUITY</td>
                                <td class="text-end">{{ number_format($reportData['total_liabilities_and_equity'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr(".flatpickr-single", {
                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                dateFormat: "Y-m-d",
                allowInput: true
            });
        });

        function handlePrint() {
            const collapseElements = document.querySelectorAll('#printable-balance-sheet .collapse');
            let hiddenElements = [];

            collapseElements.forEach(el => {
                const balanceCells = el.querySelectorAll('.text-end');
                let hasNonZero = false;

                balanceCells.forEach(cell => {
                    const val = parseFloat(cell.innerText.replace(/,/g, '')) || 0;
                    if (val !== 0) {
                        hasNonZero = true;
                    }
                });

                const targetId = '#' + el.id;
                const headerRow = document.querySelector(`[data-bs-target="${targetId}"]`);
                let totalRow = el.nextElementSibling;

                if (!hasNonZero) {
                    el.style.display = 'none';
                    hiddenElements.push(el);

                    if (headerRow) {
                        headerRow.style.display = 'none';
                        hiddenElements.push(headerRow);
                    }
                    if (totalRow && totalRow.classList.contains('qb-total-row')) {
                        totalRow.style.display = 'none';
                        hiddenElements.push(totalRow);
                    }
                } else {
                    el.classList.add('show');
                    if (headerRow) {
                        headerRow.classList.remove('collapsed');
                        headerRow.setAttribute('aria-expanded', 'true');
                    }
                }
            });

            const allRows = document.querySelectorAll('#printable-balance-sheet .qb-sub-row, #printable-balance-sheet .qb-row');
            allRows.forEach(row => {
                const balanceCell = row.querySelector('.text-end');
                if (balanceCell) {
                    const val = parseFloat(balanceCell.innerText.replace(/,/g, '')) || 0;
                    if (val === 0 && !row.classList.contains('qb-total-row') && !row.classList.contains('qb-section-header')) {
                        row.style.display = 'none';
                        hiddenElements.push(row);
                    }
                }
            });

            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    hiddenElements.forEach(item => {
                        item.style.display = '';
                    });
                }, 500);
            }, 400);
        }
    </script>
@endpush