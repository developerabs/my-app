{{-- <h1>This is grid page {{ $widget->title }}</h1> --}}

{{-- GRID SECTION START --}}

@php
    $contents = $widget->content ?? [];
    $settings = $widget->settings ?? [];

    $align = $contents['text_align'] ?? 'center';
    $fontSize = $contents['font_size'] ?? 40;

    $background = '';
    $isImage = false;
@endphp


{{-- Background Handling --}}
@php
    switch ($contents['bg_type'] ?? 'color') {

        case 'color':
            $background = 'background-color: ' . ($contents['bg_color'] ?? '#ffffff');
            break;

        case 'image':
            $background = "background-image: url("
                . asset('storage/' . ($contents['bg_image'] ?? '')) .
                "); background-size: cover; background-position: center;";
            $isImage = true;
            break;

        case 'gradient':
            $background =
                "background: linear-gradient(" .
                ($contents['bg_gradient_direction'] ?? 'to right') . ", " .
                ($contents['bg_gradient_start'] ?? '#fff') . ", " .
                ($contents['bg_gradient_end'] ?? '#000') . ")";
            break;
    }

    $justify = match ($align) {
        'left' => 'justify-content: flex-start;',
        'right' => 'justify-content: flex-end;',
        default => 'justify-content: center;',
    };
@endphp


<section style="{{ $background }}" class="relative {{ $widget->title }}">
    <div class="{{ $settings['width'] ?? 'container' }} mx-auto px-6 relative z-20">

        {{-- Heading --}}
        @if(!empty($contents['body']))
            <div style="display:flex; {{ $justify }}">
                <h1 class="heading"
                    style="text-align:{{ $align }};
                           color:{{ $contents['text_color'] ?? '#000' }};
                           font-size:{{ $fontSize }}px;">
                    {{ $contents['body'] }}
                </h1>
            </div>
        @endif

        {{-- Sub Heading --}}
        @if(!empty($contents['sub_heading']))
            <p class="sub_heading mt-2"
               style="text-align:{{ $align }};
                      color:{{ $contents['sub_heading_color'] ?? '#666' }}">
                {{ $contents['sub_heading'] }}
            </p>
        @endif

        @php
            $items = $contents['items'] ?? [];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">

            @forelse($items as $item)

                <div class="p-6 bg-white shadow rounded text-center">

                    {{-- ICON --}}
                    @if(!empty($item['icon']))
                        <i class="{{ $item['icon'] }} text-4xl mb-3"></i>
                    @endif

                    {{-- IMAGE --}}
                    @if(!empty($item['image']))
                        <img src="{{ asset('storage/' . $item['image']) }}"
                             class="w-full h-48 object-cover rounded mb-4"
                             alt="{{ $item['text'] ?? 'Image' }}">
                    @endif

                    {{-- TITLE (FEATURE NAME) --}}
                    <h3 class="font-bold text-lg">
                        {{ $item['text'] ?? 'Untitled' }}
                    </h3>

                    {{-- DESCRIPTION --}}
                    <p class="mt-2 text-gray-700">
                        {{ $item['description'] ?? '' }}
                    </p>

                </div>

            @empty
                <p class="text-center text-gray-500 col-span-3">No items available</p>
            @endforelse

        </div>

    </div>
</section>





