@php
    $paddingLeft = ($level - 1) * 20;
    $isRootGroup = ($level === 1);
@endphp

{{-- 1. COA Group Header Row (Only render if not root group or has subtotal) --}}
@if(!$isRootGroup)
    <tr class="pnl-subtotal-row">
        <td class="text-muted fw-semibold ps-{{ $level > 1 ? 3 : 2 }}">{{ $node['code'] ?? '' }}</td>
        <td style="padding-left: {{ $paddingLeft }}px !important;">
            <i class="fa-solid fa-folder-open text-warning me-1 small"></i>
            <span class="fw-bold text-dark">{{ $node['name'] ?? '' }}</span>
        </td>
        <td class="text-end fw-bold text-dark">{{ format_currency($node['total_amount'] ?? 0) }}</td>
    </tr>
@endif

{{-- 2. Render Direct Ledger Accounts under this COA Group --}}
@if(!empty($node['accounts']))
    @foreach($node['accounts'] as $acc)
        <tr>
            <td class="text-muted ps-{{ $level > 1 ? 4 : 3 }} small">{{ $acc['account_code'] }}</td>
            <td style="padding-left: {{ $paddingLeft + 20 }}px !important;">
                <a href="{{ $acc['route'] ?? 'javascript:void(0)' }}" class="text-dark text-decoration-none">
                    <i class="fa-solid fa-file-invoice text-secondary me-1 small"></i> {{ $acc['account_name'] }}
                </a>
            </td>
            <td class="text-end font-monospace">{{ format_currency($acc['amount'] ?? 0) }}</td>
        </tr>
    @endforeach
@endif

{{-- 3. Recursive Render for Child Sub-Groups --}}
@if(!empty($node['children']))
    @foreach($node['children'] as $childNode)
        @include('backend.accounting.partials._pnl_row', ['node' => $childNode, 'level' => $level + 1])
    @endforeach
@endif