@extends('backend.layouts.main')

@section('title')
    {{ __('file.title.product_management_edit') }} -
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
            {{ __('file.title.product_management_edit') }}
        @endslot
        @slot('subtitle')
            {{ __('file.title.product_management_edit_desc') }}
        @endslot
        @slot('button')
            <a href="{{ route('products.index') }}" class="btn btn-primary">
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

    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data"
        id="product-create-form">
        @csrf
        @method('PATCH')
        @include('backend.products._form', ['product' => $product, 'isEdit' => true])
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
    @include('backend.products._product_script_edit')

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

                files: [
                    @if ($product->thumbnail)
                        {
                            source: '{{ $product->thumbnail_url }}',
                            options: {
                                type: 'local'
                            }
                        }
                    @endif
                ],
                server: {
                    load: (source, load, error, progress, abort, headers) => {
                        fetch(source).then(res => res.blob()).then(load);
                    }
                }
            });

            // Initialize Gallery (Multiple)
            const galleryElement = document.querySelector('.filepond-gallery');
            const pond = FilePond.create(galleryElement, {
                labelIdle: 'Drag & Drop Gallery Images or <span class="filepond--label-action">Browse</span>',
                maxFiles: 5, // Maximum 5 images
                allowMultiple: true,
                maxFileSize: '2MB',
                imagePreviewMaxHeight: '70px',
                acceptedFileTypes: ['image/*'],
                storeAsFile: true, // Ensure the file is stored as a File object for server processing

                files: [
                    @foreach ($product->images as $image)
                        {
                            source: '{{ $image->image_url }}',
                            options: {
                                type: 'local',
                                metadata: {
                                    file_path: '{{ $image->image }}'
                                }
                            }
                        },
                    @endforeach
                ],
                server: {
                    load: (source, load, error, progress, abort, headers) => {
                        fetch(source).then(res => res.blob()).then(load);
                    }
                }
            });

            const form = galleryElement.closest('form');
            form.addEventListener('submit', function(e) {
                // Remove any existing hidden inputs to avoid duplicates
                form.querySelectorAll('.temp-existing-image').forEach(el => el.remove());

                // Get all files currently in FilePond
                pond.getFiles().forEach(file => {
                    // origin 1 means it's a file loaded from the server
                    if (file.origin === 1) {
                        const filePath = file.getMetadata('file_path');
                        if (filePath) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'existing_gallery[]';
                            input.value = filePath;
                            input.className = 'temp-existing-image';
                            form.appendChild(input);
                        }
                    }
                });
            });
        })

        function initFilepond() {
            const digitalFileElement = document.querySelector('input[name="digital_file"]');

            if (digitalFileElement) {
                // যদি আগে থেকেই ইনশিলাইজড থাকে তবে সেটি ধ্বংস (destroy) করে নতুন করে করা ভালো
                if (FilePond.find(digitalFileElement)) {
                    FilePond.destroy(digitalFileElement);
                }

                FilePond.create(digitalFileElement, {
                    server: {
                        url: '/',
                        process: '/upload-digital-file',
                        patch: '/update-digital-file/',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    },
                    chunkUploads: true,
                    chunkSize: 5242880, // 5MB
                    maxFileSize: 10 * 1024 * 1024 * 1024, // 10GB
                    allowRevert: false,
                    labelIdle: 'Drag & Drop Digital File or <span class="filepond--label-action">Browse</span>',
                });

                // ফাইল আপলোড সাকসেস হলে আইডি হিডেন ইনপুটে রাখা
                digitalFileElement.addEventListener('FilePond:processfile', function(e) {
                    if (e.detail.error) return;
                    $('#digital_file_id').val(e.detail.file.serverId);
                });
            }
        }
    </script>
@endpush
