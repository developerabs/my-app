@extends('backend.layouts.main')

@section('title', __('file.title.create_expense') ?? 'Create Expense')

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
            {{ __('file.title.create_expense') ?? 'Create Expense' }}
        @endslot
        @slot('subtitle')
            {{ __('Record operational expenses and auto-post double entry vouchers.') }}
        @endslot
        @slot('button')
            <a href="{{ route('expenses.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> {{ __('file.button.list') }} {{ __('file.expense') ?? 'Expense' }}
            </a>
        @endslot
    @endcomponent

    <form action="{{ route('expenses.store') }}" method="POST" id="expense_entry_form" enctype="multipart/form-data">
        @csrf
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-end">
                    <!-- Standard Re-usable Journal Header Partial -->
                    @include('backend.accounting.partials.journal-header', [
                        'dateLabel' => __('file.field.expense_date') ?? 'Expense Date',
                        'dateName' => 'expense_date',
                        'showPaymentAccount' => true,
                    ])

                    <!-- Supplier / Vendor (Optional with Quick Create) -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('Supplier / Vendor') }} <small
                                class="text-muted fw-normal">(Optional)</small></label>
                        <select name="supplier_id" id="supplier_id" class="form-select supplier-select">
                            <option value="">{{ __('Select Supplier (None)') }}</option>
                            @isset($suppliers)
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                        {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <!-- Project Selection (Header Scope) -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('Project') }} <small
                                class="text-muted fw-normal">(Optional)</small></label>
                        <select name="project_id" id="header_project_id" class="form-select select-picker">
                            <option value="">{{ __('Select Project (None)') }}</option>
                            @isset($projects)
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name ?? $project->title }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('file.field.payment_method') ?? 'Payment Method' }}</label>
                        <select name="payment_method" class="form-select select-picker">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="mfs">Mobile Banking</option>
                        </select>
                    </div>

                    <!-- Ref / Cheque / Txn No -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('Ref / Cheque / Txn No.') }}</label>
                        <input type="text" name="reference_no" class="form-control form-control-sm"
                            placeholder="{{ __('e.g. Cheque #12345 / Txn ID') }}">
                    </div>

                    <!-- Attachment -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('Receipt / Bill Attachment') }}</label>
                        <input type="file" name="attachment" class="form-control form-control-sm" accept="image/*,.pdf">
                    </div>

                    <!-- Remarks / Note -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">{{ __('Remarks / Note') }}</label>
                        <input type="text" name="note" class="form-control form-control-sm"
                            placeholder="{{ __('Optional expense remarks...') }}">
                    </div>
                </div>
            </div>

            <div class="card-body">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-list-check text-primary me-1"></i> {{ __('Expense Categories & Items') }}
                </h6>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="expense_items_table">
                        <thead>
                            <tr>
                                <th width="30%">{{ __('Expense Category') }} <span class="text-danger">*</span></th>
                                <th width="30%">{{ __('Description / Line Note') }}</th>
                                <th width="20%">{{ __('Project') }} <small
                                        class="text-muted fw-normal">(Optional)</small></th>
                                <th width="15%" class="text-end">{{ __('Amount') }} <span class="text-danger">*</span>
                                </th>
                                <th width="5%" class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Initial Row -->
                            <tr data-row-index="0" class="expense-row">
                                <td>
                                    <select name="items[0][expense_account_id]" class="form-select expense-account-select"
                                        required>
                                        <option value="">{{ __('Select Expense Category') }}</option>
                                        @foreach ($expenseAccounts as $eAccount)
                                            <option value="{{ $eAccount->id }}">
                                                {{ $eAccount->account_code }} - {{ $eAccount->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[0][description]" class="form-control form-control-sm"
                                        placeholder="{{ __('Line item details...') }}">
                                </td>
                                <td>
                                    <select name="items[0][project_id]"
                                        class="form-select select2-picker line-project-select">
                                        <option value="">{{ __('Default (Header Project)') }}</option>
                                        @isset($projects)
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->name ?? $project->title }}
                                                </option>
                                            @endforeach
                                        @endisset
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="items[0][amount]"
                                        class="form-control form-control-sm text-end line-amount" required
                                        placeholder="0.00">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i
                                            class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-sm btn-success px-3" id="add_expense_row_btn">
                        <i class="fa-solid fa-plus me-1"></i> {{ __('Add More Item') }}
                    </button>

                    <div class="text-end">
                        <h4 class="fw-bold mb-0 text-dark">
                            {{ __('Total Expense') }}: <span id="grand_expense_total" class="text-primary">0.00</span>
                        </h4>
                    </div>
                </div>

                <div class="text-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i> {{ __('Save & Post Expense') }}
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
        let globalExpenseRowCounter = 0;

        function calculateExpenseTotals() {
            let grandTotal = 0;
            $('#expense_items_table tbody tr').each(function() {
                let amount = parseFloat($(this).find('.line-amount').val()) || 0;
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

        $('.expense-account-select, .line-project-select').each(function() {
            initExpenseSelect(this);
        });

        // Add Dynamic Expense Row
        $('#add_expense_row_btn').click(function() {
            globalExpenseRowCounter++;
            let rowIndex = globalExpenseRowCounter;

            let newRow = `<tr data-row-index="${rowIndex}" class="expense-row">
                <td>
                    <select name="items[${rowIndex}][expense_account_id]" class="form-select expense-account-select" required>
                        <option value="">{{ __('Select Expense Category') }}</option>
                        @foreach ($expenseAccounts as $eAccount)
                            <option value="{{ $eAccount->id }}">
                                {{ $eAccount->account_code }} - {{ $eAccount->account_name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="items[${rowIndex}][description]" class="form-control form-control-sm" placeholder="{{ __('Line item details...') }}">
                </td>
                <td>
                    <select name="items[${rowIndex}][project_id]" class="form-select line-project-select">
                        <option value="">{{ __('Default (Header Project)') }}</option>
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
            initExpenseSelect($addedRow.find('.expense-account-select'));
            initExpenseSelect($addedRow.find('.line-project-select'));
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#expense_items_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateExpenseTotals();
            }
        });
    </script>
@endpush
