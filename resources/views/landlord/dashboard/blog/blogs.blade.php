@extends('landlord.layouts.main')

@section('title'){{__('file.title.blog_management')}} - SheraziPOS Landlord @endsection

@push('css')
@include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{__('file.title.blog_management')}}</h4>
            <p class="mb-0 text-muted">{{__('file.title.blog_management_desc')}}</p>
        </div>
        <div>
            <a href="{{ route('landlord.blogs.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> {{__('file.button.create')}} {{__('file.blog')}}</a>
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
    @include('landlord.layouts.partials._datatable_bottom')
    <script>
        $(document).on('change', '.pageStatus', function() {
            let id = $(this).data('id');
            let status = $(this).val();
            let url = '{{ route('landlord.blogs.updateStatus', ':id') }}';
            url = url.replace(':id', id);

            $.ajax({
                url: url,
                type: 'PATCH',
                data: {
                    status: status,
                },
                success: function(response) {
                    showFloatingAlert('success', response.message);
                },
                error: function() {
                    showFloatingAlert('error', "{{ __('file.message.unable_to_update_page_status') }}");
                }
            });

        });

        deleteBlog = (id) => {
            $('#deleteConfirmModal').modal('show');
            let deleteButton = $('#deleteConfirm');

            deleteButton.off('click').on('click', function() {
                let url = '{{ route("landlord.blogs.destroy", ["blog" => ":id"]) }}';
                url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    beforeSend: function() {
                        deleteButton.prop('disabled', true).text("{{ __('file.button.deleting') }}...");
                    },
                    success: function(response) {
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('success', response.message || "{{ __('file.message.blog_deleted_successfully') }}");
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#blogs-table').DataTable().ajax.reload();
                    },
                    error: function() {
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('error', "{{ __('file.message.something_went_wrong') }}");
                    }
                });
            });
        }
    </script>
@endpush

