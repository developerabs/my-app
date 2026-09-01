  <!-- Start::main-sidebar-header -->
            <div class="main-sidebar-header">
                <a href="{{route('landlord.dashboard')}}" class="header-logo">
                    <img style="height: 3rem;" src="{{asset('landlord/images/logo.png')}}" alt="logo">
                    <img src="{{asset('backend')}}/assets/images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
                    {{-- <img src="{{asset('backend')}}/assets/images/brand-logos/desktop-white.png" alt="logo" class="desktop-white"> --}}
                    <img src="{{asset('backend')}}/assets/images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
                </a>
            </div>
            <!-- End::main-sidebar-header -->

            <!-- Start::main-sidebar -->
            <div class="main-sidebar" id="sidebar-scroll">

                <!-- Start::nav -->
                <nav class="main-menu-container nav nav-pills flex-column sub-open">
                    <div class="slide-left" id="slide-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
                    </div>
                    <ul class="main-menu active">
                        @foreach (getLandlordMenu() as $menu)
                            @if (isset($menu['sub_menu']))
                                <li id="{{ $menu['id'] }}" class="slide has-sub {{ isActiveMenu($menu) ? 'active open' : '' }}">
                                    <a href="javascript:void(0);" class="side-menu__item main_menu {{ isActiveMenu($menu) ? 'active' : '' }}">
                                        {!! $menu['icon'] ?? '' !!}
                                        <span class="side-menu__label">{{ __($menu['title']) }}</span>
                                        <i class="fe fe-chevron-right side-menu__angle"></i>
                                    </a>
                                    <ul class="slide-menu child1 menu_list">
                                        @foreach ($menu['sub_menu'] as $submenu)
                                            <li id="{{ $submenu['id'] }}" class="slide">
                                                @if(isset($submenu['method']) && $submenu['method'] === 'POST')
                                                    <a href="javascript:void(0);" class="side-menu__item"
                                                    onclick="document.getElementById('{{ $submenu['id'] }}Form').submit();">
                                                        {{ __($submenu['title']) }}
                                                    </a>
                                                    <form action="{{ route($submenu['route']) }}" id="{{ $submenu['id'] }}Form" method="POST">
                                                        @csrf
                                                        @method('POST')
                                                    </form>
                                                @else
                                                    <a href="{{ route($submenu['route']) }}" class="side-menu__item  {{ isActiveMenu($submenu) ? 'active' : '' }}">
                                                        {{ __($submenu['title']) }}
                                                    </a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li id="{{ $menu['id'] }}" class="slide {{ isActiveMenu($menu) ? 'active' : '' }}">
                                    <a href="{{ route($menu['route']) }}" class="side-menu__item {{ isActiveMenu($menu) ? 'active' : '' }}">
                                        {!! $menu['icon'] ?? '' !!}
                                        <span class="side-menu__label">{{ __($menu['title']) }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach

                        <!-- End::slide -->
                    </ul>
                    <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
                </nav>
                <!-- End::nav -->
            </div>
            <!-- End::main-sidebar -->
