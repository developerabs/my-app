@extends('backend.layouts.main')

@section('title', __('file.public_forms'))

@push('css')
    @include('backend.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title') {{ __('file.public_forms') }} @endslot
        @slot('subtitle') {{ __('file.manage_secure_tenant_forms_field_builders_and_shareable_public_links') }} @endslot
        @slot('button')
            <a href="{{ route('public-forms.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.create_form') }}
            </a>
        @endslot
    @endcomponent
    {{-- DataTable Section --}}
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
<div class="modal fade" id="linkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('file.generate_secure_link') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="linkForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                        <label class="form-label">{{ __('file.link_validity') }}</label>
                        <select id="duration" class="form-select">
                            <option value="24_hours">24 hours</option>
                            <option value="7_days">7 days</option>
                            <option value="30_days">30 days</option>
                        </select>
                        <div id="linkResult" class="d-none mt-3">
                            <label class="form-label">{{ __('file.shareable_url') }}</label>
                            <div class="input-group">
                                <input id="generatedLink" class="form-control" readonly>
                                <button type="button" id="copyLink" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-copy"></i> Copy
                                </button>
                            </div>
                            <small id="linkExpiry" class="text-muted"></small>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('file.close') }}</button>
                    <button type="submit" form="linkForm" class="btn btn-primary">{{ __('file.generate_link') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('backend.layouts.partials._datatable_bottom')
<script>
$(function () {
    var tokenUrl = null;

    function generateLink(id) {
        let $form = $('#linkForm');
        let updateUrl = "{{ route('public-forms.tokens.store', ':id') }}".replace(':id', id);

        $form.attr('action', updateUrl);
        tokenUrl = updateUrl;
        $('#linkResult').addClass('d-none');
        $('#linkModal').modal('show'); // Updated to use Bootstrap's jQuery modal method
    }

    window.generateLink = generateLink;
     $(document).on('submit', '#linkForm', function (event) {
        event.preventDefault();
        $.ajax({
            url: tokenUrl,
            method: 'POST',
            contentType: 'application/json',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'},
            data: JSON.stringify({duration: $('#duration').val()}),
            success: function (data) {
                $('#generatedLink').val(data.url);
                $('#linkExpiry').text('Expires: ' + new Date(data.expires_at).toLocaleString());
                $('#linkResult').removeClass('d-none');
            },
            error: function (xhr) {
                showFloatingAlert('error', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to generate link.');
            }
        });
    });


    $(document).on('click', '#copyLink', function () {
        navigator.clipboard.writeText($('#generatedLink').val());
        $(this).html('<i class="fa-solid fa-check"></i> Copied');
        setTimeout(function () { $('#copyLink').html('<i class="fa-solid fa-copy"></i> Copy'); }, 1500);
    });

    $(document).on('click', '.copy-link', function () {
        var link = $(this).data('link');
        navigator.clipboard.writeText(link);
        var $btn = $(this);
        var original = $btn.html();
        $btn.html('<i class="fa-solid fa-check"></i>');
        setTimeout(function () { $btn.html(original); }, 1500);
    });
});
function toggleForm(id) {
    var url = "{{ route('public-forms.toggle', ':id') }}".replace(':id', id);
    $.ajax({
        url: url,
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'},
        data: {_method: 'PATCH'},
        success: function (response) {
            showFloatingAlert('success', response.message || 'Public form status updated successfully.');
            $('#public-form-table').DataTable().ajax.reload(null, false);
        },
        error: function (xhr) {
            showFloatingAlert('error', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to update status.');
        }
    });
}
</script>
@endpush