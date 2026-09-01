@php
    $isEdit = $isEdit ?? false;
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-end">
                    
                    <!-- 🟢 100% Generic Journal Header Partial -->
                    @include('backend.accounting.partials.journal-header', [
                        'model'       => $purchase ?? null,
                        'dateLabel'   => (\Illuminate\Support\Facades\Lang::has('file.field.purchase_date') ? __('file.field.purchase_date') : 'Purchase Date'),
                        'dateName'    => 'purchase_date',
                        'defaultDate' => isset($purchase) && $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : date('Y-m-d')
                    ])

                    <!-- Supplier Selection -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold mb-1">{{ \Illuminate\Support\Facades\Lang::has('file.field.select_supplier') ? __('file.field.select_supplier') : 'Select Supplier' }} <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-select form-select-sm select-picker" required>
                            <option value="">{{ \Illuminate\Support\Facades\Lang::has('file.option.select_supplier') ? __('file.option.select_supplier') : 'Select Supplier' }}</option>
                            @isset($suppliers)
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id', $purchase->supplier_id ?? '') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} {{ $supplier->company_name ? "({$supplier->company_name})" : '' }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    <!-- Payment Source Account -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold mb-1">{{ \Illuminate\Support\Facades\Lang::has('file.field.payment_account') ? __('file.field.payment_account') : 'Payment Source' }}</label>
                        <select name="payment_account_id" id="payment_account_id" class="form-select select-picker">
                            <option value="">{{ __('Select Payment Source') }}</option>
                            @forelse ($paymentAccounts as $pAccount)
                                <option value="{{ $pAccount->id }}" 
                                    {{ old('payment_account_id', $purchase->payment_account_id ?? ($general_settings['default_acc'] ?? '')) == $pAccount->id ? 'selected' : '' }}>
                                    {{ $pAccount->account_name }} ({{ ucfirst($pAccount->account_type->value ?? $pAccount->account_type) }})
                                </option>
                            @empty
                                <option value="">{{ \Illuminate\Support\Facades\Lang::has('file.option.no') ? __('file.option.no') : 'No Account' }}</option>
                            @endforelse
                        </select>
                    </div>

                    <!-- Purchase Status -->
                    <div class="col-md-2 mb-3">
                        <label for="purchase_status" class="form-label fw-bold mb-1">{{ \Illuminate\Support\Facades\Lang::has('file.field.purchase_status') ? __('file.field.purchase_status') : 'Purchase Status' }} <span class="text-danger">*</span></label>
                        <select name="purchase_status" class="form-select" required>
                            <option value="received" {{ old('purchase_status', $purchase->purchase_status ?? 'received') == 'received' ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Facades\Lang::has('file.option.received') ? __('file.option.received') : 'Received' }}
                            </option>
                            <option value="partial" {{ old('purchase_status', $purchase->purchase_status ?? '') == 'partial' || old('purchase_status', $purchase->purchase_status ?? '') == 'partial_received' ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Facades\Lang::has('file.option.partial') ? __('file.option.partial') : 'Partial' }}
                            </option>
                            <option value="pending" {{ old('purchase_status', $purchase->purchase_status ?? '') == 'pending' ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Facades\Lang::has('file.option.pending') ? __('file.option.pending') : 'Pending' }}
                            </option>
                            <option value="ordered" {{ old('purchase_status', $purchase->purchase_status ?? '') == 'ordered' ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Facades\Lang::has('file.option.ordered') ? __('file.option.ordered') : 'Ordered' }}
                            </option>
                        </select>
                    </div>

                    <!-- Reference No -->
                    <div class="col-md-2 mb-3">
                        <label for="reference" class="form-label fw-bold mb-1">{{ \Illuminate\Support\Facades\Lang::has('file.field.reference') ? __('file.field.reference') : 'Reference' }}</label>
                        <input type="text" name="reference" class="form-control"
                            value="{{ old('reference', $purchase->reference ?? '') }}" placeholder="e.g. PO-98765">
                    </div>

                    <!-- Memo / Invoice Number -->
                    <div class="col-md-2 mb-3">
                        <label for="memo_number" class="form-label fw-bold mb-1">{{ \Illuminate\Support\Facades\Lang::has('file.field.memo_number') ? __('file.field.memo_number') : 'Memo Number' }}</label>
                        <input type="text" name="memo_number" class="form-control"
                            value="{{ old('memo_number', $purchase->memo_number ?? '') }}" placeholder="Supplier Invoice / Memo No">
                    </div>

                </div>
            </div>

            <div class="card-body p-4">

                <!-- 🔍 Product Search Bar -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white border-0">
                                <i class="fa fa-barcode"></i>
                            </span>
                            <input type="text" id="product_search" class="form-control shadow-none border-primary"
                                placeholder="{{ __('Scan barcode or type product name / SKU...') }}">
                            <button type="button" class="btn btn-outline-primary d-md-none" onclick="startMobileScanner()">
                                <i class="fa fa-camera"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <!-- 🛒 Purchase Items Table -->
                <div class="table-responsive my-3">
                    <table class="table table-sm border table-bordered align-middle" id="purchase-table">
                        <thead>
                            <tr>
                                <th width="20%">{{ __('Product Item') }}</th>
                                <th width="15%">{{ __('Batch & Expire') }}</th>
                                <th width="15%">{{ __('Qty & Unit') }}</th>
                                <th width="10%">{{ __('Unit Cost') }}</th>
                                <th width="16%">{{ __('Discount') }}</th>
                                <th width="16%">{{ __('Tax (Method, %, Total)') }}</th>
                                <th width="6%" class="text-end">{{ __('Subtotal') }}</th>
                                <th width="2%" class="text-center"><i class="fa fa-trash text-muted"></i></th>
                            </tr>
                        </thead>
                        <tbody id="purchase_body">
                            {{-- Dynamically populated via JS --}}
                        </tbody>
                    </table>
                </div>

                <!-- 💳 Notes & Order Summary Footer -->
                <div class="row g-3 flex-column-reverse flex-md-row mt-2">
                    <div class="col-md-7 col-lg-8">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="small fw-bold mb-1">{{ \Illuminate\Support\Facades\Lang::has('file.field.note') ? __('file.field.note') : 'Note' }}</label>
                            <textarea name="note" class="form-control form-control-sm mb-3" rows="3" placeholder="Optional purchase remarks...">{{ old('note', $purchase->note ?? '') }}</textarea>
                            
                            <label class="small fw-bold mb-1">{{ \Illuminate\Support\Facades\Lang::has('file.field.attach_document') ? __('file.field.attach_document') : 'Attach Document' }}</label>
                            <input type="file" name="document" class="form-control form-control-sm" accept="image/*,.pdf">
                            @if(isset($purchase) && $purchase->document)
                                <div class="mt-1 small">
                                    <span class="text-muted">Attached: </span>
                                    <a href="{{ file_url($purchase->document) }}" target="_blank" class="fw-bold text-primary">View Current Document</a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-5 col-lg-4" id="order_summary">
                        <div class="card shadow-sm border-0 bg-light mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted fw-bold small">{{ __('Subtotal') }}:</span>
                                        <strong id="sub-total" class="text-dark">{{ isset($purchase) ? number_format($purchase->subtotal_amount, 2, '.', '') : '0.00' }}</strong>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-bold small">{{ __('Discount') }}:</span>
                                        <div class="input-group input-group-sm" style="width: 300px;">
                                            <select name="order_discount_method" id="order_discount_method" class="form-select" style="width: 100px;">
                                                <option value="flat" {{ old('order_discount_method', $purchase->order_discount_method ?? 'flat') == 'flat' ? 'selected' : '' }}>Flat</option>
                                                <option value="percentage" {{ old('order_discount_method', $purchase->order_discount_method ?? '') == 'percentage' ? 'selected' : '' }}>Percent (%)</option>
                                            </select>
                                            <input type="number" name="order_discount_rate" id="order_discount_rate" class="form-control text-end" 
                                                value="{{ old('order_discount_rate', $purchase->order_discount_rate ?? 0) }}" min="0" step="any">
                                            <input type="number" name="order_discount_amount" id="order_discount_amount" class="form-control text-end bg-white" 
                                                value="{{ old('order_discount_amount', $purchase->order_discount_amount ?? 0) }}" readonly>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-bold small">{{ __('Tax') }}:</span>
                                        <div class="input-group input-group-sm order-tax" style="width: 300px;">
                                            <select name="order_tax_method" id="order_tax_method" class="form-select" style="width: 100px;">
                                                <option value="0">No Tax</option>
                                            </select>
                                            <input type="number" name="order_tax_rate" id="order_tax_rate" class="form-control text-end" 
                                                value="{{ old('order_tax_rate', $purchase->order_tax_rate ?? 0) }}" min="0" step="any">
                                            <input type="number" name="order_tax_amount" id="order_tax_amount" class="form-control text-end bg-white" 
                                                value="{{ old('order_tax_amount', $purchase->order_tax_amount ?? 0) }}" readonly>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-bold small">{{ __('Shipping Cost') }}:</span>
                                        <input type="number" name="shipping_cost" id="shipping-cost" class="form-control form-control-sm text-end" style="width: 120px;" 
                                            value="{{ old('shipping_cost', $purchase->shipping_cost ?? 0) }}" min="0" step="any">
                                    </div>

                                    <hr class="my-1 border-secondary-subtle">

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark fs-6">{{ __('Grand Total') }}:</span>
                                        <strong class="text-primary fs-6" id="grand-total">{{ isset($purchase) ? number_format($purchase->total_amount, 2, '.', '') : '0.00' }}</strong>
                                    </div>

                                    <!-- Paid Amount -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-success small">{{ __('Paid Amount') }}:</span>
                                        <input type="number" name="paid_amount" id="paid-amount"
                                            class="form-control form-control-sm text-end fw-bold text-success"
                                            style="width: 130px;"
                                            value="{{ old('paid_amount', $purchase->paid_amount ?? 0) }}" min="0" step="any">
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-danger small">{{ __('Due Amount') }}:</span>
                                        <strong class="text-danger fs-6" id="due-amount">{{ isset($purchase) ? number_format($purchase->due_amount, 2, '.', '') : '0.00' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4 mb-2">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success px-4 shadow">
                            <i class="fa fa-save me-1"></i> {{ $isEdit ? (\Illuminate\Support\Facades\Lang::has('file.button.update') ? __('file.button.update') : 'Update') : (\Illuminate\Support\Facades\Lang::has('file.button.create') ? __('file.button.create') : 'Create') }} {{ \Illuminate\Support\Facades\Lang::has('file.purchase') ? __('file.purchase') : 'Purchase' }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>