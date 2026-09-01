@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.status_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.status_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.status_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStatusModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.status') }}
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
                                <select id="filter-status" data-dt-filter="statuses-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('statuses-table')">
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
    <div class="modal fade" id="createStatusModal" tabindex="-1" aria-labelledby="createStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createStatusModalLabel">{{ __('file.button.create') }}
                        {{ __('file.status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('statuses.store') }}" method="POST" id="createStatusForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.crm.status._form', [
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

    <div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStatusModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editStatusForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.crm.status._form', [
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
            handleFormSubmit('#createStatusForm', '#createStatusModal', '#statuses-table', false);
            handleFormSubmit('#editStatusForm', '#editStatusModal', '#statuses-table', true);
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

        function editStatus(id) {
            let url = "{{ route('statuses.edit', ':status') }}".replace(':status', id);

            $.get(url, function(response) {
                console.log(response);
                let status = response.status;

                let $modal = $('#editStatusModal');
                let $form = $('#editStatusForm');
                let updateUrl = "{{ route('statuses.update', ':status') }}".replace(':status', id);

                let type = status.type;
                let categoryId = status.category_id;

                let $categorySelect = $form.find('select[name="category_id"]');
                $categorySelect.html('<option value="">-- {{ __('file.option.select') }}</option>'); // reset

                $form.attr('action', updateUrl);
                $form.find('input[name="name"]').val(status.name);
                $form.find('input[name="progress"]').val(status.progress);
                $form.find('input[name="color"]').val(status.color);
                $form.find('input[name="order"]').val(status.sort_order);
                $form.find('select[name="is_active"]').val(status.is_active ? 1 : 0);
                $form.find('select[name="type"]').val(status.type);

                $form.find('.color-picker').val(status.color);
                $form.find('.hex-input').val(status.color);

                if (type) {
                    let url = "{{ route('categories.getCategoriesByStatusType', ':id') }}".replace(':id', type);
                    $.get(url, function(response) {
                        $categorySelect.empty();
                        $categorySelect.append('<option value="">-- {{ __('file.option.select') }}</option>');
                        response.categories.forEach(function(category) {
                            $categorySelect.append('<option value="' + category.id + '">' + category.name +
                                '</option>');
                        });
                        $categorySelect.val(categoryId);
                    });
                } else {
                    $categorySelect.val(categoryId);
                }

                $modal.modal('show');
            });
        }

        // find the category select by the type select and populate it with the categories based on the selected type
        $(document).on('change', 'select[name="type"]', function() {
            let type = $(this).val();
            let $categorySelect = $(this).closest('form').find('select[name="category_id"]');

            if (type) {
                let url = "{{ route('categories.getCategoriesByStatusType', ':id') }}".replace(':id', type);
                $.get(url, function(response) {
                    $categorySelect.empty();
                    $categorySelect.append('<option value="">-- {{ __('file.option.select') }}</option>');
                    response.categories.forEach(function(category) {
                        $categorySelect.append('<option value="' + category.id + '">' + category.name +
                            '</option>');
                    });
                });
            } else {
                $categorySelect.empty();
                $categorySelect.append('<option value="">-- {{ __('file.option.select') }}</option>');
            }
        });
    </script>
@endpush
