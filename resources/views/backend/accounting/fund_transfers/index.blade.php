@extends('backend.layouts.main')

@section('title', __('Fund Transfers (Account to Account)'))

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('Fund Transfers') }}
        @endslot
        @slot('subtitle')
            {{ __('Transfer money between bank accounts, cash registers, and mobile wallets (Contra Entry).') }}
        @endslot
        @featureCan('fund_transfers', 'acc_transfer_create')
            @slot('button')
                <button type="button" class="btn btn-primary" onclick="openCreateTransferModal()">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('New Fund Transfer') }}
                </button>
            @endslot
        @endfeatureCan
    @endcomponent

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
    {{-- ==================== CREATE / EDIT TRANSFER MODAL ==================== --}}
    <div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title text-white fw-bold mb-0" id="transferModalTitle">
                        <i class="fa-solid fa-right-left me-2"></i> {{ __('New Fund Transfer') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="transfer_form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="transfer_id" name="transfer_id">
                    <input type="hidden" id="form_method" name="_method" value="POST">

                    <!-- Hidden Base Currency Fields -->
                    <input type="hidden" name="currency_id" id="currency_id" value="{{ $general_settings['default_currency'] ?? '' }}">
                    <input type="hidden" name="exchange_rate" id="exchange_rate" value="1">

                    <div class="modal-body p-4">
                        <!-- Top Header Row: Date & Branch -->
                        <div class="row g-3 mb-3 bg-light p-3 rounded border">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Transfer Date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="transfer_date" id="transfer_date" class="form-control form-control-sm flatpickr-single" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Branch') }} <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" class="form-select form-select-sm" required>
                                    @isset($branches)
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ ($general_settings['default_branch'] ?? '') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>

                        <!-- 🟢 1. From Account Row (Dropdown + Inline Available Balance) -->
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-danger small mb-1">
                                    <i class="fa-solid fa-arrow-up-from-bracket me-1"></i> {{ __('From Account (Source)') }} <span class="text-danger">*</span>
                                </label>
                                <select name="from_account_id" id="from_account_id" class="form-select form-select-sm account-select" required>
                                    <option value="">{{ __('Select Source Account') }}</option>
                                    @foreach ($paymentAccounts as $pAccount)
                                        <option value="{{ $pAccount->id }}" data-balance="{{ $pAccount->current_balance ?? 0 }}">
                                            {{ $pAccount->account_name }} ({{ ucfirst($pAccount->account_type->value ?? $pAccount->account_type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small mb-1">{{ __('Available Balance') }}</label>
                                <div class="form-control form-control-sm bg-light text-end fw-bold text-dark" id="from_account_balance_display">0.00</div>
                            </div>
                        </div>

                        <!-- 🟢 2. To Account Row (Dropdown + Inline Current Balance) -->
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-success small mb-1">
                                    <i class="fa-solid fa-arrow-down-to-bracket me-1"></i> {{ __('To Account (Destination)') }} <span class="text-danger">*</span>
                                </label>
                                <select name="to_account_id" id="to_account_id" class="form-select form-select-sm account-select" required>
                                    <option value="">{{ __('Select Destination Account') }}</option>
                                    @foreach ($paymentAccounts as $pAccount)
                                        <option value="{{ $pAccount->id }}" data-balance="{{ $pAccount->current_balance ?? 0 }}">
                                            {{ $pAccount->account_name }} ({{ ucfirst($pAccount->account_type->value ?? $pAccount->account_type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small mb-1">{{ __('Current Balance') }}</label>
                                <div class="form-control form-control-sm bg-light text-end fw-bold text-dark" id="to_account_balance_display">0.00</div>
                            </div>
                        </div>

                        <!-- Amount & Payment Method -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Transfer Amount') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="transfer_amount" 
                                    class="form-control form-control-sm text-end fw-bold fs-6" required placeholder="0.00">
                                <small class="text-danger micro-text fw-bold" id="insufficient_balance_warning" style="display: none;">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ __('Amount exceeds available source balance!') }}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Transfer Method') }}</label>
                                <select name="payment_method" id="payment_method" class="form-select form-select-sm">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="mfs">Mobile Banking</option>
                                </select>
                            </div>
                        </div>

                        <!-- Reference No & Attachment -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Ref / Cheque / Txn No.') }}</label>
                                <input type="text" name="reference_no" id="reference_no" class="form-control form-control-sm" placeholder="e.g. Cheque #12345 / Txn ID">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small mb-1">{{ __('Attachment / Slip') }}</label>
                                <input type="file" name="attachment" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>

                        <!-- Note / Remarks -->
                        <div class="mb-0">
                            <label class="form-label fw-bold small mb-1">{{ __('Remarks / Note') }}</label>
                            <textarea name="note" id="transfer_note" class="form-control form-control-sm" rows="2" placeholder="Optional transfer notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" id="transfer_submit_btn" class="btn btn-sm btn-primary px-4">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save Transfer') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ==================== VIEW TRANSFER DETAILS MODAL ==================== --}}
    <div class="modal fade" id="viewTransferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title text-white fw-bold mb-0">
                        <i class="fa-solid fa-file-invoice me-2"></i> {{ __('Fund Transfer Details') }} - <span id="vt_no"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-3 bg-light p-3 rounded border">
                        <div class="col-md-6 mb-2">
                            <strong class="text-muted small d-block">{{ __('From (Source Account)') }}:</strong>
                            <span id="vt_from" class="fw-bold text-danger fs-6"></span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong class="text-muted small d-block">{{ __('To (Destination Account)') }}:</strong>
                            <span id="vt_to" class="fw-bold text-success fs-6"></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <strong class="text-muted small d-block">{{ __('Transfer Date') }}:</strong>
                            <span id="vt_date" class="fw-semibold"></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <strong class="text-muted small d-block">{{ __('Amount') }}:</strong>
                            <span id="vt_amount" class="fw-bold text-primary fs-6"></span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong class="text-muted small d-block">{{ __('Method') }}:</strong>
                            <span id="vt_method" class="fw-semibold"></span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong class="text-muted small d-block">{{ __('Ref / Txn No') }}:</strong>
                            <span id="vt_ref" class="fw-semibold"></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <strong class="text-muted small d-block">{{ __('Branch') }}:</strong>
                            <span id="vt_branch" class="fw-semibold"></span>
                        </div>
                        <div class="col-md-6 mt-2">
                            <strong class="text-muted small d-block">{{ __('Created By') }}:</strong>
                            <span id="vt_creator" class="fw-semibold"></span>
                        </div>
                    </div>

                    <div class="row mt-3 pt-2 border-top">
                        <div class="col-md-8">
                            <strong class="text-muted small d-block">{{ __('Remarks / Note') }}:</strong>
                            <p id="vt_note" class="text-dark mb-0 small"></p>
                        </div>
                        <div class="col-md-4 text-end" id="vt_attachment_container" style="display: none;">
                            <strong class="text-muted small d-block mb-1">{{ __('Attachment') }}:</strong>
                            <a id="vt_attachment_link" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-paperclip me-1"></i> {{ __('View Slip') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        let currentSourceBalance = 0;

        // 💡১. একই একাউন্ট ড্রপডাউনে সিলেক্ট হওয়া ব্লক করার ফাংশন
        function preventSameAccountSelection() {
            let fromVal = $('#from_account_id').val();
            let toVal = $('#to_account_id').val();

            // সব অপশন আগে অন করা
            $('#from_account_id option, #to_account_id option').prop('disabled', false);

            // From Account-এ যা সিলেক্ট হবে, তা To Account-এ ডিসেবল করা
            if (fromVal) {
                $('#to_account_id option[value="' + fromVal + '"]').prop('disabled', true);
                if (toVal === fromVal) {
                    $('#to_account_id').val('');
                    $('#to_account_balance_display').text('0.00');
                }
            }

            // To Account-এ যা সিলেক্ট হবে, তা From Account-এ ডিসেবল করা
            if (toVal) {
                $('#from_account_id option[value="' + toVal + '"]').prop('disabled', true);
                if (fromVal === toVal) {
                    $('#from_account_id').val('');
                    $('#from_account_balance_display').text('0.00');
                }
            }
        }

        // 💡 ২. রিয়াল-টাইম ইনলাইন ব্যালেন্স আপডেট ফাংশন
        function updateBalanceDisplays() {
            // From Account Balance
            let $fromOpt = $('#from_account_id').find('option:selected');
            let fromBal = $fromOpt.data('balance');

            if ($('#from_account_id').val() && fromBal !== undefined) {
                currentSourceBalance = parseFloat(fromBal) || 0;
                let formatted = typeof format_currency === 'function' ? format_currency(currentSourceBalance) : currentSourceBalance.toFixed(2);
                $('#from_account_balance_display').text(formatted);
            } else {
                currentSourceBalance = 0;
                $('#from_account_balance_display').text('0.00');
            }

            // To Account Balance
            let $toOpt = $('#to_account_id').find('option:selected');
            let toBal = $toOpt.data('balance');

            if ($('#to_account_id').val() && toBal !== undefined) {
                let toCurrentBal = parseFloat(toBal) || 0;
                let formatted = typeof format_currency === 'function' ? format_currency(toCurrentBal) : toCurrentBal.toFixed(2);
                $('#to_account_balance_display').text(formatted);
            } else {
                $('#to_account_balance_display').text('0.00');
            }

            checkAmountWarning();
        }

        // Dropdown Event Listeners
        $('#from_account_id, #to_account_id').on('change', function() {
            preventSameAccountSelection();
            updateBalanceDisplays();
        });

        // Insufficient Balance Warning Listener
        $('#transfer_amount').on('input', function() {
            checkAmountWarning();
        });

        function checkAmountWarning() {
            let enteredAmount = parseFloat($('#transfer_amount').val()) || 0;
            if (currentSourceBalance > 0 && enteredAmount > currentSourceBalance) {
                $('#insufficient_balance_warning').slideDown(150);
                $('#from_account_balance_display').removeClass('text-dark').addClass('text-danger');
            } else {
                $('#insufficient_balance_warning').slideUp(150);
                $('#from_account_balance_display').removeClass('text-danger').addClass('text-dark');
            }
        }

        window.openCreateTransferModal = function() {
            $('#transfer_form')[0].reset();
            $('#transfer_id').val('');
            $('#form_method').val('POST');
            $('#from_account_balance_display, #to_account_balance_display').text('0.00');
            $('#insufficient_balance_warning').hide();
            currentSourceBalance = 0;

            preventSameAccountSelection();

            $('#transferModalTitle').html('<i class="fa-solid fa-right-left me-2"></i> {{ __("New Fund Transfer") }}');

            if (typeof flatpickr !== 'undefined') {
                flatpickr("#transfer_date", {
                    disableMobile: true,
                    altInput: true,
                    altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                    dateFormat: "Y-m-d",
                    defaultDate: 'today',
                    static: true
                });
            }

            $('#transferModal').modal('show');
        };

        window.editTransfer = function(transferId) {
            let editUrl = "{{ route('fund-transfers.edit', ':id') }}".replace(':id', transferId);

            $.ajax({
                url: editUrl,
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        let data = res.data;
                        $('#transfer_form')[0].reset();
                        $('#transfer_id').val(data.id);
                        $('#form_method').val('PATCH');

                        if (typeof flatpickr !== 'undefined') {
                            flatpickr("#transfer_date", {
                                disableMobile: true,
                                altInput: true,
                                altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                                dateFormat: "Y-m-d",
                                defaultDate: data.transfer_date,
                                static: true
                            });
                        }

                        $('#branch_id').val(data.branch_id);
                        if (data.currency_id) $('#currency_id').val(data.currency_id);
                        $('#from_account_id').val(data.from_account_id);
                        $('#to_account_id').val(data.to_account_id);
                        
                        preventSameAccountSelection();
                        updateBalanceDisplays();

                        $('#transfer_amount').val(parseFloat(data.amount).toFixed(2)).trigger('input');
                        $('#payment_method').val(data.payment_method || 'cash');
                        $('#reference_no').val(data.reference_no || '');
                        $('#transfer_note').val(data.note || '');

                        $('#transferModalTitle').html('<i class="fa-solid fa-pen-to-square me-2"></i> {{ __("Edit Fund Transfer") }} - ' + data.transfer_no);
                        $('#transferModal').modal('show');
                    }
                }
            });
        };

        window.viewTransfer = function(transferId) {
            let viewUrl = "{{ route('fund-transfers.show', ':id') }}".replace(':id', transferId);

            $.ajax({
                url: viewUrl,
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        let data = res.data;
                        $('#vt_no').text(data.transfer_no);
                        $('#vt_from').text(data.from_account ? data.from_account.account_name : 'N/A');
                        $('#vt_to').text(data.to_account ? data.to_account.account_name : 'N/A');
                        $('#vt_date').text(typeof formatedDate === 'function' ? formatedDate(data.transfer_date) : data.transfer_date);
                        $('#vt_amount').text(typeof format_currency === 'function' ? format_currency(data.amount) : parseFloat(data.amount).toFixed(2));
                        $('#vt_method').text((data.payment_method || 'cash').toUpperCase());
                        $('#vt_ref').text(data.reference_no || 'N/A');
                        $('#vt_branch').text(data.branch ? data.branch.name : 'N/A');
                        $('#vt_creator').text(data.creator ? data.creator.name : 'System');
                        $('#vt_note').text(data.note || 'No notes provided.');

                        if (res.attachment_url) {
                            $('#vt_attachment_link').attr('href', res.attachment_url);
                            $('#vt_attachment_container').show();
                        } else {
                            $('#vt_attachment_container').hide();
                        }

                        $('#viewTransferModal').modal('show');
                    }
                }
            });
        };

        // Form Submit Handler
        $('#transfer_form').off('submit').on('submit', function(e) {
            e.preventDefault();

            let transferId = $('#transfer_id').val();
            let isEdit = !!transferId;
            let formData = new FormData(this);
            let $btn = $('#transfer_submit_btn');

            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

            let targetUrl = isEdit 
                ? "{{ route('fund-transfers.update', ':id') }}".replace(':id', transferId)
                : "{{ route('fund-transfers.store') }}";

            $.ajax({
                url: targetUrl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i> {{ __("Save Transfer") }}');
                    if (res.success) {
                        $('#transferModal').modal('hide');
                        if (typeof showFloatingAlert === "function") {
                            showFloatingAlert('success', res.message);
                        }
                        if (window.LaravelDataTables && window.LaravelDataTables['fund-transfer-table']) {
                            window.LaravelDataTables['fund-transfer-table'].ajax.reload(null, false);
                        }
                    }
                },
                error: function(err) {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i> {{ __("Save Transfer") }}');
                    let msg = err.responseJSON?.message || "Failed to save fund transfer.";
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