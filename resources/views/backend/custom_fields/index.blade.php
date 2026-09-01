@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.custom_field_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
    <style>
        .border-dashed {
            border-style: dashed !important;
            border-width: 2px !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .form-label {
            font-size: 0.875rem;
            margin-bottom: 0.3rem;
            color: #4a5568;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.custom_field_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.custom_field_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomFieldModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.custom_field') }}
            </a>
        @endslot
    @endcomponent

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
    <div class="modal fade" id="createCustomFieldModal" tabindex="-1" aria-labelledby="createCustomFieldModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCustomFieldModalLabel">{{ __('file.button.create') }}
                        {{ __('file.custom_field') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('custom-fields.store') }}" method="POST" id="createCustomFieldForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.custom_fields._form', [
                            'isEdit' => false,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCustomFieldModal" tabindex="-1" aria-labelledby="editCustomFieldModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomFieldModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.custom_field') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editCustomFieldForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.custom_fields._form', [
                            'isEdit' => true,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('file.button.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    <script>
        $(document).ready(function() {
            handleFormSubmit('#createCustomFieldForm', '#createCustomFieldModal', '#custom-field-table', false,
                resetCustomFieldFormToDefault);
            handleFormSubmit('#editCustomFieldForm', '#editCustomFieldModal', '#custom-field-table', true);
        });

        function editCustomField(id) {
            let url = "{{ route('custom-fields.edit', ':custom_field') }}".replace(':custom_field', id);
            let updateUrl = "{{ route('custom-fields.update', ':custom_field') }}".replace(':custom_field', id);

            $.get(url, function(response) {
                if (response.success) {
                    let field = response.data;
                    let form = $('#editCustomFieldForm'); // এডিট ফর্মের আইডি

                    form.attr('action', updateUrl);

                    // ইনপুট ফিল্ডগুলো সেট করা
                    form.find('select[name="model_type"]').val(field.model_type).trigger('change');
                    form.find('select[name="type"]').val(field.type).trigger('change');
                    form.find('input[name="label"]').val(field.label);
                    form.find('input[name="placeholder"]').val(field.placeholder);
                    form.find('input[name="default_value"]').val(field.default_value);
                    form.find('input[name="order"]').val(field.order);

                    /* English Comment: 
                       Finding the options_container relative to the current form 
                       to avoid conflict between Create and Edit modals.
                    */
                    let optionsContainer = form.find('#options_container');

                    if (['select', 'radio', 'checkbox'].includes(field.type)) {
                        optionsContainer.removeClass('d-none');
                        form.find('textarea[name="options"]').val(field.options_string);
                    } else {
                        optionsContainer.addClass('d-none');
                        form.find('textarea[name="options"]').val('');
                    }

                    // সুইচগুলো সেট করা (এখানে আইডি ব্যবহারের বদলে নেম ব্যবহার করা সেফ)
                    form.find('input[name="is_required"]').prop('checked', field.is_required == 1);
                    form.find('input[name="show_in_list"]').prop('checked', field.show_in_list == 1);
                    form.find('input[name="is_active"]').prop('checked', field.is_active == 1);

                    $('#editCustomFieldModal').modal('show');
                }
            });
        }

        document.getElementById('field_type').addEventListener('change', function() {
            const optionsContainer = document.getElementById('options_container');
            const typesWithOptions = ['select', 'radio', 'checkbox'];

            if (typesWithOptions.includes(this.value)) {
                optionsContainer.classList.remove('d-none');
            } else {
                optionsContainer.classList.add('d-none');
            }
        });

        function resetCustomFieldFormToDefault() {
            let form = $('#createCustomFieldForm');

            form[0].reset();

            if ($.fn.select2) {
                form.find('.select2').val(null).trigger('change');
            }

            $('#options_container').addClass('d-none');

            form.find('input[type="checkbox"]').prop('checked', false);
            form.find('#is_active').prop('checked', true);

            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('');
        }
    </script>
@endpush
