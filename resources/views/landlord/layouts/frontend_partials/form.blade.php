@php
    $contents = $widget->content ?? [];
    $settings = $widget->settings ?? [];

    $align = $contents['text_align'] ?? 'center';
    $fontSize = $contents['font_size'] ?? 40;

    $background = '';
@endphp

@php
    switch ($contents['bg_type'] ?? 'color') {

        case 'color':
            $background = 'background-color: ' . ($contents['bg_color'] ?? '#ffffff');
            break;

        case 'image':
            $background = "background-image: url(" . asset('storage/' . ($contents['bg_image'] ?? '')) . ");
                           background-size: cover; background-position: center;";
            break;

        case 'gradient':
            $background =
                "background: linear-gradient(" .
                ($contents['bg_gradient_direction'] ?? 'to right') . ", " .
                ($contents['bg_gradient_start'] ?? '#fff') . ", " .
                ($contents['bg_gradient_end'] ?? '#000') . ")";
            break;
    }

    // Text alignment
    $justify = match ($align) {
        'left' => 'justify-content: flex-start;',
        'right' => 'justify-content: flex-end;',
        default => 'justify-content: center;',
    };
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


    <form>
        <div class="grid grid-cols-12 gap-6">

            @forelse($items as $item)
                <div class="col-span-12 md:col-span-{{ $item['width'] ?? 12 }}">

                    {{-- LABEL --}}
                    <label class="block font-semibold mb-1">
                        {{ $item['field_label'] }}
                        @if(!empty($item['is_required']))
                            <span class="text-red-500">*</span>
                        @endif
                    </label>

                    @php $type = $item['field_type']; @endphp

                    {{-- EMAIL FIELD --}}
                    @if($type == 'email')
                        <input type="email"
                               name="{{ $item['field_name'] }}"
                               value="{{ $item['field_value'] }}"
                               @if($item['is_required']) required @endif
                               class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring focus:ring-blue-300">

                    {{-- NUMBER FIELD --}}
                    @elseif($type == 'number')
                        <input type="number"
                               name="{{ $item['field_name'] }}"
                               value="{{ $item['field_value'] }}"
                               @if($item['is_required']) required @endif
                               class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring focus:ring-blue-300">

                    {{-- TEXT FIELD --}}
                    @elseif($type == 'text')
                        <input type="text"
                               name="{{ $item['field_name'] }}"
                               value="{{ $item['field_value'] }}"
                               @if($item['is_required']) required @endif
                               class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring focus:ring-blue-300">

                    {{-- TEXTAREA --}}
                    @elseif($type == 'textarea')
                        <textarea name="{{ $item['field_name'] }}"
                                  rows="4"
                                  @if($item['is_required']) required @endif
                                  class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring focus:ring-blue-300">{{ $item['field_value'] }}</textarea>

                    {{-- SELECT FIELD --}}
                    @elseif($type == 'select')

                        @php
                            $options = array_map('trim', explode(',', $item['field_value']));
                        @endphp

                        <select name="{{ $item['field_name'] }}" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring focus:ring-blue-300">

                            @foreach($options as $option)
                                @php
                                    $data = array_map('trim', explode('=', $option));
                                @endphp

                                <option value="{{ $data[0] ?? '' }}">
                                    {{ $data[1] ?? $data[0] }}
                                </option>
                            @endforeach

                        </select>

                    @endif

                </div>
            @empty

                <p class="text-center text-gray-500 col-span-12">
                    No form fields available
                </p>

            @endforelse

        </div>

        {{-- SUBMIT BUTTON --}}
        <div class="mt-6 text-center">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow">
                Submit
            </button>
        </div>

    </form>
</div>

</section>


