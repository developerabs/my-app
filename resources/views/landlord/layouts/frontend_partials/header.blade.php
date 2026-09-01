    @php
        $content = $LandlordHeader->content ?? '{}';
        $settings = $LandlordHeader->settings ?? '{}';
    @endphp    
    
    <header class="{{ $settings['bottom_border'] ?? false ? 'border-b border-gray-300' : ''}} {{ $settings['position'] ?? 'sticky'}} top-0 z-50 {{ $settings['shadow'] ?? false ? 'shadow-xl' : ''}}" style="width: 100%; @if($settings['background_type'] ?? 'color' == 'color') background-color: {{ $settings['background_color'] ?? '#fff' }} @endif">
        <div class="{{ $settings['width'] ?? 'container'}} mx-auto px-4 py-2 flex justify-between items-center">
            <a class="w-1/3 flex gap-2 items-center" href="{{ route('landlord.home') }}">
                @if($content['logo_type'] == 'custom')
                        <img src="{{ asset('storage/' . $content['custom_logo']) }}" class="h-14 w-auto"
                            alt="Custom Logo">
                @else
                    <img src="{{ $LandlordGeneralSettings['company_logo'] ?? false ? asset('storage/' . $LandlordGeneralSettings['company_logo']) : asset('landlord/images/logo.png') }}" class="h-14 w-auto"
                        alt="Default Logo">
                @endif
                @if ($content['show_title'] ?? false)
                    {{ $LandlordGeneralSettings['site_title'] ?? config('app.name', 'Laravel') }}
                @endif
            </a>
            <style>
                .dynamic_menu {
                    color: {{ $content['menu_text_color'] ?? '#212529' }} !important;
                }
                .dynamic_menu:hover {
                    color: {{ $content['menu_hover_text_color'] ?? '#1a1d2f' }} !important;
                }
            </style>
            <nav class="md:flex items-center justify-end w-2/3 hidden">
                <ul class="flex space-x-8">
                    @if($content['menus'] ?? false)
                        @foreach($content['menus'] ?? [] as $menu)
                        <li><a href="{{ $menu['url'] }}" {{$menu['type'] == 'custom' ? 'target="_blank"' : ''}} class="dynamic_menu">{{ $menu['label'] }}</a></li>
                        @endforeach
                    @endif

                    @if ($content['show_language_switcher'] ?? false)
                        <li class="relative group">
                            <!-- Dropdown Trigger -->
                            <a href="#" class="text-gray-600 hover:text-gray-900 flex items-center">
                                <i class="fa-solid fa-earth-asia mr-2 bg-gradient-to-r from-[#00ADEE] to-[#3E458E] bg-clip-text text-transparent"></i>
                                <span>English</span>
                                <i class="fa-solid fa-chevron-down ml-1 text-xs"></i>
                            </a>

                            <!-- Dropdown Menu -->
                            <ul class="absolute left-0 mt-2 w-44 bg-white border border-gray-200 shadow-lg rounded-lg 
                                    opacity-0 invisible group-hover:opacity-100 group-hover:visible 
                                    transform translate-y-2 group-hover:translate-y-0 
                                    transition-all duration-200 ease-in-out">
                                
                                <li>
                                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-t-lg">
                                        <span class="ml-2">English</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        <span class="ml-2">Spanish</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-b-lg">
                                        <span class="ml-2">French</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </nav>
            <div class="md:hidden block">
                <!-- Burger Button -->
                <button id="burger_btn" class="text-gray-600 hover:text-gray-900" onclick="toggleMenu()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Overlay -->
                <div id="overlay"
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-500 z-40"
                    onclick="toggleMenu()"></div>

                <!-- Slide Menu -->
                <nav id="slide-out"
                    class="fixed top-0 right-0 w-3/4 sm:w-1/2 h-screen bg-white shadow-2xl p-6 transform transition-transform duration-500 ease-in-out translate-x-full z-50">

                    <!-- Close Button -->
                    <button id="close_btn" class="absolute top-4 left-4 text-gray-600 hover:text-gray-900" onclick="toggleMenu()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Menu Links -->
                    <ul class="mt-16 space-y-6 text-lg font-medium">
                        @if($content['menus'] ?? false)
                            @foreach($content['menus'] ?? [] as $menu)
                            <li><a onclick="toggleMenu()" href="{{ $menu['url'] }}" {{$menu['type'] == 'custom' ? 'target="_blank"' : ''}} class="dynamic_menu">{{ $menu['label'] }}</a></li>
                            @endforeach
                        @endif
                        
                        @if ($content['show_language_switcher'] ?? false)
                        <!-- Language Dropdown -->
                        <li class="relative" id="mobileLangDropdown">
                            <!-- Trigger -->
                            <button class="flex items-center w-full text-gray-600 hover:text-gray-900 focus:outline-none">
                                🌐 <span class="ml-2">Language</span>
                                <i class="fa-solid fa-chevron-down ml-1 text-xs"></i>
                            </button>

                            <!-- Dropdown -->
                            <ul class="hidden mt-2 w-44 bg-white border border-gray-200 shadow-lg rounded-lg z-50">
                                <li>
                                    <a href="#" onclick="toggleMenu()" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-t-lg">
                                        🇺🇸 <span class="ml-2">English</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" onclick="toggleMenu()" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        🇪🇸 <span class="ml-2">Spanish</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" onclick="toggleMenu()" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-b-lg">
                                        🇫🇷 <span class="ml-2">French</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>
    </header>