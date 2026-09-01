@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.manage_product_variants') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <style>
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
    @endcomponent

    <form action="{{ route('products.variants.update', $product->id) }}" method="POST" enctype="multipart/form-data"
        id="product-variant-form">
        @csrf

        <div class="card custom-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl-4 border-xl-end">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-xxl avatar-rounded border p-1 bg-light me-3">
                                <img src="{{ $product->thumb_url ?? url('backend/assets/images/no-image.png') }}"
                                    alt="Product">
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-primary">{{ $product->name }}</h6>
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
                                                @foreach ($savedAttr['values'] as $val)
                                                    <option value="{{ $val }}" selected>{{ $val }}
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
                                {{-- ডাটা না থাকলে এই ডিফল্ট রো-টি দেখাবে --}}
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
                                            data-placeholder="Values">
                                        </select>
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
                <button type="submit" class="btn btn-success btn-lg px-5 shadow">Update Inventory</button>
            </div>
        </div>
    </form>
@endsection



@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        let existingVariants = @json($existingVariants ?? []);
        let productCode = "{{ $product->code }}";
        let baseSkuCode = "{{ $product->sku }}";
        let basePrice = {{ $product->price ?? 0 }};
        let baseCost = {{ $product->cost ?? 0 }};
        let baseWholesale = {{ $product->wholesale_price ?? 0 }};
        $(document).ready(function() {

            let attributeCount = 0;
            // ১. Select2 Initialize Function
            function initSelect2(element) {
                $(element).select2({
                    tags: true,
                    width: '100%',
                    allowClear: true,
                    dropdownParent: $(element).parent()
                });
            }

            // শুরুতে থাকা ফিল্ডগুলো ইনিশিয়াল করা
            initSelect2('.attr-name-select');
            initSelect2('.attr-values-select');

            if ($('.attribute-row').length > 0) {
                generateMatrix();
            }

            // ২. Attribute সিলেক্ট করলে ভ্যালু লোড করা এবং ডুপ্লিকেট চেক
            $(document).on('change', '.attr-name-select', function() {
                let $row = $(this).closest('.attribute-row');
                let $valueSelect = $row.find('.attr-values-select');
                let selectedOption = $(this).find('option:selected');
                let values = selectedOption.data('values');

                let currentVal = $(this).val();
                let isDuplicate = false;
                $('.attr-name-select').not(this).each(function() {
                    if ($(this).val() === currentVal && currentVal !== "") {
                        isDuplicate = true;
                    }
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
                generateMatrix();
            });

            // ৩. নতুন রো অ্যাড করা
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

            // ৪. রো রিমুভ করা
            $(document).on('click', '.remove-attr-btn', function() {
                $(this).closest('.attribute-row').remove();
                generateMatrix();
            });

            // ৫. ভ্যালু চেঞ্জ হলে ম্যাট্রিক্স আপডেট
            $(document).on('change', '.attr-values-select', function() {
                generateMatrix();
            });

            // ৬. মূল ম্যাট্রিক্স জেনারেশন ফাংশন (FIXED)
            function generateMatrix() {
                let attributes = [];
                $('.attribute-row').each(function() {
                    let name = $(this).find('.attr-name-select').val();
                    let values = $(this).find('.attr-values-select').val(); // Select2 returns an array

                    if (name && values && values.length > 0) {
                        // Ensure values are clean
                        attributes.push(values.map(v => v.trim()));
                    }
                });

                if (attributes.length === 0) {
                    $('#variant-section').addClass('d-none');
                    $('#variant-matrix-body').empty();
                    return;
                }

                $('#variant-section').removeClass('d-none');

                // Cartesian product calculation
                let combinations = attributes.reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
                renderVariants(combinations);
            }

            function renderVariants(combinations) {
                let container = $('#variant-matrix-body');
                container.empty();

                combinations.forEach((variant, index) => {
                    let variantArray = Array.isArray(variant) ? variant : [variant];
                    let displayTitle = variantArray.join(' / ');

                    // ১. ডাটাবেজে এই ভেরিয়েন্টটি আছে কি না চেক করুন
                    let existing = existingVariants.find(v => v.name === displayTitle);

                    let variantName = variantArray.join('-').toUpperCase().replace(/\s+/g, '');
                    let fullItemCode = existing ? existing.code : `${productCode}-${variantName}`.replace(
                        /-+/g, '-');
                    let cleanSku = existing ? existing.sku : `${baseSkuCode}-${variantName}`.replace(/-+/g,
                        '-');

                    // ২. ভ্যালু সেট করুন (ডাটাবেজে থাকলে সেটা, না থাকলে বেস প্রাইস)
                    let cost = existing ? existing.cost : baseCost;
                    let price = existing ? existing.price : basePrice;
                    let wholesale = existing ? existing.wholesale_price : baseWholesale;

                    // ৩. এক্সিস্টিং ইমেজগুলো দেখানোর জন্য লজিক
                    let imageHtml = '';
                    if (existing && existing.images) {
                        existing.images.forEach(img => {
                            imageHtml += `
                    <div class="img-preview-item">
                        <img src="/${img.image_path}">
                        <span class="remove-img" onclick="removeImage(this)">×</span>
                    </div>`;
                        });
                    }

                    let html = `
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-primary">${displayTitle}</div>
                                <div class="small">SKU: <b>${cleanSku}</b></div>
                                <div class="small">CODE: <b>${fullItemCode}</b></div>
                                <input type="hidden" name="variants[${index}][name]" value="${displayTitle}">
                                <input type="hidden" name="variants[${index}][sku]" value="${cleanSku}">
                                <input type="hidden" name="variants[${index}][code]" value="${fullItemCode}">
                            </td>
                            <td class="pricing-group">
                                <div class="input-group mb-1">
                                    <span class="input-group-text">COST</span>
                                    <input type="number" name="variants[${index}][cost]" class="form-control" value="${cost}">
                                </div>
                                <div class="input-group mb-1">
                                    <span class="input-group-text">SELL</span>
                                    <input type="number" name="variants[${index}][price]" class="form-control" value="${price}">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">WHOLESALE</span>
                                    <input type="number" name="variants[${index}][wholesale_price]" class="form-control" value="${wholesale}">
                                </div>
                            </td>
                            <td>
                                <div class="variant-gallery-wrapper" id="gallery-container-${index}">
                                    ${imageHtml} {{-- এখানে পুরনো ছবিগুলো দেখাবে --}}
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

                    // Initialize Sortable for each new row
                    new Sortable(document.getElementById(`gallery-container-${index}`), {
                        animation: 150,
                        filter: '.add-image-box, .variant-file-input',
                        onMove: function(evt) {
                            return evt.related.className.indexOf('add-image-box') === -1;
                        }
                    });
                });
            }

            // Row remove action in the matrix table
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                if ($('#variant-matrix-body tr').length === 0) {
                    $('#variant-section').addClass('d-none');
                }
            });

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
            });
        });

        function removeImage(btn) {
            $(btn).parent().remove();
        }

        if ($('.attribute-row').length > 0) {
            generateMatrix();
        }
    </script>
@endpush
