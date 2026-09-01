@extends('backend.layouts.main')

@section('title')
    {{ Lang::has('file.title.purchase_management') ? __('file.title.purchase_management') : 'Purchase Management' }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
    <style>
        table.dataTable.nowrap th[title="Balance"],
        table.dataTable.nowrap th.text-end {
            text-align: end !important;
        }

        .flatpickr-wrapper {
            display: block !important;
            width: 100%;
        }

        #purchaseViewModal .modal-body {
            padding: 1.25rem !important;
        }

        #purchaseViewModal .modal-content {
            border-radius: 12px;
        }

        .custom-scrollbar::-webkit-scrollbar {
            height: 5px;
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .table-hover tbody tr:hover {
            background-color: #fcfcfc;
        }

        /* Prevent table header text from wrapping awkwardly */
        #purchase-table th {
            white-space: nowrap !important;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ Lang::has('file.title.purchase_management') ? __('file.title.purchase_management') : 'Purchase Management' }}
        @endslot
        @slot('subtitle')
            {{ Lang::has('file.title.purchase_management_desc') ? __('file.title.purchase_management_desc') : 'Manage purchase invoices, stock receipts, and supplier balances.' }}
        @endslot
        @slot('button')
            <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ Lang::has('file.button.create') ? __('file.button.create') : 'Create' }} {{ Lang::has('file.purchase') ? __('file.purchase') : 'Purchase' }}
            </a>
        @endslot
    @endcomponent

    {{-- Filter Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            {{-- Mobile Toggle Button --}}
            <button class="btn btn-outline-primary d-md-none w-100 mb-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterCollapse">
                <i class="fa-solid fa-filter me-2"></i> {{ Lang::has('file.field.show_filters') ? __('file.field.show_filters') : 'Show Filters' }}
            </button>

            <div class="collapse d-md-block" id="filterCollapse">
                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            {{-- Filter Icon & Title (Desktop Only) --}}
                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">{{ Lang::has('file.field.filters') ? __('file.field.filters') : 'Filters' }}:</span>
                            </div>

                            {{-- Branch Filter --}}
                            @if(isset($branches) && count($branches) > 1)
                                <div class="col-12 col-md-auto" style="min-width: 150px;">
                                    <select id="filter-branch" data-dt-filter="purchase-table" class="form-select form-select-sm shadow-none">
                                        <option value="">-- {{ Lang::has('file.option.all_branches') ? __('file.option.all_branches') : 'All Branches' }} --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Supplier Filter --}}
                            <div class="col-12 col-md-auto" style="min-width: 180px;">
                                <select id="filter-supplier" data-dt-filter="purchase-table" class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ Lang::has('file.option.all_suppliers') ? __('file.option.all_suppliers') : 'All Suppliers' }} --</option>
                                    @isset($suppliers)
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>

                            {{-- Purchase Status Filter --}}
                            <div class="col-12 col-md-auto" style="min-width: 140px;">
                                <select id="filter-purchase-status" data-dt-filter="purchase-table" class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ Lang::has('file.option.all_status') ? __('file.option.all_status') : 'All Status' }} --</option>
                                    <option value="received">{{ Lang::has('file.option.received') ? __('file.option.received') : 'Received' }}</option>
                                    <option value="partial">{{ Lang::has('file.option.partial') ? __('file.option.partial') : 'Partial' }}</option>
                                    <option value="ordered">{{ Lang::has('file.option.ordered') ? __('file.option.ordered') : 'Ordered' }}</option>
                                    <option value="pending">{{ Lang::has('file.option.pending') ? __('file.option.pending') : 'Pending' }}</option>
                                    <option value="cancelled">{{ Lang::has('file.option.cancelled') ? __('file.option.cancelled') : 'Cancelled' }}</option>
                                </select>
                            </div>

                            {{-- Payment Status Filter --}}
                            <div class="col-12 col-md-auto" style="min-width: 140px;">
                                <select id="filter-payment-status" data-dt-filter="purchase-table" class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ Lang::has('file.option.all_payments') ? __('file.option.all_payments') : 'All Payments' }} --</option>
                                    <option value="paid">{{ Lang::has('file.option.paid') ? __('file.option.paid') : 'Paid' }}</option>
                                    <option value="partially_paid">{{ Lang::has('file.option.partially_paid') ? __('file.option.partially_paid') : 'Partially Paid' }}</option>
                                    <option value="unpaid">{{ Lang::has('file.option.unpaid') ? __('file.option.unpaid') : 'Unpaid' }}</option>
                                </select>
                            </div>

                            {{-- Date Range Filter (Fixed Flex Layout) --}}
                            <div class="col-12 col-md-auto">
                                <div class="input-group input-group-sm flex-nowrap" style="min-width: 250px;">
                                    <input type="text" id="from-date" data-dt-filter="purchase-table" class="form-control shadow-none date-picker text-center" placeholder="From Date">
                                    <span class="input-group-text bg-light text-muted px-2">to</span>
                                    <input type="text" id="to-date" data-dt-filter="purchase-table" class="form-control shadow-none date-picker text-center" placeholder="To Date">
                                </div>
                            </div>

                            {{-- Reset Button --}}
                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto shadow-none" onclick="resetFilters('purchase-table')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ Lang::has('file.button.reset') ? __('file.button.reset') : 'Reset' }}
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
                        {{ $dataTable->table(['class' => 'table table-hover table-striped nowrap w-100', 'id' => 'purchase-table']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    {{-- Purchase Quick Details Modal --}}
    <div class="modal fade" id="purchaseViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold" id="purchaseModalTitle">
                        <i class="fa-solid fa-file-invoice text-primary me-2"></i> {{ __('Purchase Invoice Details') }}
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 custom-scrollbar" id="purchaseModalContent" style="max-height: 80vh; overflow-y: auto;">
                    {{-- AJAX Content Injected Here --}}
                </div>
                <div class="modal-footer border-top-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm px-3" id="printPurchaseModalBtn" onclick="window.print();">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    @include('backend.accounting.partials._quick_supplier_payment_modal')
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        $(document).ready(function() {
            flatpickr('.date-picker', {
                dateFormat: 'Y-m-d',
                static: true
            });

            $('[data-dt-filter="purchase-table"]').on('change input', function() {
                if (window.LaravelDataTables && window.LaravelDataTables['purchase-table']) {
                    window.LaravelDataTables['purchase-table'].draw();
                }
            });
        });

        function resetFilters(tableId) {
            $('#filter-branch, #filter-supplier, #filter-purchase-status, #filter-payment-status').val('');
            $('#from-date, #to-date').val('');
            if (window.LaravelDataTables && window.LaravelDataTables[tableId]) {
                window.LaravelDataTables[tableId].search('').columns().search('').draw();
            }
        }

        function viewPurchase(id) {
            const url = "{{ route('purchases.show', ':id') }}".replace(':id', id);

            $('#purchaseViewModal').modal('show');
            $('#purchaseModalContent').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary" style="width: 2.5rem; height: 2.5rem;"></div><div class="mt-2 text-muted small">Loading purchase invoice details...</div></div>'
            );

            $.get(url, function(res) {
                const purchase = res.data || res;
                if (!purchase || !purchase.id) {
                    $('#purchaseModalContent').html('<div class="alert alert-danger mb-0">Failed to load purchase details.</div>');
                    return;
                }

                const supplierName = purchase.supplier?.name || 'N/A';
                const supplierPhone = purchase.supplier?.phone || '-';
                const supplierCompany = purchase.supplier?.company_name || '';
                const branchName = purchase.branch?.name || 'N/A';

                let itemsRows = '';
                if (purchase.items && purchase.items.length > 0) {
                    purchase.items.forEach((item, idx) => {
                        const prodName = item.product?.name || 'Unknown Product';
                        const varName = item.variant?.name ? ` (${item.variant.name})` : '';
                        const sku = item.variant?.sku || item.product?.sku || '-';
                        const batchNo = item.batch?.batch_no || item.batch_number || 'DEFAULT';
                        const expiry = formatedDate(item.expiry_date) || formatedDate(item.batch?.expiry_date) || 'N/A';
                        const unitName = item.purchase_unit?.short_name || item.purchase_unit?.name || 'Unit';

                        itemsRows += `
                            <tr>
                                <td class="text-center text-muted small">${idx + 1}</td>
                                <td>
                                    <div class="fw-bold text-dark">${prodName}${varName}</div>
                                    <small class="text-muted">SKU: ${sku}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">${batchNo}</span>
                                    <br><small class="text-muted">Exp: ${expiry}</small>
                                </td>
                                <td class="text-center fw-bold">${parseFloat(item.quantity).toFixed(2)} ${unitName}</td>
                                <td class="text-end">${parseFloat(item.unit_cost).toFixed(2)}</td>
                                <td class="text-end text-danger">${parseFloat(item.total_discount || 0).toFixed(2)}</td>
                                <td class="text-end">${parseFloat(item.tax_amount || 0).toFixed(2)}</td>
                                <td class="text-end fw-bold text-dark">${parseFloat(item.subtotal).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                } else {
                    itemsRows = `<tr><td colspan="8" class="text-center py-3 text-muted">No items recorded in this purchase.</td></tr>`;
                }

                let paymentRows = '';
                if (purchase.payments && purchase.payments.length > 0) {
                    purchase.payments.forEach((pay) => {
                        paymentRows += `
                            <tr>
                                <td>${pay.payment_date || '-'}</td>
                                <td class="fw-semibold text-primary">${pay.payment_no}</td>
                                <td>${pay.payment_account?.account_name || 'Cash/Bank'}</td>
                                <td><span class="badge bg-light text-dark border">${(pay.payment_method || '').toUpperCase()}</span></td>
                                <td class="text-end fw-bold text-success">${parseFloat(pay.amount).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                } else {
                    paymentRows = `<tr><td colspan="5" class="text-center py-2 text-muted small">No payment transactions recorded yet.</td></tr>`;
                }

                const html = `
                    <div class="container-fluid p-0">
                        <div class="row g-3 mb-3 p-3 bg-light rounded-3 border">
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">PURCHASE INVOICE</small>
                                <span class="fs-6 fw-bold text-primary">${purchase.purchase_no}</span>
                                ${purchase.memo_number ? `<small class="d-block text-muted">Memo: <b>${purchase.memo_number}</b></small>` : ''}
                                ${purchase.reference ? `<small class="d-block text-muted">Ref: <b>${purchase.reference}</b></small>` : ''}
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">SUPPLIER DETAILS</small>
                                <span class="fw-bold text-dark">${supplierName}</span>
                                ${supplierCompany ? `<small class="d-block text-muted">${supplierCompany}</small>` : ''}
                                <small class="d-block text-muted">Phone: ${supplierPhone}</small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">DATE & BRANCH</small>
                                <span class="fw-bold text-dark">${formatedDate(purchase.purchase_date)}</span>
                                <small class="d-block text-muted">Branch: <b>${branchName}</b></small>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">STATUSES</small>
                                <span class="badge bg-dark text-light px-2 py-1 mb-1">${(purchase.purchase_status || 'received').toUpperCase()}</span>
                                <br>
                                <span class="badge ${purchase.payment_status === 'paid' ? 'bg-success' : 'bg-danger'} px-2 py-1">${(purchase.payment_status || 'unpaid').toUpperCase()}</span>
                            </div>
                        </div>

                        <div class="table-responsive border rounded-3 mb-3">
                            <table class="table table-sm table-striped align-middle mb-0" style="font-size: 12px;">
                                <thead class="text-uppercase" style="font-size: 11px;">
                                    <tr>
                                        <th class="text-center" width="40">#</th>
                                        <th>ITEM / SKU</th>
                                        <th>BATCH & EXPIRY</th>
                                        <th class="text-center">QTY</th>
                                        <th class="text-end">UNIT COST</th>
                                        <th class="text-end">DISCOUNT</th>
                                        <th class="text-end">TAX</th>
                                        <th class="text-end">SUBTOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsRows}
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <div class="border rounded-3 p-3 bg-white h-100">
                                    <h6 class="fw-bold mb-2 text-secondary" style="font-size: 12px;">
                                        <i class="fa-solid fa-money-bill-wave me-1 text-success"></i> PAYMENT TRANSACTIONS
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 11px;">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Payment No</th>
                                                    <th>Account</th>
                                                    <th>Method</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${paymentRows}
                                            </tbody>
                                        </table>
                                    </div>
                                    ${purchase.note ? `<div class="mt-3 p-2 bg-light rounded text-muted small"><b>Note:</b> ${purchase.note}</div>` : ''}
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="card border-0 bg-light rounded-3 p-3 shadow-none">
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span class="text-muted">Subtotal:</span>
                                        <span class="fw-bold">${parseFloat(purchase.subtotal_amount || 0).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 small text-danger">
                                        <span>Order Discount (-):</span>
                                        <span>${parseFloat(purchase.order_discount_amount || 0).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span class="text-muted">Order Tax (+):</span>
                                        <span>${parseFloat(purchase.order_tax_amount || 0).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span class="text-muted">Shipping Cost (+):</span>
                                        <span>${parseFloat(purchase.shipping_cost || 0).toFixed(2)}</span>
                                    </div>
                                    ${parseFloat(purchase.round_off || 0) !== 0 ? `
                                    <div class="d-flex justify-content-between mb-1 small text-muted">
                                        <span>Round Off:</span>
                                        <span>${parseFloat(purchase.round_off).toFixed(2)}</span>
                                    </div>` : ''}
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold fs-6">Grand Total:</span>
                                        <span class="fw-bold fs-6 text-primary">${parseFloat(purchase.total_amount || 0).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 small text-success">
                                        <span class="fw-bold">Paid Amount:</span>
                                        <span class="fw-bold">${parseFloat(purchase.paid_amount || 0).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-danger">
                                        <span class="fw-bold">Due Amount:</span>
                                        <span class="fw-bold">${parseFloat(purchase.due_amount || 0).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#purchaseModalContent').html(html);
            }).fail(function() {
                $('#purchaseModalContent').html('<div class="alert alert-danger mb-0">Failed to communicate with server.</div>');
            });
        }
    </script>
@endpush