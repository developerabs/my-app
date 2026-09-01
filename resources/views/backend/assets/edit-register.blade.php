@extends('backend.layouts.main')

@section('title')
    {{ __('Edit Asset Register') }} - {{ $register->register_no ?? 'Voucher' }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('Edit Asset Register') }}
        @endslot
        @slot('subtitle')
            {{ __('Update asset register details and post adjusted accounting entries.') }}
        @endslot
        @slot('button')
            <a href="{{ route('assets.register.index') }}" class="btn btn-primary"><i class="fa-solid fa-list me-1"></i>
                {{ __('file.button.list') }} {{ __('file.asset_register') }}</a>
        @endslot
    @endcomponent

    <form action="{{ route('assets.register.update', $register->id) }}" method="POST" id="asset_register_form">
        @csrf
        @method('PUT')
        
        <div class="card">
            <div class="card-header">
                <div class="row align-items-end">
                    @include('backend.accounting.partials.journal-header', [
                        'dateLabel' => __('file.field.register_date'),
                        'dateName' => 'register_date',
                        'defaultDate' => $register->register_date?->format('Y-m-d')
                    ])

                    <!-- Asset Entry Type -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('file.field.asset_entry_type') }}</label>
                        <select name="entry_type" id="entry_type" class="form-select select-picker" required>
                            @foreach ([\App\Enums\AssetEntryType::OPENING, \App\Enums\AssetEntryType::PURCHASE] as $entryType)
                                <option value="{{ $entryType->value }}" {{ old('entry_type', $register->entry_type?->value ?? $register->entry_type) == $entryType->value ? 'selected' : '' }}>
                                    {{ ucfirst($entryType->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Payment Account -->
                    <div class="col-md-2 mb-3 payment_account_container" style="display: none;">
                        <label class="form-label fw-bold">{{ __('file.field.payment_account') }}</label>
                        <select name="payment_account_id" id="payment_account_id" class="form-select select-picker">
                            <option value="">{{ __('Select Account') }}</option>
                            @forelse ($paymentAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('payment_account_id', $register->payment_account_id) == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_name }}
                                </option>
                            @empty
                                <option value="">{{ __('file.option.no') }}</option>
                            @endforelse
                        </select>
                    </div>

                    <!-- Remarks Field in Header -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">{{ __('Remarks') }}</label>
                        <input type="text" name="remarks" class="form-control form-control-sm"
                            value="{{ old('remarks', $register->remarks) }}" placeholder="{{ __('Optional remarks...') }}">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="asset_items_table">
                        <thead>
                            <tr>
                                <th width="18%">{{ __('Asset') }} <span class="text-danger">*</span></th>
                                <th width="15%" class="supplier_column" style="display: none;">{{ __('Supplier') }} <span class="text-danger">*</span></th>
                                <th width="8%">{{ __('Quantity') }} <span class="text-danger">*</span></th>
                                <th width="10%">{{ __('Unit Cost') }} <span class="text-danger">*</span></th>
                                <th width="10%">{{ __('Total Cost') }}</th>
                                <th width="10%" class="supplier_column" style="display: none;">{{ __('Paid Amount') }} <span class="text-danger">*</span></th>
                                <th width="9%">{{ __('Salvage Value') }}</th>
                                <th width="9%">{{ __('Useful Life') }}</th>
                                <th width="11%">{{ __('Start Date') }}</th>
                                <th width="6%" class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($register->items as $index => $item)
                                <tr data-row-index="{{ $index }}">
                                    <td>
                                        <select name="items[{{ $index }}][asset_id]" class="form-select item-asset asset-select" required>
                                            <option value="">{{ __('Select Asset') }}</option>
                                            @foreach ($assets as $asset)
                                                <option value="{{ $asset->id }}" {{ $item->asset_id == $asset->id ? 'selected' : '' }}>
                                                    {{ $asset->asset_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="supplier_column" style="display: none;">
                                        <select name="items[{{ $index }}][supplier_id]" class="form-select supplier-select">
                                            <option value="">{{ __('file.option.select_supplier') }}</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ $item->supplier_id == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="any" name="items[{{ $index }}][quantity]" class="form-control form-control-sm quantity" value="{{ $item->quantity }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="any" name="items[{{ $index }}][unit_cost]" class="form-control form-control-sm unit_cost" value="{{ $item->unit_cost }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="any" name="items[{{ $index }}][total_cost]" class="form-control form-control-sm total_cost" value="{{ $item->total_cost }}" readonly>
                                    </td>
                                    <td class="supplier_column" style="display: none;">
                                        <input type="number" step="any" name="items[{{ $index }}][paid_amount]" class="form-control form-control-sm paid_amount" value="{{ $item->paid_amount }}" placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="any" name="items[{{ $index }}][salvage_value]" class="form-control form-control-sm" value="{{ $item->salvage_value ?? 0 }}">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][useful_life]" class="form-control form-control-sm" value="{{ $item->useful_life }}" placeholder="e.g. 5 Years">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][depreciation_start_date]" class="form-control form-control-sm depreciation_start_date" value="{{ $item->depreciation_start_date?->format('Y-m-d') }}" placeholder="YYYY-MM-DD">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-sm btn-success" id="add_row_btn">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Add More') }}
                    </button>

                    <div class="text-end">
                        <h4 class="fw-bold mb-0 text-dark">
                            {{ __('Grand Total') }}: <span id="grand_total" class="text-primary">{{ number_format($register->total_amount ?? 0, 2) }}</span>
                        </h4>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> {{ __('Update Register') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('modals')
    <div class="modal fade" id="createAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Create Asset') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createAssetForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.assets._form', [
                            'isEdit' => false,
                            'accounts' => $asset_accounts,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save Asset') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('backend.layouts.partials.quick_supplier')
@endsection

@push('js')
    <script>
        let globalRowCounter = {{ count($register->items) }};
        let activeAssetSelect = null;

        function toggleEntryTypeFields() {
            let val = $('#entry_type').val();
            if (val === 'purchase') {
                $('.payment_account_container').fadeIn();
                $('#payment_account_id').prop('required', true);
                $('.supplier_column').fadeIn();
                $('.supplier-select').prop('required', true);
                $('.paid_amount').prop('required', true);
            } else {
                $('.payment_account_container').fadeOut();
                $('#payment_account_id').prop('required', false);
                $('.supplier_column').fadeOut();
                $('.supplier-select').prop('required', false);
                $('.paid_amount').prop('required', false);
            }
        }

        $('#entry_type').change(function() {
            toggleEntryTypeFields();
        });

        // Trigger initial field state
        toggleEntryTypeFields();

        function calculateTotals() {
            let grandTotal = 0;
            $('#asset_items_table tbody tr').each(function() {
                let row = $(this);
                let qty = parseFloat(row.find('.quantity').val()) || 0;
                let unitCost = parseFloat(row.find('.unit_cost').val()) || 0;
                let total = qty * unitCost;
                row.find('.total_cost').val(total.toFixed(2));
                grandTotal += total;
            });
            $('#grand_total').text(grandTotal.toFixed(2));
        }

        $(document).on('input', '.quantity, .unit_cost', function() {
            calculateTotals();
        });

        // ==================== ASSET SELECT2 LOGIC ====================

        function initAssetSelect(element) {
            if ($(element).hasClass("select2-hidden-accessible")) {
                $(element).select2('destroy');
            }

            $(element).select2({
                width: '100%',
                dropdownParent: $('#asset_register_form'),
                language: {
                    noResults: function() {
                        let term = $('.select2-container--open .select2-search__field').val() || '';
                        return $(`<button type="button" class="btn btn-link text-primary p-0" onclick="prepareAssetModal(this)">
                            <i class="fa fa-plus-circle me-1"></i> Add New: <strong>${term}</strong>
                            </button>`).data('search-term', term).data('select-el', element);
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                }
            });
        }

        window.prepareAssetModal = function(btnElement) {
            let $btn = $(btnElement);
            activeAssetSelect = $btn.data('select-el');
            let term = $btn.data('search-term');

            $('.asset-select').select2('close');
            $('input[name="asset_name"]').val(term);
            $('#createAssetModal').modal('show');
        };

        $('.asset-select').each(function() {
            initAssetSelect(this);
        });

        $('#createAssetForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let assetNameInput = $('#createAssetModal').find('input[name="asset_name"]').val() || formData.get('asset_name');
            let $targetSelect = $(activeAssetSelect);

            $.ajax({
                url: "{{ route('assets.store') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        let newId = response.id;
                        let newName = assetNameInput;

                        $('.asset-select').each(function() {
                            let $select = $(this);
                            if (!$select.find(`option[value="${newId}"]`).length) {
                                let newOption = new Option(newName, newId, false, false);
                                $select.append(newOption);
                            }
                        });

                        if ($targetSelect && $targetSelect.length) {
                            $targetSelect.val(newId).trigger('change');
                        }

                        $('#createAssetModal').modal('hide');
                        $('#createAssetForm')[0].reset();
                        $('#createAssetModal').find('select').each(function() {
                            $(this).val(null).trigger('change');
                        });

                        activeAssetSelect = null;
                        if (typeof showFloatingAlert === "function") {
                            showFloatingAlert('success', 'Asset created successfully.');
                        }
                    }
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || "Failed to create asset";
                    if (typeof showFloatingAlert === "function") {
                        showFloatingAlert('error', msg);
                    } else {
                        alert(msg);
                    }
                }
            });
        });

        // ==================== FLATPICKR INITIALIZATION ====================

        function initFlatpickr(element) {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(element, {
                    altInput: true,
                    altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                    dateFormat: "Y-m-d",
                    static: true,
                });
            }
        }

        $('.depreciation_start_date').each(function() {
            initFlatpickr(this);
        });

        // Add Dynamic Row
        $('#add_row_btn').click(function() {
            globalRowCounter++;
            let rowIndex = globalRowCounter;

            let isPurchase = $('#entry_type').val() === 'purchase';
            let supplierStyle = isPurchase ? '' : 'display: none;';
            let supplierRequired = isPurchase ? 'required' : '';

            let newRow = `<tr data-row-index="${rowIndex}">
                <td>
                    <select name="items[${rowIndex}][asset_id]" class="form-select item-asset asset-select" required>
                        <option value="">{{ __('Select Asset') }}</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}">{{ $asset->asset_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="supplier_column" style="${supplierStyle}">
                    <select name="items[${rowIndex}][supplier_id]" class="form-select supplier-select" ${supplierRequired}>
                        <option value="">{{ __('file.option.select_supplier') }}</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="any" name="items[${rowIndex}][quantity]" class="form-control form-control-sm quantity" value="1" required></td>
                <td><input type="number" step="any" name="items[${rowIndex}][unit_cost]" class="form-control form-control-sm unit_cost" required placeholder="0.00"></td>
                <td><input type="number" step="any" name="items[${rowIndex}][total_cost]" class="form-control form-control-sm total_cost" readonly placeholder="0.00"></td>
                <td class="supplier_column" style="${supplierStyle}">
                    <input type="number" step="any" name="items[${rowIndex}][paid_amount]" class="form-control form-control-sm paid_amount" placeholder="0.00">
                </td>
                <td><input type="number" step="any" name="items[${rowIndex}][salvage_value]" class="form-control form-control-sm" value="0"></td>
                <td><input type="text" name="items[${rowIndex}][useful_life]" class="form-control form-control-sm" placeholder="e.g. 5 Years"></td>
                <td><input type="text" name="items[${rowIndex}][depreciation_start_date]" class="form-control form-control-sm depreciation_start_date" placeholder="YYYY-MM-DD"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;

            $('#asset_items_table tbody').append(newRow);

            let $addedRow = $('#asset_items_table tbody tr:last');
            initAssetSelect($addedRow.find('.asset-select'));
            
            if (typeof window.initGlobalSupplierSelect === "function") {
                window.initGlobalSupplierSelect($addedRow.find('.supplier-select'));
            }

            initFlatpickr($addedRow.find('.depreciation_start_date')[0]);
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#asset_items_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateTotals();
            }
        });

        $('#createAssetModal').find('.account-picker').select2({
            placeholder: "Select an Account",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#createAssetModal')
        });
    </script>
@endpush