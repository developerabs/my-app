@php

    $contents = $widget->content ?? '{}';
    $settings = $widget->settings ?? '{}';

    $sectionClass = 'section_' . $widget->id;

    $background = '';
    $isImage = false;
    $overlay = '';
    $justify = '';
    switch ($contents['text_align'] ?? 'center') {
        case 'left':
            $justify = 'justify-content: flex-start;';
            break;
        case 'center':
            $justify = 'justify-content: center;';
            break;
        case 'right':
            $justify = 'justify-content: flex-end;';
            break;
        default:
            $justify = 'justify-content: flex-center;';
            break;
    }

    switch ($contents['bg_type'] ?? 'color') {
        case 'color':
            $background = 'background-color: ' . (!empty($contents['bg_color']) ? $contents['bg_color'] : '#ffffff') . ';';
            break;
        case 'image':
            $background = 'background-image: url(' . asset('storage') .'/'. $contents['bg_image'] . '); background-size:' . $contents['bg_image_size'] . '; background-position:' . $contents['bg_image_position'] . '; background-repeat:' . $contents['bg_image_repeat'] . ';';
            $isImage = true;
            break;
        case 'gradient':
            $gradientStart = $contents['bg_gradient_start'] ?? '#ffffff';
            $gradientEnd = $contents['bg_gradient_end'] ?? '#000000';
            $gradientDirection = $contents['bg_gradient_direction'] ?? 'to right';
            $background = "background: linear-gradient({$gradientDirection}, {$gradientStart}, {$gradientEnd});";
            break;
        default:
            break;
    }
