@extends('backend.layouts.main')

@section('title')
    {{ \Illuminate\Support\Facades\Lang::has('file.title.purchase_management_edit') ? __('file.title.purchase_management_edit') : 'Edit Purchase' }}: {{ $purchase->purchase_no }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <link rel="stylesheet" href="{{ url('backend/assets/plugins/jquery-ui/jquery-ui.css') }}">
    <style>
        .purchase-form-container .select2-container--default .select2-selection--single {
            height: 31px;
            font-size: 13px;
        }

        #purchase-table thead th {
            font-size: 12px;
            text-transform: uppercase;
            color: #555;
        }

        .form-control-sm {
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .purchase-form-container {
                padding: 5px;
            }
            .row-number {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ \Illuminate\Support\Facades\Lang::has('file.title.purchase_management_edit') ? __('file.title.purchase_management_edit') : 'Edit Purchase Invoice' }}: {{ $purchase->purchase_no }}
        @endslot
        @slot('subtitle')
            {{ \Illuminate\Support\Facades\Lang::has('file.title.purchase_management_edit_desc') ? __('file.title.purchase_management_edit_desc') : 'Modify purchase lines, update batch information, and re-balance payments.' }}
        @endslot
        @slot('button')
            <div class="d-flex gap-2">
                <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-info text-white">
                    <i class="fa-solid fa-eye me-1"></i> {{ \Illuminate\Support\Facades\Lang::has('file.view') ? __('file.view') : 'View' }}
                </a>
                <a href="{{ route('purchases.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-list me-1"></i> {{ \Illuminate\Support\Facades\Lang::has('file.button.list') ? __('file.button.list') : 'Purchase List' }}
                </a>
            </div>
        @endslot
    @endcomponent

    <form action="{{ route('purchases.update', $purchase->id) }}" method="POST" enctype="multipart/form-data" id="purchase-create-form">
        @csrf
        @method('PUT')
        @include('backend.purchases._edit_form', ['isEdit' => true])
    </form>
@endsection

@section('modals')
    @include('backend.layouts.partials.quick_supplier')
    
    {{-- IMEI & Barcode Modal --}}
    <div class="modal fade" id="imeiBarcodeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalTitle">IMEI & Barcode Management</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    <input type="hidden" id="product_row_id" name="product_row_id">
                </div>
                <div class="modal-body p-4">
                    <div class="imei-container mb-3"></div>
                    <div class="barcode-container"></div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm px-3 save-btn" onclick="ImeiBarcodeManager.saveImeisToProductRow()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ url('backend') }}/js/quick-product-script.js"></script>
    <script src="{{ url('backend') }}/js/imei-barcode-manage.js"></script>
    <script src="{{ url('backend') }}/js/product-manager.js"></script>
    <script src="{{ url('backend') }}/js/CartManager.js"></script>
    
    @include('backend.purchases._edit_script')
@endpush