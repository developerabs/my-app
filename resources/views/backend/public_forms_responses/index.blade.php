@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.public_forms_submissions') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title') {{ __('file.public_forms_submissions') }} @endslot
        @slot('subtitle') {{ __('file.manage_public_forms_submissions') }} @endslot
        @slot('button')
            <a href="{{ route('public-forms.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.form_list') }}
            </a>
        @endslot
    @endcomponent

    @if($filterableFields->isNotEmpty())
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card border-0 mb-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto d-none d-md-flex align-items-center gap-2">
                                <i class="fa-solid fa-filter text-primary"></i>
                                <span class="fw-bold text-secondary">{{ __('file.field.filters') }}:</span>
                            </div>

                            @foreach($filterableFields as $filterField)
                                <div class="col-12 col-md-auto" style="min-width: 220px;">
                                    @if($filterField['type'] === 'select' && !empty($filterField['options']))
                                        <select
                                            id="{{ $filterField['elementId'] }}"
                                            data-dt-filter="public-form-response-table"
                                            class="form-select form-select-sm shadow-none selectnew2"
                                        >
                                            <option value="">-- {{ $filterField['label'] }} --</option>
                                            @foreach($filterField['options'] as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($filterField['type'] === 'date')
                                        <input
                                            type="text"
                                            id="{{ $filterField['elementId'] }}"
                                            data-dt-filter="public-form-response-table"
                                            class="form-control form-control-sm shadow-none dt-date-filter"
                                            placeholder="{{ $filterField['label'] }}"
                                            autocomplete="off"
                                        >
                                    @else
                                        <input
                                            type="text"
                                            id="{{ $filterField['elementId'] }}"
                                            data-dt-filter="public-form-response-table"
                                            class="form-control form-control-sm shadow-none"
                                            placeholder="{{ $filterField['label'] }}"
                                        >
                                    @endif
                                </div>
                            @endforeach

                            <div class="col-12 col-md-auto ms-md-auto">
                                <button
                                    type="button"
                                    class="btn btn-light btn-sm border"
                                    onclick="resetFilters('public-form-response-table')"
                                >
                                    <i class="fa-solid fa-rotate-left me-1"></i>
                                    {{ __('file.button.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
        </div>
    </div>
@endsection
@section('modals')
<!-- Response Details Modal -->
<div class="modal fade" id="responseDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('file.submission_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="response-details-content" class="table-responsive">
                    <!-- ডাইনামিক ফিল্ডের সব ডেটা এখানে লিস্ট আকারে দেখাবে -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
@include('backend.layouts.partials._datatable_bottom')
<script>
function showResponseDetails(id) {
    var url = "{{ route('public-forms-responses.show', ':id') }}".replace(':id', id);

    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            let content = '<div class="list-group list-group-flush">';

            if (response.fields && response.fields.length > 0) {
                response.fields.forEach(function (field) {
                    let formattedKey = (field.label || '').replace(/_/g, ' ').toUpperCase();
                    let displayVal;

                    if (field.type === 'file' && field.url) {
                        displayVal = '<a href="' + field.url + '" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip me-1"></i>{{ __('file.button.view') }}</a>';
                    } else {
                        displayVal = field.value || 'N/A';
                    }

                    content += `
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted fw-semibold" style="width: 35%;">${formattedKey}:</span>
                            <span class="text-dark fw-bold text-start" style="width: 65%;">${displayVal}</span>
                        </div>
                    `;
                });
            } else {
                content += `
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No additional data found for this submission.</p>
                    </div>
                `;
            }
            
            content += '</div>';
            
            document.getElementById('response-details-content').innerHTML = content;
            
            // মোডাল ওপেন করা (Bootstrap 5)
            var responseDetailsModal = new bootstrap.Modal(document.getElementById('responseDetailsModal'));
            responseDetailsModal.show();
        },
        error: function(xhr) {
            toastr.error('Something went wrong! Please try again.');
        }
    });
}

$(function () {
    $('.dt-date-filter').each(function () {
        flatpickr(this, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            onChange: function () {
                if (window.LaravelDataTables && window.LaravelDataTables['public-form-response-table']) {
                    window.LaravelDataTables['public-form-response-table'].draw();
                }
            }
        });
    });
});
</script>
@endpush