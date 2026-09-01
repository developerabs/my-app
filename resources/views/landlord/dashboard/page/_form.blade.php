@php
    $page = $page ?? null; // $page object will exist on edit, null on create
@endphp

<div class="row">
    <div class="col-md-5 mb-3">
        <label for="title" class="form-label">{{ __('file.field.title') }}</label>
        <input type="text" class="form-control" id="title" name="title" 
               value="{{ old('title', $page->title ?? '') }}" required>
    </div>
    <div class="col-md-5 mb-3">
        <label for="slug" class="form-label">{{ __('file.field.slug') }}</label>
        <input type="text" class="form-control" id="slug" name="slug" 
               value="{{ old('slug', $page->slug ?? '') }}" required>
    </div>
    <div class="col-md-2 mb-3">
        <label for="status" class="form-label">{{ __('file.field.status') }}</label>
        <select class="form-select" id="status" name="status" required>
            @php $status = old('status', $page->status ?? 'published'); @endphp
            <option value="published" {{ $status === 'published' ? 'selected' : '' }}>{{ __('file.option.Published') }}</option>
            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>{{ __('file.option.Draft') }}</option>
            <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>{{ __('file.option.Archived') }}</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label for="content" class="form-label">{{ __('file.field.content') }}</label> <button type="button" class="btn btn-sm btn-info" id="toggle-source">Toggle HTML</button>
    <div id="content">{!! old('content', $page->content ?? '') !!}</div>
    <textarea id="source-container" style="display:none; width:100%; height:300px;"></textarea>
    
    <input type="hidden" name="content" id="content_input" value="{{ old('content', $page->content ?? '') }}">
</div>

<div class="row">
    <div class="col-md-6">
        <div class="col-12 mb-3">
            <label for="meta_title" class="form-label">{{ __('file.field.meta_title') }}</label>
            <input type="text" class="form-control" id="meta_title" name="meta_title"
                   value="{{ old('meta_title', $page->meta['meta_title'] ?? '') }}">
        </div>
        <div class="col-12 mb-3">
            <label for="meta_keywords" class="form-label">{{ __('file.field.meta_keywords') }}</label>
            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords"
                   value="{{ old('meta_keywords', $page->meta['meta_keywords'] ?? '') }}">
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="meta_description" class="form-label">{{ __('file.field.meta_description') }}</label>
        <textarea class="form-control" id="meta_description" name="meta_description" rows="5">{{ old('meta_description', $page->meta['meta_description'] ?? '') }}</textarea>
    </div>
</div>
