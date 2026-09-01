@extends('backend.layouts.main')
@section('title', __('file.edit_public_form'))
@section('content')
    @component('backend.layouts.partials.header')
        @slot('title') {{ __('file.edit_public_form') }} @endslot
        @slot('subtitle') {{ __('file.update_branding_settings_and_custom_public_form_fields') }} @endslot
        @slot('button')
            <a href="{{ route('public-forms.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> {{ __('file.form_list') }}
            </a>
        @endslot
    @endcomponent
    <form id="publicFormEdit" action="{{ route('public-forms.update', $publicForm) }}" method="POST"  enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('backend.public_forms._form', ['publicForm' => $publicForm])
    </form>
@endsection
@push('js')
<script>
    $('#publicFormEdit').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(form[0]);

            let allSubmitBtns = form.find('button[type="submit"]');
            let clickedBtn = $(document.activeElement);
            
            $.ajax({
                url: form.attr("action"),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    allSubmitBtns.prop("disabled", true);
                    form.find(".invalid-feedback").remove();
                    form.find(".is-invalid").removeClass("is-invalid");
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                        showFloatingAlert('success', response.message || 'An error occurred. Please try again.');
                    } else {
                        showFloatingAlert('error', response.message || 'An error occurred. Please try again.');
                        allSubmitBtns.prop("disabled", false);
                        clickedBtn.html(clickedBtn.data('original-html'));
                    }
                },
                error: function(xhr) {
                    showFloatingAlert('error', xhr.responseJSON?.message || 'An error occurred. Please check your input and try again.');
                    allSubmitBtns.prop("disabled", false);
                    clickedBtn.html(clickedBtn.data('original-html'));
                },
                complete: function() {
                    allSubmitBtns.prop("disabled", false);
                    clickedBtn.html(clickedBtn.data('original-html'));
                }
            })
        });
</script>
@endpush