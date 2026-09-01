@php
    $contents = $widget->content ?? [];
    $settings = $widget->settings ?? [];

    $align = $contents['text_align'] ?? 'center';
    $fontSize = $contents['font_size'] ?? 40;

    $background = '';
@endphp


<section style="{{ $background }}" class="relative {{ $widget->title }}">
 <div class="{{ $settings['width'] ?? 'container' }} mx-auto px-6 py-10 relative z-20">
        {{-- Form Heading --}}
        @if(!empty($contents['body']))
            <h2 class="text-3xl font-bold mb-4 text-{{ $align }}">
                {{ $contents['body'] }}
            </h2>
        @endif

        @if(!empty($contents['sub_heading']))
            <p class="text-gray-600 mb-6 text-{{ $align }}">
                {{ $contents['sub_heading'] }}
            </p>
        @endif

        @php
            $items = $contents['items'] ?? [];
        @endphp

   <div class="bg-white shadow-lg rounded-xl p-8">

            @if(!empty($widget['title']))
            <h1 class="text-2xl font-bold text-center mb-2">
                {{ $widget['title'] }}
            </h1>
           @endif

        @if(!empty($widget['subtitle']))
            <p class="text-center text-gray-500 mb-6">
                {{ $widget['subtitle'] }}
            </p>
        @endif

           <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">

            @forelse($items as $item)

                <div class="p-6 bg-white shadow rounded text-center">


                   {{-- DESCRIPTION --}}
                    <p class="mt-2 text-gray-700">
                        {!! $item['description'] ?? '' !!}
                    </p>


                </div>

            @empty
                <p class="text-center text-gray-500 col-span-3">No items available</p>
            @endforelse

        </div>
 </div>
</section>


