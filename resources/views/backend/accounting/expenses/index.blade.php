@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.expense_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.expense_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.expense_management_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.expense') }}
            </a>
        @endslot
    @endcomponent

    {{-- Filter Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <button class="btn btn-outline-primary d-md-none w-100 mb-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterCollapse">
                <i class="fa-solid fa-filter me-2"></i> {{ __('file.field.show_filters') }}
            </button>

            <div class="collapse d-md-block" id="filterCollapse">
                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">{{ __('file.field.filters') }}:</span>
                            </div>

                            <div class="col-12 col-md-auto" style="min-width: 180px;">
                                <select id="filter-status" data-dt-filter="expense-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="posted">Posted</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('expense-table')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('file.button.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Section --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-hover table-striped nowrap w-100']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <!-- View Expense Details Modal -->
    <div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="fa-solid fa-receipt me-2 text-primary"></i>Expense Voucher Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="expenseDetailsBody">
                    <div class="text-center py-5" id="expenseDetailsLoader">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer no-print">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print me-1"></i> Print Voucher</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        function viewExpense(id) {
            const modal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));
            const body = $('#expenseDetailsBody');
            const loader = $('#expenseDetailsLoader');

            modal.show();
            body.html(loader.html());

            let url = "{{ route('expenses.show', ':expense') }}".replace(':expense', id);
            
            $.get(url, function(response) {
                if (response.success) {
                    const e = response.data;
                    const items = e.items || [];
                    const paymentAcc = e.payment_account || {};
                    const branch = e.branch || {};
                    const attachmentUrl = response.attachment_url;

                    let itemsHtml = items.map((item, idx) => `
                        <tr>
                            <td class="ps-3">${idx + 1}</td>
                            <td class="fw-semibold text-dark">${item.expense_account ? item.expense_account.account_name : 'Expense Account'} (${item.expense_account ? item.expense_account.account_code : ''})</td>
                            <td>${item.description || '-'}</td>
                            <td class="text-end pe-3 fw-bold">${formatMoney(parseFloat(item.base_amount).toFixed(2))}</td>
                        </tr>
                    `).join('');

                    let attachmentHtml = '';
                    if (attachmentUrl) {
                        attachmentHtml = `
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="fw-bold text-secondary small text-uppercase mb-2"><i class="fa-solid fa-paperclip me-1"></i> Attached Document</h6>
                                <a href="${attachmentUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-download me-1"></i> View / Download Attachment
                                </a>
                            </div>`;
                    }

                    let html = `
                        <div class="p-2" id="printable-expense-voucher">
                            <div class="row border-bottom pb-3 mb-3 align-items-center">
                                <div class="col-md-6">
                                    <h4 class="fw-bold text-primary mb-1">${e.expense_no}</h4>
                                    <span class="badge bg-success-subtle text-success border border-success">Posted</span>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="text-muted small">Date: <strong class="text-dark">${formatedDate(e.expense_date)}</strong></div>
                                    <div class="text-muted small">Branch: <strong class="text-dark">${branch.name || 'Main Branch'}</strong></div>
                                    <div class="text-muted small">Created By: <strong class="text-dark">${e.creator ? e.creator.name : 'System'}</strong></div>
                                </div>
                            </div>

                            <div class="row bg-light rounded p-3 mb-4 mx-0">
                                <div class="col-md-6">
                                    <small class="text-muted text-uppercase fw-bold">Payment Source</small>
                                    <h6 class="fw-bold text-dark mb-0">${paymentAcc.account_name || 'N/A'}</h6>
                                    <small class="text-secondary">Type: ${paymentAcc.account_type ? paymentAcc.account_type.toUpperCase() : 'CASH'}</small>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted text-uppercase fw-bold">Payment Details</small>
                                    <div class="small fw-semibold text-dark">Method: ${e.payment_method ? e.payment_method.toUpperCase() : 'CASH'}</div>
                                    <div class="small text-muted">Ref/Txn No: ${e.reference_no || 'N/A'}</div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-secondary small text-uppercase mb-2">Expense Line Items</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3" width="5%">#</th>
                                            <th width="40%">Expense Category</th>
                                            <th width="35%">Description</th>
                                            <th class="text-end pe-3" width="20%">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsHtml}
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="3" class="text-end py-2">Total Expense:</td>
                                            <td class="text-end pe-3 py-2 text-primary fs-14">${formatMoney(parseFloat(e.total_base_amount).toFixed(2))}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            ${e.note ? `<div class="mb-2"><small class="fw-bold text-secondary">Note / Remarks:</small> <span class="text-dark small">${e.note}</span></div>` : ''}
                            ${attachmentHtml}
                        </div>`;

                    body.html(html);
                } else {
                    body.html('<div class="alert alert-danger">Failed to load expense details.</div>');
                }
            }).fail(function() {
                body.html('<div class="alert alert-danger">Something went wrong while fetching data.</div>');
            });
        }
    </script>
@endpush