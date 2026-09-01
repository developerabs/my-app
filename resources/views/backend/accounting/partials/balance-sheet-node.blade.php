@php
    $hasChildren = !empty($node['children']);
    $collapseId = 'node-' . str_replace(['-', ' '], '_', $node['id']);
    $paddingLeft = $level * 20;
    
    $isLedger = str_starts_with($node['id'], 'ledger-');
    $isSubLedger = str_starts_with($node['id'], 'subledger-');
@endphp

<tr class="{{ $hasChildren ? 'qb-row qb-section-header collapsed fw-semibold' : 'qb-sub-row' }}" 
    @if($hasChildren) data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" style="cursor: pointer;" @endif>
    
    <td style="padding-left: {{ $paddingLeft }}px; width: 75%;">
        @if($hasChildren)
            <i class="fa-solid fa-chevron-down toggle-icon me-1"></i>
        @endif

        @if(!empty($node['route']))
            <!-- Clickable Link for Ledger / Sub-Ledger / Flattened CoA -->
            <a href="{{ $node['route'] }}" onclick="event.stopPropagation();" class="text-dark text-decoration-none hover-blue">
                @if($isSubLedger)
                    <i class="fa-solid fa-user-tag text-secondary me-1 small"></i>
                @endif
                {{ $node['name'] }}
                @if(!empty($node['code']))
                    <span class="text-muted small fw-normal">({{ $node['code'] }})</span>
                @endif
            </a>
        @else
            {{ $node['name'] }} <span class="text-muted small fw-normal">({{ $node['code'] ?? '' }})</span>
        @endif
    </td>
    <td class="text-end" style="width: 25%;">{{ number_format($node['total_balance'], 2) }}</td>
</tr>

@if($hasChildren)
    <tr class="collapse" id="{{ $collapseId }}">
        <td colspan="2" class="p-0 border-0">
            <table class="table mb-0">
                @foreach($node['children'] as $child)
                    @include('backend.accounting.partials.balance-sheet-node', ['node' => $child, 'level' => $level + 1])
                @endforeach
            </table>
        </td>
    </tr>
@endif