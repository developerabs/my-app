@extends('backend.layouts.main')

@section('title')
    {{ $lead->name }} - {{ $general_settings['site_title'] ?? ($general_settings['company_name'] ?? 'SheraziPOS') }}
@endsection

@section('content')
    {{-- Header Section --}}
    @component('backend.layouts.partials.header')
        @slot('title')
            <div class="d-flex align-items-center gap-2">
                <span>{{ $lead->name }}</span>
                @if($lead->priority)
                    <span class="badge bg-{{ $lead->priority == 'high' ? 'danger' : ($lead->priority == 'medium' ? 'warning' : 'info') }} text-capitalize fs-6">
                        {{ $lead->priority }}
                    </span>
                @endif
            </div>
        @endslot
        @slot('subtitle')
            {{ $lead->company_name ? $lead->company_name . ' • ' : '' }}{{ $lead->leadStatus?->name ?? 'No Status' }}
        @endslot
        @slot('button')
            <div class="d-flex gap-2">
                <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('file.button.back') }}
                </a>
                <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editLeadModal">
                    <i class="fa-solid fa-pen-to-square me-1"></i> {{ __('file.button.edit') ?? 'Edit' }}
                </a>
            </div>
        @endslot
    @endcomponent

    <div class="row g-3">
        {{-- Main Left Column --}}
        <div class="col-lg-8">
            
            {{-- Lead Info Overview Card --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold m-0">
                        <i class="fa-solid fa-address-card text-primary me-2"></i>{{ $lead->type == 'lead' ? __('file.lead_details') : __('file.deal_details') }}
                    </h6>
                    @if($lead->expected_value)
                        <span class="badge bg-light text-dark border fs-6">
                            <strong>{{ __('file.field.expected_value') }}:</strong> ${{ number_format($lead->expected_value, 2) }}
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ __('file.field.name') }}</label>
                            <span class="fw-semibold">{{ $lead->name }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ __('file.field.company') }}</label>
                            <span class="fw-semibold">{{ $lead->company_name ?? '-' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ __('file.field.email') }}</label>
                            @if($lead->email)
                                <a href="mailto:{{ $lead->email }}" class="text-decoration-none fw-semibold">
                                    <i class="fa-regular fa-envelope me-1"></i>{{ $lead->email }}
                                </a>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ __('file.field.phone') }}</label>
                            @if($lead->phone)
                                <a href="tel:{{ $lead->phone }}" class="text-decoration-none fw-semibold">
                                    <i class="fa-solid fa-phone me-1"></i>{{ $lead->phone }}
                                </a>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ __('file.field.website') }}</label>
                            @if($lead->website)
                                <a href="{{ Str::startsWith($lead->website, 'http') ? $lead->website : 'https://' . $lead->website }}" target="_blank" class="text-decoration-none fw-semibold">
                                    <i class="fa-solid fa-globe me-1"></i>{{ $lead->website }}
                                </a>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">{{ __('file.field.follow_up_date') }}</label>
                            <span class="fw-semibold text-danger">
                                <i class="fa-regular fa-calendar-check me-1"></i>{{ formatDate($lead->follow_up_date, true) ?? '-' }}
                            </span>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small d-block">{{ __('file.field.address') }}</label>
                            <span>{{ $lead->address->address ?? '-' }}</span>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small d-block">{{ __('file.field.description') }}</label>
                            <p class="mb-0 text-secondary p-2 rounded">{{ $lead->description ?? __('file.no_description_available') }}</p>
                        </div>
                        @if($lead->attachment)
                            <div class="col-md-12">
                                <label class="text-muted small d-block">{{ __('file.field.attachment') }}</label>
                                <a href="{{ $lead->attachment_url }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                    <i class="fa-solid fa-paperclip me-1"></i>{{ __('file.button.download') ?? 'View Attachment' }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Notes & Timeline Activity Card --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold m-0">
                        <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>{{ __('file.notes') }} & {{ __('file.activity') }}
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($leadNotes ?? [] as $note)
                        @php($creatorName = $note->creator?->name ?? __('file.system'))
                        <div class="border-start border-2 border-primary ps-3 pb-3 mb-3 position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold"
                                        style="width: 28px; height: 28px; font-size: 11px;">
                                        {{ strtoupper(substr($creatorName, 0, 1)) }}
                                    </span>
                                    <span class="fw-semibold text-dark">{{ $creatorName }}</span>
                                    <small class="text-muted">• {{ formatDate($note->created_at, true) }}</small>
                                </div>
                                <span class="badge text-uppercase" style="background-color: {{ $note->status?->color ?? '#6c757d' }};">
                                    {{ $note->status?->name ?? __('file.not_assigned') }}
                                </span>
                            </div>

                            <p class="my-2 text-dark">{{ $note->note }}</p>

                            <div class="d-flex flex-wrap gap-3 small text-muted p-2 rounded">
                                <div><strong>{{ __('file.field.type') }}:</strong> <span class="text-capitalize">{{ $note->note_type ?? '-' }}</span></div>
                                <div><strong>{{ __('file.field.follow_up_date') }}:</strong> {{ $note->next_follow_up_at ? formatDate($note->next_follow_up_at, true) : '-' }}</div>
                                @if($note->effective_phone)
                                    <div><strong>{{ __('file.field.phone') }}:</strong> {{ $note->effective_phone }}</div>
                                @endif
                                @if($note->attachment)
                                    <div>
                                        <a href="{{ $note->attachment_url ?? '#' }}" target="_blank" class="text-decoration-none">
                                            <i class="fa-solid fa-paperclip me-1"></i>{{ __('file.button.view_attachment') }}
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- Embedded Meeting Info --}}
                            @php($meeting = $leadMeetingsByCreatedAt->get(formatDate($note->created_at, true), collect())->first())
                            @if($meeting)
                                <div class="mt-2 p-2 border-start border-3 border-info bg-light rounded">
                                    <div class="fw-semibold text-info small">
                                        <i class="fa-solid fa-calendar-check me-1"></i> {{ __('file.label.meeting') }}: {{ $meeting->title }}
                                    </div>
                                    <div class="small text-muted">{{ formatDate($meeting->start_at, true) }}{{ $meeting->end_at ? ' - ' . formatDate($meeting->end_at, true) : '' }}</div>
                                    <div class="row g-1 small mt-1 text-secondary">
                                        <div class="col-6"><strong>{{ __('file.label.location') }}:</strong> {{ $meeting->location ?: 'N/A' }}</div>
                                        <div class="col-6"><strong>{{ __('file.label.assigned_to') }}:</strong> {{ $meeting->assignedTo?->name ?? '-' }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="fa-regular fa-folder-open fs-2 mb-2 d-block"></i>
                            {{ __('file.no_notes_found') ?? 'No notes or activities recorded yet.' }}
                        </div>
                    @endforelse

                    @if(method_exists($leadNotes, 'links'))
                        <div class="mt-3">
                            {{ $leadNotes->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Sidebar Column --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title fw-bold m-0">
                        <i class="fa-solid fa-chart-pie text-primary me-2"></i>{{ __('file.lead_summary') }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.category') }}</span>
                            <span class="fw-semibold small">{{ $lead->category?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.status') }}</span>
                            <span class="badge bg-secondary">{{ $lead->leadStatus?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.subject') }}</span>
                            <span class="fw-semibold small">{{ $lead->leadSubject?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.source') }}</span>
                            <span class="badge bg-light text-dark border">{{ $lead->leadSource?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.manager') }}</span>
                            <span class="fw-semibold small">{{ $lead->manager?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.assigned_to') }}</span>
                            <span class="fw-semibold small text-primary">{{ $lead->assignedTo?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.created_by') }}</span>
                            <span class="fw-semibold small">{{ $lead->createdBy?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">{{ __('file.field.updated_by') }}</span>
                            <span class="fw-semibold small">{{ $lead->updatedBy?->name ?? '-' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function(){
            if (typeof flatpickr !== "undefined") {
                flatpickr(".date-picker", {
                    altInput: true,
                    altFormat: (window.appSettings && window.appSettings.date_format) ? window.appSettings.date_format : "Y-m-d",
                    dateFormat: "Y-m-d",
                    defaultDate: "today",
                    static: true,
                    allowInput: true,
                });
            }
        });
    </script>
@endpush