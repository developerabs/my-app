@php
    $selected = $selected ?? null;
    $itemName = $itemName ?? '';
    $items = $items ?? null;
@endphp

<option value="">Select {{ $itemName }}</option>
@forelse ($items as $item)
    <option value="{{ $item->id .'-'. $item->name}}" {{ $item->name === $selected ? 'selected' : '' }}>{{ $item->name }}</option>
@empty
    <option value="">No item found</option>
@endforelse