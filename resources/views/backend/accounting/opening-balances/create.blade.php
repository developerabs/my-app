@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.opening_balance') }}
@endsection

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.opening_balance') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.opening_balancedesc') }}
        @endslot
    @endcomponent

    <form action="{{ route('opening-balances.store') }}" method="POST" id="opening-balance-form">
        @csrf
        <div class="card">
                        <div class="card-header">
                <div class="row align-items-end">
                    @include('backend.accounting.partials.journal-header', [
                        'dateLabel' => __('file.field.register_date'),
                        'dateName' => 'register_date',
                    ])

                    <!-- Remarks Field in Header -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('Remarks') }}</label>
                        <input type="text" name="remarks" class="form-control form-control-sm"
                            placeholder="{{ __('Optional remarks...') }}">
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    @include('backend.accounting.partials.journal-scripts')
@endpush