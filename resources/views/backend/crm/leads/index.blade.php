@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.lead_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.lead_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.lead_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importLeadModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.import') }}
            </a>
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLeadModal">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.lead') }}
            </a>
        @endslot
    @endcomponent

    {{-- Filter Section --}}
    <div class="row mb-3">
        <div class="col-md-12">

            {{-- Mobile Show Filters --}}
            <button
                class="btn btn-outline-primary d-md-none w-100 mb-2"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#leadFilterCollapse"
            >
                <i class="fa-solid fa-filter me-2"></i>
                {{ __('file.field.show_filters') }}
            </button>

            <div class="collapse d-md-block" id="leadFilterCollapse">

                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">

                        {{-- Quick Filters --}}
                        <div class="row g-2 align-items-center">

                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">
                                    {{ __('file.field.filters') }}:
                                </span>
                            </div>

                            
                            {{-- Follow Up Date Range --}}
                            <div class="col-12 col-md-auto" style="min-width: 250px;">

                                {{-- Single visible field --}}
                                <input
                                    type="text"
                                    id="filter-follow-up-date"
                                    data-dt-filter="lead-table"
                                    class="form-control form-select-sm shadow-none"
                                    placeholder="{{ __('file.field.follow_up_date') }} - Select date range"
                                >
                            </div>

                            {{-- Status --}}
                            <div class="col-12 col-md-auto" style="min-width: 250px;">
                                <select
                                    id="filter-status"
                                    data-dt-filter="lead-table"
                                    class="form-select form-select-sm shadow-none selectnew2"
                                >
                                    <option value="">
                                        -- {{ __('file.option.all_status') }} --
                                    </option>

                                    @foreach($statuses ?? [] as $status)
                                        <option value="{{ $status->id }}">
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Address --}}
                            <div class="col-12 col-md-auto" style="min-width: 250px;">

                                {{-- Single visible field --}}
                                <input
                                    type="text"
                                    id="filter-address"
                                    data-dt-filter="lead-table"
                                    class="form-control form-select-sm shadow-none"
                                    placeholder="{{ __('file.field.address') }}"
                                    style="min-height: 38px;"
                                >
                            </div>

                            {{-- All Priority --}}
                            <div class="col-12 col-md-auto" style="min-width: 250px;">
                                <select
                                    id="filter-priority"
                                    data-dt-filter="lead-table"
                                    class="form-select form-select-sm shadow-none selectnew2"
                                >
                                    <option value="">
                                        -- {{ __('file.field.all_priority') }} --
                                    </option>

                                    <option value="low">
                                        {{ __('file.label.low') }}
                                    </option>
                                    <option value="medium">
                                        {{ __('file.label.medium') }}
                                    </option>
                                    <option value="high">
                                        {{ __('file.label.high') }}
                                    </option>
                                </select>
                            </div>

                            {{-- Faileds --}}
                            <div class="col-12 col-md-auto" style="min-width: 250px;">
                                <select
                                    id="filter-failed"
                                    data-dt-filter="lead-table"
                                    class="form-select form-select-sm shadow-none selectnew2"
                                >
                                    <option value="">
                                        -- {{ __('file.field.show_failed') }} --
                                    </option>

                                    <option value="1">
                                        {{ __('file.label.failed') }}
                                    </option>
                                    <option value="0">
                                        {{ __('file.label.not_failed') }}
                                    </option>
                                </select>
                            </div>

                            {{-- Actions --}}
                            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">

                                {{-- More Filters --}}
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm flex-fill"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#advancedLeadFilters"
                                    aria-expanded="false"
                                >
                                    <i class="fa-solid fa-sliders me-1"></i>
                                    {{ __('file.button.more_filters') }}
                                </button>

                                {{-- Reset --}}
                                <button
                                    type="button"
                                    class="btn btn-light btn-sm border flex-fill"
                                    onclick="resetLeadFilters('lead-table')"
                                >
                                    <i class="fa-solid fa-rotate-left me-1"></i>
                                    {{ __('file.button.reset') }}
                                </button>

                            </div>

                        </div>


                        {{-- Advanced Filters --}}
                        <div class="collapse mt-3" id="advancedLeadFilters">

                            <div class="more-filters-container">

                                <div class="row g-2">
                            
                                    {{-- Manager --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <select
                                            id="filter-manager"
                                            data-dt-filter="lead-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">
                                                -- {{ __('file.field.manager') }} --
                                            </option>

                                            @foreach($users ?? [] as $user)
                                                <option value="{{ $user->id }}">
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Assigned To --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <select
                                            id="filter-assigned-to"
                                            data-dt-filter="lead-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">
                                                -- {{ __('file.field.assigned_to') }} --
                                            </option>

                                            @foreach($users ?? [] as $user)
                                                <option value="{{ $user->id }}">
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Category --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <select
                                            id="filter-category"
                                            data-dt-filter="lead-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">
                                                -- {{ __('file.field.category') }} --
                                            </option>

                                            @foreach($categories ?? [] as $category)
                                                <option value="{{ $category->id }}">
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Subject --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <select
                                            id="filter-subject"
                                            data-dt-filter="lead-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">
                                                -- {{ __('file.field.subject') }} --
                                            </option>

                                            @foreach($leadSubjects ?? [] as $subject)
                                                <option value="{{ $subject->id }}">
                                                    {{ $subject->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>


                                    {{-- Source --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <select
                                            id="filter-source"
                                            data-dt-filter="lead-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">
                                                -- {{ __('file.field.source') }} --
                                            </option>

                                            @foreach($leadSources ?? [] as $source)
                                                <option value="{{ $source->id }}">
                                                    {{ $source->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Created By --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <select
                                            id="filter-created-by"
                                            data-dt-filter="lead-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">
                                                -- {{ __('file.field.created_by') }} --
                                            </option>

                                            @foreach($users ?? [] as $user)
                                                <option value="{{ $user->id }}">
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>


                                    {{-- Updated By --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <select
                                            id="filter-updated-by"
                                            data-dt-filter="lead-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">
                                                -- {{ __('file.field.updated_by') }} --
                                            </option>

                                            @foreach($users ?? [] as $user)
                                                <option value="{{ $user->id }}">
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- created date --}}
                                    <div class="col-12 col-md-auto mb-2" style="min-width: 220px;">
                                        <input
                                            type="text"
                                            id="filter-created-at-date"
                                            data-dt-filter="lead-table"
                                            class="form-control shadow-none"
                                            placeholder="{{ __('file.field.created_at') }} - Select date range"
                                            style="min-height: 38px;"
                                        >
                                    </div>

                                </div>

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
                        {{ $dataTable->table(['class' => 'table nowrap w-100']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="createLeadModal" tabindex="-1" aria-labelledby="createLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createLeadModalLabel">{{ __('file.button.create') }}
                        {{ __('file.lead') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('leads.store') }}" method="POST" id="createLeadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.crm.leads._form', [
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

    <div class="modal fade" id="editLeadModal" tabindex="-1" aria-labelledby="editLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLeadModalLabel">{{ __('file.button.edit') }}
                        {{ __('file.lead') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="editLeadForm" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        @include('backend.crm.leads._form', [
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

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoteModalLabel">{{ __('file.button.add_note') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="addNoteForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category_id" value="">
                    <div class="modal-body">
                        @include('backend.crm.leads._add_note_form')
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

    <!-- View Lead Notes Modal -->
    <div class="modal fade" id="viewLeadNotesModal" tabindex="-1" aria-labelledby="viewLeadNotesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLeadNotesModalLabel">{{ __('file.notes') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="viewLeadNotesLoader" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="viewLeadNotesContent" class="d-none" style="height: 400px; overflow-y: auto;">
                        <div id="viewLeadNotesList" class="list-group"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.button.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Convert to Deal Modal -->
    <div class="modal fade" id="convertToDealModal" tabindex="-1" aria-labelledby="convertToDealModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="convertToDealModalLabel">{{ __('file.button.convert_to') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="convertToDealForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category_id" value="">
                    <div class="modal-body">
                        @include('backend.crm.leads._convert_to_deal_form')
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

    <!-- Mark as failed -->
    <div class="modal fade" id="markAsFailedModal" tabindex="-1" aria-labelledby="markAsFailedModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="markAsFailedModalLabel">{{ __('file.button.mark_as_failed') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="markAsFailedForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category_id" value="">
                    <div class="modal-body">
                        @include('backend.crm.leads._mark_as_failed_form')
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
    <!-- Remove from failed -->
    <div class="modal fade" id="removeFromFailedModal" tabindex="-1" aria-labelledby="removeFromFailedModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="removeFromFailedModalLabel">{{ __('file.button.remove_from_failed') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="removeFromFailedForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="category_id" value="">
                    <div class="modal-body">
                        @include('backend.crm.leads._mark_as_failed_form', ['isRemove' => true])
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
    <!-- Import Leads Modal -->
    <div class="modal fade" id="importLeadModal" tabindex="-1" aria-labelledby="importLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="importCsvForm" action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('file.button.import_leads') }} (CSV, XLSX, XLS)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="importErrors" class="alert alert-danger d-none"></div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('file.button.upload_file') }} <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".csv, .xlsx, .xls" required>
                        </div>
                        <!-- Download sample CSV -->
                        <div class="mt-4">
                            <a href="{{ asset('samples/leads_sample.csv') }}" class="btn btn-link">{{ __('file.button.download_sample_csv') }}</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('file.button.cancel') }}</button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">{{ __('file.button.import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')
    @php
        $leadCategoryTypeMap = \App\Models\CategoryType::whereIn('name', ['lead', 'deal'])
            ->pluck('id', 'name')
            ->toArray();
    @endphp
    <script>
        const leadCategoryTypeMap = @json($leadCategoryTypeMap ?? []);

        function resolveLeadType(type) {
            return (type === 'lead' || type === 'deal') ? type : 'lead';
        }

        function getLeadCategoryTypeId(type) {
            const resolvedType = resolveLeadType(type);
            return leadCategoryTypeMap[resolvedType] || leadCategoryTypeMap.lead || null;
        }

        function initLeadDynamicSelect(selector, route, payloadBuilder, dropdownParent) {
            const $select = $(selector);
            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            const select2Instance = $select.select2({
                placeholder: 'Select or type to add...',
                tags: true,
                allowClear: true,
                width: '100%',
                dropdownParent: dropdownParent || $('body'),
                createTag: function(params) {
                    const term = $.trim(params.term);
                    if (term === '') return null;
                    return {
                        id: term,
                        text: term,
                        isNew: true
                    };
                }
            });

            select2Instance.on('select2:select', function(e) {
                const data = e.params.data;
                if (!data || !data.isNew) return;

                const payload = payloadBuilder ? payloadBuilder(data) : { name: data.text, is_active: 1 };
                if (!payload || payload.__abort) {
                    $select.find('option[value="' + data.id + '"]').remove();
                    $select.trigger('change');
                    return;
                }

                $.ajax({
                    url: route,
                    method: 'POST',
                    data: payload,
                    success: function(response) {
                        $select.find('option[value="' + data.id + '"]').remove();
                        const newOption = new Option(data.text, response.id, true, true);
                        $select.append(newOption).trigger('change');
                        showFloatingAlert('success', response.message || 'Created successfully.');
                    },
                    error: function(xhr) {
                        $select.find('option[value="' + data.id + '"]').remove();
                        $select.trigger('change');
                        showFloatingAlert('error', 'Could not create entry.');
                        console.error(xhr.responseText || xhr.statusText || xhr);
                    }
                });
            });

            return select2Instance;
        }
        

        function bindLeadDynamicCreateFields($form) {
            const $typeSelect = $form.find('select[name="type"]');
            const $categorySelect = $form.find('select[name="category_id"]');
            const $subjectSelect = $form.find('select[name="lead_subject_id"]');
            const $sourceSelect = $form.find('select[name="lead_source_id"]');
            const $statusSelect = $form.find('select[name="status_id"]');
            const $modal = $form.closest('.modal');

            initLeadDynamicSelect($categorySelect, "{{ route('categories.store') }}", function(data) {
                const type = resolveLeadType($typeSelect.val() || 'lead');
                const categoryTypeId = getLeadCategoryTypeId(type);

                if (!categoryTypeId) {
                    return { __abort: true };
                }

                return {
                    name: data.text,
                    category_type_id: categoryTypeId,
                    is_active: 1,
                };
            }, $modal);

            initLeadDynamicSelect($subjectSelect, "{{ route('lead-subjects.store') }}", function(data) {
                return { name: data.text, is_active: 1 };
            }, $modal);

            initLeadDynamicSelect($sourceSelect, "{{ route('lead-sources.store') }}", function(data) {
                return { name: data.text, is_active: 1 };
            }, $modal);

            initLeadDynamicSelect($statusSelect, "{{ route('statuses.store') }}", function(data) {
                const type = resolveLeadType($typeSelect.val() || 'lead');
                const categoryId = $categorySelect.val();

                if (!categoryId) {
                    showFloatingAlert('error', 'Please select a category first.');
                    return { __abort: true };
                }

                return {
                    name: data.text,
                    type: type,
                    category_id: categoryId,
                    progress: 0,
                    color: '#000000',
                    is_active: 1,
                };
            }, $modal);
        }

        $(document).ready(function() {
            bindLeadDynamicCreateFields($('#createLeadForm'));
            bindLeadDynamicCreateFields($('#editLeadForm'));
            handleFormSubmit('#createLeadForm', '#createLeadModal', '#lead-table', false);
            handleFormSubmit('#editLeadForm', '#editLeadModal', '#lead-table', true);
            handleFormSubmit('#addNoteForm', '#addNoteModal', '#lead-table', false);
            handleFormSubmit('#convertToDealForm', '#convertToDealModal', '#lead-table', false);

            $('#importCsvForm').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $submitBtn = $('#submitBtn');
                const $errorBox = $('#importErrors');
                const formData = new FormData(this);

                $submitBtn.prop('disabled', true).text('Importing...');
                $errorBox.addClass('d-none').empty();

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Import response:', response);
                        showFloatingAlert('success', response.message + (response.data && response.data.imported ? ' Imported: ' + response.data.imported : '') + (response.data && response.data.failed ? ' Failed: ' + response.data.failed : '') || 'File imported successfully.');
                        $('#importLeadModal').modal('hide');
                        $form[0].reset();
                        if (window.LaravelDataTables && window.LaravelDataTables['lead-table']) {
                            window.LaravelDataTables['lead-table'].draw();
                        }
                    },
                    error: function(xhr) {
                        let message = 'Import failed. Please check the CSV format and try again.';

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }

                            if (xhr.responseJSON.errors) {
                                const validationMessages = Object.values(xhr.responseJSON.errors).flat();
                                message = validationMessages.join('<br>');
                            }
                        }

                        $errorBox.removeClass('d-none').html(message);
                        showFloatingAlert('error', 'Lead import failed. Check error details in the modal.');
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text('Import CSV');
                    }
                });
            });

            // Initialize Flatpickr with altInput
            const flatpickrInstances = flatpickr(".date-picker", {
                enableTime: true,
                time_24hr: false,

                altInput: true,
                altFormat: (window.appSettings && window.appSettings.date_format)
                    ? window.appSettings.date_format + " H:i"
                    : "d-m-Y H:i",

                dateFormat: "Y-m-d H:i:S",

                minDate: getMinDateSafe(),
                static: true,
                allowInput: true,
                onClose: function(selectedDates, dateStr, instance) {
                    // Ensure the input value is properly set
                    if (instance.input) {
                        instance.input.value = dateStr;
                    }
                },
                onChange: function(selectedDates, dateStr, instance) {
                    // Enable submit button when date changes
                    if (instance.input && instance.input.form) {
                        const submitBtn = $(instance.input.form).find('button[type="submit"]');
                        if (submitBtn.length) {
                            submitBtn.prop('disabled', false);
                        }
                    }
                }
            });
            flatpickr("#filter-follow-up-date", {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d M Y",
                onChange: function(selectedDates) {
                    $('#lead-table').DataTable().ajax.reload();
                }
            });

            flatpickr("#filter-created-at-date", {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d M Y",
                onChange: function () {
                    $('#lead-table').DataTable().ajax.reload();
                }
            });

            window.resetLeadFilters = function(tableId) {
                $('[data-dt-filter="' + tableId + '"]').each(function() {
                    const $field = $(this);
                    if ($field.hasClass('selectnew2') && $field.data('select2')) {
                        $field.val('').trigger('change');
                    } else if ($field[0] && $field[0]._flatpickr) {
                        $field[0]._flatpickr.clear();
                    } else {
                        $field.val('');
                    }
                });

                if (window.LaravelDataTables && window.LaravelDataTables[tableId]) {
                    window.LaravelDataTables[tableId].draw();
                }
            };

            // ensure create modal starts with extra details collapsed and no attachment preview
            $('#createLeadModal').on('show.bs.modal', function() {
                $(this).find('.current-attachment').html('');
                $(this).find('.selectnew2').select2({
                    width: '100%',
                    dropdownParent: $('#createLeadModal')
                });
            });
            // ensure create modal starts with extra details collapsed and no attachment preview
            $('#editLeadModal').on('show.bs.modal', function() {
                $(this).find('.selectnew2').select2({
                    width: '100%',
                    dropdownParent: $('#editLeadModal')
                });
            });
            // add note modal 
            $('#addNoteModal').on('show.bs.modal', function() {
                const $modal = $(this);
                $modal.find('textarea[name="note"]').val('');
                $modal.find('select[name="status_id"]').val('').trigger('change');
                const $dateInput = $modal.find('input[name="follow_up_date"]');
                if ($dateInput.length && $dateInput[0]._flatpickr) {
                    $dateInput[0]._flatpickr.clear();
                }
                $modal.find('input[name="attachment"]').val('');
                $modal.find('.current-attachment').html('');
                $modal.find('input[name="schedule_meeting"]').prop('checked', false);
                $('#meetingFields').stop(true, true).hide();
                $modal.find('.meeting-field').val('').prop('required', false);
                const $meetingDate = $modal.find('.meeting-date-picker');
                if ($meetingDate.length && $meetingDate[0]._flatpickr) {
                    $meetingDate[0]._flatpickr.clear();
                }
                // Ensure phone input is ready
                const $phoneInput = $modal.find('input[name="phone"]');
                if ($phoneInput.length) {
                    if (!$phoneInput[0].iti) {
                        window.initPhoneInputs('.phone-input');
                    }
                    // Clear any error messages
                    $phoneInput.closest('div').find('.phone-error').text('');
                }
                $modal.find('.selectnew2').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                    $select.select2({
                        width: '100%',
                        dropdownParent: $modal
                    });
                });
            });
            $('#addNoteModal').on('change', 'input[name="schedule_meeting"]', function() {
                const enabled = $(this).is(':checked');
                const $fields = $('#meetingFields');
                $fields.stop(true, true)[enabled ? 'slideDown' : 'slideUp'](220);
                $fields.find('.meeting-field').prop('required', enabled);
                if (enabled) {
                    $.get("{{ route('meetings.statuses') }}", function(response) {
                        const $status = $('#addNoteForm select[name="meeting_status_id"]');
                        $status.empty().append('<option value="">-- {{ __('file.option.select') }} --</option>');
                        (response.statuses || []).forEach(function(status) {
                            $status.append('<option value="' + status.id + '">' + $('<div>').text(status.name).html() + '</option>');
                        });
                    });
                }
            });
            function syncMeetingStartDate(instance) {
                const $form = $(instance.input).closest('form');
                const selectedDate = (instance.selectedDates || [])[0];
                $form.find('input[name="meeting_start_at"]').val(
                    selectedDate ? instance.formatDate(selectedDate, 'Y-m-d H:i:S') : ''
                );
            }

            flatpickr('#addNoteForm .meeting-date-picker', {
                enableTime: true,
                time_24hr: false,
                dateFormat: 'Y-m-d H:i:S',
                altInput: true,
                altFormat: 'd M Y H:i',
                defaultDate: null,
                minDate: getMinDateSafe(),
                static: true,
                allowInput: false,
                onChange: function(selectedDates, dateStr, instance) {
                    syncMeetingStartDate(instance);
                }
            });
            document.getElementById('addNoteForm').addEventListener('submit', function() {
                const picker = this.querySelector('.meeting-date-picker')?._flatpickr;
                if (picker) {
                    syncMeetingStartDate(picker);
                }
            }, true);
            // convert to deal modal
            $('#convertToDealModal').on('show.bs.modal', function() {
                const $modal = $(this);
                $modal.find('textarea[name="note"]').val('');
                $modal.find('select[name="status_id"]').val('').trigger('change');
                const $dateInput = $modal.find('input[name="follow_up_date"]');
                if ($dateInput.length && $dateInput[0]._flatpickr) {
                    $dateInput[0]._flatpickr.clear();
                }
                $modal.find('input[name="attachment"]').val('');
                $modal.find('.current-attachment').html('');
                $modal.find('.selectnew2').select2({
                    width: '100%',
                    dropdownParent: $('#convertToDealModal')
                });
            });
            // mark as failed modal
            $('#markAsFailedModal').on('show.bs.modal', function() {
                const $modal = $(this);
                $modal.find('textarea[name="note"]').val('');
                $modal.find('.selectnew2').select2({
                    width: '100%',
                    dropdownParent: $('#markAsFailedModal')
                });
            });

            // remove from failed modal
            $('#removeFromFailedModal').on('show.bs.modal', function() {
                const $modal = $(this);
                $modal.find('textarea[name="note"]').val('');
                $modal.find('.selectnew2').select2({
                    width: '100%',
                    dropdownParent: $('#removeFromFailedModal')
                });
            });

        });

        // find the category select by the type select and populate it with the categories based on the selected type
        $(document).on('change', 'select[name="type"]', function() {
            let type = $(this).val() || 'lead';
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
                    if ($categorySelect.data('select2')) {
                        $categorySelect.trigger('change.select2');
                    }
                });
            } else {
                $categorySelect.empty();
                $categorySelect.append('<option value="">-- {{ __('file.option.select') }}</option>');
            }
            let $statusSelect = $(this).closest('form').find('select[name="status_id"]');
            $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>');
        });

        $('select[name="type"]').trigger('change');

        function loadLeadStatuses($form, categoryId, type, selectedStatusId = null) {
            let $statusSelect = $form.find('select[name="status_id"]');
            $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>');

            if (!categoryId || !type) {
                if (selectedStatusId) {
                    $statusSelect.val(selectedStatusId);
                    if ($statusSelect.data('select2')) {
                        $statusSelect.trigger('change.select2');
                    }
                }
                return;
            }

            $.get("{{ route('statuses.by-category-and-type') }}", {
                category_id: categoryId,
                type: type
            }, function(res) {
                if (res.status && res.statuses) {
                    res.statuses.forEach(function(st) {
                        $statusSelect.append('<option value="' + st.id + '">' + st.name + '</option>');
                    });
                }

                if (selectedStatusId) {
                    $statusSelect.val(selectedStatusId);
                }

                if ($statusSelect.data('select2')) {
                    $statusSelect.trigger('change.select2');
                }
            });
        }

        // get lead status by category and type 
        $(document).on('change', 'select[name="category_id"]', function() {
            let categoryId = $(this).val();
            let $form = $(this).closest('form');
            let $type = $form.find('select[name="type"]').val();
            loadLeadStatuses($form, categoryId, $type);
        });

        function editLead(id) {
            let url = "{{ route('leads.edit', ':lead') }}".replace(':lead', id);

            $.get(url, function(response) {
                let lead = response.lead;

                let $modal = $('#editLeadModal');
                let $form = $('#editLeadForm');
                let updateUrl = "{{ route('leads.update', ':lead') }}".replace(':lead', id);

                // Populate category select first, then populate status select based on the selected category
                let type = lead.type;
                let categoryId = lead.category_id;
                let selectedStatusId = lead.status_id;

                let $categorySelect = $form.find('select[name="category_id"]');
                $categorySelect.html('<option value="">-- {{ __('file.option.select') }}</option>'); // reset

                let $statusSelect = $form.find('select[name="status_id"]');
                $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>'); // reset

                $form.find('select[name="type"]').val(type);

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
                        if ($categorySelect.data('select2')) {
                            $categorySelect.trigger('change.select2');
                        }

                        loadLeadStatuses($form, categoryId, type, selectedStatusId);
                    });
                } else {
                    $categorySelect.val(categoryId);
                    if ($categorySelect.data('select2')) {
                        $categorySelect.trigger('change.select2');
                    }
                    loadLeadStatuses($form, categoryId, type, selectedStatusId);
                }

                $form.attr('action', updateUrl);
                $form.find('input[name="name"]').val(lead.name);
                $form.find('input[name="company_name"]').val(lead.company_name);
                $form.find('input[name="email"]').val(lead.email);
                $form.find('input[name="phone"]').val(lead.phone);
                $form.find('textarea[name="description"]').val(lead.description);
                $form.find('input[name="website"]').val(lead.website);
                $form.find('select[name="priority"]').val(lead.priority);
                $form.find('input[name="expected_value"]').val(lead.expected_value);
                $form.find('input[name="address"]').val(lead.address?.address ?? '');
                $form.find('input[name="username"]').val(lead.username);

                const $leadSubjectSelect = $form.find('select[name="lead_subject_id"]');
                const $leadSourceSelect = $form.find('select[name="lead_source_id"]');
                const $managerSelect = $form.find('select[name="manager_id"]');
                const $assignedToSelect = $form.find('select[name="assigned_to_id"]');

                $leadSubjectSelect.val(lead.lead_subject_id);
                $leadSourceSelect.val(lead.lead_source_id);
                $managerSelect.val(lead.manager_id);
                $assignedToSelect.val(lead.assigned_to_id);

                [$leadSubjectSelect, $leadSourceSelect, $managerSelect, $assignedToSelect].forEach(function($el) {
                    if ($el && $el.data('select2')) {
                        $el.trigger('change');
                    }
                });

                $form.find('select[name="priority"]').val(lead.priority);
                

                function setFollowUpDateValue($field, dtValue) {
                    if (!dtValue) {
                        if ($field[0] && $field[0]._flatpickr) {
                            $field[0]._flatpickr.clear();
                        } else {
                            $field.val('');
                        }
                        return;
                    }

                    const parsedValue = String(dtValue).trim();
                    const flatpickrInstance = $field[0] && $field[0]._flatpickr;

                    if (flatpickrInstance) {
                        const normalizedValue = parsedValue.replace(' ', 'T');
                        if (normalizedValue && !isNaN(new Date(normalizedValue).getTime())) {
                            flatpickrInstance.setDate(normalizedValue, true, 'Y-m-d H:i:S');
                            return;
                        }

                        flatpickrInstance.setDate(parsedValue, true, 'Y-m-d H:i:S');
                        return;
                    }

                    $field.val(parsedValue);
                }

                setFollowUpDateValue($form.find('input[name="follow_up_date"]'), lead.follow_up_date);

                // show current attachment link if present
                if (lead.attachment) {
                    let url = '{{ asset('storage') }}' + '/' + lead.attachment;
                    $form.find('.current-attachment').html('<a href="' + url + '" target="_blank">View attachment</a>');
                } else {
                    $form.find('.current-attachment').html('');
                }

                if (typeof window.initPhoneInputs === "function") {
                    window.initPhoneInputs();
                }

                $modal.modal('show');
            });
        }

        function addNote(id, categoryId, noteType) {
            let url = "{{ route('leads.add-note', ':lead') }}".replace(':lead', id);
            let $modal = $('#addNoteModal');
            let $form = $('#addNoteForm');

            let $statusSelect = $form.find('select[name="status_id"]');
            $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>'); // reset
            if (!noteType) return;
            $.get("{{ route('statuses.by-category-and-type') }}", {
                category_id: categoryId ? categoryId : null,
                type: noteType
            }, function(res) {
                    console.log(res);
                if (res.status && res.statuses) {
                    res.statuses.forEach(function(st) {
                        $statusSelect.append('<option value="' + st.id + '">' + st.name + '</option>');
                    });
                }
            });
            
            $form.attr('action', url);
            $form.find('input[name="meeting_category_id"]').val(categoryId || '');
            $modal.modal('show');
        }

        function showLeadNotes(id) {
            let url = "{{ route('leads.history', ':lead') }}".replace(':lead', id);
            let $modal = $('#viewLeadNotesModal');
            let $loader = $('#viewLeadNotesLoader');
            let $content = $('#viewLeadNotesContent');
            let $list = $('#viewLeadNotesList');

            $loader.removeClass('d-none');
            $content.addClass('d-none');
            $list.html('');

            $.get(url, function(response) {
                console.log(response);
                $loader.addClass('d-none');
                $content.removeClass('d-none');

                if (!response.history || response.history.length === 0) {
                    $list.html('<div class="alert alert-info mb-0">No notes found for this lead.</div>');
                    return;
                }

                response.history.forEach(function(note) {
                    let noteText = $('<div>').text(note.note || '').html();
                    let attachmentHtml = '';
                    if (note.attachment_url) {
                        let attachmentUrl = note.attachment_url;
                        attachmentHtml = `
                            <div class="mt-3">
                                <a href="${attachmentUrl}" target="_blank" class="small text-decoration-none">
                                    <i class="fa-solid fa-paperclip me-1"></i>
                                    {{ __('file.button.view_attachment') ?? 'View attachment' }}
                                </a>
                            </div>
                        `;
                    }

                    let creator = note.created_by;
                    let creatorName = creator || '{{ __('file.system') }}';
                    let creatorInitial = $('<div>').text(creatorName.trim().charAt(0).toUpperCase()).html();
                    let creatorHtml = `<div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 13px;">${creatorInitial}</span>
                        <span>${$('<div>').text(creatorName).html()}</span>
                    </div>`;
                    let createdAt = note.created_at ? note.created_at : '';
                    let followUpDate = note.next_follow_up_at ? note.next_follow_up_at : '{{ __('file.not_set') }}';
                    let noteType = note.note_type;
                    let noteStatus = note.status;
                    let effectivePhone = note.effective_phone ?? 'N/A';
                    let noteStatusColor = note.color || '#6c757d'; // default to gray if no color
                    let meetingHtml = '';
                    if (note.meeting) {
                        let meetingType = $('<div>').text(note.meeting.type || '-').html();
                        let meetingStatus = $('<div>').text(note.meeting.status || '-').html();
                        let meetingLocation = $('<div>').text(note.meeting.location || '-').html();
                        let meetingAssignee = $('<div>').text(note.meeting.assigned_to || '-').html();
                        meetingHtml = `<div class="mt-3 p-2 border-start border-primary bg-light">
                            <strong><i class="fa-solid fa-calendar-days me-1"></i> Meeting Scheduled</strong>
                            <div class="small mt-1">${$('<div>').text(note.meeting.title || '').html()}</div>
                            <div class="small text-muted">${note.meeting.start_at || ''}${note.meeting.end_at ? ' - ' + note.meeting.end_at : ''}</div>
                            <div class="row small mt-2">
                                <div class="col-md-3"><strong>{{ __('file.field.type') }}:</strong> ${meetingType}</div>
                                <div class="col-md-3"><strong>{{ __('file.field.status') }}:</strong> ${meetingStatus}</div>
                                <div class="col-md-3"><strong>{{ __('file.field.location') }}:</strong> ${meetingLocation}</div>
                                <div class="col-md-3"><strong>{{ __('file.field.assigned_to') }}:</strong> ${meetingAssignee}</div>
                            </div>
                        </div>`;
                    }

                    $list.append(`
                        <div class="list-group-item mb-3 shadow-sm rounded border">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1">${creatorHtml}</h6>
                                    <small class="text-muted">${createdAt}</small>
                                </div>
                                <span class="badge text-uppercase small" style="background-color: ${noteStatusColor};">${noteStatus}</span>
                            </div>
                            <div class="row text-muted mb-3 small">
                                <div class="col-sm-3 mb-2 mb-sm-0">
                                    <strong>{{ __('file.field.created_at') }}:</strong> ${createdAt}
                                </div>
                                <div class="col-sm-3 mb-2 mb-sm-0">
                                    <strong>{{ __('file.field.follow_up_date') }}:</strong> ${followUpDate}
                                </div>
                                <div class="col-sm-3">
                                    <strong>{{ __('file.field.type') }}:</strong> <span class="text-capitalize">${noteType}</span>
                                </div>
                                <div class="col-sm-3">
                                    <strong>{{ __('file.field.status') }}:</strong> ${noteStatus}
                                </div>
                                <div class="col-sm-3">
                                    <strong>{{ __('file.field.phone') }}:</strong> ${effectivePhone}
                                </div>
                            </div>
                            <div class="mb-2 text-break">${noteText}</div>
                            ${meetingHtml}
                            ${attachmentHtml}
                        </div>
                    `);
                });
            }).fail(function() {
                $loader.addClass('d-none');
                $content.removeClass('d-none');
                $list.html('<div class="alert alert-danger mb-0">Unable to load notes. Please try again.</div>');
            });

            $modal.modal('show');
        }

        function convertLead(id) {
            if (!confirm('Convert this lead to customer?')) return;
            let url = "{{ route('leads.convert', ':lead') }}".replace(':lead', id);
            $.post(url, {_token: '{{ csrf_token() }}'}, function(res){
                if(res.status) {
                    toastSuccess(res.message || 'Converted');
                    $('#lead-table').DataTable().ajax.reload();
                } else {
                    toastError(res.message || 'Failed');
                }
            }).fail(function(){ toastError('Request failed'); });
        }
        function convertToDeal(id) {
            let url = "{{ route('leads.convert-deal', ':lead') }}".replace(':lead', id);
            let $modal = $('#convertToDealModal');
            let $form = $('#convertToDealForm');

            $form.attr('action', url);
            $modal.modal('show');
        }

        // function failLead(id) {
        //     let reason = prompt('Reason for marking as failed (optional)');
        //     if (reason === null) return; // cancelled
        //     let url = "{{ route('leads.fail', ':lead') }}".replace(':lead', id);
        //     $.post(url, {_token: '{{ csrf_token() }}', reason: reason}, function(res){
        //         if(res.status) {
        //             toastSuccess(res.message || 'Marked failed');
        //             $('#lead-table').DataTable().ajax.reload();
        //         } else {
        //             toastError(res.message || 'Failed');
        //         }
        //     }).fail(function(){ toastError('Request failed'); });
        // }

        function markAsFailed(id, categoryId, noteType) {
            let url = "{{ route('leads.fail', ':lead') }}".replace(':lead', id);
            let $modal = $('#markAsFailedModal');
            let $form = $('#markAsFailedForm');

            let $statusSelect = $form.find('select[name="status_id"]');
            $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>'); // reset
            if (!noteType) return;
            $.get("{{ route('statuses.by-category-and-type') }}", {
                category_id: categoryId ? categoryId : null,
                type: noteType
            }, function(res) {
                    console.log(res);
                if (res.status && res.statuses) {
                    res.statuses.forEach(function(st) {
                        $statusSelect.append('<option value="' + st.id + '">' + st.name + '</option>');
                    });
                }
            });
            
            $form.attr('action', url);
            $modal.modal('show');
        }

        function removeFromFailed(id, categoryId, noteType) {
            let url = "{{ route('leads.unfail', ':lead') }}".replace(':lead', id);
            let $modal = $('#removeFromFailedModal');
            let $form = $('#removeFromFailedForm');

            let $statusSelect = $form.find('select[name="status_id"]');
            $statusSelect.html('<option value="">-- {{ __('file.option.select') }}</option>'); // reset
            if (!noteType) return;
            $.get("{{ route('statuses.by-category-and-type') }}", {
                category_id: categoryId ? categoryId : null,
                type: noteType
            }, function(res) {
                    console.log(res);
                if (res.status && res.statuses) {
                    res.statuses.forEach(function(st) {
                        $statusSelect.append('<option value="' + st.id + '">' + st.name + '</option>');
                    });
                }
            });
            
            $form.attr('action', url);
            $modal.modal('show');
        }
    </script>
@endpush
