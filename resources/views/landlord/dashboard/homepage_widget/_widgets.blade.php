<div class="card-body">
    <ul class="list-group">
        {{-- Header --}}
        <li class="list-group-item p-0 border-0 mb-2">
            <button type="button" onclick="configWidget({{ $header->id }})" class="btn btn-info w-100 {{ $header->is_enabled ? '' : 'opacity-25' }} py-2 text-capitalize">
                {{ $header->title }}
            </button>
        </li>

        {{-- Body Widgets --}}
        @foreach ($bodyWidgets as $widget)
            <li class="list-group-item p-0 border-0 mb-2">
                <button type="button" data-id="{{ $widget->id }}" onclick="configWidget({{ $widget->id }})" class="btn btn-info w-100 {{ $widget->is_enabled ? '' : 'opacity-25' }} py-2 text-capitalize">
                    {{ $widget->title }}
                </button>
            </li>
        @endforeach

        {{-- Create New Widget Button --}}
        <li class="list-group-item p-0 border-0 mb-2">
            <button
                type="button"
                class="btn btn-outline-info border-dashed w-100 py-2 text-capitalize create-widget-btn"
                data-bs-toggle="modal"
                data-bs-target="#createNewWidgetModal"
            >
                <i class="bi bi-plus-circle me-2"></i> {{ __('file.button.create_new_widget') }}
            </button>
        </li>

        {{-- Footer --}}
        <li class="list-group-item p-0 border-0 mb-2">
            <button type="button" onclick="configWidget({{ $footer->id }})" class="btn btn-info w-100 {{ $footer->is_enabled ? '' : 'opacity-25' }} py-2 text-capitalize">
                {{ $footer->title }}
            </button>
        </li>
    </ul>
</div>
