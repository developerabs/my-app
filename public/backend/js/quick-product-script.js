const quickProductModal = $('#quickProductModal');
const unitGroup = quickProductModal.find($('#unit_group'));
const baseUnit = quickProductModal.find('select[name="base_unit_id"]');
const unitVariantContent = quickProductModal.find('#unit_variables_container');
const hasVariants = quickProductModal.find('input[name="has_variants"]');
const attributeSection = quickProductModal.find('#attribute_section');
const baseCost = quickProductModal.find('input[name="cost"]').val();
const basePrice = quickProductModal.find('input[name="price"]').val();
const baseWholesale = quickProductModal.find('input[name="wholesale_price"]').val();
const baseSku = quickProductModal.find('input[name="sku"]').val();
const variantSection = quickProductModal.find('#variant-section');
const variantMatrixBody = quickProductModal.find('#variant-matrix-body');

function calculatePrice() {
    let margin = parseFloat($('#profit_margin').val()) || 0;
    let cost = parseFloat($('#product_cost').val()) || 0;
    let currentPrice = parseFloat($('#product_price').val()) || 0;

    // নেগেটিভ মার্জিন ঠেকানোর জন্য
    if (margin < 0) {
        margin = 0;
        $('#profit_margin').val(0);
    }

    // মার্জিন অনুযায়ী সেলিং প্রাইস ক্যালকুলেশন: Cost + (Cost * Margin / 100)
    let expectedPrice = cost + (cost * (margin / 100));

    // যদি বর্তমান প্রাইস এক্সপেক্টেড প্রাইসের চেয়ে কম হয় অথবা নতুন এন্ট্রি হয়, তবেই আপডেট হবে
    if (currentPrice < expectedPrice || currentPrice === 0) {
        $('#product_price').val(expectedPrice.toFixed(2));
    }
}

// ইনপুট ইভেন্ট (টাইপ করার সাথে সাথে কাজ করবে)
$(document).on('input', '#profit_margin, #product_cost', function() {
    calculatePrice();
});

// প্রাইস ম্যানুয়ালি চেঞ্জ করলে চেক করবে
$(document).on('blur', '#product_price', function() {
    let margin = parseFloat($('#profit_margin').val()) || 0;
    let cost = parseFloat($('#product_cost').val()) || 0;
    let inputPrice = parseFloat($(this).val()) || 0;

    let minAllowedPrice = cost + (cost * (margin / 100));

    // যদি ইউজার ক্যালকুলেটেড প্রাইসের চেয়ে কম দিতে চায়, তাকে বাধা দিবে
    if (inputPrice < minAllowedPrice) {
        showFloatingAlert('Price cannot be less than the required profit margin (' + minAllowedPrice
            .toFixed(2) + ')');
        $(this).val(minAllowedPrice.toFixed(2));
    }
});

hasVariants.on('change', function() {
    if ($(this).is(':checked')) {
        attributeSection.show();
        generateMatrix();
    } else {
        attributeSection.hide();
        variantSection.addClass('d-none');
    }
});

let attributeCount = $('.attribute-row').length;


unitGroup.change(() => {
    unitVariantContent.empty();
    const groupId = unitGroup.val();
    if(groupId){
        UnitManager.fetchBaseUnits(groupId, 'select[name="base_unit_id"]');
    }
});

baseUnit.change(() => {
    unitVariantContent.empty();
    const baseUnitId = baseUnit.val();
    const baseUnitName = baseUnit.find('option:selected').text();
    if(baseUnitId){
        UnitManager.fetchSubUnits(baseUnitId, baseUnitName, '#unit_variables_container', 'select[name="purchase_unit_id"]', 'select[name="sale_unit_id"]');
    }
});

function initSelect2(element) {
    $(element).select2({
        tags: true,
        width: '100%',
        allowClear: true,
        dropdownParent: $(element).parent()
    });
}

