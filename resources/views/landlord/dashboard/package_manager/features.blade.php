@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.feature_management') }} - SheraziPOS Landlord
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('backend/assets/plugins/DataTables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/plugins/DataTables/responsive.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/css/custome-datatable.css') }}">
    <style>
        .icon-btn {
            width: 70px;
            height: 70px;
            transition: 0.2s;
        }

        .icon-btn:hover {
            background-color: #f0f0f0;
            transform: scale(1.1);
        }
    </style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.feature_management') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.feature_management_desc') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('backend/assets/plugins/DataTables/datatables.min.js') }}"></script>
    <script src="{{ asset('backend/assets/plugins/DataTables/dataTables.responsive.min.js') }}"></script>
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@endpush
