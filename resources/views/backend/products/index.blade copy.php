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
                console.log(product.unit_details[product.base_unit_id]);
                let mainThumb = product.thumb_url || 'https://via.placeholder.com/300?text=No+Image';

                // ক্যাটেগরি ব্যাজ
                let categoryBadges = product.categories && product.categories.length > 0 ? 
                    product.categories.map(cat =>
                        `<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1" style="font-size: 9px; padding: 2px 6px;">${cat.name}</span>`
                    ).join('') : '<span class="text-muted small">N/A</span>';
                
                // Get base unit safely using optional chaining
                let baseUnit = product.unit_details?.[product.base_unit_id] || null;

                // Get precision safely, default to 0 if null/undefined
                let precision = parseInt(baseUnit?.precision ?? 0);

                // Parse stock to number, default to 0 if NaN or invalid
                let rawStock = Number(product.total_stock);
                let formattedNumber = isNaN(rawStock) ? '0' : rawStock.toFixed(precision);

                // Combine number with short name (fallback to name, then empty string)
                let unitName = baseUnit ? (baseUnit.short_name || baseUnit.name || '') : '';
                let totalStockInBaseUnit = `${formattedNumber} ${unitName}`.trim();

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

                                <!-- অতিরিক্ত তথ্য (কম্প্যাক্ট লিস্ট) -->
                                <div class="p-2 bg-light rounded-3 border" style="font-size: 11px;">
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Short Name:</span> <span class="fw-bold">${product.short_name || '-'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Total Stock:</span> <span class="fw-bold">${totalStockInBaseUnit || '0'}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 pb-1 border-bottom border-white">
                                        <span class="text-muted">Formated Stock:</span> <span class="fw-bold">${product.formatted_stock || '0'}</span>
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
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-dark px-2 py-1" style="font-size: 8px; letter-spacing: 0.5px; text-transform: uppercase;">${product.type}</span>
                                    <span class="text-muted" style="font-size: 11px;">SKU: <b class="text-dark">${product.sku}</b></span>
                                    <span class="text-muted" style="font-size: 11px;">CODE: <b class="text-dark">${product.code}</b></span>
                                </div>
                                <h4 class="fw-bold text-dark mb-2" style="font-size: 1.25rem;">${product.name}</h4>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-3"><small class="text-muted d-block fw-bold" style="font-size: 10px;">BRAND</small><span class="badge bg-light text-dark border fw-normal">${product.brand?.name || 'Generic'}</span></div>
                                    <div class="col-9"><small class="text-muted d-block fw-bold" style="font-size: 10px;">CATEGORIES</small><div>${categoryBadges}</div></div>
                                </div>

                                <!-- প্রাইস সেকশন (কম্প্যাক্ট কার্ড) -->
                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <div class="p-2 border-0 rounded-3 bg-primary text-white text-center shadow-sm">
                                            <small class="opacity-75 d-block" style="font-size: 9px; font-weight: 700;">SALE</small>
                                            <span class="fw-bold" style="font-size: 15px;">${formatMoney(product.price)}</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border rounded-3 bg-success-subtle border-success-subtle text-center shadow-sm">
                                            <small class="text-success d-block fw-bold" style="font-size: 9px;">WHOLESALE</small>
                                            <span class="fw-bold text-success" style="font-size: 15px;">${formatMoney(product.wholesale_price)}</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border rounded-3 bg-light text-center shadow-sm">
                                            <small class="text-muted d-block fw-bold" style="font-size: 9px;">COST</small>
                                            <span class="fw-bold text-secondary" style="font-size: 15px;">${formatMoney(product.cost)}</span>
                                        </div>
                                    </div>
                                </div>

                                ${product.short_description ? `<div class="mb-3 p-2 bg-light rounded border-start border-3 border-primary" style="font-size: 12px; color: #555;">${product.short_description}</div>` : ''}

                                <!-- স্পেসিফিকেশন ও অন্যান্য (ইনলাইন স্টাইল) -->
                                <div class="row g-2">
                                    <!-- স্পেসিফিকেশন কলাম (বাম পাশ) -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-1" style="font-size: 10px; color: #666;">SPECIFICATIONS</h6>
                                        ${product.specifications?.length > 0 ? `
                                        <div class="table-responsive border rounded">
                                            <table class="table table-sm table-bordered bg-white mb-0" style="font-size: 10px;">
                                                <tbody>
                                                    ${product.specifications.slice(0, 5).map(s => `
                                                        <tr>
                                                            <td class="bg-light fw-bold" width="40%">${s.key}</td>
                                                            <td>${s.value}</td>
                                                        </tr>`).join('')}
                                                </tbody>
                                            </table>
                                        </div>` : '<div class="text-muted small p-2 border rounded bg-light" style="font-size: 10px;">No specifications available</div>'}
                                    </div>

                                    <!-- ওয়ারেন্টি ডিটেইলস কলাম (ডান পাশ) -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-1" style="font-size: 10px; color: #666;">WARRANTY DETAILS</h6>
                                        ${product.has_warranty && product.warranty_details ? `
                                            <div class="table-responsive border rounded text-dark">
                                                <table class="table table-sm table-bordered bg-white mb-0" style="font-size: 10px;">
                                                    <tbody>
                                                        ${(() => {
                                                            // JSON ডাটা পার্স করা
                                                            let details = typeof product.warranty_details === 'string' ? JSON.parse(product.warranty_details) : product.warranty_details;
                                                            
                                                            // পিরিয়ড এবং টাইপ মিলিয়ে নতুন একটি ফিল্ড তৈরি করা
                                                            let period = details.warranty_period || '';
                                                            let type = details.period_type || '';
                                                            let duration = period ? `${period} ${type}`.trim() : 'N/A';

                                                            // যে ৩টি রো আমরা দেখাতে চাই
                                                            let rows = [
                                                                { label: 'Warranty Type', value: details.warranty_type },
                                                                { label: 'Warranty Provider', value: details.warranty_provider },
                                                                { label: 'Warranty Period', value: duration }
                                                            ];

                                                            return rows.map(row => `
                                                                <tr>
                                                                    <td class="bg-light fw-bold text-capitalize" width="45%">${row.label}</td>
                                                                    <td>${row.value || 'N/A'}</td>
                                                                </tr>
                                                            `).join('');
                                                        })()}
                                                    </tbody>
                                                </table>
                                            </div>` : '<div class="text-muted small p-2 border rounded bg-light" style="font-size: 10px;">No warranty info</div>'}
                                    </div>
                                    <div class="col-md-12">
                                        ${product.dropshipping_detail ? `
                                        <div class="mt-3 card border-0 shadow-sm rounded-3 overflow-hidden">
                                            <!-- হেডার অংশ -->
                                            <div class="card-header bg-dark d-flex justify-content-between align-items-center py-2 px-3">
                                                <div class="text-white">
                                                    <i class="fa-solid fa-truck-ramp-box me-1 text-info"></i>
                                                    <span class="fw-bold small text-uppercase" style="letter-spacing: 0.5px;">Dropshipping Details</span>
                                                </div>
                                                <span class="badge bg-info text-dark fw-bolder" style="font-size: 10px;">
                                                    ${product.dropshipping_detail.platform_name || 'Global'}
                                                </span>
                                            </div>
                                            
                                            <div class="card-body p-0">
                                                <!-- মেইন ইনফো গ্রিড -->
                                                <div class="row g-0">
                                                    <!-- কলাম ১: সাপ্লাইয়ার এবং টাইম -->
                                                    <div class="col-6 border-end p-2 bg-light-subtle">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="bg-primary-subtle p-1 rounded me-2">
                                                                <i class="fa-solid fa-user-tie text-primary" style="font-size: 10px;"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block" style="font-size: 9px;">SUPPLIER</small>
                                                                <span class="fw-bold d-block" style="font-size: 11px;">${product.dropshipping_detail.supplier_name || 'Unknown'}</span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-warning-subtle p-1 rounded me-2">
                                                                <i class="fa-solid fa-clock text-warning" style="font-size: 10px;"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block" style="font-size: 9px;">LEAD TIME</small>
                                                                <span class="fw-bold d-block" style="font-size: 11px;">${product.dropshipping_detail.delivery_lead_time} Business Days</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- কলাম ২: কস্টিং এবং প্রাইস -->
                                                    <div class="col-6 p-2">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="bg-danger-subtle p-1 rounded me-2">
                                                                <i class="fa-solid fa-hand-holding-dollar text-danger" style="font-size: 10px;"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block" style="font-size: 9px;">SHIPPING COST</small>
                                                                <span class="fw-bold d-block text-danger" style="font-size: 11px;">${formatMoney(product.dropshipping_detail.estimated_shipping_cost)}</span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-success-subtle p-1 rounded me-2">
                                                                <i class="fa-solid fa-tags text-success" style="font-size: 10px;"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block" style="font-size: 9px;">EXT. SKU / CODE</small>
                                                                <span class="fw-bold d-block" style="font-size: 11px;">${product.dropshipping_detail.external_sku || product.dropshipping_detail.external_product_code || '-'}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- প্রাইস কম্পারিজন বার -->
                                                <div class="px-3 py-2 bg-light border-top border-bottom d-flex justify-content-between align-items-center">
                                                    <div class="small">
                                                        <span class="text-muted" style="font-size: 10px;">BUY:</span> 
                                                        <span class="fw-bold text-dark ms-1">${formatMoney(product.dropshipping_detail.buying_price)}</span>
                                                    </div>
                                                    <i class="fa-solid fa-arrow-right-long text-muted mx-2"></i>
                                                    <div class="small text-end">
                                                        <span class="text-muted" style="font-size: 10px;">SELL:</span> 
                                                        <span class="fw-bold text-success ms-1" style="font-size: 13px;">${formatMoney(product.dropshipping_detail.selling_price)}</span>
                                                    </div>
                                                </div>

                                                <!-- ভিজিট বাটন -->
                                                ${product.dropshipping_detail.external_product_url ? `
                                                <div class="p-2">
                                                    <a href="${product.dropshipping_detail.external_product_url}" target="_blank" 
                                                    class="btn btn-dark w-100 py-1 shadow-sm d-flex align-items-center justify-content-center" 
                                                    style="font-size: 10px; border-radius: 6px; transition: 0.3s;">
                                                        <i class="fa-solid fa-cart-shopping me-2"></i> SOURCE PRODUCT ON ${product.dropshipping_detail.platform_name.toUpperCase()}
                                                    </a>
                                                </div>` : ''}
                                            </div>
                                        </div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Variants List -->
                        <div class="row">
                            <div class="col-12">
                                ${product.has_variants && product.variants?.length > 0 ? `
                                <div class="card border rounded-3 shadow-sm mb-3">
                                    <div class="py-2 px-3 border-bottom bg-light">
                                        <h6 class="fw-bold mb-0 text-primary" style="font-size: 12px;">Variants & Stock Details</h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3" width="60">IMG</th>
                                                    <th>NAME</th>
                                                    <th>SKU</th>
                                                    <th>COST</th>
                                                    <th>SALE</th>
                                                    <th>WHOLESALE</th>
                                                    <th class="text-center">STOCK</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${product.variants.map(v => {
                                                    let vImg = (v.images?.length > 0) ? v.images[0].full_url : mainThumb;

                                                    let variantRawStock = Number(v.total_stock);
                                                    let formattedVariantStock = isNaN(variantRawStock) ? '0' : variantRawStock.toFixed(precision);
                                                    let variantStockInBaseUnit = `${formattedVariantStock} ${unitName}`.trim();
                                                    return `
                                                    <tr>
                                                        <td class="ps-3">
                                                            <img src="${vImg}" class="rounded border" style="width: 32px; height: 32px; object-fit: cover; cursor: pointer;" 
                                                                onclick="changeMainImage('${vImg}', this)">
                                                        </td>
                                                        <td><span class="fw-bold text-dark">${v.name}</span></td>
                                                        <td class="text-muted">${v.sku || '-'}</td>
                                                        <td class="fw-bold text-danger">${formatMoney(v.cost)}</td>
                                                        <td class="fw-bold text-primary">${formatMoney(v.price)}</td>
                                                        <td class="text-success">${formatMoney(v.wholesale_price)}</td>
                                                        <td class="text-center">${variantStockInBaseUnit}</td>
                                                    </tr>`;
                                                }).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>` : ''}

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