const attributeNameSelect = quickProductModal.find('.attr-name-select');
const attributeValueSelect = quickProductModal.find('.attr-values-select');
// শুরুতে থাকা ফিল্ডগুলো ইনিশিয়াল করা
attributeNameSelect.each(function() {
    initSelect2(this);
});
attributeValueSelect.each(function() {
    initSelect2(this);
});

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
function getAttributeOptions() {
    return window.allAttributesData.map(attr => {
        // Safe stringify for data attribute
        const valuesJson = JSON.stringify(attr.values.map(v => v.value));
        return `<option value="${attr.name}" data-values='${valuesJson}'>${attr.name}</option>`;
    }).join('');
}

$('#add-attribute-btn').on('click', function() {
    attributeCount++;
    
    let html = `
    <div class="row g-2 attribute-row align-items-center mb-2 animate__animated animate__fadeIn">
        <div class="col-3">
            <select name="attributes[${attributeCount}][name]" class="form-control form-control-sm attr-name-select" data-placeholder="Select Attribute">
                <option></option>
                ${getAttributeOptions()}
            </select>
        </div>
        <div class="col-8">
            <select name="attributes[${attributeCount}][values][]" class="form-control form-control-sm attr-values-select" multiple data-placeholder="Values"></select>
        </div>
        <div class="col-1 text-end align-content-end">
            <button type="button" class="btn btn-danger-light btn remove-attr-btn"><i class="ri-delete-bin-line"></i></button>
        </div>
    </div>`;

    let $newRow = $(html);
    $('#attribute-wrapper').append($newRow);
    
    // Initializing Select2 for the new elements
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
$(document).on('click', '.remove-row', function() {
    $(this).closest('tr').remove();
});

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
        variantSection.addClass('d-none');
        variantMatrixBody.empty();
        return;
    }

    variantSection.removeClass('d-none');

    // Cartesian Product logic (একটি বা অনেক রো - সব ক্ষেত্রেই কাজ করবে)
    const cartesian = (arrays) => arrays.reduce((a, b) => a.flatMap(d => b.map(e => [d, e].flat())));
    let combinations = (attributes.length === 1) ? attributes[0].map(v => [v]) : cartesian(attributes);
    
    renderVariants(combinations);
}

// ২. renderVariants ফাংশনে ডায়নামিক ভ্যালু গেট করুন
function renderVariants(combinations) {
    let container = variantMatrixBody;
    container.empty();

    // মডাল থেকে বর্তমান ভ্যালুগুলো নিন (Hardcoded না রেখে)
    let currentCost = $('input[name="cost"]').val() || 0;
    let currentPrice = $('input[name="price"]').val() || 0;
    let currentWholesale = $('input[name="wholesale_price"]').val() || 0;
    let baseSkuCode = $('input[name="sku"]').val() || 'SKU';

    combinations.forEach((variant, index) => {
        let displayTitle = Array.isArray(variant) ? variant.join(' / ') : variant;
        let skuSuffix = Array.isArray(variant) ? variant.join('-') : variant;
        let sku = `${baseSkuCode}-${skuSuffix.toUpperCase().replace(/\s+/g, '')}`;

        let html = `
            <tr>
                <td class="ps-3 align-middle">
                    <div class="fw-bold text-primary" style="font-size: 0.85rem;">${displayTitle}</div>
                    <div class="text-muted" style="font-size: 0.70rem;">SKU: <b>${sku}</b></div>
                    <input type="hidden" name="variants[${index}][name]" value="${displayTitle}">
                    <input type="hidden" name="variants[${index}][sku]" value="${sku}">
                </td>
                <td class="p-2">
                    <div class="row g-1">
                        <div class="col-4">
                            <input type="number" name="variants[${index}][cost]" class="form-control form-control-sm" placeholder="Cost" value="${currentCost}" title="Cost">
                        </div>
                        <div class="col-4">
                            <input type="number" name="variants[${index}][price]" class="form-control form-control-sm" placeholder="Sell" value="${currentPrice}" title="Sell Price">
                        </div>
                        <div class="col-4">
                            <input type="number" name="variants[${index}][wholesale_price]" class="form-control form-control-sm" placeholder="Wholesale" value="${currentWholesale}" title="Wholesale Price">
                        </div>
                    </div>
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 remove-row">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>`;
        container.append(html);
    });
}