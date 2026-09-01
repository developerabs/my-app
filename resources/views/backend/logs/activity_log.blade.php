@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.activity_log') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('backend.layouts.partials._datatable_top')
    <style>
        table.dataTable.nowrap th[title="Action"] {
            text-align: start !important;
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.activity_log') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.activity_log_desc') }}
        @endslot
        @slot('button')
            @if (Auth::user()->hasRole('Super Admin'))
                <button class="btn btn-primary" onclick="clearAll();"><i class="fa-solid fa-broom me-1"></i>
                    {{ __('file.button.clear') }}</button>
            @endif
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

                            <div class="col-12 col-md-auto" style="min-width: 200px;">
                                <select id="filter-user" data-dt-filter="activity-log-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_causers') }}</option>
                                    @foreach ($users as $name => $id)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-auto" style="min-width: 200px;">
                                <select id="filter-event" data-dt-filter="activity-log-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_actions') }}</option>
                                    @foreach ($events as $event)
                                        <option value="{{ $event }}">{{ ucfirst($event) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-auto" style="min-width: 200px;">
                                <select id="filter-model" data-dt-filter="activity-log-table"
                                    class="form-select form-select-sm shadow-none">
                                    <option value="">-- {{ __('file.option.all_models') }}</option>
                                    @foreach ($targetModels as $class => $name)
                                        <option value="{{ $class }}">{{ ucfirst($name) }}</option>
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
                                    onclick="resetFilters('activity-log-table')">
                                    <i class="fa-solid fa-rotate-left me-1"></i> {{ __('file.button.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> {{ __('file.title.activity_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="activity-modal-body">
                    <p class="text-center text-muted">Loading...</p>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        function showDetails(id) {
            let url = "{{ route('activity-log.details', ':id') }}";
            url = url.replace(':id', id);

            $('#activity-modal-body').html(
                '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p>Loading...</p></div>');
            $('#activityModal').modal('show');

            $.ajax({
                url: url,
                type: "GET",
                success: function(res) {
                    let html = `
                <div class="row g-2 mb-3 bg-light p-2 rounded border mx-0">
                    <div class="col-6"><small class="text-muted d-block">User</small><strong>${res.user}</strong></div>
                    <div class="col-6"><small class="text-muted d-block">Action</small><span class="badge bg-primary">${res.action}</span></div>
                    <div class="col-12"><small class="text-muted d-block">Time</small><strong>${res.date}</strong></div>
                </div>
            `;

                    // Helper function to render a single list of data (For Created/Deleted/Restored)
                    const renderSingleList = (data, title, colorClass) => {
                        let section =
                            `<h6 class="fw-bold mt-3 border-bottom pb-1">${title}</h6><div class="list-group">`;
                        if (typeof data !== 'object' || Object.keys(data).length === 0) {
                            return section +
                                `<p class="text-muted p-2">No specific data recorded.</p></div>`;
                        }
                        $.each(data, function(key, val) {
                            section += `
                        <div class="list-group-item px-2 py-1 border-0 border-bottom">
                            <div class="row">
                                <div class="col-5 text-muted small text-capitalize">${key.replace(/_/g, ' ')}</div>
                                <div class="col-7 text-break fw-semibold ${colorClass}" style="font-size: 13px;">
                                    ${(val === null) ? '-' : (typeof val === 'object' ? JSON.stringify(val) : val)}
                                </div>
                            </div>
                        </div>`;
                        });
                        return section + `</div>`;
                    };

                    // Main Logic
                    if (res.type === 'updated') {
                        html += `<h6 class="fw-bold mt-3 border-bottom pb-1">Comparison</h6>`;
                        $.each(res.attributes, function(key, newVal) {
                            let oldVal = res.old[key] ?? '-';
                            html += `
                        <div class="border rounded p-2 mb-2 shadow-sm bg-white">
                            <div class="fw-bold text-capitalize text-primary small mb-1 border-bottom d-flex justify-content-between">
                                <span>${key.replace(/_/g, ' ')}</span>
                                <span class="text-muted fw-normal" style="font-size: 10px;">Changed</span>
                            </div>
                            <div class="row g-1">
                                <div class="col-6 border-end pe-2">
                                    <small class="text-muted d-block" style="font-size: 10px;">OLD</small>
                                    <div class="text-danger text-break" style="font-size: 12px;">${(typeof oldVal === 'object') ? JSON.stringify(oldVal) : oldVal}</div>
                                </div>
                                <div class="col-6 ps-2">
                                    <small class="text-muted d-block" style="font-size: 10px;">NEW</small>
                                    <div class="text-success text-break fw-bold" style="font-size: 12px;">${(typeof newVal === 'object') ? JSON.stringify(newVal) : newVal}</div>
                                </div>
                            </div>
                        </div>`;
                        });
                    } else if (res.type === 'created' || res.type === 'restored') {
                        // restored এখন এখানে আসবে এবং সুন্দর লিস্ট দেখাবে
                        html += renderSingleList(res.attributes, res.type === 'created' ?
                            'Created Record Info' : 'Restored Record Data', 'text-success');
                    } else if (res.type === 'deleted') {
                        html += renderSingleList(res.old, 'Deleted Record Info', 'text-danger');
                    } else {
                        // If it's something else like 'custom' or 'system'
                        html += renderSingleList(res.attributes, 'Activity Details', 'text-dark');
                    }

                    $('#activity-modal-body').html(html);
                }
            });
        }
    </script>

    @if (Auth::user()->hasRole('Super Admin'))
        <script>
            function clearAll() {
                $.ajax({
                    url: "{{ route('activity-log.clear') }}",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        showFloatingAlert('success', res.message);
                        $('#activity-log-table').DataTable().ajax.reload();
                    },
                    error: function(res) {
                        showFloatingAlert('error', res.responseJSON.message);
                    }
                });
            }
        </script>
    @endif
@endpush
