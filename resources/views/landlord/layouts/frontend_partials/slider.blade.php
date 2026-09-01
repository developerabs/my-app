@php
    $contents = $widget->content ?? '{}';
    $settings = $widget->settings ?? '{}';
    $sectionClass = 'section_' . $widget->id;
@endphp

@if($settings['show_title_on_top'] ?? false)
    <div class="my-16 text-center after:content-[''] after:w-10 after:h-1 after:bg-gradient-to-r after:from-[#00ADEE] after:to-[#3E458E] after:block after:mx-auto after:mt-2 hover:after:w-60 hover:after:transition-all after:duration-300">
        <h2 class="text-2xl md:text-3xl font-bold uppercase text-center">{{ $widget->title ?? '' }}</h2>
        <p class="italic text-sm md:text-lg">{{ $widget->subtitle ?? '' }}</p>
    </div>
@endif

<section class="{{ $sectionClass }} w-full">
    <div class="{{ $settings['width'] ?? 'container' }} mx-auto">
        <div class="{{ $sectionClass . '_slider' }}">
            @foreach ($contents['items'] ?? [] as $item)
              <a href="{{ $item['url'] ?? '#' }}" class="relative mx-2">
                  @if(!empty($item['image']))
                    <img src="{{ asset('storage') }}/{{ $item['image'] ?? '' }}" alt="{{ $item['title'] ?? '' }}" class="w-full">
                  @endif
                    @if(!empty($item['icon_class']))
                        <div class="p-10 bg-gray-300 flex items-center justify-center rounded">
                            <i class="{{ $item['icon_class'] ?? '' }} text-4xl text-red-800"></i>
                        </div>
                    @endif
                  @if($settings['show_caption'] ?? false)
                    <h3 style="position: absolute; bottom: 10%; left: 50%; transform: translateX(-50%); color:{{ $settings['caption_color'] ?? '#fff' }}; font-size: 16px; ">{{ $item['text'] ?? '' }}</h3>
                  @endif
                </a>  
            @endforeach
        </div>
    </div>
</section>


<script>
window.addEventListener('load', () => {

    const slidesDefault = {{ (int) ($settings['slides_to_show'] ?? 1) }};
    const slidesTablet = {{ (int) ($settings['slides_to_show_tablet'] ?? 1) }};
    const slidesMobile = {{ (int) ($settings['slides_to_show_mobile'] ?? 1) }};

    $('.{{ $sectionClass . '_slider' }}').slick({
        dots: {{ $settings['dots'] ? 'true' : 'false' }},
        infinite: {{ $settings['infinite_loop'] ? 'true' : 'false' }},
        speed: 300,
        autoplay: {{ $settings['autoplay'] ? 'true' : 'false' }},
        autoplaySpeed: {{ $settings['autoplay_speed'] ?? 3000 }},
        slidesToShow: slidesDefault,
        slidesToScroll: 1,

        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: slidesTablet,
                    slidesToScroll: 1,
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: slidesMobile,
                    slidesToScroll: 1
                }
            }
        ]
    });
});
</script>