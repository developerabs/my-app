@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.brand_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.brand_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.brand_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBrandModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.brand') }}
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
                                <select id="filter-status" data-dt-filter="brand-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_status') }}</option>
                                    <option value="1">{{ __('file.option.active') }}</option>
                                    <option value="0">{{ __('file.option.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto" style="min-width: 200px;">
                                <select id="filter-source" data-dt-filter="brand-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_sources') }}</option>
                                    @foreach ($sources as $source)
                                        <option value="{{ $source }}">{{ $source }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- <div class="col-12 col-md-auto">
                                <div class="input-group input-group-sm">
                                    <input type="date" id="from-date" data-dt-filter="brand-table" class="form-control shadow-none">
                                    <span class="input-group-text bg-light">to</span>
                                    <input type="date" id="to-date" data-dt-filter="brand-table" class="form-control shadow-none">
                                </div>
                            </div> --}}

                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border w-100 w-md-auto"
                                    onclick="resetFilters('brand-table')">
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
    <div class="modal fade" id="createBrandModal" tabindex="-1" aria-labelledby="createBrandModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createBrandModalLabel">{{ __('file.button.create') }}
                        {{ __('file.brand') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('brands.store') }}" method="POST" id="createBrandForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.brands._form', [
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

    <div class="modal fade" id="editBrandModal" tabindex="-1" aria-labelledby="editBrandModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBrandModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.brand') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editBrandForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.brands._form', [
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
            handleFormSubmit('#createBrandForm', '#createBrandModal', '#brand-table', false);
            handleFormSubmit('#editBrandForm', '#editBrandModal', '#brand-table', true);
        });

        function editBrand(id) {
            let url = "{{ route('brands.edit', ':brand') }}".replace(':brand', id);

            $.get(url, function(response) {
                //console.log(response);
                let brand = response.brand;
                let brandLogo = response.brand_logo;
                let coverImage = response.cover_image;

                let $modal = $('#editBrandModal');
                let $form = $('#editBrandForm');
                let updateUrl = "{{ route('brands.update', ':brand') }}".replace(':brand', id);

                $form.attr('action', updateUrl);
                $form.find('input[name="name"]').val(brand.name);
                $form.find('input[name="website_url"]').val(brand.website_url);
                $form.find('textarea[name="description"]').val(brand.description);
                $form.find('input[name="sort_order"]').val(brand.sort_order);
                $form.find('select[name="is_active"]').val(brand.is_active ? 1 : 0);
                $form.find('input[name="is_featured"]').prop('checked', brand.is_featured == 1);
                $form.find('input[name="meta_title"]').val(brand.meta_title);
                $form.find('input[name="meta_keywords"]').val(brand.meta_keywords);
                $form.find('textarea[name="meta_description"]').val(brand.meta_description);

               
                if (brand.brand_logo) {
                    $('#edit_logo_preview').attr('src', brandLogo);
                } else {
                    $('#edit_logo_preview').attr('src', "{{ url('images/preview_image.png') }}");
                }

                if (brand.cover_image) {
                    $('#edit_cover_preview').attr('src', coverImage);
                } else {
                    $('#edit_cover_preview').attr('src', "{{ url('images/preview_image.png') }}");
                }

                $modal.modal('show');
            });
        }
    </script>
@endpush
