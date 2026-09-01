@extends('landlord.layouts.main')

@section('title'){{__('file.title.create_page')}} - SheraziPOS Landlord @endsection

@push('css')
<link rel="stylesheet" href="{{asset('backend')}}/assets/libs/quill/quill.snow.css">
<style></style>
@endpush

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">{{__('file.title.create_blog')}}</h4>
            <p class="mb-0 text-muted">{{__('file.title.create_blog_desc')}}</p>
        </div>
        <div>
            <a href="{{ route('landlord.blogs') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> {{__('file.button.back')}}</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('landlord.blogs.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @include('landlord.dashboard.blog._form')
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">💾 {{__('file.button.create')}} {{__('file.blog')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    @include('landlord.dashboard.page._quill_js')
    <script>
        $(document).ready(function() {

            initQuill();

            $('#title').on('input', function() {
                var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-');
                $('#slug').val(slug);
            });
        })
    </script>
@endpush
