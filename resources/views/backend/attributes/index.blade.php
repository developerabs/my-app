@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.manage_attributes') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.manage_attributes') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.manage_attributes_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAttributeModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.attribute') }}</a>
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="createAttributeModal" tabindex="-1"
        aria-labelledby="createAttributeModalLabel"aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAttributeModalLabel">{{ __('file.title.create_attribute') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="createAttributeForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('backend.attributes._form', ['isEdit' => false])
                        <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.create') }}
                            {{ __('file.attribute') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAttributeModal" tabindex="-1" aria-labelledby="editAttributeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAttributeModalLabel">{{ __('file.title.edit_attribute') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="editAttributeForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="id" id="edit_id">
                        @include('backend.attributes._form', ['isEdit' => true])
                        <button type="submit" class="btn btn-primary mt-3">{{ __('file.button.update') }}
                            {{ __('file.attribute') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    <script>
        $(document).ready(function() {

            handleFormSubmit('#createAttributeForm', '#createAttributeModal', '#attribute-table', false,
                resetFormToDefault);
            handleFormSubmit('#editAttributeForm', '#editAttributeModal', '#attribute-table', true);

            // English comment: Auto-check/uncheck based on name
            $(document).on('input', 'input[name="name"]', function() {
                let attrName = $(this).val().toLowerCase().trim();
                let colorKeywords = ['color', 'colour', 'clr', 'clrs', 'রঙ', 'রং'];
                let colorSwitch = $('.is-color-check');

                if (attrName === "") {
                    colorSwitch.prop('checked', false).trigger('change');
                    return;
                }

                let isColorMatched = colorKeywords.some(keyword => attrName.includes(keyword));
                colorSwitch.prop('checked', isColorMatched).trigger('change');
            });

            // English comment: Toggle color column visibility
            $(document).on('change', '.is-color-check', function() {
                if ($(this).is(':checked')) {
                    $('.color-input-col').show();
                } else {
                    $('.color-input-col').hide();
                }
            });

            // English comment: Add new value in 2-column grid
            $(document).on('click', '.add-value-btn', function() {
                // এটি অটোমেটিক যে বাটনে ক্লিক করা হয়েছে তার কাছের র‍্যাপার খুঁজে নিবে
                let wrapper = $(this).closest('.modal-content').find('[id$="value_wrapper"]');
                let isColorChecked = $(this).closest('.modal-content').find('.is-color-check').is(
                    ':checked');
                let displayStyle = isColorChecked ? 'block' : 'none';

                let newRow = `
                    <div class="col-md-6 value-row animate__animated animate__fadeIn">
                        <div class="border p-2 rounded bg-white shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1">
                                    <input type="text" name="values[]" class="form-control form-control-sm" placeholder="Value" required>
                                    <input type="hidden" name="value_ids[]" class="value-id" value="">
                                </div>
                                <div class="color-input-col" style="display: ${displayStyle}; min-width: 150px;">
                                    <div class="input-group input-group-sm">
                                        <input type="color" class="form-control form-control-color p-0 color-picker" value="#34c38f" style="width: 35px; height: 31px;">
                                        <input type="text" name="color_codes[]" class="form-control hex-input" value="#34c38f" placeholder="#Hex" style="font-size: 11px;">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-link text-danger p-0 remove-value-btn">
                                    <i class="fa-solid fa-circle-xmark fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>`;

                wrapper.append(newRow);

                // প্রথম রো-এর ডিলিট বাটন যদি হাইড থাকে, তবে সেটি শো করা
                wrapper.find('.remove-value-btn').removeClass('d-none');
            });

            let rowToRemove = null;

            $(document).on('click', '.remove-value-btn', function() {
                const $row = $(this).closest('.value-row');
                const $wrapper = $row.parent();
                const valueId = $row.find('.value-id').val();

                // ১. কমপক্ষে একটি রো থাকতে হবে
                if ($wrapper.find('.value-row').length <= 1) {
                    alert("At least one attribute value is required.");
                    return;
                }

                // ২. আইডি থাকলে কাস্টম লজিক
                if (valueId) {
                    rowToRemove = $row;
                    const $modal = $('#deleteConfirmModal');

                    // কন্টেন্ট পরিবর্তন
                    $modal.find('.modal-title').text('Remove Attribute Value');
                    $modal.find('#deleteMessage').text(
                        'Are you sure? Removing this saved value might affect your existing product variations.'
                        );

                    // ৩. পুরাতন ডিলিট বাটন হাইড করা
                    $modal.find('#deleteConfirm').hide();

                    // ৪. নতুন "Remove" বাটন জাভাস্ক্রিপ্ট দিয়ে তৈরি করে অ্যাড করা (যদি আগে না থাকে)
                    if ($('#removeRowConfirm').length === 0) {
                        $modal.find('.modal-footer').append(
                            '<button type="button" class="btn btn-warning" id="removeRowConfirm">Remove</button>'
                        );
                    } else {
                        $('#removeRowConfirm').show();
                    }

                    $modal.modal('show');
                } else {
                    $row.remove();
                }
            });

            // ৫. নতুন তৈরি করা "Remove" বাটনের ক্লিক ইভেন্ট
            $(document).on('click', '#removeRowConfirm', function() {
                if (rowToRemove) {
                    rowToRemove.remove();
                    rowToRemove = null;
                    $('#deleteConfirmModal').modal('hide');
                }
            });

            // ৬. মডাল বন্ধ হলে সব আগের অবস্থায় রিসেট করা
            $('#deleteConfirmModal').on('hidden.bs.modal', function() {
                const $modal = $(this);
                // ডিফল্ট টেক্সট রিসেট
                $modal.find('.modal-title').text('Delete Confirm');
                $modal.find('#deleteMessage').text('Are you sure you want to delete this?');

                // বাটন রিসেট: ডিলিট শো করা এবং রিমুভ হাইড করা
                $modal.find('#deleteConfirm').show();
                $('#removeRowConfirm').hide();
            });

            // English comment: Color sync logic
            $(document).on('input', '.color-picker', function() {
                $(this).siblings('.hex-input').val($(this).val().toUpperCase());
            });

            $(document).on('input', '.hex-input', function() {
                let hexVal = $(this).val();
                if (/^#[0-9A-F]{6}$/i.test(hexVal)) {
                    $(this).siblings('.color-picker').val(hexVal);
                }
            });
        });

        function editAttribute(id) {
            const url = "{{ route('attributes.edit', ':id') }}".replace(':id', id);

            $.get(url, function(response) {
                const attribute = response.data;
                const $modal = $('#editAttributeModal');
                const editUrl = "{{ route('attributes.update', ':attribute') }}".replace(':attribute', id);
                const isColorChecked = !!attribute.is_color;

                $modal.find('form').attr('action', editUrl);
                $modal.find('#edit_id').val(attribute.id);
                $modal.find('#edit_name').val(attribute.name);
                $modal.find('#edit_is_active').val(attribute.is_active ? 1 : 0);
                $modal.find('#edit_description').val(attribute.description);

                // কালার সুইচ সিঙ্ক
                $modal.find('.is-color-check').prop('checked', isColorChecked).trigger('change');

                const wrapper = $modal.find('[id$="edit_value_wrapper"]');
                wrapper.find('.value-row').not(':first').remove();
                const firstRow = wrapper.find('.value-row:first');

                if (attribute.values && attribute.values.length > 0) {
                    attribute.values.forEach((valObj, index) => {
                        let currentRow;
                        if (index === 0) {
                            currentRow = firstRow;
                        } else {
                            currentRow = firstRow.clone();
                            wrapper.append(currentRow);
                        }

                        currentRow.find('.value-id').val(valObj.id || '');
                        currentRow.find('input[name="values[]"]').val(valObj.value);

                        // এডিট মোডে সব রো-তে ডিলিট বাটন শো করবে
                        currentRow.find('.remove-value-btn').removeClass('d-none');

                        if (isColorChecked) {
                            const color = valObj.color_code || '#34c38f';
                            currentRow.find('.color-picker').val(color);
                            currentRow.find('.hex-input').val(color.toUpperCase());
                        }
                    });
                }
                $modal.modal('show');
            });
        }
        /**
         * Resets the attribute form to its initial state
         * @param {string} formId - The ID of the form to reset
         */
        function resetFormToDefault() {
            const form = $('#createAttributeForm');

            form[0].reset();

            form.find('.is-color-check').prop('checked', false).trigger('change');


            const wrapper = form.find('[id$="value_wrapper"]');
            wrapper.find('.value-row').not(':first').remove();

            const firstRow = wrapper.find('.value-row:first');
            firstRow.find('input[type="text"]').val('');
            firstRow.find('.color-picker').val('#34c38f');
            firstRow.find('.hex-input').val('#34c38f');

            firstRow.find('.remove-value-btn').addClass('d-none');
        }
    </script>
@endpush
