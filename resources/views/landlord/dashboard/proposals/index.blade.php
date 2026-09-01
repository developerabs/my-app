@extends('landlord.layouts.main')

@section('title', 'Proposal Management - SheraziPOS Landlord')

@push('css')
    @include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')

<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h4 class="mb-0">{{ __('file.title.proposal_management') }}</h4>
        <p class="mb-0 text-muted">{{ __('file.title.proposal_management_desc') }}</p>
    </div>
    <div>
        <a href="{{ route('landlord.proposals.create') }}" class="btn btn-primary"> <i class="fa-solid fa-plus me-1"></i>{{ __('file.button.create') }} {{ __('file.proposal') }}</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
    </div>
</div>

@endsection

@push('js')
    @include('landlord.layouts.partials._datatable_bottom')
<script>

    deleteProposal = (id) => {
        $('#deleteConfirmModal').modal('show');
        let deleteButton = $('#deleteConfirm');
        deleteButton.off('click').on('click', function() {
            let url = '{{ route("landlord.proposals.destroy", ["proposal" => ":id"]) }}';
                url = url.replace(':id', id);

            $.ajax({
                url: url,
                type: 'DELETE',
                beforeSend: function() {
                    deleteButton.prop('disabled', true).text("{{ __('file.button.deleting') }}...");
                },
              success: function(response) {
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('success', response.message || "{{ __('file.message.proposal_deleted_successfully') }}");
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#proposals-table').DataTable().ajax.reload();
                    },
                 error: function() {
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('error', "{{ __('file.message.something_went_wrong') }}");
                    }
            });
        });
    };
</script>
@endpush
