@extends('backend.layouts.main')

@section('title', 'Edit Vendor Bill - ' . $bill->bill_no)

@push('css')
    <style>
        .bill-row td { vertical-align: middle; padding: 8px 6px; }
        .line-amount::-webkit-inner-spin-button, .line-amount::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .line-amount { -moz-appearance: textfield; font-weight: 600; }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.edit_bill') ?? 'Edit Vendor Bill' }}
        @endslot
        @slot('subtitle')
            Editing Bill No: <strong class="text-primary">{{ $bill->bill_no }}</strong>
        @endslot
        @slot('button')
            <a href="{{ route('bills.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> Bill List
            </a>
        @endslot
    @endcomponent

    <form action="{{ route('bills.update', $bill->id) }}" method="POST" enctype="multipart/form-data" id="bill_form">
        @csrf
        @method('PATCH')
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-end">
                    <!-- Standard Re-usable Journal Header Partial -->
                    @include('backend.accounting.partials.journal-header', [
                        'dateLabel' => 'Bill Date',
                        'dateName' => 'bill_date',
                    ])

                    <!-- Supplier Selection (Added .supplier-select class) -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-select supplier-select" required>
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $bill->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Vendor Invoice No -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-semibold">Vendor Invoice No.</label>
                        <input type="text" name="vendor_invoice_no" class="form-control"
                            value="{{ old('vendor_invoice_no', $bill->vendor_invoice_no) }}" placeholder="e.g. INV-98765">
                    </div>

                    <!-- Due Date -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                        <input type="text" name="due_date" id="due_date" class="form-control flatpickr-single"
                            value="{{ old('due_date', $bill->due_date?->format('Y-m-d')) }}" required>
                    </div>

                    <!-- Project Selection (Header Scope) -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-semibold">Project <small class="text-muted fw-normal">(Optional)</small></label>
                        <select name="project_id" id="header_project_id" class="form-select select-picker">
                            <option value="">Select Project (None)</option>
                            @isset($projects)
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id', $bill->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->name ?? $project->title }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <!-- Attachment -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Bill Copy / Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                        @if($bill->attachment)
                            <small class="text-success d-block mt-1"><i class="fa-solid fa-paperclip me-1"></i> Current File Attached</small>
                        @endif
                    </div>

                    <!-- Note / Remarks -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Note / Remarks</label>
                        <input type="text" name="note" class="form-control"
                            value="{{ old('note', $bill->note) }}" placeholder="Optional vendor bill notes...">
                    </div>
                    <div class="col-md-8">
                        @include('backend.accounting.partials._late_fee_config', ['model' => $bill])
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Reusable Late Fee Configuration Partial (Populated with $bill data) -->
                

                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-1"></i> Bill Categories & Line Items</h6>
                
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="bill_items_table">
                        <thead>
                            <tr>
                                <th width="30%">Expense Category <span class="text-danger">*</span></th>
                                <th width="30%">Description / Line Note</th>
                                <th width="20%">Project <small class="text-muted fw-normal">(Optional)</small></th>
                                <th width="15%" class="text-end">Amount <span class="text-danger">*</span></th>
                                <th width="5%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bill->items as $index => $item)
                                <tr data-row-index="{{ $index }}" class="bill-row">
                                    <td>
                                        <select name="items[{{ $index }}][expense_account_id]" class="form-select select2-picker" required>
                                            <option value="">Select Expense Category</option>
                                            @foreach ($expenseAccounts as $eAccount)
                                                <option value="{{ $eAccount->id }}" {{ $item->expense_account_id == $eAccount->id ? 'selected' : '' }}>
                                                    {{ $eAccount->account_code }} - {{ $eAccount->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][description]" value="{{ $item->description }}" class="form-control form-control-sm" placeholder="Line item details...">
                                    </td>
                                    <td>
                                        <select name="items[{{ $index }}][project_id]" class="form-select select2-picker line-project-select">
                                            <option value="">Default (Header Project)</option>
                                            @isset($projects)
                                                @foreach($projects as $project)
                                                    <option value="{{ $project->id }}" {{ ($item->project_id ?? $bill->project_id) == $project->id ? 'selected' : '' }}>
                                                        {{ $project->name ?? $project->title }}
                                                    </option>
                                                @endforeach
                                            @endisset
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][amount]" value="{{ $item->amount }}" class="form-control form-control-sm text-end line-amount" required placeholder="0.00">
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
                    <button type="button" class="btn btn-sm btn-success px-3" id="add_bill_row_btn">
                        <i class="fa-solid fa-plus me-1"></i> Add More Item
                    </button>

                    <div class="text-end">
                        <h4 class="fw-bold mb-0 text-dark">
                            Total Bill: <span id="grand_bill_total" class="text-primary">{{ number_format($bill->total_amount, 2) }}</span>
                        </h4>
                    </div>
                </div>

                <div class="text-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Update & Re-post Bill
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('modals')
    <!-- Quick Supplier Modal Partial Integration -->
    @include('backend.layouts.partials.quick_supplier')
@endsection

@push('js')
    <script>
        let globalBillRowCounter = {{ count($bill->items) }};

        function calculateBillTotals() {
            let grandTotal = 0;
            $('#bill_items_table tbody tr').each(function() {
                let amount = parseFloat($(this).find('.line-amount').val()) || 0;
                grandTotal += amount;
            });
            $('#grand_bill_total').text(grandTotal.toFixed(2));
        }

        $(document).on('input', '.line-amount', function() {
            calculateBillTotals();
        });

        function initSelect2Picker(element) {
            if ($(element).hasClass("select2-hidden-accessible")) {
                $(element).select2('destroy');
            }
            $(element).select2({ width: '100%' });
        }

        $('.select2-picker').each(function() {
            initSelect2Picker(this);
        });

        flatpickr("#due_date", {
            disableMobile: true,
            altInput: true,
            altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
            dateFormat: "Y-m-d",
            static: true,
            allowInput: true
        });

        $('#add_bill_row_btn').click(function() {
            globalBillRowCounter++;
            let rowIndex = globalBillRowCounter;

            let newRow = `<tr data-row-index="${rowIndex}" class="bill-row">
                <td>
                    <select name="items[${rowIndex}][expense_account_id]" class="form-select select2-picker" required>
                        <option value="">Select Expense Category</option>
                        @foreach ($expenseAccounts as $eAccount)
                            <option value="{{ $eAccount->id }}">
                                {{ $eAccount->account_code }} - {{ $eAccount->account_name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][description]" class="form-control form-control-sm" placeholder="Line item details...">
                </td>
                <td>
                    <select name="items[${rowIndex}][project_id]" class="form-select select2-picker line-project-select">
                        <option value="">Default (Header Project)</option>
                        @isset($projects)
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name ?? $project->title }}</option>
                            @endforeach
                        @endisset
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][amount]" class="form-control form-control-sm text-end line-amount" required placeholder="0.00">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;

            $('#bill_items_table tbody').append(newRow);

            let $addedRow = $('#bill_items_table tbody tr:last');
            initSelect2Picker($addedRow.find('.select2-picker'));
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#bill_items_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateBillTotals();
            }
        });
    </script>
@endpush