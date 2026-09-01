@php
    $contents = $LandlordFooter->content ?? '{}';
    $settings = $LandlordFooter->settings ?? '{}';

    $sectionClass = 'footer_' . $LandlordFooter->id;

    $background = '';
    $isImage = false;
    $overlay = '';
    switch ($contents['bg_type'] ?? 'color') {
        case 'color':
            $background = 'background-color: ' . $contents['bg_color'] . ';';
            break;
        case 'image':
            $background = 'background-image: url(' . asset('storage') .'/'. $contents['bg_image'] . '); background-size:' . $contents['bg_image_size'] . '; background-position:' . $contents['bg_image_position'] . '; background-repeat:' . $contents['bg_image_repeat'] . ';';
            $isImage = true;
            break;
        case 'gradient':
            $gradientStart = $contents['bg_gradient_start'] ?? '#ffffff';
            $gradientEnd = $contents['bg_gradient_end'] ?? '#000000';
            $gradientDirection = $contents['bg_gradient_direction'] ?? 'to right';
            $overlay = 'background: ' . $contents['overlay_color'] . ';' . 'opacity: ' . $contents['overlay_opacity'] . ';';
            $background = "background: linear-gradient({$gradientDirection}, {$gradientStart}, {$gradientEnd});";
            break;
        default:
            break;
    }
@endphp

<style>
    @media (max-width: 575.98px) {
        .{{ $sectionClass }} .footer_item{ 
            width: 100% !important;
        }
    }

    @media (min-width: 576px) and (max-width: 767.98px) {
        .{{ $sectionClass }} .footer_item{ 
            width: 100% !important;
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        .{{ $sectionClass }} .footer_item{ 
            width: 50% !important;
        }
    }

    @media (min-width: 992px) and (max-width: 1199.98px) {
        .{{ $sectionClass }} .footer_item{ 
            width: 50% !important;
        }
    }

    @media (min-width: 1200px) {
        .{{ $sectionClass }} .footer_item{ 
            width: 25% !important;
        }
    }
</style>


<footer class="w-full pt-20 pb-3 {{ $isImage ? 'relative' : ''}}" style="{{$background}} margin-top: 80px">

    @if ($isImage)
        <div class="absolute inset-0 z-10"
             style="background: {{ $contents['overlay_color'] ?? '#000000' }};
                    opacity: {{ $contents['overlay_opacity'] ?? '0.5' }};">
        </div>
    @endif

    <div class="{{$sectionClass}} {{$settings['width'] ?? 'container'}} flex flex-col md:flex-row justify-between gap-20 mx-auto px-6 {{ $isImage ? 'relative' : ''}} z-50">
        <div class="footer_item description_part flex flex-col gap-2">
            @if($contents['show_logo'] ?? false)
                <img src="{{ $LandlordGeneralSettings['company_logo'] ?? false ? asset('storage/' . $LandlordGeneralSettings['company_logo']) : asset('landlord/images/logo.png') }}"
                     class="w-32 h-auto"
                     alt="Logo">
            @endif

            <p class="text-md" style="color: {{$contents['text_color']}}">
                {{ $contents['description'] ?? '' }}
            </p>
            @if(!empty($contents['social_links']))
                <div class="social_links flex gap-2 mt-4">
                    @foreach ($contents['social_links'] as $socialLink)
                        <a href="{{ $socialLink['url'] ?? '#' }}" target="_blank">
                            <i class="{{$socialLink['icon_class'] ?? ''}}" style="color: {{$contents['icon_color'] ?? ''}}; font-size: 30px;"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if(!empty($contents['company_links']))
        <div class="footer_item company_links flex flex-col gap-2">
            <h5 style="color: {{$contents['text_color'] ?? ''}}; font-weight: 600; font-size:22px;">
                Company Links
            </h5>
            @foreach ($contents['company_links'] as $companyLink)
                <a href="{{ $companyLink['url'] ?? '#' }}" class="text-md" style="color: {{$contents['text_color'] ?? ''}}">
                    <i class="fa-solid fa-arrow-right"></i> {{ $companyLink['label'] ?? '' }}
                </a>
            @endforeach
        </div>
        @endif

        @if(!empty($contents['quick_links']))
        <div class="footer_item contact_links flex flex-col gap-2">
            <h5 style="color: {{$contents['text_color'] ?? ''}}; font-weight: 600; font-size:22px;">
                Contact Links
            </h5>
            @foreach ($contents['quick_links'] as $contactLink)
                <a href="{{ $contactLink['url'] ?? '#' }}" class="text-md" style="color: {{$contents['text_color'] ?? ''}}">
                    <i class="fa-solid fa-arrow-right"></i> {{ $contactLink['label'] ?? '' }}
                </a>
            @endforeach
        </div>
        @endif

        @if($contents['show_newsletter'] ?? false)
            <div class="footer_item newsletter_part flex flex-col gap-2">
                <h5 style="color: {{$contents['text_color'] ?? ''}}; font-weight: 600; font-size:22px;">
                    Newsletter
                </h5>
                <p class="text-md" style="color: {{$contents['text_color'] ?? ''}}">
                    To receive the latest news and updates from Sherazipos, please enter your email address below.
                </p>

                <form action="#" method="post" class="flex flex-col gap-2">
                    @csrf
                    <input type="email" name="email" placeholder="Enter your email"
                           class="w-full px-4 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-[#00ADEE] to-[#3E458E] text-white font-bold py-2 px-4 rounded-md">Subscribe
                    </button>
                </form>
            </div>
        @endif
    </div>
    @if(!empty($contents['copyright_text']))
        <div class="w-full text-center pt-3 relative z-50">
            <p class="text-md text-center" style="color: {{$contents['text_color'] ?? ''}}; opacity: 0.7;">
                Copyright &copy; {{ now()->format('Y') }} {{ $contents['copyright_text'] ?? '' }}
            </p>
        </div>
    @endif
</footer>
