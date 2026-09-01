@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.manage_product_variants') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <style>
        .avatar-rounded img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%;
        }

        .avatar-rounded {
            flex-shrink: 0;
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .variant-gallery-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px;
            background: #fdfdfd;
            border: 1px dashed #ddd;
            border-radius: 8px;
            min-height: 100px;
        }

        .img-preview-item {
            position: relative;
            width: 80px;
            height: 80px;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
            cursor: grab;
        }

        .img-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-preview-item .remove-img {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            font-size: 12px;
            font-weight: bold;
            width: 20px;
            height: 20px;
            text-align: center;
            cursor: pointer;
            line-height: 18px;
            z-index: 10;
        }

        .add-image-box {
            width: 80px;
            height: 80px;
            border: 2px dashed #007bff;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            cursor: pointer;
            color: #007bff;
            background: #f0f7ff;
            transition: 0.2s;
        }

        .pricing-group .input-group-text {
            min-width: 110px !important;
            font-size: 11px;
            font-weight: 700;
            background-color: #f8f9fa;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.manage_product_variants') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.manage_product_variants_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('products.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> {{ __('file.button.list') }} {{ __('file.product') }}
            </a>
        @endslot
    @endcomponent

    @if ($existingVariants->count() > 0)
        <div class="alert shadow-sm d-flex align-items-center" role="alert"
            style="background-color: #fffec8; border: 1px solid #ffeeba; border-left: 4px solid #664d03; color: #856404; padding: 10px 15px;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div class="flex-grow-1">
                <strong style="color: #664d03;">{{ __('file.warning') }}:</strong>
                {!! __('file.message.existing_variants_warning') !!}
            </div>
        </div>
    @endif

    <form action="{{ route('products.variants.update', $product->id) }}" method="POST" enctype="multipart/form-data"
        id="product-variant-form">
        @csrf

        <div class="card custom-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl-4 border-xl-end">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-xxl avatar-rounded border p-1 bg-light me-3 flex-shrink-0"
                                style="width: 70px; height: 70px;">
                                <img src="{{ $product->thumb_url ?? url('backend/assets/images/no-image.png') }}"
                                    alt="Product"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            </div>

                            <div class="overflow-hidden">
                                <h6 class="fw-bold mb-1 text-primary text-truncate" title="{{ $product->name }}">
                                    {{ $product->name }}
                                </h6>
                                <span
                                    class="badge bg-dark-transparent mb-1">#{{ str_pad($product->code ?? $product->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <div class="text-muted fs-12">BASE SKU: <span id="base-sku"
                                        class="fw-bold text-dark">{{ $product->sku }}</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold fs-13 mb-0">Attributes Configuration</h6>
                            <button type="button" class="btn btn-primary btn-sm shadow-sm" id="add-attribute-btn">
                                <i class="ri-add-circle-line align-middle"></i> Add Attribute
                            </button>
                        </div>
                        <div id="attribute-wrapper">
                            @if (!empty($savedAttributes))
                                @foreach ($savedAttributes as $key => $savedAttr)
                                    <div class="row g-2 attribute-row align-items-center mb-2">
                                        <div class="col-3">
                                            <select name="attributes[{{ $key }}][name]"
                                                class="form-control form-control-sm attr-name-select">
                                                <option></option>
                                                @foreach ($allAttributes as $attr)
                                                    {{-- এখানে ডাটা-ভ্যালু হিসেবে ওই অ্যাট্রিবিউটের সব অপশন পাস করছি --}}
                                                    <option value="{{ $attr->name }}"
                                                        {{ $savedAttr['name'] == $attr->name ? 'selected' : '' }}
                                                        data-values="{{ json_encode($attr->values->pluck('value')) }}">
                                                        {{ $attr->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <select name="attributes[{{ $key }}][values][]"
                                                class="form-control form-control-sm attr-values-select" multiple>
                                                @php
                                                    // বর্তমান অ্যাট্রিবিউটের জন্য ডাটাবেজের সব সম্ভাব্য ভ্যালু খুঁজে বের করা
                                                    $currentAttrDef = $allAttributes
                                                        ->where('name', $savedAttr['name'])
                                                        ->first();
                                                    $allAvailableValues = $currentAttrDef
                                                        ? $currentAttrDef->values->pluck('value')->toArray()
                                                        : [];
                                                @endphp

                                                @foreach ($allAvailableValues as $val)
                                                    <option value="{{ $val }}"
                                                        {{ in_array($val, $savedAttr['values']) ? 'selected' : '' }}>
                                                        {{ $val }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-1 text-end">
                                            <button type="button" class="btn btn-danger-light btn-sm remove-attr-btn">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="row g-2 attribute-row align-items-center mb-2">
                                    <div class="col-3">
                                        <select name="attributes[0][name]"
                                            class="form-control form-control-sm attr-name-select"
                                            data-placeholder="Select Attribute">
                                            <option></option>
                                            @foreach ($allAttributes as $attr)
                                                <option value="{{ $attr->name }}"
                                                    data-values="{{ json_encode($attr->values->pluck('value')) }}">
                                                    {{ $attr->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <select name="attributes[0][values][]"
                                            class="form-control form-control-sm attr-values-select" multiple
                                            data-placeholder="Values"></select>
                                    </div>
                                    <div class="col-1 text-end">
                                        <button type="button" class="btn btn-danger-light btn-sm remove-attr-btn">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card d-none shadow-lg" id="variant-section">
            <div class="card-header bg-primary-transparent">
                <h6 class="card-title text-primary mb-0">Product Variant Matrix</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 table-variant">
                        <thead>
                            <tr>
                                <th style="width: 20%;" class="ps-3">Variant Details</th>
                                <th style="width: 30%;">Pricing Settings</th>
                                <th style="width: 45%;">Gallery (Images)</th>
                                <th style="width: 5%;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="variant-matrix-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end bg-light">
                <a href="{{ route('products.index') }}" class="btn btn-secondary btn-lg px-5 shadow me-2">{{ __('file.button.cancel') }}</a>
                <button type="submit" class="btn btn-success btn-lg px-5 shadow">{{ __('file.button.update') }}
                    {{ __('file.variants') }}</button>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        // কন্ট্রোলার থেকে আসা ডাটা
        let existingVariants = @json($existingVariants ?? []);
        let productCode = "{{ $product->code }}";
        let baseSkuCode = "{{ $product->sku }}";
        let basePrice = {{ $product->price ?? 0 }};
        let baseCost = {{ $product->cost ?? 0 }};
        let baseWholesale = {{ $product->wholesale_price ?? 0 }};
        let attributeCount = $('.attribute-row').length;
        let mainProductUnitDetails = @json($product->unit_details ?? []);

        console.log("mainProductUnitDetails:", mainProductUnitDetails); // English Comment: Debug output to verify unit details structure

        $(document).ready(function() {
            function initSelect2(element) {
                $(element).select2({
                    tags: true,
                    width: '100%',
                    allowClear: true,
                    dropdownParent: $(element).parent(),
                    // 💡 ১. আসল ফিক্স: কাস্টম সার্চ ম্যাচিং ইঞ্জিন
                    matcher: function(params, data) {
                        var term = $.trim(params.term).toLowerCase();
                        if (term === '') {
                            return data;
                        }

                        if (typeof data.text === 'undefined') {
                            return null;
                        }

                        var text = $.trim(data.text).toLowerCase();

                        // 🛑 অলরেডি সিলেক্টেড থাকা অপশনকে অন্য সার্চের সাথে ম্যাচ হতে দেবে না (যদি না হুবহু এক হয়)
                        if (data.selected && text !== term) {
                            return null;
                        }

                        // হুবহু এক হলে বা টাইপ করা শব্দ দিয়ে শুরু হলে ম্যাচ করাবে
                        if (text === term || text.startsWith(term)) {
                            return data;
                        }

                        return null;
                    },
                    createTag: function (params) {
                        var term = $.trim(params.term);

                        if (term === '') {
                            return null;
                        }

                        return {
                            id: term,
                            text: term,
                            isNew: true
                        };
                    },
                    insertTag: function (data, tag) {
                        var isExactExist = false;

                        $.each(data, function(index, item) {
                            if (item.text.trim().toLowerCase() === tag.text.trim().toLowerCase()) {
                                isExactExist = true;
                                return false;
                            }
                        });

                        if (!isExactExist) {
                            data.unshift(tag);
                        }
                    }
                });
            }

            // শুরুতে থাকা ফিল্ডগুলো ইনিশিয়াল করা
            $('.attr-name-select').each(function() {
                initSelect2(this);
            });
            $('.attr-values-select').each(function() {
                initSelect2(this);
            });

            // পেজ লোড হওয়ার সময় যদি ডাটা থাকে তবে টেবিল জেনারেট করা
            if ($('.attribute-row').length > 0) {
                generateMatrix();
            }

            // Attribute সিলেক্ট করলে ভ্যালু লোড করা
            $(document).on('change', '.attr-name-select', function() {
                let $row = $(this).closest('.attribute-row');
                let $valueSelect = $row.find('.attr-values-select');
                let selectedOption = $(this).find('option:selected');
                let values = selectedOption.data('values');

                let currentVal = $(this).val();
                let isDuplicate = false;
                $('.attr-name-select').not(this).each(function() {
                    if ($(this).val() === currentVal && currentVal !== "") isDuplicate = true;
                });

                if (isDuplicate) {
                    showFloatingAlert("warning", "This attribute is already selected!");
                    $(this).val('').trigger('change');
                    return;
                }

                $valueSelect.empty();
                if (values) {
                    values.forEach(v => {
                        $valueSelect.append(new Option(v, v, false, false));
                    });
                }
                $valueSelect.trigger('change');
            });

            // নতুন অ্যাট্রিবিউট রো অ্যাড করা
            $('#add-attribute-btn').on('click', function() {
                attributeCount++;
                let html = `
                <div class="row g-2 attribute-row align-items-center mb-2 animate__animated animate__fadeIn">
                    <div class="col-3">
                        <select name="attributes[${attributeCount}][name]" class="form-control form-control-sm attr-name-select" data-placeholder="Select Attribute">
                            <option></option>
                            @foreach ($allAttributes as $attr)
                                <option value="{{ $attr->name }}" data-values="{{ json_encode($attr->values->pluck('value')) }}">{{ $attr->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-8">
                        <select name="attributes[${attributeCount}][values][]" class="form-control form-control-sm attr-values-select" multiple data-placeholder="Values"></select>
                    </div>
                    <div class="col-1 text-end">
                        <button type="button" class="btn btn-danger-light btn-sm remove-attr-btn"><i class="ri-delete-bin-line"></i></button>
                    </div>
                </div>`;
                let $newRow = $(html);
                $('#attribute-wrapper').append($newRow);
                initSelect2($newRow.find('.attr-name-select'));
                initSelect2($newRow.find('.attr-values-select'));
            });

            $(document).on('click', '.remove-attr-btn', function() {
                $(this).closest('.attribute-row').remove();
                generateMatrix();
            });

            $(document).on('change', '.attr-values-select', function() {
                generateMatrix();
            });

            // ম্যাট্রিক্স জেনারেশন
            function generateMatrix() {
                let attributes = [];
                $('.attribute-row').each(function() {
                    let name = $(this).find('.attr-name-select').val();
                    let values = $(this).find('.attr-values-select').val();
                    if (name && values && values.length > 0) {
                        attributes.push(values.map(v => v.trim()));
                    }
                });

                if (attributes.length === 0) {
                    $('#variant-section').addClass('d-none');
                    $('#variant-matrix-body').empty();
                    return;
                }

                $('#variant-section').removeClass('d-none');
                let combinations = attributes.reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
                renderVariants(combinations);
            }

            function renderVariants(combinations) {
                let container = $('#variant-matrix-body');
                container.empty();

                combinations.forEach((variant, index) => {
                    let variantArray = Array.isArray(variant) ? variant : [variant];
                    let displayTitle = variantArray.join(' / ');

                    // এক্সিস্টিং ডাটা চেক
                    let existing = existingVariants.find(v => v.name === displayTitle);

                    let cost = existing ? existing.cost : baseCost;
                    let price = existing ? existing.price : basePrice;
                    let wholesale = existing ? existing.wholesale_price : baseWholesale;
                    let sku = existing ? existing.sku :
                        `${baseSkuCode}-${variantArray.join('-').toUpperCase().replace(/\s+/g, '')}`;
                    let code = existing ? existing.code :
                        `${productCode}-${variantArray.join('-').toUpperCase().replace(/\s+/g, '')}`;

                    //let existingUnitVars = existing && existing.unit_vars ? existing.unit_vars : null;

                    let unitFieldsHtml = '';
        
                    // English Comment: Check if main product has valid unit details structured as an object
                    if (mainProductUnitDetails && typeof mainProductUnitDetails === 'object') {
                        $.each(mainProductUnitDetails, function(unitId, unitData) {
                            
                            if (unitData.is_formulaic && unitData.user_vars && typeof unitData.user_vars === 'object') {
                                let hasVariables = Object.keys(unitData.user_vars).length > 0;
                                
                                if (hasVariables) {
                                    unitFieldsHtml += `
                                        <div class="mt-2 text-start">
                                            <div class="d-flex flex-wrap">`;
                                    
                                    $.each(unitData.user_vars, function(varName, defaultValue) {
                                        
                                        // 🔥 English Comment: CRUCIAL FALLBACK LOGIC
                                        // Start by assuming the default value from the main mother product
                                        let finalValue = defaultValue; 
                                        
                                        // English Comment: If an existing saved variant row is matched, extract its specific saved value from unit_details
                                        if (existing && existing.unit_details && existing.unit_details[unitId]) {
                                            let existingUnit = existing.unit_details[unitId];
                                            if (existingUnit.user_vars && existingUnit.user_vars[varName] !== undefined) {
                                                finalValue = existingUnit.user_vars[varName];
                                            }
                                        }

                                        unitFieldsHtml += `
                                            <div class="d-flex align-items-center gap-1 mb-1 me-2">
                                                <span class="text-muted fw-medium fs-12">${varName}:</span>
                                                <input type="number" step="any" 
                                                    name="variants[${index}][unit_vars][${unitId}][${varName}]" 
                                                    class="form-control form-control-sm px-1 text-center fw-bold" 
                                                    style="width: 65px; height: 26px; font-size: 12px; border-radius: 4px;"
                                                    value="${finalValue}" 
                                                    placeholder="${defaultValue}" required>
                                            </div>`;
                                    });

                                    unitFieldsHtml += `</div></div>`;
                                }
                            }
                        });
                    }
                    // ইমেজ রেন্ডারিং
                    let imageHtml = '';
                    if (existing && existing.images) {
                        existing.images.forEach(img => {
                            imageHtml += `
                            <div class="img-preview-item">
                                <img src="${img.image_path}">
                                <input type="hidden" name="variants[${index}][existing_images][]" value="${img.id}">
                                <span class="remove-img" onclick="removeImage(this)">×</span>
                            </div>`;
                        });
                    }

                    let html = `
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-primary">${displayTitle}</div>
                            <div class="small">SKU: <b>${sku}</b></div>
                            <div class="small">CODE: <b>${code}</b></div>
                            <input type="hidden" name="variants[${index}][id]" value="${existing ? existing.id : ''}">
                            <input type="hidden" name="variants[${index}][name]" value="${displayTitle}">
                            <input type="hidden" name="variants[${index}][sku]" value="${sku}">
                            <input type="hidden" name="variants[${index}][code]" value="${code}">
                            <div class="variant-unit-inputs-block">
                                ${unitFieldsHtml}
                            </div>
                        </td>
                        <td class="pricing-group">
                            <div class="input-group mb-1">
                                <span class="input-group-text">COST</span>
                                <input type="text" pattern="[0-9]+(\.[0-9]+)?" name="variants[${index}][cost]" class="form-control" value="${cost}">
                            </div>
                            <div class="input-group mb-1">
                                <span class="input-group-text">SELL</span>
                                <input type="text" pattern="[0-9]+(\.[0-9]+)?" name="variants[${index}][price]" class="form-control" value="${price}">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">WHOLESALE</span>
                                <input type="text" pattern="[0-9]+(\.[0-9]+)?" name="variants[${index}][wholesale_price]" class="form-control" value="${wholesale}">
                            </div>
                        </td>
                        <td>
                            <div class="variant-gallery-wrapper" id="gallery-container-${index}">
                                ${imageHtml}
                                <div class="add-image-box" onclick="$(this).next('.variant-file-input').click()">
                                    <i class="ri-image-add-line"></i>
                                    <span>Add Photo</span>
                                </div>
                                <input type="file" name="variants[${index}][images][]" multiple class="d-none variant-file-input" data-index="${index}">
                            </div>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger-light remove-row"><i class="ri-delete-bin-line"></i></button>
                        </td>
                    </tr>`;
                    container.append(html);

                    new Sortable(document.getElementById(`gallery-container-${index}`), {
                        animation: 150,
                        filter: '.add-image-box, .variant-file-input'
                    });
                });
            }

            $(document).on('change', '.variant-file-input', function(e) {
                let index = $(this).data('index');
                let files = e.target.files;
                let galleryWrapper = $(`#gallery-container-${index}`);
                let addButton = galleryWrapper.find('.add-image-box');

                for (let i = 0; i < files.length; i++) {
                    let reader = new FileReader();
                    reader.onload = function(event) {
                        let imgHtml = `
                        <div class="img-preview-item animate__animated animate__zoomIn">
                            <img src="${event.target.result}">
                            <span class="remove-img" onclick="removeImage(this)">×</span>
                        </div>`;
                        addButton.before(imgHtml);
                    };
                    reader.readAsDataURL(files[i]);
                }
            });

            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                if ($('#variant-matrix-body tr').length === 0) $('#variant-section').addClass('d-none');
            });
        });

        function removeImage(btn) {
            $(btn).parent().remove();
        }
    </script>
@endpush
