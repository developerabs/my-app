@extends('landlord.layouts.main')

@section('title')
    {{ __('file.title.addon_management') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    @include('landlord.layouts.partials._datatable_top')
@endpush

@section('content')
    @component('landlord.layouts.partials.header')
        @slot('title')
            {{ __('file.title.addon_management') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.addon_management_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAddonModal"><i
                    class="fa-solid fa-plus me-1"></i> {{ __('file.button.create') }} {{ __('file.addon') }}</a>
        @endslot
    @endcomponent

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

@section('modals')
    {{-- Create --}}
    <div class="modal fade" id="createAddonModal">
        <div class="modal-dialog">
            <div class="modal-content mt-5">
                <div class="modal-header">
                    <h5>{{ __('file.title.create_addon') }}</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createAddonForm" action="{{ route('landlord.addons.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('landlord.dashboard.addons._form', ['isEdit' => false])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary w-100">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit --}}
    <div class="modal fade" id="editAddonModal">
        <div class="modal-dialog">
            <div class="modal-content mt-5">
                <div class="modal-header">
                    <h5>{{ __('file.title.edit_addon') }}</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editAddonForm">
                    <div class="modal-body">
                        @include('landlord.dashboard.addons._form', ['isEdit' => true])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary w-100">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('landlord.layouts.partials._datatable_bottom')
    <script>
        function initAddonForm(modal) {

            // Feature select2
            modal.find('.feature-select').select2({
                dropdownParent: modal,
                width: '100%',
                placeholder: '-- Select Feature --'
            });

            // Type toggle
            modal.find('.addon-type').off('change').on('change', function() {
                let type = $(this).val();

                if (type === 'limit') {
                    modal.find('.limit-fields').slideDown(200).removeClass('d-none');
                } else {
                    modal.find('.limit-fields').slideUp(200);
                }
            });
        }

        /* ---------- CREATE ---------- */
        $('#createAddonModal').on('shown.bs.modal', function() {
            initAddonForm($(this));
        });

        $('#createAddonForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let rawForm = form[0];
            let url = form.attr('action');
            let data = new FormData(rawForm);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.creating') }}...");
                },
                success: function(response) {
                    console.log(response);
                    showFloatingAlert('success', response.message ||
                        "{{ __('file.message.addon_created_successfully') }}");
                    $('#createAddonModal').modal('hide');
                    $('#addons-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }} {{ __('file.addon') }}");
                    showFloatingAlert('error', xhr.responseJSON && xhr.responseJSON.message ? xhr
                        .responseJSON.message : 'Unable to create addon.');
                },
                complete: function() {
                    form.find('button[type="submit"]').prop('disabled', false).text(
                        "{{ __('file.button.create') }} {{ __('file.addon') }}");
                    form.trigger('reset');
                }
            })

        });

        function editAddon(id) {
            let url = '{{ route('landlord.addons.edit', ':id') }}';
            url = url.replace(':id', id);

            $.get(url, function(response) {
                let data = response.data;
                let modal = $('#editAddonModal');

                // ১. ফর্ম ইনিশিয়ালাইজ করা (যাতে Select2 এবং Toggle লজিক কাজ করে)
                initAddonForm(modal);

                // ২. সাধারণ ফিল্ডগুলো সেট করা
                modal.find('#addon_id').val(data.id);
                modal.find('#edit_name').val(data.name);
                modal.find('[name="price"]').val(data.price);
                modal.find('[name="duration_days"]').val(data.duration_days);

                // ৩. মেটা (meta) ডাটা থেকে ভ্যালুগুলো বের করা
                // যদি মডেলে $casts['meta' => 'array'] থাকে তবে data.meta সরাসরি অবজেক্ট হবে
                let meta = data.meta || {};

                // ৪. টাইপ সেট করা এবং ম্যানুয়ালি ট্রিগার করা যাতে 'Limit' ফিল্ডগুলো দৃশ্যমান হয়
                modal.find('#edit_addon_type').val(data.type).trigger('change');
                modal.find('.feature-select').val(data.reference_id).trigger('change');

                // ৫. লিমিট ফিল্ডগুলো সেট করা (meta থেকে)
                modal.find('[name="limit_mode"]').val(meta.limit_mode);
                modal.find('[name="limit_value"]').val(meta.limit_value);

                // চেকবক্স সেট করা
                let isChecked = meta.reset_on_expiry == 1 || meta.reset_on_expiry == true;
                modal.find('[name="reset_on_expiry"]').prop('checked', isChecked);

                // ৬. ইমেজ প্রিভিউ হ্যান্ডেল করা
                if (meta.image) {
                    modal.find('#edit_image_preview').attr('src', '{{ asset('storage') }}/' + meta.image);
                } else {
                    modal.find('#edit_image_preview').attr('src', '{{ asset('images/preview_image.png') }}');
                }

                // ফর্ম অ্যাকশন সেট করা এবং মোডাল দেখানো
                modal.find('#editAddonForm').attr('action', `/landlord/addons/${data.id}`);
                modal.modal('show');
            });
        }

        /* ---------- UPDATE ---------- */
        $('#editAddonForm').on('submit', function(e) {
            e.preventDefault();
            let addonId = $('#addon_id').val();
            let form = $(this);
            let rawForm = form[0];
            let url = '{{ route("landlord.addons.update", ":id") }}';
            url = url.replace(':id', addonId);
            let formData = new FormData(rawForm);
            formData.append('_method', 'PATCH');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text(
                        "{{ __('file.button.updating') }}...");
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showFloatingAlert('success', response.message || "Addon updated successfully.");
                        $('#editAddonModal').modal('hide');
                        $('#addons-table').DataTable().ajax.reload();
                    }
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    if (errors) {
                        let firstError = Object.values(errors)[0][0];
                        showFloatingAlert('error', firstError);
                    } else {
                        showFloatingAlert('error', 'Update failed.');
                    }
                },
                complete: function() {
                    form.find('button[type="submit"]').prop('disabled', false).text("Update");
                    form.trigger('reset');
                }
            });
        });

        function deleteAddon(id) {
            $('#deleteConfirmModal').modal('show');
            let deleteButton = $('#deleteConfirm');

            deleteButton.off('click').on('click', function() {
                let url = '{{ route('landlord.addons.destroy', ['addon' => ':id']) }}';
                url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    beforeSend: function() {
                        deleteButton.prop('disabled', true).text(
                        "{{ __('file.button.deleting') }}...");
                    },
                    success: function(response) {
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('success', '{{ __('file.message.addon_delete_successfully') }}');
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#addons-table').DataTable().ajax.reload();
                    },
                    error: function() {
                        deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                        $('#deleteConfirmModal').modal('hide');
                        showFloatingAlert('error', "{{ __('file.message.unable_to_delete_addon') }}");
                    }
                });
            });

        }
    </script>
@endpush
