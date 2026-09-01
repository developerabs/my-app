@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.product_management_create') }} -
    {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@push('css')
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/filepond/filepond.min.css">
    <link rel="stylesheet"
        href="{{ url('backend') }}/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css">
    <link rel="stylesheet"
        href="{{ url('backend') }}/assets/libs/filepond-plugin-image-edit/filepond-plugin-image-edit.min.css">
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/quill/quill.snow.css">
@endpush

@section('content')
    @component('backend.layouts.partials.header')
        @slot('title')
            {{ __('file.title.product_management_create') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.product_management_create_desc') }}
        @endslot
        @slot('button')
            <a href="#" class="btn btn-primary">
                <i class="fa-solid fa-list me-1"></i> {{ __('file.button.list') }} {{ __('file.product') }}
            </a>
        @endslot
    @endcomponent

    @if ($unitGroups->isEmpty())
        <div class="alert shadow-sm d-flex align-items-center" role="alert"
            style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-left: 4px solid #721c24; color: #721c24; padding: 10px 15px;">
            <i class="fas fa-folder-plus me-2"></i>
            <div class="flex-grow-1">
                <strong>{{ __('file.warning') }}:</strong>
                {!! __('file.message.unit_group_required_warning') !!}
                <a href="{{ route('unit-groups.index') }}" class="fw-bold ms-1 text-decoration-underline"
                    style="color: #721c24;">
                    {{ __('file.message.create_unit_group') }}
                </a>
            </div>
        </div>

        {{-- 2. If groups exist but total unit count is 0 --}}
    @elseif ($unitGroups->sum('units_count') == 0)
        <div class="alert shadow-sm d-flex align-items-center" role="alert"
            style="background-color: #fffec8; border: 1px solid #ffeeba; border-left: 4px solid #664d03; color: #856404; padding: 10px 15px;">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div class="flex-grow-1">
                <strong style="color: #664d03;">{{ __('file.warning') }}:</strong>
                {!! __('file.message.unit_required_warning') !!}
                <a href="{{ route('units.index') }}" class="fw-bold ms-1 text-decoration-underline"
                    style="color: #664d03;">
                    {{ __('file.message.create_unit') }}
                </a>
            </div>
        </div>
    @endif

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="product-create-form">
        @csrf
        @include('backend.products._form')
    </form>
@endsection



@push('js')
    <script src="{{ url('backend') }}/assets/libs/filepond/filepond.min.js"></script>
    <script src="{{ url('backend') }}/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js">
    </script>
    <script
        src="{{ url('backend') }}/assets/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js">
    </script>
    <script
        src="{{ url('backend') }}/assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js">
    </script>
    <script src="{{ url('backend') }}/assets/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js"></script>
    <script src="{{ url('backend') }}/assets/libs/filepond-plugin-image-edit/filepond-plugin-image-edit.min.js"></script>
    <script
        src="{{ url('backend') }}/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js">
    </script>
    <script
        src="{{ url('backend') }}/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js">
    </script>
    <script src="{{ url('backend') }}/assets/libs/filepond-plugin-image-crop/filepond-plugin-image-crop.min.js"></script>
    <script src="{{ url('backend') }}/assets/libs/filepond-plugin-image-resize/filepond-plugin-image-resize.min.js">
    </script>
    <script src="{{ url('backend') }}/assets/libs/filepond-plugin-image-transform/filepond-plugin-image-transform.min.js">
    </script>
    @include('backend.layouts.partials._quill_js')
    @include('backend.products._product_script')

    <script>
        $(document).ready(function() {
            initQuill();

            FilePond.registerPlugin(
                FilePondPluginImagePreview,
                FilePondPluginFileValidateType,
                FilePondPluginFileValidateSize
            );

            // Initialize Thumbnail (Single)
            const thumbnailElement = document.querySelector('.filepond-thumbnail');
            FilePond.create(thumbnailElement, {
                labelIdle: 'Drag & Drop Thumbnail or <span class="filepond--label-action">Browse</span>',
                maxFileSize: '2MB',
                acceptedFileTypes: ['image/*'],
                storeAsFile: true, // Ensure the file is stored as a File object for server processing
            });

            // Initialize Gallery (Multiple)
            const galleryElement = document.querySelector('.filepond-gallery');
            FilePond.create(galleryElement, {
                labelIdle: 'Drag & Drop Gallery Images or <span class="filepond--label-action">Browse</span>',
                maxFiles: 5, // Maximum 5 images
                allowMultiple: true,
                maxFileSize: '2MB',
                imagePreviewMaxHeight: '70px',
                acceptedFileTypes: ['image/*'],
                storeAsFile: true, // Ensure the file is stored as a File object for server processing
            });
        })

        // function initFilepond() {
        //     const digitalFileElement = document.querySelector('input[name="digital_file"]');

        //     if (digitalFileElement) {
        //         // যদি আগে থেকেই ইনশিলাইজড থাকে তবে সেটি ধ্বংস (destroy) করে নতুন করে করা ভালো
        //         if (FilePond.find(digitalFileElement)) {
        //             FilePond.destroy(digitalFileElement);
        //         }

        //         FilePond.create(digitalFileElement, {
        //             server: {
        //                 url: '/',
        //                 process: '/upload-digital-file',
        //                 patch: '/update-digital-file/',
        //                 headers: {
        //                     'X-CSRF-TOKEN': '{{ csrf_token() }}'
        //                 }
        //             },
        //             chunkUploads: true,
        //             chunkSize: 5242880, // 5MB
        //             maxFileSize: 10 * 1024 * 1024 * 1024, // 10GB
        //             allowRevert: false,
        //             labelIdle: 'Drag & Drop Digital File or <span class="filepond--label-action">Browse</span>',
        //         });

        //         // ফাইল আপলোড সাকসেস হলে আইডি হিডেন ইনপুটে রাখা
        //         digitalFileElement.addEventListener('FilePond:processfile', function(e) {
        //             if (e.detail.error) return;
        //             $('#digital_file_id').val(e.detail.file.serverId);
        //         });
        //     }
        // }

        function initFilepond() {
            const digitalFileElement = document.querySelector('input[name="digital_upload"]');
            const submitBtn = $('#submit-button'); // আপনার বাটনের আইডি অনুযায়ী পরিবর্তন করুন

            if (digitalFileElement) {
                if (FilePond.find(digitalFileElement)) {
                    FilePond.destroy(digitalFileElement);
                }

                const pond = FilePond.create(digitalFileElement, {
                    server: {
                        url: '',
                        process: {
                            url: '/upload-digital-file',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        },
                        patch: {
                            url: '/upload-digital-file/', // শেষে স্লাশ রাখা জরুরি চাঙ্ক আপলোডের জন্য
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }
                    },
                    chunkUploads: true,
                    chunkSize: 5242880, // 5MB
                    maxFileSize: 200 * 1024 * 1024, // 200MB
                    allowRevert: false,
                    labelIdle: 'Drag & Drop Digital File or <span class="filepond--label-action">Browse</span>',
                });

                // ১. ফাইল আপলোড শুরু হলে বাটন ডিজেবল করুন
                digitalFileElement.addEventListener('FilePond:addfilestart', function() {
                    submitBtn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...'
                    );
                });

                // ২. ফাইল আপলোড সাকসেস হলে আইডি রাখা এবং বাটন এনাবল করা
                digitalFileElement.addEventListener('FilePond:processfile', function(e) {
                    if (e.detail.error) {
                        submitBtn.prop('disabled', false).text('Update Product');
                        return;
                    }

                    // হিডেন ইনপুটে S3 পাথ রাখা
                    const s3Path = e.detail.file.serverId; // আপনার সার্ভার থেকে সঠিক পাথ রিটার্ন করা উচিত
                    $('#digital_file_id').val(s3Path);

                    // বাটন আবার সচল করা
                    submitBtn.prop('disabled', false).text('Update Product');
                });

                // ৩. যদি আপলোড চলাকালীন কোনো এরর হয়
                digitalFileElement.addEventListener('FilePond:error', function(e) {
                    submitBtn.prop('disabled', false).text('Update Product');
                    alert('File upload failed!');
                });
            }
        }

        $('#product-create-form').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(form[0]);

            let allSubmitBtns = form.find('button[type="submit"]');
            let clickedBtn = $(document.activeElement);

            if (clickedBtn.attr('name')) {
                formData.append(clickedBtn.attr('name'), clickedBtn.attr('value'));
            }

            form.find('input[type="checkbox"]').each(function() {
                let name = this.name;

                if (name) {
                    // যদি এটি বেনিফিট অ্যারে হয়, তবে একে ডিস্টার্ব করবো না
                    if (name.includes('[]')) {
                        // benefits[] এর ডাটা FormData আগেই নিয়েছে, তাই এখানে কিছু করার দরকার নেই
                        return;
                    } else {
                        // শুধুমাত্র is_active এর মতো সিঙ্গেল চেকবক্সের জন্য ১ বা ০ সেট করবো
                        formData.set(name, this.checked ? 1 : 0);
                    }
                }
            });
            $.ajax({
                url: form.attr("action"),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    allSubmitBtns.prop("disabled", true);
                    clickedBtn.data('original-html', clickedBtn.html()); // আগের টেক্সট সেভ করে রাখা
                    clickedBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + '{{ __('file.button.saving') }}');
                    form.find(".invalid-feedback").remove();
                    form.find(".is-invalid").removeClass("is-invalid");
                },
                success: function(response) {
                    if (response.success) {
                        if(typeof window.syncProducts === 'function') {
                            window.syncProducts(true); // ফোর্স সিঙ্ক
                        }
                        window.location.href = response.redirect;
                    } else {
                        showFloatingAlert(response.message || 'An error occurred. Please try again.');
                        allSubmitBtns.prop("disabled", false);
                        clickedBtn.html(clickedBtn.data('original-html'));
                    }
                },
                error: function(xhr) {
                    showFloatingAlert(xhr.responseJSON?.message || 'An error occurred. Please check your input and try again.');
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
