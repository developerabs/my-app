@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.supplier_payment_create') ?? 'Make Supplier Payment' }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 5px;
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }
        .invoice-row-selected {
            background-color: #f0fdf4 !important;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.supplier_payment_create') ?? 'Make Supplier Payment' }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.supplier_payment_create_desc') ?? 'Settle vendor bills, purchase invoices, asset purchases, and on-account advance payments.' }}
        @endslot
        @slot('button')
            <a href="{{ route('supplier-payments.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> {{ __('file.button.list') ?? 'Payment History' }}
            </a>
        @endslot
    @endcomponent

    <form action="{{ route('supplier-payments.store') }}" method="POST" enctype="multipart/form-data" id="supplier-payment-form">
        @csrf
        
        <input type="hidden" id="q_pay_payable_type" name="payable_type" value="supplier">
        <input type="hidden" id="q_pay_payable_id" name="payable_id" value="{{ $selectedSupplierId ?? '' }}">

        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-end">
                            
                            <!-- 🟢 Journal Header Section (Date, Branch, Currency, FX Rate) -->
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Payment Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <!-- Paying Branch Selector (Scoped to Allowed User Branches) -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Paying Branch') }} <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" class="form-select form-select-sm select-picker" required>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" 
                                            data-currency-id="{{ $b->currency_id }}"
                                            {{ (session('branch_id') == $b->id || (auth()->user()->branch_id ?? '') == $b->id || $b->is_default) ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payment Currency -->
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Payment Currency') }} <span class="text-danger">*</span></label>
                                <select name="currency_id" id="currency_id" class="form-select form-select-sm select-picker" required>
                                    @foreach($currencies as $c)
                                        <option value="{{ $c->id }}" data-code="{{ $c->code }}" data-symbol="{{ $c->symbol ?? $c->code }}">
                                            {{ $c->code }} ({{ $c->symbol ?? $c->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- FX Rate to Base -->
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Exchange Rate') }}</label>
                                <input type="number" step="0.00000001" min="0.00000001" name="exchange_rate" id="exchange_rate" class="form-control form-control-sm" value="1.00000000" required>
                            </div>

                            <!-- Supplier Selection -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Select Supplier') }} <span class="text-danger">*</span></label>
                                <select name="supplier_id" id="supplier_id" class="form-select form-select-sm select-picker" required>
                                    <option value="">-- {{ __('Select Supplier to Pay') }} --</option>
                                    @isset($suppliers)
                                        @foreach($suppliers as $sup)
                                            <option value="{{ $sup->id }}" {{ (old('supplier_id', $selectedSupplierId ?? '') == $sup->id) ? 'selected' : '' }}>
                                                {{ $sup->name }} {{ $sup->company_name ? "({$sup->company_name})" : '' }}
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>

                            <!-- Payment Source Account -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Payment Account Source') }} <span class="text-danger">*</span></label>
                                <select name="payment_account_id" id="payment_account_id" class="form-select form-select-sm select-picker" required>
                                    <option value="">-- {{ __('Select Payment Source') }} --</option>
                                    @forelse ($paymentAccounts as $pAccount)
                                        <option value="{{ $pAccount->id }}" {{ ($general_settings['default_acc'] ?? '') == $pAccount->id ? 'selected' : '' }}>
                                            {{ $pAccount->account_name }} ({{ ucfirst($pAccount->account_type->value ?? $pAccount->account_type) }})
                                        </option>
                                    @empty
                                        <option value="">{{ __('No Accounts Found') }}</option>
                                    @endforelse
                                </select>
                            </div>

                            <!-- Payment Method -->
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                                <select name="payment_method" id="payment_method" class="form-select form-select-sm" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="mfs">Mobile Banking (bKash/Nagad)</option>
                                </select>
                            </div>

                            <!-- Ref / Txn No -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold mb-1">{{ __('Ref / Cheque / Txn No.') }}</label>
                                <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="e.g. CHQ-98765">
                            </div>

                            <!-- Total Payment Amount (Auto-Calculated & Distributable) -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold mb-1 text-success">{{ __('Total Amount to Pay') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="amount" id="total_pay_amount" 
                                    class="form-control form-control-sm text-end fw-bold text-success fs-6" required placeholder="0.00">
                            </div>

                        </div>
                    </div>

                    <div class="card-body p-4">

                        {{-- 1. Outstanding Supplier Balance Summary Banner --}}
                        <div class="alert alert-success-subtle border border-success-subtle py-2 px-3 mb-4 d-flex justify-content-between align-items-center" id="supplier_due_banner" style="display: none;">
                            <div>
                                <span class="fw-bold text-dark fs-6" id="banner_supplier_name">Supplier Name</span>
                                <small class="d-block text-muted" id="banner_supplier_info"></small>
                            </div>
                            <div class="text-end">
                                <small class="d-block text-muted fw-bold text-uppercase">Currency Invoices Due:</small>
                                <strong id="banner_total_due" class="text-danger fs-5">0.00</strong>
                            </div>
                        </div>

                        {{-- 2. Open Bills, Purchases & Asset Invoices Allocation Table --}}
                        <div id="open_invoices_wrapper" class="mb-4" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-list-check me-1 text-primary"></i> {{ __('Unpaid Invoices for Selected Currency') }}:
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3" onclick="autoAllocateFullDue()">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto Allocate All Dues
                                </button>
                            </div>

                            <div class="table-responsive border rounded custom-scrollbar">
                                <table class="table table-sm table-striped table-bordered align-middle mb-0" style="font-size: 12px;">
                                    <thead class="table-light text-uppercase" style="font-size: 11px;">
                                        <tr>
                                            <th class="text-center" width="40">
                                                <input type="checkbox" id="check_all_invoices" class="form-check-input" title="Select / Deselect All">
                                            </th>
                                            <th width="110">Date</th>
                                            <th width="120">Branch</th>
                                            <th>Invoice / Reference</th>
                                            <th class="text-end" width="130">Total</th>
                                            <th class="text-end" width="130">Due</th>
                                            <th class="text-end" width="160">Payment Allocation</th>
                                        </tr>
                                    </thead>
                                    <tbody id="open_invoices_tbody">
                                        {{-- Injected via AJAX --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 3. Document Attachment & Remarks --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Remarks / Note') }}</label>
                                <textarea name="note" class="form-control form-control-sm" rows="3" placeholder="Optional payment note..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Payment Receipt Slip') }}</label>
                                <input type="file" name="attachment" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>

                        <div class="row mt-4 mb-2">
                            <div class="col-12 text-end">
                                <button type="submit" id="submit_payment_btn" class="btn btn-success px-4 shadow">
                                    <i class="fa-solid fa-check-circle me-1"></i> {{ __('Submit Payment') }}
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
<script>
    $(document).ready(function() {

        let isDistributing = false; // Lock flag to prevent recursive event loops

        // 1. Initial Currency Setup based on selected Branch
        function syncBranchDefaultCurrency() {
            const $branchOption = $('#branch_id option:selected');
            const defaultCurrencyId = $branchOption.data('currency-id');

            if (defaultCurrencyId && $('#currency_id').val() != defaultCurrencyId) {
                $('#currency_id').val(defaultCurrencyId).trigger('change');
            }
        }

        // 2. Branch Change -> Auto-set Branch Currency
        $('#branch_id').on('change', function() {
            syncBranchDefaultCurrency();
            triggerReloadOpenInvoices();
        });

        // 3. Currency Change -> Update Invoices and Exchange Rate
        $('#currency_id').on('change', function() {
            triggerReloadOpenInvoices();
        });

        // 4. Supplier Change -> Trigger Reload
        $('#supplier_id').on('change', function() {
            const supplierId = $(this).val();
            $('#q_pay_payable_id').val(supplierId);
            triggerReloadOpenInvoices();
        });

        function triggerReloadOpenInvoices() {
            const supplierId = $('#supplier_id').val();
            const currencyId = $('#currency_id').val();
            const branchId = $('#branch_id').val();

            if (supplierId) {
                fetchSupplierOpenInvoices(supplierId, currencyId, branchId);
            } else {
                $('#supplier_due_banner').slideUp(150);
                $('#open_invoices_wrapper').slideUp(150);
                $('#total_pay_amount').val('');
                $('#open_invoices_tbody').empty();
            }
        }

        // 5. Fetch Open Invoices via AJAX
        function fetchSupplierOpenInvoices(supplierId, currencyId = null, branchId = null) {
            const url = "{{ route('supplier-payments.open-invoices', ':id') }}".replace(':id', supplierId);
            
            $.get(url, { currency_id: currencyId, branch_id: branchId }, function(res) {
                if (res.success && res.data) {
                    const data = res.data;
                    const selectedCurrencyDue = parseFloat(data.selected_currency_due) || 0;
                    const currencySymbol = $('#currency_id option:selected').data('symbol') || '';

                    // Breakdown Badges for other currencies
                    let summaryBadges = '';
                    if (data.currency_summary) {
                        Object.keys(data.currency_summary).forEach(code => {
                            const item = data.currency_summary[code];
                            summaryBadges += `<span class="badge bg-light text-dark border me-1">${code}: ${item.symbol} ${parseFloat(item.total_due).toFixed(2)} (${item.count})</span>`;
                        });
                    }

                    // Render Banner
                    $('#banner_supplier_name').text(data.supplier_name);
                    $('#banner_supplier_info').html(
                        (data.company_name ? data.company_name + ' | ' : '') + 
                        (data.supplier_phone ? data.supplier_phone + '<br>' : '') +
                        '<small class="fw-bold text-muted text-uppercase">Unpaid Currencies: </small>' + (summaryBadges || 'None')
                    );
                    $('#banner_total_due').text(currencySymbol + ' ' + selectedCurrencyDue.toFixed(2));
                    $('#supplier_due_banner').slideDown(150);

                    let rowsHtml = '';

                    // 1. Render Unpaid Bills (Vendor Bills & Asset Bills)
                    if (data.bills && data.bills.length > 0) {
                        data.bills.forEach(bill => {
                            const sym = bill.currency ? (bill.currency.symbol || bill.currency.code) : '';
                            const branchName = bill.branch ? bill.branch.name : 'Main';
                            const isAssetBill = bill.vendor_invoice_no && bill.vendor_invoice_no.startsWith('ASSET-');
                            
                            const billTitle = isAssetBill 
                                ? `<span class="fw-bold text-dark"><i class="fa-solid fa-boxes-stacked text-info me-1"></i> Asset Bill: ${bill.bill_no}</span>`
                                : `<span class="fw-bold text-primary"><i class="fa-solid fa-file-invoice me-1"></i> Bill: ${bill.bill_no}</span>`;

                            rowsHtml += `
                                <tr class="invoice-row" data-due="${bill.due_amount}">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input invoice-row-checkbox">
                                    </td>
                                    <td>${bill.bill_date}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary border">${branchName}</span></td>
                                    <td>${billTitle}</td>
                                    <td class="text-end">${sym} ${parseFloat(bill.total_amount).toFixed(2)}</td>
                                    <td class="text-end text-danger fw-bold">${sym} ${parseFloat(bill.due_amount).toFixed(2)}</td>
                                    <td>
                                        <input type="hidden" name="allocations[b_${bill.id}][type]" value="bill">
                                        <input type="hidden" name="allocations[b_${bill.id}][id]" value="${bill.id}">
                                        <input type="number" step="0.01" min="0" max="${bill.due_amount}" name="allocations[b_${bill.id}][amount]" class="form-control form-control-sm text-end alloc-input fw-bold" placeholder="0.00">
                                    </td>
                                </tr>
                            `;
                        });
                    }

                    // 2. Render Unpaid Purchases
                    if (data.purchases && data.purchases.length > 0) {
                        data.purchases.forEach(pur => {
                            const sym = pur.currency ? (pur.currency.symbol || pur.currency.code) : '';
                            const branchName = pur.branch ? pur.branch.name : 'Main';

                            rowsHtml += `
                                <tr class="invoice-row" data-due="${pur.due_amount}">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input invoice-row-checkbox">
                                    </td>
                                    <td>${pur.purchase_date}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary border">${branchName}</span></td>
                                    <td class="fw-bold text-success"><i class="fa-solid fa-cart-shopping me-1"></i> Purchase: ${pur.purchase_no}</td>
                                    <td class="text-end">${sym} ${parseFloat(pur.total_amount).toFixed(2)}</td>
                                    <td class="text-end text-danger fw-bold">${sym} ${parseFloat(pur.due_amount).toFixed(2)}</td>
                                    <td>
                                        <input type="hidden" name="allocations[p_${pur.id}][type]" value="purchase">
                                        <input type="hidden" name="allocations[p_${pur.id}][id]" value="${pur.id}">
                                        <input type="number" step="0.01" min="0" max="${pur.due_amount}" name="allocations[p_${pur.id}][amount]" class="form-control form-control-sm text-end alloc-input fw-bold" placeholder="0.00">
                                    </td>
                                </tr>
                            `;
                        });
                    }

                    if (rowsHtml) {
                        $('#open_invoices_tbody').html(rowsHtml);
                        $('#open_invoices_wrapper').slideDown(150);

                        if (selectedCurrencyDue > 0) {
                            $('#total_pay_amount').val(selectedCurrencyDue.toFixed(2));
                            distributeTotalToRows(selectedCurrencyDue);
                        }
                    } else {
                        $('#open_invoices_tbody').html(`<tr><td colspan="7" class="text-center text-muted py-3">No unpaid invoices found for this currency & branch.</td></tr>`);
                        $('#open_invoices_wrapper').slideDown(150);
                        $('#total_pay_amount').val('');
                    }
                }
            });
        }

        // =========================================================================
        // 🚀 DYNAMIC FIFO ALLOCATION ENGINE
        // =========================================================================
        function distributeTotalToRows(totalAmount) {
            if (isDistributing) return;
            isDistributing = true;

            let remainingAmount = parseFloat(totalAmount) || 0;
            let allChecked = true;
            let hasAnyRow = false;

            $('.invoice-row').each(function() {
                hasAnyRow = true;
                const $row = $(this);
                const $input = $row.find('.alloc-input');
                const $checkbox = $row.find('.invoice-row-checkbox');
                const maxDue = parseFloat($row.attr('data-due')) || 0;

                if (remainingAmount <= 0) {
                    $input.val('');
                    $checkbox.prop('checked', false);
                    $row.removeClass('invoice-row-selected');
                    allChecked = false;
                } else if (remainingAmount >= maxDue) {
                    $input.val(maxDue.toFixed(2));
                    $checkbox.prop('checked', true);
                    $row.addClass('invoice-row-selected');
                    remainingAmount = Math.round((remainingAmount - maxDue) * 100) / 100;
                } else {
                    $input.val(remainingAmount.toFixed(2));
                    $checkbox.prop('checked', true);
                    $row.addClass('invoice-row-selected');
                    remainingAmount = 0;
                    allChecked = false;
                }
            });

            $('#check_all_invoices').prop('checked', hasAnyRow && allChecked);
            isDistributing = false;
        }

        // =========================================================================
        // 🔄 RECALCULATE TOTAL FROM ROWS
        // =========================================================================
        function recalculateTotalFromRows() {
            if (isDistributing) return;
            isDistributing = true;

            let totalSum = 0;
            let allChecked = true;
            let hasAnyRow = false;

            $('.invoice-row').each(function() {
                hasAnyRow = true;
                const $row = $(this);
                const $input = $row.find('.alloc-input');
                const $checkbox = $row.find('.invoice-row-checkbox');
                const val = parseFloat($input.val()) || 0;
                const maxDue = parseFloat($row.attr('data-due')) || 0;

                if (val > maxDue) {
                    $input.val(maxDue.toFixed(2));
                    totalSum += maxDue;
                } else {
                    totalSum += val;
                }

                if (val > 0) {
                    $checkbox.prop('checked', true);
                    $row.addClass('invoice-row-selected');
                } else {
                    $checkbox.prop('checked', false);
                    $row.removeClass('invoice-row-selected');
                    allChecked = false;
                }
            });

            $('#check_all_invoices').prop('checked', hasAnyRow && allChecked);
            $('#total_pay_amount').val(totalSum > 0 ? totalSum.toFixed(2) : '');
            
            isDistributing = false;
        }

        // Total input changed -> Distribute down
        $('#total_pay_amount').on('input change', function() {
            const enteredTotal = parseFloat($(this).val()) || 0;
            distributeTotalToRows(enteredTotal);
        });

        // Row input changed -> Sum up
        $(document).on('input change', '.alloc-input', function() {
            recalculateTotalFromRows();
        });

        // Row Checkbox Clicked
        $(document).on('change', '.invoice-row-checkbox', function() {
            const $row = $(this).closest('.invoice-row');
            const $input = $row.find('.alloc-input');
            const maxDue = parseFloat($row.attr('data-due')) || 0;

            if ($(this).is(':checked')) {
                $input.val(maxDue.toFixed(2));
                $row.addClass('invoice-row-selected');
            } else {
                $input.val('');
                $row.removeClass('invoice-row-selected');
            }

            recalculateTotalFromRows();
        });

        // Select All Checkbox
        $(document).on('change', '#check_all_invoices', function() {
            const isChecked = $(this).is(':checked');

            $('.invoice-row').each(function() {
                const $row = $(this);
                const $input = $row.find('.alloc-input');
                const $checkbox = $row.find('.invoice-row-checkbox');
                const maxDue = parseFloat($row.attr('data-due')) || 0;

                $checkbox.prop('checked', isChecked);
                if (isChecked) {
                    $input.val(maxDue.toFixed(2));
                    $row.addClass('invoice-row-selected');
                } else {
                    $input.val('');
                    $row.removeClass('invoice-row-selected');
                }
            });

            recalculateTotalFromRows();
        });

        window.autoAllocateFullDue = function() {
            $('#check_all_invoices').prop('checked', true).trigger('change');
        };

        // Initialize on Load
        syncBranchDefaultCurrency();
        if ($('#supplier_id').val()) {
            triggerReloadOpenInvoices();
        }

        // Form Submit
        $('#supplier-payment-form').on('submit', function(e) {
            e.preventDefault();

            const totalAmount = parseFloat($('#total_pay_amount').val()) || 0;
            if (totalAmount <= 0) {
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert("error", "Please enter a valid payment amount or select invoices to pay.");
                } else {
                    alert("Please enter a valid payment amount.");
                }
                return;
            }

            const $btn = $('#submit_payment_btn');
            const originalBtnHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing Payment...');

            const formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    if (response.success) {
                        if (typeof showFloatingAlert === "function") {
                            showFloatingAlert("success", response.message || "Payment processed successfully!");
                        }

                        setTimeout(function() {
                            window.location.href = "{{ route('supplier-payments.index') }}";
                        }, 600);
                    } else {
                        $btn.prop('disabled', false).html(originalBtnHtml);
                        if (typeof showFloatingAlert === "function") {
                            showFloatingAlert("error", response.message || "Payment failed.");
                        }
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalBtnHtml);

                    let errorMessage = "An error occurred while processing payment.";
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) errorMessage = xhr.responseJSON.message;
                        if (xhr.responseJSON.errors) {
                            const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                            errorMessage = xhr.responseJSON.errors[firstKey][0];
                        }
                    }

                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert("error", errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        });

    });
</script>
@endpush