@extends('landlord.layouts.main')

@section('title'){{__('file.title.homepage_widget')}} - SheraziPOS Landlord @endsection

@push('css')
<link rel="stylesheet" href="{{asset('backend')}}/assets/libs/quill/quill.snow.css">
<style>
    .border-dashed {
        border-style: dashed !important;
    }

    .create-widget-btn {
        transition: none !important; 
    }

    .create-widget-btn:hover,
    .create-widget-btn:focus {
        background-color: transparent !important; 
        color: var(--bs-info) !important; 
        border-color: var(--bs-info) !important;
    }
</style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{__('file.title.homepage_widget')}}</h4>
            <p class="mb-0 text-muted">{{__('file.title.homepage_widget_desc')}}</p>
        </div>
    </div>

    <div class="row">
        {{-- Dynamic widgets list --}}
        <div class="col-md-3">
            <div class="card" id="widgets">
                
            </div>
        </div>
        {{-- Widgets content and configuration --}}
        <div class="col-md-9">
            <div class="card">
                <div class="card-body" id="widgets-container">
                    <p>Please Select a Widget to Configure</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <!-- Create New Widget Modal -->
    <div class="modal fade" id="createNewWidgetModal" tabindex="-1" aria-labelledby="createNewWidgetModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 80px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="createNewWidgetModalLabel">{{ __('file.title.create_new_widget') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('landlord.store-widget') }}" method="POST" id="createWidgetForm">
                        @csrf
                        <div class="mb-3">
                            <label for="title" class="form-label">{{__('file.field.title')}}</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">{{__('file.field.type')}}</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option selected disabled value="">{{__('file.option.choose_option')}}</option>
                                    <option value="slider">{{__('file.option.slider')}}</option>
                                    <option value="grid">{{__('file.option.grid')}}</option>
                                    <option value="text">{{__('file.option.text')}}</option>
                                    <option value="form">{{__('file.option.form')}}</option>
                                    <option value="section">{{__('file.option.section')}}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="sort_order" class="form-label">{{__('file.field.order')}}</label>
                                <input type="text" class="form-control" id="sort_order" name="sort_order" pattern="[0-9]*" value="0" required>
                            </div>
                        </div>
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-6">
                                <label for="content_type" class="form-label">{{__('file.field.content_type')}}</label>
                                <select class="form-select" id="content_type" name="content_type" required>
                                    <option selected disabled value="">{{__('file.option.choose_option')}}</option>
                                    <option value="dynamic">{{__('file.option.dynamic')}}</option>
                                    <option value="static" selected>{{__('file.option.static')}}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" type="checkbox" value="1" id="is_global" name="is_global">
                                <label class="form-check-label" for="is_global">
                                    {{__('file.field.is_global')}}
                                </label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" type="checkbox" value="1" id="is_editable" name="is_editable" checked>
                                <label class="form-check-label" for="is_editable">
                                    {{__('file.field.is_editable')}}
                                </label>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('file.button.create') }} {{ __('file.widget') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')

 @include('landlord.dashboard.page._quill_js')
 <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
 <script>
    $(document).ready(function () {
        // Load Widgets
        loadWidgets();

        // Toggle Content Type (dynamic content is only available for slider and grid widgets)
        function toggleContentType() {
            let type = $("#type").val();

            if (type === "slider" || type === "grid") {
                $("#content_type option[value='dynamic']").show().prop("disabled", false);
            } else {
                $("#content_type option[value='dynamic']").hide().prop("disabled", true);
                $("#content_type").val("static");
            }
        }

        toggleContentType();

        $("#type").on("change", function () {
            toggleContentType();
        });
    })

    // Load Widgets function
    function loadWidgets() {
        $.get('{{route("landlord.get-widgets")}}', function (data) {
            $('#widgets').html(data);
        })
    }
    
    // Create Widget
    $('#createWidgetForm').on('submit', function (e) {
        e.preventDefault();
        let form = $(this);
        let url = form.attr('action');
        let data = form.serialize();
        
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            beforeSend: function () {
                form.find('button[type="submit"]').prop('disabled', true).text("{{ __('file.button.creating') }}...");
            },
            success: function (response) {
                loadWidgets();
                showFloatingAlert('success', response.message || "{{ __('file.message.widget_created_successfully') }}");
                $('#createNewWidgetModal').modal('hide');
                configWidget(response.widget.id);
            },
            error: function (xhr) {
                form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.create') }} {{ __('file.widget') }}");
                showFloatingAlert('error', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to create widget.');
            },
            complete: function () {
                form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.create') }} {{ __('file.widget') }}");
                form.trigger('reset');
            }
        })
    })

    // Config Widget page
    function configWidget(id) {
        $('#widgets').find('.btn-primary').removeClass('btn-primary').addClass('btn-info');
        $.get('{{route("landlord.configure-widget", ":id")}}'.replace(':id', id), function (data) {
            $('#widgets-container').html(data.view);
            $('#widgets').find(`[data-id="${id}"]`).addClass('btn-primary').removeClass('btn-info');
            if(data.type === 'text') {
                initQuill();
            }
        });
    }

    // Edit Widget submit
    $(document).on('submit', '#editWidgetForm', function (e) {
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
            beforeSend: function () {
                form.find('button[type="submit"]').prop('disabled', true).text("{{ __('file.button.updating') }}...");
            },
            success: function (response) {
                showFloatingAlert('success', response.message || "{{ __('file.message.widget_updated_successfully') }}");
                loadWidgets();
                configWidget(response.widget.id);
            },
            error: function (xhr) {
                form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.update') }} {{ __('file.widget') }}");
                showFloatingAlert('error', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to update widget.');
            },
            complete: function () {
                form.find('button[type="submit"]').prop('disabled', false).text("{{ __('file.button.update') }} {{ __('file.widget') }}");
            }
        });
    })

    // Delete Widget
    function deleteWidget(id) {
        $('#deleteConfirmModal').modal('show');
        let deleteButton = $('#deleteConfirm');

        deleteButton.off('click').on('click', function() {
            let url = '{{ route("landlord.delete-widget", ["widget" => ":id"]) }}';
            url = url.replace(':id', id);
            $.ajax({
                url: url,
                type: 'DELETE',
                beforeSend: function() {
                    deleteButton.prop('disabled', true).text("{{ __('file.button.deleting') }}...");
                },
                success: function(response) {
                    $('#deleteConfirmModal').modal('hide');
                    showFloatingAlert('success', response.message || "{{ __('file.message.widget_deleted_successfully') }}");
                    deleteButton.prop('disabled', false).text("{{ __('file.button.delete') }}");
                    loadWidgets();
                    $('#widgets-container').html('<p>Please Select a Widget to Configure</p>');
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
