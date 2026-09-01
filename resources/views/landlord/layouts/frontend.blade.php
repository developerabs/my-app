<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @if($LandlordGeneralSettings['favicon'] ?? false)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $LandlordGeneralSettings['favicon']) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('backend/assets/images/brand-logos/favicon.ico') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('landlord/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('landlord/plugins/slick/slick.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('landlord/plugins/slick/slick-theme.css') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('css')

    <style>
        html{
            scroll-behavior: smooth;
        }
        .label {
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .switch,
        .input:checked+.label .left,
        .input:not(:checked)+.label .right {
            pointer-events: all;
            cursor: pointer;
        }

        /* most of the stuff below is the same as the W3Schools stuff,
    but modified a bit to reflect changed HTML structure */

        .input {
            display: none;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #00ADEE;
            -webkit-transition: 0.4s;
            transition: 0.4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: 0.4s;
            transition: 0.4s;
        }

        input:checked+.label .slider {
            background-color: #3E458E;
        }

        input:focus+.label .slider {
            box-shadow: 0 0 1px #00ADEE;
        }

        input:checked+.label .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        /* styling to make it look like your screenshot */

        .left,
        .right {
            margin: 0 .5em;
            font-weight: bold;
            text-transform: uppercase;
            font-family: sans-serif;
        }

        .left {
            color: #00ADEE;
        }

        .right {
            color: #3E458E;
        }
    </style>
</head>

<body>
    {{-- Header Part --}}
    @include('landlord.layouts.frontend_partials.header')
    {{-- Hero Section --}}
    @yield('content')

    @include('landlord.layouts.frontend_partials.footer')
    <!-- Signup Modal (Tailwind) -->
    <div id="signupModal"
        class="fixed inset-0 z-50 hidden bg-black/30 backdrop-blur-sm flex items-center justify-center transition-opacity duration-300 opacity-0">

        <div id="signupModalContent"
            class="bg-white w-full max-w-3xl rounded-2xl shadow-lg p-8 relative transform scale-95 transition-transform duration-300 modal-content">

            <!-- Close Button -->
            <button onclick="closeModal('signupModal')"
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl">
                ✕
            </button>

            <!-- Modal Content -->
            <div class="text-center mb-6">
                <h3 class="uppercase font-bold text-2xl text-gray-700">Register Now</h3>
                <p class="text-gray-600">or Call Us: <a href="tel:1234567890" class="text-blue-500">123-456-7890</a></p>
            </div>

            <form action="#" method="POST" class="flex flex-col gap-4">
                <input class="border border-gray-300 rounded-lg px-4 py-2" name="company_name" type="text" placeholder="Company Name" required>
                <div class="w-full flex flex-col md:flex-row justify-between gap-4">
                    <input class="border border-gray-300 rounded-lg px-4 py-2 w-full md:w-1/2" name="phone_number" type="text" placeholder="Contact Number" required>
                    <input class="border border-gray-300 rounded-lg px-4 py-2 w-full md:w-1/2" type="email" placeholder="Email" required>
                </div>
                <div class="w-full flex flex-col md:flex-row justify-between gap-4">
                    <input class="border border-gray-300 rounded-lg px-4 py-2 w-full md:w-1/2" name="name" type="text" placeholder="Username" required>
                    <input class="border border-gray-300 rounded-lg px-4 py-2 w-full md:w-1/2" name="password" type="password" placeholder="Password" required>
                </div>
                <div class="w-full flex">
                    <input type="text" id="tenant" name="tenant" class="w-3/5 px-4 py-2 border border-gray-300 rounded-l-lg" placeholder="Subdomain">
                    <input type="text" disabled value="{{env('CENTRAL_DOMAIN')}}" class="w-2/5 px-4 py-2 border border-gray-300 bg-gray-200 rounded-r-lg" readonly>
                </div>
                <input type="text" name="custom_domain" placeholder="Set Custom Domain" class="border border-gray-300 rounded-lg px-4 py-2">
                <div class="w-full">
                    <p class="text-gray-600">If you want to set a custom domain (e.g. <code>example.com</code>), You have to put add A record to your DNS.</p>
                    <button type="button" class="text-blue-500" onclick="openModal('instructionsModal')">
                        Instructions
                    </button>
                </div>
                <div class="col-span-2 mt-4">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-[#00ADEE] to-[#3E458E] text-white py-2 px-4 rounded-lg font-semibold hover:opacity-90 transition">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Signup Modal (Tailwind) -->
    <div id="instructionsModal"
        class="fixed inset-0 z-50 hidden bg-black/30 backdrop-blur-sm flex items-center justify-center transition-opacity duration-300 opacity-0">

        <div id="signupModalContent"
            class="bg-white w-full max-w-3xl rounded-2xl shadow-lg p-8 relative transform scale-95 transition-transform duration-300 modal-content">

            <!-- Close Button -->
            <button onclick="closeModal('instructionsModal')"
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl">
                ✕
            </button>

            <!-- Modal Content -->
            <div class="text-center mb-6">
                <h3 class="uppercase font-bold text-2xl text-gray-700">Instructions</h3>
                <p class="text-gray-500">Please follow the instruction below:</p>
            </div>
            <div class="w-full">
                <ol class="list-decimal ml-6 mb-6 text-start">
                    <li>Go to your domain control panel</li>
                    <li>Add A record with the following IP: <code>163.223.240.102</code></li>
                    <li>After adding the records, you can check the status from the following link: <a class="text-blue-500" href="https://www.whatsmydns.net/" target="_blank">https://www.whatsmydns.net/</a></li>
                    <li>Please note that it may take 24 hours to propagate.</li>
                    <li>After that, you can test your domain</li>
                </ol>
                <table class="table-auto w-full mb-6">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 border border-gray-300 ">Type</th>
                            <th class="px-4 py-2 border border-gray-300 ">Host</th>
                            <th class="px-4 py-2 border border-gray-300 ">Value</th>
                            <th class="px-4 py-2 border border-gray-300 ">TTL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">A Record</td>
                            <td class="border border-gray-300 px-4 py-2">@</td>
                            <td class="border border-gray-300 px-4 py-2">163.223.240.102</td>
                            <td class="border border-gray-300 px-4 py-2">600</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">A Record</td>
                            <td class="border border-gray-300 px-4 py-2">www</td>
                            <td class="border border-gray-300 px-4 py-2">163.223.240.102</td>
                            <td class="border border-gray-300 px-4 py-2">600</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>





    <script src="{{ asset('landlord/js/jquery-3.7.1.min.js') }}"></script>
    <script type="text/javascript" src="{{asset('landlord/plugins/slick/slick.min.js')}}"></script>
    <script src="{{ asset('landlord/js/slider.js') }}"></script>

    <script>
    function toggleMenu() {
        const menu = document.getElementById('slide-out');
        const overlay = document.getElementById('overlay');

        // Slide effect
        menu.classList.toggle('translate-x-0');
        menu.classList.toggle('translate-x-full');

        // Fade effect for overlay
        if (overlay.classList.contains('opacity-0')) {
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            overlay.classList.add('opacity-100');
        } else {
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('pointer-events-none');
            }, 500); // match transition duration
        }
    }

    const mobileLangDropdown = document.getElementById('mobileLangDropdown');
    const langBtn = mobileLangDropdown.querySelector('button');
    const langMenu = mobileLangDropdown.querySelector('ul');

    langBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        langMenu.classList.toggle('hidden');
    });

    // Click outside to close dropdown
    document.addEventListener('click', (e) => {
        if (!mobileLangDropdown.contains(e.target)) {
            langMenu.classList.add('hidden');
        }
    });


    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = modal.querySelector('.modal-content');

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
        }, 10);

        document.body.classList.add('overflow-hidden'); // disable scroll
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = modal.querySelector('.modal-content');

        modal.classList.add('opacity-0');
        content.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300); // match transition duration

        document.body.classList.remove('overflow-hidden'); // enable scroll
    }
</script>

@stack('js')

</body>

</html>
