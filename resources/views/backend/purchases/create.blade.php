@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.purchase_management_create') ?? 'Create Purchase' }} -
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
            {{ __('file.title.purchase_management_create') ?? 'Create Purchase' }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.purchase_management_create_desc') ?? 'Record purchase invoices, stock receipts, and manage supplier balances.' }}
        @endslot
        @slot('button')
            <a href="{{ route('purchases.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> {{ __('file.purchase') }} {{ __('file.button.list') }}
            </a>
        @endslot
    @endcomponent

    <form action="{{ route('purchases.store') }}" method="POST" enctype="multipart/form-data" id="purchase-create-form">
        @csrf
        @include('backend.purchases._form', ['isEdit' => false])
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
    
    @include('backend.purchases._create_script')
@endpush