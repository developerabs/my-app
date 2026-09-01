@foreach($children as $child)
    <option value="{{ $child->id }}"
        {{ in_array($child->id, old('category_id', $selectedCategories ?? [])) ? 'selected' : '' }}>
        {{ $prefix }} {{ $child->name }}
    </option>
    
    @if($child->allChildren && $child->allChildren->count() > 0)
        @include('backend.products._category_options', [
            'children' => $child->allChildren, 
            'prefix' => $prefix . '—',
            'selectedCategories' => $selectedCategories ?? []
        ])
    @endif
@endforeach