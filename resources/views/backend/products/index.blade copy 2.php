@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.product_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
    <link rel="stylesheet" href="{{ url('backend/assets/libs/flatpickr/flatpickr.min.css') }}">
    <style>
        table.dataTable.nowrap th[title="Balance"] {
            text-align: end !important;
        }

        .flatpickr-wrapper {
            display: block !important;
            width: 100%;
        }

        /* মডাল সাইজ এবং প্যাডিং কন্ট্রোল */
        #productViewModal .modal-body {
            padding: 1.25rem !important;
        }

        #productViewModal .modal-content {
            border-radius: 12px;
        }

        /* ছোট স্ক্রলবার */
        .custom-scrollbar::-webkit-scrollbar {
            height: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e0e0e0;
            border-radius: 10px;
        }

        /* ভেরিয়েন্ট টেবিল হোভার ইফেক্ট */
        .table-hover tbody tr:hover {
            background-color: #fcfcfc;
        }

        /* থাম্বনেইল বর্ডার */
        .img-thumbnail {
            transition: all 0.2s;
            border: 1px solid #eee;
        }

        .img-thumbnail.border-primary {
            border-width: 2px !important;
        }

        /* ডেসক্রিপশন টেক্সট লিমিট */
        .product-description {
            font-size: 12px;
            color: #666;
        }
        .custom-product-accordion .accordion-button::after {
            width: 14px; height: 14px; background-size: 14px;
        }
        .custom-product-accordion .accordion-button:not(.collapsed) {
            background-color: #f8f9fa !important; color: #0d6efd !important; box-shadow: none;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.product_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.product_management_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.product') }}
            </a>
        @endslot
    @endcomponent

    {{-- Filter Section --}}
    <div class="row mb-3">
        <div class="col-md-12">
            {{-- Mobile Toggle Button --}}
            <button class="btn btn-outline-primary d-md-none w-100 mb-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#filterCollapse">
                <i class="fa-solid fa-filter me-2"></i> {{ __('file.field.show_filters') }}
            </button>

            <div class="collapse d-md-block" id="filterCollapse">
                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            {{-- Filter Icon & Title (Desktop Only) --}}
                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">{{ __('file.field.filters') }}:</span>
                            </div>

                            <div class="col-12 col-md-auto" style="min-width: 180px;">
                                <select id="filter-status" data-dt-filter="supplier-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>

                            {{-- <div class="col-12 col-md-auto">
                                <div class="input-group input-group-sm">
                                    <input type="date" id="from-date" data-dt-filter="customer-table" class="form-control shadow-none">
                                    <span class="input-group-text bg-light">to</span>
                                    <input type="date" id="to-date" data-dt-filter="customer-table" class="form-control shadow-none">
                                </div>
                            </div> --}}

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('supplier-table')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('file.button.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    <!-- Product Details Modal -->
    <div class="modal fade" id="productViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="productModalContent">
                    <!-- Data will load here -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ url('backend/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ url('backend/assets/js/date&time_pickers.js') }}"></script>
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        function viewProduct(id) {
            var url = "{{ route('products.show', ':id') }}".replace(':id', id);

            $('#productViewModal .modal-dialog').removeClass('modal-lg').addClass('modal-xl');
            $('#productViewModal').modal('show');
            $('#productModalContent').html(
                '<div class="text-center py-4"><div class="spinner-border text-primary" style="width: 2rem; height: 2rem;"></div></div>'
            );

            $.get(url, function(product) {
                console.log(product);
                let mainThumb = product.thumb_url || 'https://via.placeholder.com/300?text=No+Image';

                // ক্যাটেগরি ব্যাজ
                let categoryBadges = product.categories && product.categories.length > 0 ? 
                    product.categories.map(cat =>
                        `<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1" style="font-size: 9px; padding: 2px 6px;">${cat.name}</span>`
                    ).join('') : '<span class="text-muted small">N/A</span>';

                let html = `
                    <div class="container-fluid p-0">
                        <!-- Primary Row -->
                        <div class="row g-3 mb-3">
                            <!-- বাম কলাম -->
                            <div class="col-md-4">
                                <div class="bg-white rounded-3 border overflow-hidden shadow-sm mb-2">
                                    <img src="${mainThumb}" class="img-fluid w-100" id="mainProdImage" style="height: 250px; object-fit: contain; background: #f8f9fa;">
                                </div>
                                <div class="d-flex gap-1 overflow-auto pb-1 mb-2">
                                    <img src="${mainThumb}" class="img-thumbnail rounded-2 p-0 border-primary shadow-sm" style="width: 42px; height: 42px; object-fit: cover; cursor:pointer;" onclick="changeMainImage(this.src, this)">
                                    ${product.images ? product.images.map(img => `<img src="${img.full_url}" class="img-thumbnail rounded-2 p-0" style="width: 42px; height: 42px; object-fit: cover; cursor:pointer;" onclick="changeMainImage(this.src, this)">`).join('') : ''}
                                </div>

                                <!-- Others Info -->
                                <div class="p-2 bg-light rounded-3 border" style="font-size: 11px;">
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Short Name:</span> <span class="fw-bold">${product.short_name || '-'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Total Stock:</span> <span class="fw-bold">${product.baseStock || '0'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Formated Stock:</span> <span class="fw-bold">${product.formatted_stock || '0'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Alert Quantity:</span> <span class="fw-bold">${product.alert_quantity || '0'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Profit Margin:</span> <span class="fw-bold">${product.profit_margin || '0'}%</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Tax Type:</span> <span class="fw-bold">${product.tax_type}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Commission:</span> <span class="fw-bold text-success">${product.max_sale_commision || 0}%</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Status:</span> <span class="fw-bold">${product.status}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- ডান কলাম -->
                            <div class="col-md-8">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-dark px-2 py-1" style="font-size: 9px; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600;">${product.type}</span>
                                    <span class="text-muted" style="font-size: 11px;">SKU: <b class="text-dark">${product.sku || '-'}</b></span>
                                    <span class="text-muted" style="font-size: 11px;">| CODE: <b class="text-dark">${product.code || '-'}</b></span>
                                </div>
                                
                                <h3 class="fw-bold text-dark mb-2" style="font-size: 1.4rem; letter-spacing: -0.3px;">${product.name}</h3>
                                
                                <div class="d-flex flex-wrap gap-3 align-items-center mb-3 pb-3 border-bottom border-gray-100" style="font-size: 12px;">
                                    <div>
                                        <span class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Brand:</span>
                                        <span class="badge bg-light text-dark border ms-1 fw-normal px-2 py-1">${product.brand?.name || 'Generic'}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted fw-bold text-uppercase me-1" style="font-size: 10px;">Categories:</span>
                                        <div class="d-flex flex-wrap gap-1">${categoryBadges}</div>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <div class="p-2 border border-primary-subtle rounded-3 bg-primary-subtle text-center shadow-sm">
                                            <small class="text-primary d-block fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">SALE PRICE</small>
                                            <span class="fw-bolder text-primary" style="font-size: 18px;">${formatMoney(product.price)}</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border border-success-subtle rounded-3 bg-success-subtle text-center shadow-sm">
                                            <small class="text-success d-block fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">WHOLESALE</small>
                                            <span class="fw-bolder text-success" style="font-size: 18px;">${formatMoney(product.wholesale_price)}</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border border-secondary-subtle rounded-3 bg-light text-center shadow-sm">
                                            <small class="text-secondary d-block fw-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px;">COST PRICE</small>
                                            <span class="fw-bolder text-secondary" style="font-size: 18px;">${formatMoney(product.cost)}</span>
                                        </div>
                                    </div>
                                </div>

                                ${product.short_description ? `
                                    <div class="mb-3 p-2 bg-light rounded-2 border-start border-3 border-primary" style="font-size: 12px; color: #555; line-height: 1.5;">
                                        ${product.short_description}
                                    </div>
                                ` : ''}

                                ${(product.specifications?.length > 0 || (product.has_warranty && product.warranty_details) || product.dropshipping_detail) ? `
                                    <div class="accordion custom-product-accordion mt-3" id="metaAccordion-${product.id}">
                                        
                                        ${product.specifications?.length > 0 ? `
                                        <div class="accordion-item border rounded-2 mb-2 overflow-hidden shadow-sm">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-2 px-3 fw-bold bg-light" style="font-size: 11px; color: #444;" type="button" data-bs-toggle="collapse" data-bs-target="#acc-spec-${product.id}">
                                                    <i class="fa-solid fa-list-check me-2 text-secondary"></i> SPECIFICATIONS
                                                </button>
                                            </h2>
                                            <div id="acc-spec-${product.id}" class="accordion-collapse collapse" data-bs-parent="#metaAccordion-${product.id}">
                                                <div class="accordion-body p-0">
                                                    <table class="table table-sm table-striped table-bordered mb-0" style="font-size: 11px;">
                                                        <tbody>
                                                            ${product.specifications.slice(0, 10).map(s => `
                                                                <tr>
                                                                    <td class="bg-light fw-bold text-secondary" width="35%">${s.key}</td>
                                                                    <td class="ps-3">${s.value}</td>
                                                                </tr>`).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>` : ''}

                                        ${product.has_warranty && product.warranty_details ? `
                                        <div class="accordion-item border rounded-2 mb-2 overflow-hidden shadow-sm">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-2 px-3 fw-bold bg-light" style="font-size: 11px; color: #444;" type="button" data-bs-toggle="collapse" data-bs-target="#acc-warranty-${product.id}">
                                                    <i class="fa-solid fa-shield-halved me-2 text-secondary"></i> WARRANTY INFO
                                                </button>
                                            </h2>
                                            <div id="acc-warranty-${product.id}" class="accordion-collapse collapse" data-bs-parent="#metaAccordion-${product.id}">
                                                <div class="accordion-body p-0">
                                                    <table class="table table-sm table-striped table-bordered mb-0" style="font-size: 11px;">
                                                        <tbody>
                                                            ${(() => {
                                                                let details = typeof product.warranty_details === 'string' ? JSON.parse(product.warranty_details) : product.warranty_details;
                                                                let period = details.warranty_period || '';
                                                                let type = details.period_type || '';
                                                                let duration = period ? `${period} ${type}`.trim() : 'N/A';

                                                                let rows = [
                                                                    { label: 'Warranty Type', value: details.warranty_type },
                                                                    { label: 'Warranty Provider', value: details.warranty_provider },
                                                                    { label: 'Warranty Period', value: duration }
                                                                ];

                                                                return rows.map(row => `
                                                                    <tr>
                                                                        <td class="bg-light fw-bold text-secondary" width="35%">${row.label}</td>
                                                                        <td class="ps-3">${row.value || 'N/A'}</td>
                                                                    </tr>`).join('');
                                                            })()}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>` : ''}

                                        ${product.dropshipping_detail ? `
                                        <div class="accordion-item border rounded-2 mb-0 overflow-hidden shadow-sm">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-2 px-3 fw-bold bg-light" style="font-size: 11px; color: #444;" type="button" data-bs-toggle="collapse" data-bs-target="#acc-dropship-${product.id}">
                                                    <i class="fa-solid fa-truck-ramp-box me-2 text-secondary"></i> DROPSHIPPING DETAILS
                                                </button>
                                            </h2>
                                            <div id="acc-dropship-${product.id}" class="accordion-collapse collapse" data-bs-parent="#metaAccordion-${product.id}">
                                                <div class="accordion-body p-2 bg-light-subtle">
                                                    <div class="row g-2 mb-2" style="font-size: 11px;">
                                                        <div class="col-6 border-end">
                                                            <small class="text-muted d-block">SUPPLIER</small>
                                                            <b class="text-dark">${product.dropshipping_detail.supplier_name || 'Unknown'}</b>
                                                            <small class="text-muted d-block mt-1">LEAD TIME</small>
                                                            <b class="text-dark">${product.dropshipping_detail.delivery_lead_time} Days</b>
                                                        </div>
                                                        <div class="col-6 ps-2">
                                                            <small class="text-muted d-block">SHIPPING COST</small>
                                                            <b class="text-danger">${formatMoney(product.dropshipping_detail.estimated_shipping_cost)}</b>
                                                            <small class="text-muted d-block mt-1">EXT. CODE</small>
                                                            <b class="text-dark">${product.dropshipping_detail.external_sku || '-'}</b>
                                                        </div>
                                                    </div>
                                                    <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center" style="font-size: 11px;">
                                                        <div><span class="text-muted">BUY:</span> <b class="text-dark">${formatMoney(product.dropshipping_detail.buying_price)}</b></div>
                                                        <i class="fa-solid fa-arrow-right text-muted"></i>
                                                        <div><span class="text-muted">SELL:</span> <b class="text-success">${formatMoney(product.dropshipping_detail.selling_price)}</b></div>
                                                    </div>
                                                    ${product.dropshipping_detail.external_product_url ? `
                                                        <a href="${product.dropshipping_detail.external_product_url}" target="_blank" class="btn btn-sm btn-dark w-100 mt-2 py-1 fw-bold" style="font-size: 10px;">
                                                            <i class="fa-solid fa-cart-shopping me-1"></i> SOURCE ON ${product.dropshipping_detail.platform_name.toUpperCase()}
                                                        </a>
                                                    ` : ''}
                                                </div>
                                            </div>
                                        </div>` : ''}

                                    </div>
                                ` : ''}
                                
                                ${(() => {
                                    // Determine data availability based on the clean backend structures
                                    let hasVariants = product.has_variants && product.variants?.length > 0;
                                    
                                    // Direct batches will only exist if the product has no variants (thanks to whereNull in controller)
                                    let hasDirectBatches = !product.has_variants && product.batches && product.batches.length > 0;
                                    
                                    // Check if branch stocks are available
                                    let hasBranches = product.branch_stocks && product.branch_stocks.length > 0;

                                    // Safe fallback: if absolutely no data inside, don't render this block at all
                                    if (!hasVariants && !hasDirectBatches && !hasBranches) return '';

                                    // Decide which tab should be marked active by default on first render
                                    let activeTab = hasVariants ? 'variants' : (hasDirectBatches ? 'batches' : 'branches');

                                    return `
                                    <div class="w-100 mb-4">
                                        <div class="d-flex border-bottom border-gray-300 mb-2">
                                            <ul class="nav nav-tabs border-0 custom-pos-tabs" id="productTab-${product.id}" role="tablist" style="font-size: 13px; margin-bottom: -1px;">
                                                
                                                ${hasVariants ? `
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active py-2 px-3 bg-transparent" 
                                                            id="variants-tab-${product.id}" data-bs-toggle="tab" data-bs-target="#variants-pane-${product.id}" 
                                                            type="button" role="tab" aria-controls="variants-pane-${product.id}" aria-selected="true">
                                                        Variants & Nested Batches
                                                    </button>
                                                </li>` : ''}

                                                ${hasDirectBatches ? `
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active py-2 px-3 bg-transparent" 
                                                            id="batches-tab-${product.id}" data-bs-toggle="tab" data-bs-target="#batches-pane-${product.id}" 
                                                            type="button" role="tab" aria-controls="batches-pane-${product.id}" aria-selected="true">
                                                        Batch Details
                                                    </button>
                                                </li>` : ''}

                                                ${hasBranches ? `
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link ${activeTab === 'branches' ? 'active' : ''} py-2 px-3 bg-transparent" 
                                                            id="branches-tab-${product.id}" data-bs-toggle="tab" data-bs-target="#branches-pane-${product.id}" 
                                                            type="button" role="tab" aria-controls="branches-pane-${product.id}" aria-selected="${activeTab === 'branches'}">
                                                        Branch Wise Stock
                                                    </button>
                                                </li>` : ''}
                                                
                                            </ul>
                                        </div>

                                        <style>
                                            .custom-pos-tabs .nav-link {
                                                border: none !important;
                                                border-bottom: 2px solid transparent !important;
                                                border-radius: 0 !important;
                                                color: #6c757d !important;
                                                transition: all 0.15s ease-in-out;
                                            }
                                            .custom-pos-tabs .nav-link:hover {
                                                color: #212529 !important;
                                            }
                                            .custom-pos-tabs .nav-link.active {
                                                border-bottom: 2px solid #0d6efd !important;
                                                color: #0d6efd !important;
                                                font-weight: bold !important;
                                            }
                                        </style>

                                        <div class="tab-content" id="productTabContent-${product.id}">
                                            
                                            ${hasVariants ? `
                                            <div class="tab-pane fade show active" id="variants-pane-${product.id}" role="tabpanel" aria-labelledby="variants-tab-${product.id}">
                                                <div class="table-responsive rounded border border-gray-200">
                                                    <table class="table table-sm table-striped align-middle m-0" style="font-size: 12px;">
                                                        <thead class="table-light text-uppercase" style="font-size: 11px;">
                                                            <tr>
                                                                <th class="text-center py-2" width="50">IMG</th>
                                                                <th class="ps-2">NAME</th>
                                                                <th>SKU</th>
                                                                <th>COST</th>
                                                                <th>SALE</th>
                                                                <th class="text-center">TOTAL STOCK</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${product.variants.map(v => {
                                                                let vImg = (v.images?.length > 0) ? v.images[0].full_url : (product.thumb_url || mainThumb);
                                                                let hasNestedBatches = v.batches && v.batches.length > 0;
                                                                
                                                                return `
                                                                <tr>
                                                                    <td class="text-center py-1">
                                                                        <img src="${vImg}" class="rounded border" style="width: 28px; height: 28px; object-fit: cover;"
                                                                            onclick="if(typeof changeMainImage === 'function') changeMainImage('${vImg}', this)">
                                                                    </td>
                                                                    <td class="ps-2 fw-bold text-dark">
                                                                        ${v.name}
                                                                        ${hasNestedBatches ? `
                                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" 
                                                                                style="font-size: 10px; cursor: pointer; padding: 3px 6px;" 
                                                                                data-bs-toggle="collapse" data-bs-target="#nested-batch-${v.id}">
                                                                                ${v.batches.length} Batches ▾
                                                                            </span>` : ''}
                                                                    </td>
                                                                    <td class="text-muted">${v.sku || '-'}</td>
                                                                    <td class="fw-bold">${typeof formatMoney === 'function' ? formatMoney(v.cost) : v.cost}</td>
                                                                    <td class="fw-bold">${typeof formatMoney === 'function' ? formatMoney(v.price) : v.price}</td>
                                                                    <td class="text-center fw-bold text-dark">${v.baseStock}</td>
                                                                </tr>
                                                                
                                                                ${hasNestedBatches ? `
                                                                <tr class="collapse" id="nested-batch-${v.id}">
                                                                    <td colspan="6" class="bg-light p-2">
                                                                        <div class="border rounded bg-white shadow-sm mx-3">
                                                                            <table class="table table-sm table-borderless m-0" style="font-size: 11px;">
                                                                                <thead class="table-light border-bottom text-uppercase" style="font-size: 10px;">
                                                                                    <tr>
                                                                                        <th class="ps-3 py-2">BATCH NO</th>
                                                                                        <th>EXPIRY DATE</th>
                                                                                        <th>BATCH COST</th>
                                                                                        <th>BATCH SALE</th>
                                                                                        <th class="text-center">BATCH STOCK</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    ${v.batches.map(b => `
                                                                                        <tr>
                                                                                            <td class="ps-3 fw-bold text-secondary">${b.batch_no || '-'}</td>
                                                                                            <td class="text-muted">${b.expiry_date || 'No Expiry'}</td>
                                                                                            <td class="fw-bold">${typeof formatMoney === 'function' ? formatMoney(b.cost) : b.cost}</td>
                                                                                            <td class="fw-bold">${typeof formatMoney === 'function' ? formatMoney(b.price) : b.price}</td>
                                                                                            <td class="text-center fw-bold">${b.baseStock}</td>
                                                                                        </tr>
                                                                                    `).join('')}
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </td>
                                                                </tr>` : ''}
                                                                `;
                                                            }).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>` : ''}

                                            ${hasDirectBatches ? `
                                            <div class="tab-pane fade show active" id="batches-pane-${product.id}" role="tabpanel" aria-labelledby="batches-tab-${product.id}">
                                                <div class="table-responsive rounded border border-gray-200">
                                                    <table class="table table-sm table-striped align-middle m-0" style="font-size: 12px;">
                                                        <thead class="table-light text-uppercase" style="font-size: 11px;">
                                                            <tr>
                                                                <th class="ps-3 py-2">BATCH NO</th>
                                                                <th>EXPIRY DATE</th>
                                                                <th>COST PRICE</th>
                                                                <th>SELLING PRICE</th>
                                                                <th class="text-center">REMAINING STOCK</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${product.batches.map(b => `
                                                                <tr>
                                                                    <td class="ps-3 fw-bold text-dark">${b.batch_no || '-'}</td>
                                                                    <td class="text-muted">${b.expiry_date || 'No Expiry'}</td>
                                                                    <td class="fw-bold">${typeof formatMoney === 'function' ? formatMoney(b.cost) : b.cost}</td>
                                                                    <td class="fw-bold">${typeof formatMoney === 'function' ? formatMoney(b.price) : b.price}</td>
                                                                    <td class="text-center fw-bold">${b.baseStock}</td>
                                                                </tr>
                                                            `).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>` : ''}

                                            ${hasBranches ? `
                                            <div class="tab-pane fade ${activeTab === 'branches' ? 'show active' : ''}" id="branches-pane-${product.id}" role="tabpanel" aria-labelledby="branches-tab-${product.id}">
                                                <div class="table-responsive rounded border border-gray-200">
                                                    <table class="table table-sm table-striped align-middle m-0" style="font-size: 12px;">
                                                        <thead class="table-light text-uppercase" style="font-size: 11px;">
                                                            <tr>
                                                                <th class="ps-3 py-2">BRANCH / WAREHOUSE</th>
                                                                <th>LOCATION</th>
                                                                <th class="text-center">AVAILABLE STOCK</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${product.branch_stocks.map(bs => `
                                                                <tr>
                                                                    <td class="ps-3 fw-bold text-dark">${bs.branch_name}</td>
                                                                    <td class="text-muted">${bs.location || '-'}</td>
                                                                    <td class="text-center fw-bold text-success">${Number(bs.stock).toFixed(precision)} ${unitName}</td>
                                                                </tr>
                                                            `).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>` : ''}

                                        </div>
                                    </div>
                                    `;
                                })()}
                            </div>        
                        </div>

                        <!-- Variants List -->
                        <div class="row">
                            <div class="col-12">
                                <!-- view full description -->
                                <div class="text-center mb-3">
                                    <button class="btn btn-sm btn-outline-primary px-4 fw-bold rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#fullDescriptionCollapse">
                                        VIEW FULL DESCRIPTION
                                    </button>
                                </div>

                                <div class="collapse" id="fullDescriptionCollapse">
                                    <div class="card card-body bg-light border-0 rounded-3 p-3 mb-3" style="font-size: 13px; color: #444; line-height: 1.6;">
                                        ${product.description || 'No detailed description available.'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#productModalContent').html(html);
            });
        }

        function changeMainImage(src, element) {
            $('#mainProdImage').attr('src', src);
            $('.img-thumbnail').removeClass('border-primary shadow-sm');
            $(element).addClass('border-primary shadow-sm');
        }
    </script>
@endpush
