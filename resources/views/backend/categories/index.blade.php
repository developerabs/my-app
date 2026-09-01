@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.category_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.category_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.category_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.category') }}
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
                                <select id="filter-status" data-dt-filter="category-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-auto" style="min-width: 200px;">
                                <select id="filter-type" data-dt-filter="category-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_types') }}</option>
                                    @foreach ($categoryTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-auto" style="min-width: 200px;">
                                <select id="filter-source" data-dt-filter="category-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_sources') }}</option>
                                    @foreach ($sources as $source)
                                        <option value="{{ $source }}">{{ $source }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- <div class="col-12 col-md-auto">
                                <div class="input-group input-group-sm">
                                    <input type="date" id="from-date" data-dt-filter="category-table" class="form-control shadow-none">
                                    <span class="input-group-text bg-light">to</span>
                                    <input type="date" id="to-date" data-dt-filter="category-table" class="form-control shadow-none">
                                </div>
                            </div> --}}

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('category-table')">
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
    <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCategoryModalLabel">{{ __('file.button.create') }}
                        {{ __('file.category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('categories.store') }}" method="POST" id="createCategoryForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.categories._form', [
                            'isEdit' => false,
                            'categoryTypes' => $categoryTypes,
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

    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editCategoryForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.categories._form', [
                            'isEdit' => true,
                            'categoryTypes' => $categoryTypes,
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
            handleFormSubmit('#createCategoryForm', '#createCategoryModal', '#category-table', false);
            handleFormSubmit('#editCategoryForm', '#editCategoryModal', '#category-table', true);

            $('#createCategoryModal .parent_category').select2({
                placeholder: "Select a category",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createCategoryModal')
            });

            $('#editCategoryModal .parent_category').select2({
                placeholder: "Select a category",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editCategoryModal')
            });

            // ২. ক্যাটাগরি টাইপ চেঞ্জ হলে প্যারেন্ট ক্যাটাগরি লোড করা
            $('.category-type').on('change', function() {
                let categoryTypeId = $(this).val();
                // এটি নিশ্চিত করে যে শুধু বর্তমান ওপেন থাকা মোডালের ড্রপডাউনটি আপডেট হবে
                let $modal = $(this).closest('.modal');
                let $parentCategory = $modal.find('.parent_category');

                if (categoryTypeId) {
                    let url = "{{ route('categories.getCategoriesByType', ':id') }}".replace(':id',
                        categoryTypeId);
                    $.get(url, function(response) {
                        $parentCategory.empty();
                        $parentCategory.append(
                            '<option value="">{{ __('file.option.none_top_level') }}</option>');
                        $.each(response, function(index, category) {
                            $parentCategory.append('<option value="' + category.id + '">' +
                                category.name + '</option>');
                        });
                        $parentCategory.trigger('change');
                    });
                } else {
                    $parentCategory.empty().append(
                        '<option value="">{{ __('file.option.none_top_level') }}</option>').trigger(
                        'change');
                }
            });
        });

        function editCategory(id) {
            let url = "{{ route('categories.edit', ':category') }}".replace(':category', id);

            $.get(url, function(response) {
                // যেহেতু কন্ট্রোলার থেকে return response()->json(['category' => $category, 'image_url' => ...]) পাঠাচ্ছেন
                let category = response.category;
                let imageUrl = response.image_url;
                let thumbUrl = response.thumb_url;

                let $modal = $('#editCategoryModal');
                let $form = $('#editCategoryForm');
                let updateUrl = "{{ route('categories.update', ':category') }}".replace(':category', id);

                // ফর্ম ভ্যালু সেট করা
                $form.attr('action', updateUrl);
                $form.find('input[name="name"]').val(category.name);
                $form.find('select[name="category_type_id"]').val(category.category_type_id);
                $form.find('input[name="sort_order"]').val(category.sort_order);
                $form.find('select[name="is_active"]').val(category.is_active ? 1 : 0);
                $form.find('textarea[name="description"]').val(category.description);
                $form.find('input[name="meta_title"]').val(category.meta_title);
                $form.find('input[name="meta_description"]').val(category.meta_description);
                $form.find('input[name="is_featured"]').prop('checked', category.is_featured == 1);

                // ইমেজ প্রিভিউ (HasFiles Trait থেকে আসা URL ব্যবহার করছি)
                if (category.image) {
                    $('#edit_image_preview').attr('src', imageUrl);
                } else {
                    $('#edit_image_preview').attr('src', "{{ url('images/preview_image.png') }}");
                }

                // এডিট মোডালের টাইপ অনুযায়ী প্যারেন্ট ক্যাটাগরিগুলো লোড করা
                if (category.category_type_id) {
                    let catUrl = "{{ route('categories.getCategoriesByType', ':id') }}".replace(':id', category
                        .category_type_id);

                    $.get(catUrl, function(categories) {
                        let $parentSelect = $modal.find('.parent_category');
                        $parentSelect.empty().append(
                            '<option value="">{{ __('file.option.none_top_level') }}</option>');

                        $.each(categories, function(index, cat) {
                            // নিজে নিজের প্যারেন্ট হতে পারবে না (UUID comparison)
                            if (cat.id !== category.id) {
                                let selected = (cat.id == category.parent_id) ? 'selected' : '';
                                $parentSelect.append('<option value="' + cat.id + '" ' + selected +
                                    '>' + cat.name + '</option>');
                            }
                        });
                        $parentSelect.trigger('change');
                    });
                }

                $modal.modal('show');
            });
        }
    </script>
@endpush
