@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.client_due_management') }} - SheraziPOS Landlord
@endsection

@push('css')
    @include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{ __('file.title.client_due_management') }}</h4>
            <p class="mb-0 text-muted">{{ __('file.title.client_due_management_desc') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{ $dataTable->table(['class' => 'table nowrap responsive display']) }}
        </div>
    </div>

    <!-- Create Note Modal -->
    <div class="modal fade" id="createNoteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="createNoteForm">
                    @csrf
                    <input type="hidden" name="reseller_client_id" id="resellerClientId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createNoteModalTitle">Add Client Note</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="note" class="form-label">Note</label>
                            <textarea name="note" id="note" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Create Note Modal -->

    <!-- View All Notes Modal -->
    <div class="modal fade" id="viewNotesModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Client Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="viewNotesContent" style="max-height: 700px; overflow-y: auto;">
                    <p class="text-center text-muted">Loading...</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>
    <!-- End View All Notes Modal -->
@endsection
@push('js')
    @include('landlord.layouts.partials._datatable_bottom')
    <script>
        $(document).ready(function() {
            // Open Create Note Modal
            $(document).on('click', '.createNoteBtn', function() {
                let clientId = $(this).data('reseller-client-id');
                $('#resellerClientId').val(clientId);
                $('#note').val('');
                $('#createNoteModal').modal('show');
            });

            // AJAX Create Note
            $('#createNoteForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let url = "{{ route('landlord.clientNotes.store') }}";
                let data = form.serialize();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    beforeSend: function() {
                        form.find('button[type="submit"]').prop('disabled', true).text(
                            "Saving...");
                    },
                    success: function(response) {
                        $('#createNoteModal').modal('hide');
                        form[0].reset();
                        showFloatingAlert('success', response.message ??
                            'Note added successfully');
                        $('#clientdues-table').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let errorMsg = 'Something went wrong!';
                        if (errors) {
                            errorMsg = Object.values(errors).map(e => e.join(', ')).join('\n');
                        }
                        showFloatingAlert('error', errorMsg);
                    },
                    complete: function() {
                        form.find('button[type="submit"]').prop('disabled', false).text(
                            "Save Note");
                    }
                });
            });
        });
        // Open View All Notes Modal
        function viewClientNotes(id) {
            let url = '{{ route('landlord.getClientNotes', ['id' => ':id']) }}'
            url = url.replace(':id', id);

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    let notes = response.notes;
                    let content = '';
                    if (notes.length > 0) {
                        content += '<ul class="note-list list-group">';
                        notes.forEach(function(note) {
                            content += `
                                    <li class="list-group-item">
                                        <small class="text-muted">
                                            ( <strong>${note.added_by}</strong>) | ${note.created_at}
                                        </small>
                                          <p class="mt-1 mb-0">${note.note}</p>
                                    </li>
                                `;
                        });
                        content += '</ul>';
                    } else {
                        content = '<p class="text-center text-muted">No notes found for this client.</p>';
                    }
                    $('#viewNotesContent').html(content);
                    $('#viewNotesModal').modal('show');
                },
                error: function() {
                    $('#viewNotesContent').html('<p class="text-center text-danger">Failed to load notes.</p>');
                }
            });
        }
    </script>
@endpush
