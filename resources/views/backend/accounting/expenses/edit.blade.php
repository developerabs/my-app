@extends('backend.layouts.main')

@section('title', 'Edit Direct Expense - ' . $expense->expense_no)

@push('css')
    <style>
        .expense-row td {
            vertical-align: middle;
            padding: 8px 6px;
        }

        .line-amount::-webkit-inner-spin-button,
        .line-amount::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .line-amount {
            -moz-appearance: textfield;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')

    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.edit_expense') ?? 'Create Expense' }}
        @endslot
        @slot('subtitle')
            Editing Voucher No: <strong class="text-primary">{{ $expense->expense_no }}</strong>
        @endslot
        @slot('button')
            <a href="{{ route('expenses.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> Expense List
            </a>
        @endslot
    @endcomponent

    <form action="{{ route('expenses.update', $expense->id) }}" method="POST" enctype="multipart/form-data"
        id="expense_entry_form">
        @csrf
        @method('PATCH')

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-end">
                    <!-- Standard Re-usable Journal Header Partial -->
                    @include('backend.accounting.partials.journal-header', [
                        'dateLabel' => 'Expense Date',
                        'dateName' => 'expense_date',
                        'showPaymentAccount' => true,
                    ])

                    <!-- Supplier / Vendor (Optional) -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-semibold">Supplier / Vendor <small
                                class="text-muted fw-normal">(Optional)</small></label>
                        <select name="supplier_id" id="supplier_id" class="form-select supplier-select">
                            <option value="">Select Supplier (None)</option>
                            @isset($suppliers)
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id', $expense->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                        {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <!-- Project Selection (Header Scope) -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-semibold">Project <small
                                class="text-muted fw-normal">(Optional)</small></label>
                        <select name="project_id" id="header_project_id" class="form-select select-picker">
                            <option value="">Select Project (None)</option>
                            @isset($projects)
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}"
                                        {{ old('project_id', $expense->project_id ?? '') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name ?? $project->title }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash"
                                {{ old('payment_method', $expense->payment_method) == 'cash' ? 'selected' : '' }}>Cash
                            </option>
                            <option value="bank_transfer"
                                {{ old('payment_method', $expense->payment_method) == 'bank_transfer' ? 'selected' : '' }}>
                                Bank Transfer</option>
                            <option value="cheque"
                                {{ old('payment_method', $expense->payment_method) == 'cheque' ? 'selected' : '' }}>Cheque
                            </option>
                            <option value="mfs"
                                {{ old('payment_method', $expense->payment_method) == 'mfs' ? 'selected' : '' }}>Mobile
                                Banking</option>
                        </select>
                    </div>

                    <!-- Reference No -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-semibold">Ref / Cheque / Txn No.</label>
                        <input type="text" name="reference_no" class="form-control"
                            value="{{ old('reference_no', $expense->reference_no) }}" placeholder="e.g. Cheque #12345">
                    </div>

                    <!-- Attachment -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Receipt / Bill Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                        @if ($expense->attachment)
                            <small class="text-success d-block mt-1"><i class="fa-solid fa-paperclip me-1"></i> Current File
                                Attached</small>
                        @endif
                    </div>

                    <!-- Note / Remarks -->
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-semibold">Note / Remarks</label>
                        <input type="text" name="note" class="form-control" value="{{ old('note', $expense->note) }}"
                            placeholder="Expense payment details...">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-1"></i> Expense
                    Categories & Items</h6>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="expense_items_table">
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
                            @foreach ($expense->items as $index => $item)
                                <tr data-row-index="{{ $index }}" class="expense-row">
                                    <td>
                                        <select name="items[{{ $index }}][expense_account_id]"
                                            class="form-select select2-picker" required>
                                            <option value="">Select Expense Category</option>
                                            @foreach ($expenseAccounts as $eAccount)
                                                <option value="{{ $eAccount->id }}"
                                                    {{ $item->expense_account_id == $eAccount->id ? 'selected' : '' }}>
                                                    {{ $eAccount->account_code }} - {{ $eAccount->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][description]"
                                            value="{{ $item->description }}" class="form-control form-control-sm"
                                            placeholder="Line item details...">
                                    </td>
                                    <td>
                                        <select name="items[{{ $index }}][project_id]"
                                            class="form-select select2-picker line-project-select">
                                            <option value="">Default (Header Project)</option>
                                            @isset($projects)
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}"
                                                        {{ ($item->project_id ?? ($expense->project_id ?? '')) == $project->id ? 'selected' : '' }}>
                                                        {{ $project->name ?? $project->title }}
                                                    </option>
                                                @endforeach
                                            @endisset
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01"
                                            name="items[{{ $index }}][amount]" value="{{ $item->amount }}"
                                            class="form-control form-control-sm text-end line-amount" required
                                            placeholder="0.00">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-row"><i
                                                class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-sm btn-success px-3" id="add_expense_row_btn">
                        <i class="fa-solid fa-plus me-1"></i> Add More Item
                    </button>

                    <div class="text-end">
                        <h4 class="fw-bold mb-0 text-dark">
                            Total Expense: <span id="grand_expense_total"
                                class="text-primary">{{ number_format($expense->total_amount, 2) }}</span>
                        </h4>
                    </div>
                </div>

                <div class="text-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> Update & Re-post Expense
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('modals')
    <!-- Quick Supplier Modal Integration -->
    @include('backend.layouts.partials.quick_supplier')
@endsection

@push('js')
    <script>
        let globalExpenseRowCounter = {{ count($expense->items) }};

        function calculateExpenseTotals() {
            let grandTotal = 0;
            $('.line-amount').each(function() {
                let amount = parseFloat($(this).val()) || 0;
                grandTotal += amount;
            });
            $('#grand_expense_total').text(grandTotal.toFixed(2));
        }

        $(document).on('input', '.line-amount', function() {
            calculateExpenseTotals();
        });

        function initExpenseSelect(element) {
            if ($(element).hasClass("select2-hidden-accessible")) {
                $(element).select2('destroy');
            }

            $(element).select2({
                width: '100%'
            });
        }

        $('.select2-picker').each(function() {
            initExpenseSelect(this);
        });

        $('#add_expense_row_btn').click(function() {
            globalExpenseRowCounter++;
            let rowIndex = globalExpenseRowCounter;

            let newRow = `<tr data-row-index="${rowIndex}" class="expense-row">
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
                            @foreach ($projects as $project)
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

            $('#expense_items_table tbody').append(newRow);

            let $addedRow = $('#expense_items_table tbody tr:last');
            initExpenseSelect($addedRow.find('.select2-picker'));
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#expense_items_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateExpenseTotals();
            }
        });
    </script>
@endpush
