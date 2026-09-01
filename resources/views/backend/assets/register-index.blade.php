@extends('backend.layouts.main')

@section('title', __('file.title.asset_register_list') ?? 'Asset Register List')

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.asset_register_list') ?? 'Asset Register List' }}
        @endslot
        @slot('subtitle')
            {{ __('View and manage registered assets & purchase records.') }}
        @endslot
        @slot('button')
            <a href="{{ route('assets.register.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.asset_register') }}
            </a>
        @endslot
    @endcomponent

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
    <!-- View Asset Register Details Modal -->
    <div class="modal fade" id="viewAssetRegisterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>Asset Register Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="assetRegisterDetailsBody">
                    <div class="text-center py-5" id="assetRegisterDetailsLoader">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer no-print">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print me-1"></i> Print / PDF</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('backend.layouts.partials._datatable_bottom')

    <script>
        function viewAssetRegister(id) {
            const modal = new bootstrap.Modal(document.getElementById('viewAssetRegisterModal'));
            const body = $('#assetRegisterDetailsBody');
            const loader = $('#assetRegisterDetailsLoader');

            modal.show();
            body.html(loader.html());

            let url = "{{ route('assets.register.show', ':register') }}".replace(':register', id);
            
            $.get(url, function(response) {
                if (response.success) {
                    const r = response.data;
                    const items = r.items || [];
                    const branch = r.branch || {};
                    const currency = r.currency || {};

                    let itemsHtml = items.map((item, idx) => `
                        <tr>
                            <td class="ps-3">${idx + 1}</td>
                            <td class="fw-semibold text-dark">${item.asset ? item.asset.asset_name : 'Asset Item'}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatMoney(parseFloat(item.unit_cost).toFixed(2))}</td>
                            <td class="text-end pe-3 fw-bold">${formatMoney(parseFloat(item.total_cost).toFixed(2))}</td>
                        </tr>
                    `).join('');

                    let html = `
                        <div class="p-2" id="printable-asset-register">
                            <div class="row border-bottom pb-3 mb-3 align-items-center">
                                <div class="col-md-6">
                                    <h4 class="fw-bold text-primary mb-1">${r.register_no}</h4>
                                    <span class="badge bg-primary-subtle text-primary border">${r.entry_type.toUpperCase()}</span>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="text-muted small">Date: <strong class="text-dark">${formatedDate(r.register_date)}</strong></div>
                                    <div class="text-muted small">Branch: <strong class="text-dark">${branch.name || 'Main Branch'}</strong></div>
                                    <div class="text-muted small">Created By: <strong class="text-dark">${r.creator ? r.creator.name : 'System'}</strong></div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-secondary small text-uppercase mb-2">Registered Asset Items</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3" width="5%">#</th>
                                            <th width="45%">Asset Name</th>
                                            <th class="text-center" width="15%">Qty</th>
                                            <th class="text-end" width="15%">Unit Cost</th>
                                            <th class="text-end pe-3" width="20%">Total Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsHtml}
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="4" class="text-end py-2">Grand Total:</td>
                                            <td class="text-end pe-3 py-2 text-primary fs-14">${formatMoney(parseFloat(r.total_cost).toFixed(2))}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            ${r.remarks ? `<div class="mb-2"><small class="fw-bold text-secondary">Remarks / Note:</small> <span class="text-dark small">${r.remarks}</span></div>` : ''}
                        </div>`;

                    body.html(html);
                } else {
                    body.html('<div class="alert alert-danger">Failed to load asset register details.</div>');
                }
            }).fail(function() {
                body.html('<div class="alert alert-danger">Something went wrong while fetching data.</div>');
            });
        }
    </script>
@endpush