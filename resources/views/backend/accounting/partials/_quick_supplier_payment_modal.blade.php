{{-- ==================== QUICK & UNIVERSAL SUPPLIER PAYMENT MODAL ==================== --}}
<div class="modal fade" id="quickSupplierPaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="fa-solid fa-money-check-dollar me-2"></i> <span id="q_pay_modal_title">Supplier Payment</span>
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quick_supplier_pay_form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="q_pay_supplier_id" name="supplier_id">
                <input type="hidden" id="q_pay_payable_type" name="payable_type">
                <input type="hidden" id="q_pay_payable_id" name="payable_id">

                <div class="modal-body p-4">
                    {{-- 1. Document Mode Header Alert (Shown when paying a single Bill/Purchase) --}}
                    <div class="alert alert-light border py-2 px-3 mb-3 d-flex justify-content-between align-items-center" id="q_doc_mode_alert" style="display: none;">
                        <div>
                            <small class="d-block text-muted">Invoice Ref: <strong id="q_pay_doc_no" class="text-dark">---</strong></small>
                            <small class="d-block text-muted">Supplier: <strong id="q_pay_supplier_name" class="text-dark">---</strong></small>
                        </div>
                        <div class="text-end">
                            <small class="d-block text-muted">Current Due:</small>
                            <strong id="q_pay_doc_due_text" class="text-danger fs-5">0.00</strong>
                        </div>
                    </div>

                    {{-- 2. Supplier Select Mode (Shown when paying on-account from main payment menu) --}}
                    <div class="row mb-3" id="q_supplier_select_row">
                        <div class="col-12">
                            <label class="form-label fw-bold small">Select Supplier <span class="text-danger">*</span></label>
                            <select id="q_pay_supplier_select" class="form-select form-select-sm select-picker">
                                <option value="">-- Select Supplier --</option>
                                @isset($suppliers)
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }} {{ $sup->phone ? "({$sup->phone})" : '' }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                    </div>

                    {{-- 3. Open Invoices Multi-Bill Allocation Table (QuickBooks Style) --}}
                    <div id="q_open_invoices_wrapper" class="mb-3" style="display: none;">
                        <h6 class="fw-bold text-dark small mb-2"><i class="fa-solid fa-list-check me-1 text-primary"></i> Open Unpaid Bills & Purchases:</h6>
                        <div class="table-responsive border rounded custom-scrollbar" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-striped align-middle mb-0" style="font-size: 11px;">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30">#</th>
                                        <th>Date</th>
                                        <th>Document No</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Due</th>
                                        <th class="text-end" width="120">Pay Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="q_open_invoices_tbody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 4. Payment Account & Transaction Details --}}
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small">Payment Date <span class="text-danger">*</span></label>
                            <input type="text" name="payment_date" id="q_pay_date" class="form-control form-control-sm" required placeholder="Select Date">
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small">Total Amount to Pay <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="q_pay_total_amount" class="form-control form-control-sm text-end fw-bold text-success" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small">Payment Account Source <span class="text-danger">*</span></label>
                            <select name="payment_account_id" id="q_pay_account_id" class="form-select form-select-sm" required>
                                <option value="">-- Select Source Account --</option>
                                @isset($paymentAccounts)
                                    @foreach ($paymentAccounts as $pAccount)
                                        <option value="{{ $pAccount->id }}" {{ ($general_settings['default_acc'] ?? '') == $pAccount->id ? 'selected' : '' }}>
                                            {{ $pAccount->account_name }} ({{ ucfirst($pAccount->account_type->value ?? $pAccount->account_type) }})
                                        </option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small">Payment Method</label>
                            <select name="payment_method" id="q_pay_method" class="form-select form-select-sm">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="mfs">Mobile Banking (bKash/Nagad)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small">Ref / Cheque / Txn No.</label>
                            <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="e.g. CHQ-98765">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small">Receipt Slip / Attachment</label>
                            <input type="file" name="attachment" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>

                    <div class="mb-0 mt-1">
                        <label class="form-label fw-bold small">Remarks / Note</label>
                        <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Optional payment note..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="q_pay_submit_btn" class="btn btn-sm btn-success px-4">
                        <i class="fa-solid fa-check-circle me-1"></i> Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    let qPayFlatpickrInstance = null;

    $(document).ready(function() {
        // 1. Initialize Flatpickr with App Settings Date Format & AltInput
        const payDateInput = document.getElementById('q_pay_date');
        if (payDateInput && typeof flatpickr !== 'undefined') {
            qPayFlatpickrInstance = flatpickr(payDateInput, {
                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                dateFormat: "Y-m-d",
                defaultDate: "today",
                static: true,
                allowInput: true,
            });
        }
    });

    /**
     * Mode 1: Open Modal for a Specific Document (Quick Pay from Bill/Purchase Row)
     */
    window.openDocumentPaymentModal = function(options) {
        const $modal = $('#quickSupplierPaymentModal');
        const $form = $('#quick_supplier_pay_form');
        $form[0].reset();

        // Reset and set Flatpickr Date to today
        if (qPayFlatpickrInstance) {
            qPayFlatpickrInstance.setDate("today", true);
        } else {
            $('#q_pay_date').val(new Date().toISOString().split('T')[0]);
        }

        $('#q_pay_supplier_id').val(options.supplierId);
        $('#q_pay_payable_type').val(options.type);
        $('#q_pay_payable_id').val(options.id);

        $('#q_pay_doc_no').text(options.no);
        $('#q_pay_supplier_name').text(options.supplierName || 'N/A');
        
        const due = parseFloat(options.due) || 0;
        $('#q_pay_doc_due_text').text(due.toFixed(2));
        $('#q_pay_total_amount').val(due.toFixed(2)).attr('max', due);

        $('#q_doc_mode_alert').show();
        $('#q_supplier_select_row').hide();
        $('#q_open_invoices_wrapper').hide();

        $form.data('datatable-id', options.tableId || '');
        $modal.modal('show');
    };

    /**
     * Mode 2: Open Modal for Standalone Supplier Select (QuickBooks Style)
     */
    window.openSupplierPaymentModal = function(tableId = 'supplier-payment-table') {
        const $modal = $('#quickSupplierPaymentModal');
        const $form = $('#quick_supplier_pay_form');
        $form[0].reset();

        if (qPayFlatpickrInstance) {
            qPayFlatpickrInstance.setDate("today", true);
        } else {
            $('#q_pay_date').val(new Date().toISOString().split('T')[0]);
        }

        $('#q_doc_mode_alert').hide();
        $('#q_supplier_select_row').show();
        $('#q_open_invoices_wrapper').hide();

        $('#q_pay_payable_type').val('supplier');
        $('#q_pay_payable_id').val('');
        $('#q_pay_total_amount').removeAttr('max');

        $form.data('datatable-id', tableId);
        $modal.modal('show');
    };

    /**
     * Mode 3: Open Modal directly for a specific Supplier from Supplier DataTable
     */
    window.openPaymentForSupplier = function(supplierId, supplierName, currentBalance, tableId = 'supplier-table') {
        openSupplierPaymentModal(tableId);

        // Pre-select supplier in dropdown & trigger change to auto-load open bills
        $('#q_pay_supplier_select').val(supplierId).trigger('change');
        $('#q_pay_supplier_id').val(supplierId);
        $('#q_pay_payable_type').val('supplier');
        $('#q_pay_payable_id').val(supplierId);
    };

    // On Supplier Change -> Fetch Open Invoices dynamically
    $('#q_pay_supplier_select').on('change', function() {
        const supplierId = $(this).val();
        $('#q_pay_supplier_id').val(supplierId);
        $('#q_pay_payable_id').val(supplierId);

        if (!supplierId) {
            $('#q_open_invoices_wrapper').hide();
            return;
        }

        const url = "{{ route('supplier-payments.open-invoices', ':id') }}".replace(':id', supplierId);
        
        $.get(url, function(res) {
            if (res.success && (res.data.bills.length > 0 || res.data.purchases.length > 0)) {
                let rowsHtml = '';
                let index = 1;

                // Render Bills
                res.data.bills.forEach(bill => {
                    const formattedBillDate = (typeof formatedDate === 'function') ? formatedDate(bill.bill_date) : bill.bill_date;
                    rowsHtml += `
                        <tr>
                            <td class="text-center">${index++}</td>
                            <td>${formattedBillDate}</td>
                            <td class="fw-bold text-primary">Bill: ${bill.bill_no}</td>
                            <td class="text-end">${parseFloat(bill.total_amount).toFixed(2)}</td>
                            <td class="text-end text-danger fw-bold">${parseFloat(bill.due_amount).toFixed(2)}</td>
                            <td>
                                <input type="hidden" name="allocations[b_${bill.id}][type]" value="bill">
                                <input type="hidden" name="allocations[b_${bill.id}][id]" value="${bill.id}">
                                <input type="number" step="0.01" min="0" max="${bill.due_amount}" name="allocations[b_${bill.id}][amount]" class="form-control form-control-xs text-end alloc-input" placeholder="0.00">
                            </td>
                        </tr>
                    `;
                });

                // Render Purchases
                res.data.purchases.forEach(pur => {
                    const formattedPurDate = (typeof formatedDate === 'function') ? formatedDate(pur.purchase_date) : pur.purchase_date;
                    rowsHtml += `
                        <tr>
                            <td class="text-center">${index++}</td>
                            <td>${formattedPurDate}</td>
                            <td class="fw-bold text-success">Purchase: ${pur.purchase_no}</td>
                            <td class="text-end">${parseFloat(pur.total_amount).toFixed(2)}</td>
                            <td class="text-end text-danger fw-bold">${parseFloat(pur.due_amount).toFixed(2)}</td>
                            <td>
                                <input type="hidden" name="allocations[p_${pur.id}][type]" value="purchase">
                                <input type="hidden" name="allocations[p_${pur.id}][id]" value="${pur.id}">
                                <input type="number" step="0.01" min="0" max="${pur.due_amount}" name="allocations[p_${pur.id}][amount]" class="form-control form-control-xs text-end alloc-input" placeholder="0.00">
                            </td>
                        </tr>
                    `;
                });

                $('#q_open_invoices_tbody').html(rowsHtml);
                $('#q_open_invoices_wrapper').slideDown(150);
            } else {
                $('#q_open_invoices_wrapper').slideUp(150);
            }
        });
    });

    // Auto-sum allocations into Total Amount
    $(document).on('input', '.alloc-input', function() {
        let sum = 0;
        $('.alloc-input').each(function() {
            sum += parseFloat($(this).val()) || 0;
        });
        if (sum > 0) {
            $('#q_pay_total_amount').val(sum.toFixed(2));
        }
    });

    // Submit Payment Form Handler
    $('#quick_supplier_pay_form').off('submit').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#q_pay_submit_btn');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

        const formData = new FormData(this);
        const dtId = $(this).data('datatable-id');

        $.ajax({
            url: "{{ route('supplier-payments.store') }}",
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                $btn.prop('disabled', false).html(originalHtml);
                if (res.success) {
                    $('#quickSupplierPaymentModal').modal('hide');
                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert('success', res.message);
                    }
                    if (dtId && window.LaravelDataTables && window.LaravelDataTables[dtId]) {
                        window.LaravelDataTables[dtId].ajax.reload(null, false);
                    }
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                const msg = xhr.responseJSON?.message || "Payment submission failed.";
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert('error', msg);
                } else {
                    alert(msg);
                }
            }
        });
    });
</script>
@endpush