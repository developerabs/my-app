@php
    $indentPx = $level * 22;
    $treeConnector = $level > 0 ? '<span class="text-muted opacity-50 me-1" style="font-family: monospace;">└─</span>' : '';
@endphp

{{-- Group Header Row --}}
<tr>
    <td class="fw-bold text-primary">{{ $node['code'] }}</td>
    <td class="fw-bold text-dark" style="padding-left: {{ $indentPx + 10 }}px;">
        {!! $treeConnector !!}
        <i class="{{ $level === 0 ? 'fa-solid fa-folder text-warning me-1' : 'fa-regular fa-folder text-warning me-1' }}"></i>
        <span class="{{ $level === 0 ? 'text-dark fw-bold' : 'text-secondary fw-semibold' }}">{{ $node['name'] }}</span>
    </td>
    <td class="text-end fw-bold text-dark">{{ $node['total_debit'] > 0 ? format_currency($node['total_debit']) : '-' }}</td>
    <td class="text-end fw-bold text-dark">{{ $node['total_credit'] > 0 ? format_currency($node['total_credit']) : '-' }}</td>
</tr>

{{-- Leaf Accounts under this Group --}}
@if (!empty($node['accounts']))
    @foreach ($node['accounts'] as $acc)
        <tr>
            <td class="text-muted small" style="padding-left: {{ $indentPx + 28 }}px;">{{ $acc['account_code'] }}</td>
            <td style="padding-left: {{ $indentPx + 28 }}px;">
                <span class="text-muted opacity-50 me-1" style="font-family: monospace;">├─</span>
                <a href="{{ $acc['route'] }}" class="text-decoration-none text-dark fw-semibold" target="_blank" title="Click to view General Ledger">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-1" style="font-size:11px;"></i> {{ $acc['account_name'] }}
                </a>
            </td>
            <td class="text-end fw-semibold text-dark">{{ $acc['debit'] > 0 ? format_currency($acc['debit']) : '-' }}</td>
            <td class="text-end fw-semibold text-dark">{{ $acc['credit'] > 0 ? format_currency($acc['credit']) : '-' }}</td>
        </tr>
    @endforeach
@endif

{{-- Recursive Render Children Sub-Groups --}}
@if (!empty($node['children']))
    @foreach ($node['children'] as $child)
        @include('backend.accounting.partials._trial_balance_row', ['node' => $child, 'level' => $level + 1])
    @endforeach
@endif