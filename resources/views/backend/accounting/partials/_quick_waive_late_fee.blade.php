<!-- Reusable Quick Waive Late Fee Modal Markup -->
<div class="modal fade" id="quickWaiveModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="fa-solid fa-hand-holding-dollar me-2"></i> {{ __('Waive / Discount Late Fee') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quick_waive_form">
                @csrf
                <input type="hidden" id="qw_charge_id" name="charge_id">
                <input type="hidden" id="qw_document_type" name="document_type">
                <input type="hidden" id="qw_document_id" name="document_id">

                <div class="modal-body p-4">
                    <!-- Info Alert Box -->
                    <div class="alert alert-warning border-warning-subtle py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="d-block text-muted">{{ __('Document Ref') }}: <strong id="qw_doc_no" class="text-dark"></strong></small>
                        </div>
                        <div class="text-end">
                            <small class="d-block text-muted">{{ __('Active Late Fee') }}:</small>
                            <strong id="qw_active_fee_text" class="text-danger fs-6">0.00</strong>
                        </div>
                    </div>

                    <!-- Waive Amount Field -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">{{ __('Waive Amount') }} <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-xs btn-link text-primary text-decoration-none p-0 fw-bold" id="qw_full_waive_btn">
                                <i class="fa-solid fa-bolt me-1"></i> {{ __('100% Full Waive') }}
                            </button>
                        </div>
                        <input type="number" step="0.01" min="0.01" name="waive_amount" id="qw_waive_amount" 
                            class="form-control form-control-sm text-end fw-bold fs-6" required placeholder="0.00">
                        <small class="text-muted" style="font-size: 11px;">{{ __('Enter full or partial amount to discount.') }}</small>
                    </div>

                    <!-- Waive Reason -->
                    <div class="mb-0">
                        <label class="form-label fw-bold">{{ __('Reason / Remarks') }} <span class="text-danger">*</span></label>
                        <textarea name="reason" id="qw_reason" class="form-control form-control-sm" rows="2" required 
                            placeholder="{{ __('e.g. Goodwill discount approved by Manager') }}"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" id="qw_submit_btn" class="btn btn-sm btn-danger px-4">
                        <i class="fa-solid fa-check-circle me-1"></i> {{ __('Submit Waiver') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 💡 @push('js') ব্যবহার করায় লারাভেল এই স্ক্রিপ্টটিকে পেজের একদম নিচে jQuery লোড হওয়ার পর প্লেস করবে --}}
@push('js')
<script>
$(document).ready(function () {
    let currentMaxAmount = 0;

       window.openWaiveModal = function(chargeId, documentNo, activeAmount) {
        $('#quick_waive_form')[0].reset();
        $('#qw_charge_id').val(chargeId);
        $('#qw_document_type').val('');
        $('#qw_document_id').val('');

        setupModalData(documentNo, activeAmount);
    };

    // 💡 ১.২ গ্লোবাল ফাংশন: ডকুমেন্টস (Bill/Invoice/AssetRegister) টাইপ ও ID দিয়ে মাফ করার জন্য
    window.openWaiveModalForDocument = function(documentType, documentId, documentNo, activeAmount) {
        $('#quick_waive_form')[0].reset();
        $('#qw_charge_id').val('');
        $('#qw_document_type').val(documentType);
        $('#qw_document_id').val(documentId);

        setupModalData(documentNo, activeAmount);
    };

    // 💡 ১.৩ শর্টকাট অ্যালিয়াস (Bill এর জন্য)
    window.openWaiveModalForBill = function(billId, billNo, activeAmount) {
        window.openWaiveModalForDocument('bill', billId, billNo, activeAmount);
    };

    // 💡 ১.৪ শর্টকাট অ্যালিয়াস (Invoice এর জন্য)
    window.openWaiveModalForInvoice = function(invoiceId, invoiceNo, activeAmount) {
        window.openWaiveModalForDocument('invoice', invoiceId, invoiceNo, activeAmount);
    };

    function setupModalData(documentNo, activeAmount) {
        currentMaxAmount = parseFloat(activeAmount) || 0;

        $('#qw_doc_no').text(documentNo);
        $('#qw_active_fee_text').text((typeof format_currency === 'function' ? format_currency(currentMaxAmount) : currentMaxAmount.toFixed(2)));
        $('#qw_waive_amount').val(currentMaxAmount.toFixed(2)).attr('max', currentMaxAmount);
        $('#qw_reason').val('Goodwill discount approved by management');

        $('#quickWaiveModal').modal('show');
    }


    // 100% Full Waive Quick Click Button
    $('#qw_full_waive_btn').click(function() {
        $('#qw_waive_amount').val(currentMaxAmount.toFixed(2));
    });

    // AJAX Form Submission
    $('#quick_waive_form').off('submit').on('submit', function(e) {
        e.preventDefault();

        let chargeId = $('#qw_charge_id').val();
        let docType = $('#qw_document_type').val();
        let docId = $('#qw_document_id').val();

        let waiveAmount = parseFloat($('#qw_waive_amount').val()) || 0;
        if (waiveAmount <= 0 || waiveAmount > currentMaxAmount) {
            let msg = `Invalid amount. Max allowable waive is ${currentMaxAmount.toFixed(2)}`;
            if (typeof showFloatingAlert === "function") { showFloatingAlert('error', msg); } else { alert(msg); }
            return;
        }

        let $btn = $('#qw_submit_btn');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

        // রাউট ইউআরএল নির্ধারণ
        let targetUrl = chargeId 
            ? "{{ route('finance-charges.waive', ':id') }}".replace(':id', chargeId)
            : "{{ route('finance-charges.waive-document') }}";

        let postData = {
            _token: "{{ csrf_token() }}",
            waive_amount: waiveAmount,
            reason: $('#qw_reason').val()
        };

        if (!chargeId && docType && docId) {
            postData.document_type = docType;
            postData.document_id = docId;
        }

        $.ajax({
            url: targetUrl,
            type: 'POST',
            data: postData,
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check-circle me-1"></i> Submit Waiver');
                if (res.success) {
                    $('#quickWaiveModal').modal('hide');

                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert('success', res.message);
                    }

                    // ভিউ বিল মোডাল খোলা থাকলে তা ক্লোজ করা
                    if ($('#viewBillModal').is(':visible')) {
                        $('#viewBillModal').modal('hide');
                    }

                    // ডাটাটেবিলগুলো স্বয়ংক্রিয়ভাবে রিফ্রেশ করা
                    if (window.LaravelDataTables) {
                        ['bill-table', 'invoice-table', 'asset-register-table', 'finance-charge-table'].forEach(function(tableId) {
                            if (window.LaravelDataTables[tableId]) {
                                window.LaravelDataTables[tableId].ajax.reload(null, false);
                            }
                        });
                    }
                }
            },
            error: function(err) {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check-circle me-1"></i> Submit Waiver');
                let msg = err.responseJSON?.message || "Failed to waive finance charge.";
                if (typeof showFloatingAlert === "function") {
                    showFloatingAlert('error', msg);
                } else {
                    alert(msg);
                }
            }
        });
    });
});
</script>
@endpush