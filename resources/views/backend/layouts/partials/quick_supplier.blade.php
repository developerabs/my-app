<!-- Quick Supplier Modal -->
<div class="modal fade" id="quickSupplierModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title text-light fw-bold mb-0">
                    <i class="fa fa-user-plus me-1"></i> Quick Add Supplier
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickSupplierForm">
                @csrf
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <!-- Supplier Contact Person Name -->
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small mb-1">
                                Supplier Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" id="quick_s_name" class="form-control form-control-sm" required placeholder="e.g. Rahim Khan">
                        </div>

                        <!-- Company / Shop Name -->
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small mb-1">
                                Company / Shop Name <small class="text-muted fw-normal">(Optional)</small>
                            </label>
                            <input type="text" name="company_name" id="quick_s_company" class="form-control form-control-sm" placeholder="e.g. Rahim Traders">
                        </div>

                        <!-- Phone Number -->
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small mb-1">
                                Phone Number <small class="text-muted fw-normal">(Optional)</small>
                            </label>
                            <input type="tel" name="phone" id="quick_s_phone" class="form-control form-control-sm phone-input" placeholder="+88017XXXXXXXX">
                        </div>

                        <!-- Opening Balance -->
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold small mb-1">
                                Opening Balance <small class="text-muted fw-normal">(Payable Due)</small>
                            </label>
                            <input type="number" step="0.01" min="0" name="opening_balance" id="quick_s_opening_balance" class="form-control form-control-sm text-end" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="quick_s_submit_btn" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="fa fa-save me-1"></i> Save & Select
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reusable JavaScript Logic -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
    let activeSupplierSelectElement = null;

    // 1. Global Select2 Initialization with Quick-Add Search Result
    window.initGlobalSupplierSelect = function(element) {
        let $el = $(element);
        if ($el.hasClass("select2-hidden-accessible")) {
            $el.select2('destroy');
        }

        $el.select2({
            width: '100%',
            language: {
                noResults: function() {
                    let term = $('.select2-container--open .select2-search__field').val() || '';
                    return $(`<button type="button" class="btn btn-link text-primary p-0 text-start fw-bold" onclick="window.prepareSupplierModal(this)">
                        <i class="fa fa-plus-circle me-1"></i> Add New Supplier: <strong>${term}</strong>
                        </button>`).data('search-term', term).data('select-el', element);
                }
            },
            escapeMarkup: function(markup) {
                return markup;
            }
        });
    };

    // 2. Triggered when clicking "Add New Supplier" link from Select2 dropdown
    window.prepareSupplierModal = function(btnElement) {
        let $btn = $(btnElement);
        activeSupplierSelectElement = $btn.data('select-el');
        let term = ($btn.data('search-term') || '').trim();

        $('.supplier-select, select[name="supplier_id"]').select2('close');

        // Reset form fields
        $('#quickSupplierForm')[0].reset();

        let name = term;
        let company = "";
        let phone = "";

        // Smart text parser for quick-type search formats:
        // Case A: "Rahim - 01700..."
        if (term.includes('-')) {
            let parts = term.split('-');
            name = parts[0].trim();
            phone = parts[1].trim();
        } 
        // Case B: "Rahim (Rahim Store)"
        else if (term.includes('(') && term.includes(')')) {
            name = term.substring(0, term.indexOf('(')).trim();
            company = term.substring(term.indexOf('(') + 1, term.indexOf(')')).trim();
        }

        $('#quick_s_name').val(name);
        $('#quick_s_company').val(company);
        $('#quick_s_phone').val(phone);
        $('#quick_s_opening_balance').val('');

        if (typeof window.initPhoneInputs === "function") {
            window.initPhoneInputs("#quick_s_phone");
        }

        $('#quickSupplierModal').modal('show');
    };

    // Auto-initialize for existing supplier select inputs on page load
    $('.supplier-select, select[name="supplier_id"]').each(function() {
        window.initGlobalSupplierSelect(this);
    });

    // 3. AJAX Form Submission
    $('#quickSupplierForm').off('submit').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#quick_s_submit_btn');
        const originalBtnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        let phoneInput = document.querySelector("#quick_s_phone");
        let rawPhone = $('#quick_s_phone').val().trim();
        let fullPhone = null;

        if (rawPhone !== '') {
            fullPhone = (phoneInput && phoneInput.iti) ? phoneInput.iti.getNumber() : rawPhone;
        }

        let nameVal = $('#quick_s_name').val().trim();
        let companyVal = $('#quick_s_company').val().trim() || null;

        let payload = {
            _token: "{{ csrf_token() }}",
            name: nameVal,
            company_name: companyVal,
            phone: fullPhone,
            opening_balance: parseFloat($('#quick_s_opening_balance').val()) || 0
        };

        let $targetSelect = $(activeSupplierSelectElement || '.supplier-select, select[name="supplier_id"]');

        $.ajax({
            url: "{{ route('suppliers.store') }}",
            method: "POST",
            data: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                $btn.prop('disabled', false).html(originalBtnHtml);

                if (res.success || res.data) {
                    let newSupplier = res.data || res.supplier;
                    let newId = newSupplier.id;
                    
                    // 🟢 Smart Fallback Display Title
                    let displayName = newSupplier.name;
                    if (newSupplier.company_name) {
                        displayName += ` (${newSupplier.company_name})`;
                    } else if (newSupplier.phone) {
                        displayName += ` - ${newSupplier.phone}`;
                    } else {
                        displayName += ` [ID: #${newId.substring(0, 8).toUpperCase()}]`;
                    }

                    // Dynamically append new option to all supplier dropdowns on the page
                    $('.supplier-select, select[name="supplier_id"]').each(function() {
                        let $select = $(this);
                        if (!$select.find(`option[value="${newId}"]`).length) {
                            let newOption = new Option(displayName, newId, false, false);
                            $select.append(newOption);
                        }
                    });

                    // Auto-select in the active dropdown
                    if ($targetSelect && $targetSelect.length) {
                        $targetSelect.val(newId).trigger('change');
                    }

                    $('#quickSupplierModal').modal('hide');
                    $('#quickSupplierForm')[0].reset();
                    activeSupplierSelectElement = null;

                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert('success', res.message || 'Supplier created successfully.');
                    }
                }
            },
            error: function(err) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                let msg = err.responseJSON?.message || "Failed to create supplier";
                if (err.responseJSON?.errors) {
                    const firstKey = Object.keys(err.responseJSON.errors)[0];
                    msg = err.responseJSON.errors[firstKey][0];
                }

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