@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.bill_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.bill_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.bill_management_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('bills.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.bill') }}
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
                                <select id="filter-status" data-dt-filter="bill-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }} --</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="partially_paid">Partially Paid</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('bill-table')">
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
    {{-- ==================== VIEW BILL DETAILS MODAL ==================== --}}
    <div class="modal fade" id="viewBillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="fa-solid fa-file-invoice-dollar me-2"></i> {{ __('Vendor Bill Details') }} - <span
                            id="v_bill_no"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Loader --}}
                    <div id="view_bill_loader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted small mt-2">Loading bill details...</p>
                    </div>

                    {{-- Bill Content --}}
                    <div id="view_bill_content" style="display: none;">
                        <div class="row mb-4 bg-light p-3 rounded border">
                            <div class="col-md-6 mb-2">
                                <strong class="text-muted small d-block">{{ __('Supplier / Vendor') }}:</strong>
                                <span id="v_supplier" class="fw-bold text-dark fs-6"></span>
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong class="text-muted small d-block">{{ __('Vendor Invoice No') }}:</strong>
                                <span id="v_vendor_invoice" class="fw-bold text-dark"></span>
                            </div>
                            <div class="col-md-3 mb-2">
                                <strong class="text-muted small d-block">{{ __('Payment Status') }}:</strong>
                                <span id="v_payment_status"></span>
                            </div>
                            <div class="col-md-3 mt-2">
                                <strong class="text-muted small d-block">{{ __('Bill Date') }}:</strong>
                                <span id="v_bill_date" class="fw-semibold"></span>
                            </div>
                            <div class="col-md-3 mt-2">
                                <strong class="text-muted small d-block">{{ __('Due Date') }}:</strong>
                                <span id="v_due_date" class="fw-semibold"></span>
                            </div>
                            <div class="col-md-3 mt-2">
                                <strong class="text-muted small d-block">{{ __('Branch') }}:</strong>
                                <span id="v_branch" class="fw-semibold"></span>
                            </div>
                            <div class="col-md-3 mt-2">
                                <strong class="text-muted small d-block">{{ __('Created By') }}:</strong>
                                <span id="v_creator" class="fw-semibold"></span>
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <h6 class="fw-bold text-dark mb-2">
                            <i class="fa-solid fa-list me-1 text-primary"></i> {{ __('Expense Items') }}
                        </h6>
                        <div class="table-responsive mb-2">
                            <table class="table table-bordered align-middle table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="35%">{{ __('Category') }}</th>
                                        <th width="40%">{{ __('Description') }}</th>
                                        <th width="20%" class="text-end">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="v_items_tbody">
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold text-muted py-2" style="font-size: 13px;">{{ __('Items Subtotal') }}:</th>
                                        <th class="text-end fw-bold text-dark py-2" style="font-size: 13px;" id="v_items_subtotal">0.00</th>
                                    </tr>
                                    <tr id="v_late_fee_row" style="display: none;">
                                        <th colspan="3" class="text-end fw-bold text-warning-emphasis py-2" style="font-size: 13px;">
                                            <span class="me-2">
                                                <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> {{ __('Late Fees / Finance Charges') }}:
                                            </span>
                                            <button type="button" class="btn btn-xs btn-outline-warning text-dark py-0 px-2 fw-bold" 
                                                data-bs-toggle="collapse" data-bs-target="#v_fc_collapse" style="font-size: 11px;">
                                                <span id="v_fc_count_badge">0</span> Charges <i class="fa-solid fa-chevron-down ms-1 text-muted"></i>
                                            </button>
                                        </th>
                                        <th class="text-end fw-bold text-danger py-2" style="font-size: 13px;" id="v_late_fee_total">0.00</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold py-2 text-dark" style="font-size: 13px;">{{ __('Grand Total') }}:</th>
                                        <th class="text-end fw-bold py-2 text-primary" style="font-size: 13px;" id="v_total_amount">0.00</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold text-muted py-2" style="font-size: 13px;">{{ __('Paid Amount') }}:</th>
                                        <th class="text-end fw-bold text-success py-2" style="font-size: 13px;" id="v_paid_amount">0.00</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold text-danger py-2" style="font-size: 13px;">{{ __('Due Amount') }}:</th>
                                        <th class="text-end fw-bold text-danger py-2" style="font-size: 13px;" id="v_due_amount">0.00</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Collapsible Late Fees Breakdown --}}
                        <div class="collapse mt-2 mb-3" id="v_fc_collapse">
                            <div class="card card-body p-3 shadow-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold text-dark mb-0 small">
                                        <i class="fa-solid fa-clock-rotate-left me-1 text-warning"></i> {{ __('Late Fees Breakdown') }}
                                    </h6>
                                    <button type="button" class="btn-close btn-sm" data-bs-toggle="collapse" data-bs-target="#v_fc_collapse" aria-label="Close"></button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered bg-white align-middle mb-0" style="font-size: 12px;">
                                        <thead class="text-center">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th>{{ __('Charge No') }}</th>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Days Overdue') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th class="text-end">{{ __('Amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="v_finance_charges_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Collapsible Payments History --}}
                        <div id="v_payments_section" class="mt-3" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-history me-1 text-success"></i> {{ __('Payment History') }}
                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" id="v_payments_count_badge">0</span>
                                </h6>
                                <button type="button" class="btn btn-xs btn-outline-success fw-semibold" data-bs-toggle="collapse" data-bs-target="#v_payments_collapse" style="font-size: 11px;">
                                    <i class="fa-solid fa-chevron-down me-1"></i> {{ __('Toggle Payments') }}
                                </button>
                            </div>
                            <div class="collapse show" id="v_payments_collapse">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped border align-middle mb-0" style="font-size: 12px;">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Payment No') }}</th>
                                                <th>{{ __('Account Source') }}</th>
                                                <th>{{ __('Method') }}</th>
                                                <th class="text-end">{{ __('Amount Paid') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="v_payments_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Remarks & Attachment --}}
                        <div class="row mt-3 pt-2 border-top">
                            <div class="col-md-8">
                                <strong class="text-muted small d-block">{{ __('Remarks / Note') }}:</strong>
                                <p id="v_note" class="text-dark mb-0 small"></p>
                            </div>
                            <div class="col-md-4 text-end" id="v_attachment_container" style="display: none;">
                                <strong class="text-muted small d-block mb-1">{{ __('Attachment') }}:</strong>
                                <a id="v_attachment_link" href="#" target="_blank"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-paperclip me-1"></i> {{ __('View Attachment') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary"
                        data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== PAY BILL MODAL ==================== --}}
    

     @include('backend.accounting.partials._quick_waive_late_fee')
     @include('backend.accounting.partials._quick_supplier_payment_modal')
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        // Reset Filter Function
        function resetFilters(tableId) {
            $('#filter-status').val('');
            if (window.LaravelDataTables && window.LaravelDataTables[tableId]) {
                window.LaravelDataTables[tableId].ajax.reload();
            }
        }

        // Custom Filter Listener
        $('#filter-status').change(function() {
            let status = $(this).val();
            let table = window.LaravelDataTables['bill-table'];
            if (table) {
                table.column('payment_status:name').search(status).draw();
            }
        });

        // ==================== VIEW BILL DETAILS HANDLER ====================
        window.viewBill = function(billId) {
            $('#viewBillModal').modal('show');
            $('#view_bill_loader').show();
            $('#view_bill_content').hide();
            $('#v_fc_collapse').collapse('hide');

            let url = "{{ route('bills.show', ':billId') }}".replace(':billId', billId);

            $.ajax({
                url: url,
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        let bill = res.data;

                        $('#v_bill_no').text(bill.bill_no);
                        $('#v_supplier').text(bill.supplier ? bill.supplier.name : 'N/A');
                        $('#v_vendor_invoice').text(bill.vendor_invoice_no || 'N/A');
                        $('#v_bill_date').text(typeof formatedDate === 'function' ? formatedDate(bill.bill_date) : bill.bill_date);
                        $('#v_due_date').text(typeof formatedDate === 'function' ? formatedDate(bill.due_date) : bill.due_date);
                        $('#v_branch').text(bill.branch ? bill.branch.name : 'N/A');
                        $('#v_creator').text(bill.creator ? bill.creator.name : 'System');
                        $('#v_note').text(bill.note || 'No remarks provided.');

                        // Status Badge
                        let badgeClass = bill.payment_status === 'paid' ? 'bg-success' : (bill.payment_status === 'partially_paid' ? 'bg-warning text-dark' : 'bg-danger');
                        let statusText = bill.payment_status.replace('_', ' ').toUpperCase();
                        $('#v_payment_status').html(`<span class="badge ${badgeClass}">${statusText}</span>`);

                        // Render Line Items and Calculate Items Subtotal
                        let itemsHtml = '';
                        let itemsSubtotal = 0;
                        if (bill.items && bill.items.length > 0) {
                            bill.items.forEach((item, index) => {
                                let catName = item.expense_account ?
                                    `${item.expense_account.account_code} - ${item.expense_account.account_name}` :
                                    'N/A';
                                let amount = parseFloat(item.amount) || 0;
                                itemsSubtotal += amount;

                                itemsHtml += `<tr>
                                    <td>${index + 1}</td>
                                    <td class="fw-semibold">${catName}</td>
                                    <td>${item.description || '-'}</td>
                                    <td class="text-end fw-bold">${amount.toFixed(2)}</td>
                                </tr>`;
                            });
                        } else {
                            itemsHtml = `<tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>`;
                        }
                        $('#v_items_tbody').html(itemsHtml);
                        $('#v_items_subtotal').text(itemsSubtotal.toFixed(2));

                        // Render Finance Charges (Late Fees)
                        let activeLateFeesTotal = 0;
                        if (bill.finance_charges && bill.finance_charges.length > 0) {
                            let fcHtml = '';
                            let fcCount = 0;

                            bill.finance_charges.forEach((fc) => {
                                fcCount++;
                                let fcStatus = (fc.status || 'posted').toLowerCase();
                                let rawAmount = parseFloat(fc.amount) || 0;
                                
                                // 💡 মাফ (Waived/Cancelled) করা চার্জ টোটাল থেকে ০ গণনা হবে
                                let effectiveAmount = 0;
                                let fcBadge = 'bg-secondary';
                                let statusText = fcStatus.toUpperCase();

                                if (fcStatus === 'posted') {
                                    effectiveAmount = rawAmount;
                                    fcBadge = 'bg-danger';
                                } else if (fcStatus === 'partially_waived') {
                                    effectiveAmount = rawAmount;
                                    fcBadge = 'bg-warning text-dark';
                                    statusText = 'PARTIALLY WAIVED';
                                } else if (fcStatus === 'waived') {
                                    effectiveAmount = 0;
                                    fcBadge = 'bg-success';
                                    statusText = 'WAIVED';
                                } else if (fcStatus === 'cancelled') {
                                    effectiveAmount = 0;
                                    fcBadge = 'bg-secondary';
                                    statusText = 'CANCELLED';
                                }

                                activeLateFeesTotal += effectiveAmount;

                                let displayAmountText = (fcStatus === 'waived' || fcStatus === 'cancelled')
                                    ? `<span class="text-decoration-line-through text-muted me-1">${rawAmount.toFixed(2)}</span> <small class="text-success fw-bold">(Waived)</small>`
                                    : `${effectiveAmount.toFixed(2)}`;

                                let overdueDays = (fc.days_overdue !== undefined && fc.days_overdue !== null) ? fc.days_overdue : 0;

                                fcHtml += `<tr>
                                    <td>${fcCount}</td>
                                    <td class="fw-bold text-primary">${fc.charge_no}</td>
                                    <td>${typeof formatedDate === 'function' ? formatedDate(fc.charge_date) : fc.charge_date}</td>
                                    <td>${overdueDays} days</td>
                                    <td><span class="badge ${fcBadge}">${statusText}</span></td>
                                    <td class="text-end fw-bold">${displayAmountText}</td>
                                </tr>`;
                            });

                            $('#v_finance_charges_tbody').html(fcHtml);
                            $('#v_fc_count_badge').text(bill.finance_charges.length);

                            if (activeLateFeesTotal > 0) {
                                $('#v_late_fee_total').html(activeLateFeesTotal.toFixed(2));
                                $('#v_late_fee_row').show();
                            } else {
                                $('#v_late_fee_total').html('<span class="text-success fw-bold">0.00 (Waived)</span>');
                                $('#v_late_fee_row').show();
                            }
                        } else {
                            $('#v_late_fee_row').hide();
                        }

                        // Totals Calculation (Items Subtotal + Active Late Fees)
                        let grossTotal = itemsSubtotal + activeLateFeesTotal;
                        $('#v_total_amount').text(grossTotal.toFixed(2));
                        $('#v_paid_amount').text(parseFloat(bill.paid_amount).toFixed(2));
                        $('#v_due_amount').text(parseFloat(bill.due_amount).toFixed(2));

                        // Render Payment History
                        if (bill.payments && bill.payments.length > 0) {
                            let paymentsHtml = '';
                            bill.payments.forEach((pay) => {
                                let accName = pay.payment_account ? pay.payment_account.account_name : 'N/A';
                                paymentsHtml += `<tr>
                                    <td>${typeof formatedDate === 'function' ? formatedDate(pay.payment_date) : pay.payment_date}</td>
                                    <td><span class="fw-bold">${pay.payment_no}</span></td>
                                    <td>${accName}</td>
                                    <td>${pay.payment_method ? pay.payment_method.toUpperCase() : '-'}</td>
                                    <td class="text-end fw-bold text-success">${parseFloat(pay.amount).toFixed(2)}</td>
                                </tr>`;
                            });
                            $('#v_payments_tbody').html(paymentsHtml);
                            $('#v_payments_count_badge').text(bill.payments.length);
                            $('#v_payments_section').show();
                        } else {
                            $('#v_payments_section').hide();
                        }

                        // Attachment
                        if (res.attachment_url) {
                            $('#v_attachment_link').attr('href', res.attachment_url);
                            $('#v_attachment_container').show();
                        } else {
                            $('#v_attachment_container').hide();
                        }

                        $('#view_bill_loader').hide();
                        $('#view_bill_content').fadeIn();
                    }
                },
                error: function(err) {
                    $('#viewBillModal').modal('hide');
                    let msg = err.responseJSON?.message || "Failed to fetch bill details.";
                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert('error', msg);
                    } else {
                        alert(msg);
                    }
                }
            });
        };
    </script>
@endpush