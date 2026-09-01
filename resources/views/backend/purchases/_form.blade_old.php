@php
    $isEdit = $isEdit ?? false;
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('file.field.purchase_date') }}</label>
                        <input type="text" name="purchase_date" class="form-control date-picker purchase_date"
                            value="{{ date('Y-m-d') }}" readonly>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">{{ __('file.field.select_supplier') }}<span
                                class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-control select2-ajax"
                            data-placeholder="{{ __('file.option.select_supplier') }}" required>
                            <option value="">{{ __('file.option.select_supplier') }}</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ isset($purchase) && $purchase->supplier_id ?? '' == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- ২. ওয়্যারহাউস বা লোকেশন -->
                    <div class="col-md-2 mb-3">
                        <label class="fw-bold form-label">{{ __('file.field.select_branch') }} <span
                                class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control select2" required>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ isset($purchase) && $branch->id == ($purchase->branch_id ?? '') ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="reference" class="form-label fw-bold">{{ __('file.field.reference') }}</label>
                        <input type="text" name="reference" class="form-control"
                            value="{{ old('reference', $purchase->reference ?? '') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="memo_number" class="form-label fw-bold">{{ __('file.field.memo_number') }}</label>
                        <input type="text" name="memo_number" class="form-control"
                            value="{{ old('memo_number', $purchase->memo_number ?? '') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        @include('backend.layouts.partials._currency_field', [
                            'currencies' => $currencies,
                            'selectedId' => $purchase->currency_id ?? null, // যদি থাকে
                            'rate' => $purchase->exchange_rate ?? null, // যদি থাকে
                        ])
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="purchase_status"
                            class="form-label fw-bold">{{ __('file.field.purchase_status') }}</label>
                        <select name="purchase_status" class="form-control">
                            <option value="received"
                                {{ $purchase->purchase_status ?? '' == 'received' ? 'selected' : '' }}>
                                {{ __('file.option.received') }}
                            </option>
                            <option value="partial"
                                {{ $purchase->purchase_status ?? '' == 'partial' ? 'selected' : '' }}>
                                {{ __('file.option.partial') }}
                            </option>
                            <option value="pending"
                                {{ $purchase->purchase_status ?? '' == 'pending' ? 'selected' : '' }}>
                                {{ __('file.option.pending') }}
                            </option>
                            <option value="ordered"
                                {{ $purchase->purchase_status ?? '' == 'ordered' ? 'selected' : '' }}>
                                {{ __('file.option.ordered') }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white border-0">
                                <i class="fa fa-barcode"></i>
                            </span>
                            <input type="text" id="product_search" class="form-control shadow-none border-primary"
                                placeholder="Scan barcode or type product name...">
                            <button type="button" class="btn btn-outline-primary d-md-none"
                                onclick="startMobileScanner()">
                                <i class="fa fa-camera"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <hr class="my-1">
                <div class="table-responsive my-3">
                    <table class="table table-sm border table-bordered" id="purchase-table">
                        <thead class="bg-light">
                            <tr>
                                <th width="20%">Product</th>
                                <th width="15%">Batch & Expire</th>
                                <th width="15%">Qty, Rcv. Qty & Unit</th>
                                <th width="10%">Unit Cost</th>
                                <th width="16%">Discount</th>
                                <th width="16%">Tax (Method, %, Total)</th>
                                <th width="6%" class="text-end">Subtotal</th>
                                <th width="2%" class="text-center"><i class="fa fa-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody id="purchase_body"></tbody>
                    </table>
                </div>
                <div class="row g-3 flex-column-reverse flex-md-row">
                    <div class="col-md-8">
                        <div class="p-2 border rounded bg-white">
                            <label class="small fw-bold">Note</label>
                            <textarea name="note" class="form-control mb-2" rows="2"></textarea>
                            <label class="small fw-bold">Attach File</label>
                            <input type="file" name="document" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="col-md-4" id="order_summary">
                        <div class="card shadow-sm border-0 bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted fw-bold">Subtotal:</span>
                                        <strong id="sub-total">0.00</strong>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-bold small">Discount:</span>
                                        <div class="input-group input-group-sm" style="width: 260px;">
                                            <select name="order_discount_method" id="order_discount_method" class="form-select"
                                                style="max-width: 100px;">
                                                <option value="flat">Flat</option>
                                                <option value="percentage">Percent (%)</option>
                                            </select>
                                            <input type="number" name="order_discount_rate" id="order_discount_rate" class="form-control text-end"
                                                value="0">
                                            <input type="number" name="order_discount_amount" id="order_discount_amount"
                                                class="form-control text-end bg-white" value="0" readonly>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-bold small">Tax:</span>
                                        <div class="input-group input-group-sm order-tax" style="width: 260px;">
                                            <select name="order_tax_method" id="order_tax_method" class="form-select"
                                                style="max-width: 100px;">
                                                <option value="0">No Tax</option>
                                            </select>
                                            <input type="number" name="order_tax_rate" id="order_tax_rate" class="form-control text-end"
                                                value="0">
                                            <input type="number" name="order_tax_amount" id="order_tax_amount"
                                                class="form-control text-end bg-white" value="0" readonly>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-bold small">Shipping:</span>
                                        <input type="number" name="shipping_cost" id="shipping-cost"
                                            class="form-control form-control-sm text-end" style="width: 120px;"
                                            value="0">
                                    </div>

                                    <hr class="my-1">

                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Grand Total:</span>
                                        <strong class="text-primary" id="grand-total">0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-success">Paid:</span>
                                        <input type="number" name="paid_amount" id="paid-amount"
                                            class="form-control form-control-sm text-end" onchange="ProductManager.calculateDue();" style="width: 120px;"
                                            value="0" min="0" step="any">
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-danger">Due:</span>
                                        <strong class="text-danger" id="due-amount">0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-2 mb-5">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success shadow">
                            <i class="fa fa-save"></i> {{ __('file.button.create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
