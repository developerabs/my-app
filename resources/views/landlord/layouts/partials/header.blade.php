<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h4 class="mb-0">{{ $title ?? '' }}</h4>
        @isset($subtitle)
            <p class="mb-0 text-muted">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($button)
        <div class="d-flex gap-2">
            {!! $button !!}
        </div>
    @endisset
</div>