@endphp
<style>
    .fimage1{
        animation: topBottom 4s infinite alternate;
    }
    .fimage2{
        animation: topBottom 4s infinite alternate-reverse;
    }

    @keyframes topBottom {
        0% {
            transform: translateY(0);
        }
        100% {
            transform: translateY(35%);
        }
    }


    .{{ $sectionClass }} .btn{
        background: {{ $contents['button_color'] ?? '#ffffff' }};
        padding: 10px 20px;
        border-radius: 5px;
        color: {{ $contents['button_text_color'] ?? '#000000' }};
        border: 1px solid {{ $contents['button_color'] ?? '#ffffff' }};
    }
    .{{ $sectionClass }} .btn:hover{
        background: {{ $contents['button_hover_color'] ?? '#ffffff' }};
        color: {{ $contents['button_hover_text_color'] ?? '#ffffff' }};
    }
    .{{ $sectionClass }} .section_btn{
        background: {{ $contents['button_color'] ?? '#ffffff' }};
        padding: 10px 20px;
        border-radius: 5px;
        color: {{ $contents['button_text_color'] ?? '#000000' }};
        border: 1px solid {{ $contents['button_color'] ?? '#ffffff' }};
    }
    .{{ $sectionClass }} .section_btn:hover{
        background: {{ $contents['button_hover_color'] ?? '#ffffff' }};
        color: {{ $contents['button_hover_text_color'] ?? '#ffffff' }};
    }
    @media (max-width: 575.98px) {
        .{{ $sectionClass }} {
            padding: 40px 0;
        }
        .{{ $sectionClass }} .heading{
            font-size: 24px !important;
        }
        .{{ $sectionClass }} .sub_heading{
            font-size: 14px !important;
        }
        .{{ $sectionClass }} .fimage1,
        .{{ $sectionClass }} .fimage2{
            display: none;
        }
    }

    @media (min-width: 576px) and (max-width: 767.98px) {
        .{{ $sectionClass }} {
            padding: 40px 0;
        }
        .{{ $sectionClass }} .heading{
            font-size: 28px !important;
        }
        .{{ $sectionClass }} .sub_heading{
            font-size: 18px !important;
        }
        .{{ $sectionClass }} .fimage1,
        .{{ $sectionClass }} .fimage2{
            display: none;
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        .{{ $sectionClass }} {
            padding: 40px 0;
        }
        .{{ $sectionClass }} .heading{
            font-size: {{ $contents['font_size'] ?? 36 }}px !important;
        }
        .{{ $sectionClass }} .sub_heading{
            font-size: 18px !important;
        }
        .{{ $sectionClass }} .fimage1,
        .{{ $sectionClass }} .fimage2{
            display: none;
        }
    }

    @media (min-width: 992px) and (max-width: 1199.98px) {
        .{{ $sectionClass }} {
            padding: 60px 0;
        }
        .{{ $sectionClass }} .heading{
            font-size: {{ $contents['font_size'] ?? 40 }}px !important;
        }
        .{{ $sectionClass }} .sub_heading{
            font-size: 20px !important;
        }
    }

    @media (min-width: 1200px) {
        .{{ $sectionClass }} {
            padding: 80px 0;
        }
        .{{ $sectionClass }} .heading{
            font-size: {{ $contents['font_size'] ?? 42 }}px !important;
        }
        .{{ $sectionClass }} .sub_heading{
            font-size: 22px !important;
        }
    }
</style>

@if($settings['show_title_on_top'] ?? false)
    <div class="mb-4 mt-4 text-center after:content-[''] after:w-10 after:h-1 after:bg-gradient-to-r after:from-[#00ADEE] after:to-[#3E458E] after:block after:mx-auto after:mt-2 hover:after:w-60 hover:after:transition-all after:duration-300">
        <h2 class="text-2xl md:text-3xl font-bold uppercase text-center">{{ $widget->title ?? '' }}</h2>
        <p class="italic text-sm md:text-lg">{{ $widget->subtitle ?? '' }}</p>
    </div>
@endif

<section style="{{$background}}" class="{{ $sectionClass }} relative {{ Str::slug($widget->title) }}" id="{{ Str::slug($widget->title) }}">
    @if ($isImage)
        <div class="absolute inset-0 z-10" style="background: #000000; opacity: 0.5;">
        </div>
    @endif
    <div class="{{$settings['width'] ?? 'container'}} flex flex-col mx-auto px-6 relative z-30">
        @if($contents['show_customer_counter'] ?? false)
            @php
                $clients = DB::table('tenants')->count();
            @endphp
            <span class="text-white text-center font-semibold mb-4">Total Satisfied Customers {{ $clients ?? '1200' }}+</span>
        @endif

        <div class="w-full" style="display: flex; {{$justify}}  text-align: {{$contents['text_align'] ?? 'center'}};">
            <div style="max-width: 600px">
                <h1 class="heading" style="text-align: {{$contents['text_align'] ?? 'center'}}; color: {{$contents['text_color'] ?? ''}}; font-weight: {{$contents['font_weight'] ?? '600'}}};">{{ $contents['body'] ?? 'Section Title' }}</h1>
                <p class="sub_heading mt-3 pb-3 mb-10" style="color: {{$contents['sub_heading_color'] ?? '#000000'}}; text-align: {{$contents['text_align'] ?? 'center'}}; ">{{ $contents['sub_heading'] ?? '' }}</p>
               @if($contents['show_button'] ?? false)

                <a class="section_btn" @if($contents['button_type'] == 'internal') href="{{ $contents['button_route'] ?? '#' }}" @else target="_blank" href="{{ $contents['button_url'] ?? '#' }}" @endif>
                    {{ $contents['button_text'] ?? '' }}
                </a>
                @endif
            </div>
        </div>
        @if($contents['show_floating_image_1'] ?? false)
            <img style="position: absolute; left:0; bottom:0px; max-width: 200px;" class="fimage1" src="{{ asset('storage') }}/{{ $contents['floating_image_1'] ?? '' }}" alt="Image">
        @endif
        @if($contents['show_floating_image_2'] ?? false)
            <img style="position: absolute; right:0; bottom:0px; max-width: 200px;" class="fimage1" src="{{ asset('storage') }}/{{ $contents['floating_image_2'] ?? '' }}" alt="Image">
        @endif
    </div>

</section>
