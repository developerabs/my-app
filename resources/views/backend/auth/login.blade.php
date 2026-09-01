<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login- {{ $general_settings['site_title'] ?? $general_settings['company_name'] ?? 'SheraziPOS' }}</title>
    <link rel="icon" href="{{ file_url($general_settings['favicon'] ?? 'backend/assets/images/brand-logos/favicon.ico') }}" type="image/x-icon">
    <link href="{{url('backend')}}/assets/css/icons.css" rel="stylesheet" >
    <link rel="stylesheet" href="{{ url('auth/assets/app.css') }}">
    <script src="{{ url('auth/assets/app.js')}}"></script>
</head>
<body>
    <div class="h-screen flex p-4 items-center justify-center bg-[#D9DBF3] relative">
        <div class="w-full md:w-2/5 xl:w-1/3 px-[2px] bg-gradient-to-r from-[#00ADEE] to-[#3E458E] rounded-lg z-30 ">
            <div class="bg-[#fff] w-full p-8  md:p-12 rounded-lg shadow-lg z-40">
                <div class="flex items-center justify-center mb-4">
                    <img src="{{ !empty($general_settings['company_logo']) ? file_url($general_settings['company_logo']) : url('landlord/images/logo.png') }}" alt="Posizer Logo" style="max-height: 70px; width:auto;">
                </div>
                <h1 class="text-xl text-center uppercase text-[#00ADEE] font-bold mb-4"> Login</h1>
                <form action="{{ route('tenant.login') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <input type="text" name="login" id="login" class="w-full border bg-[#D9DBF3] border-gray-400 rounded-lg p-2" placeholder="Username or Email" required>
                    </div>
                    @error('login')
                        <p class="text-red-500 mb-4">{{ $message }}</p>
                    @enderror
                    <div class="relative mb-4">
                        <input type="password" name="password" id="password" class="w-full border bg-[#D9DBF3] border-gray-400 rounded-lg p-2 pr-10" placeholder="Password" required style="padding-right: 2.5rem;">
                        <span onclick="togglePassword('#password', '#password-icon')" style="position: absolute; right:10px; top:50%; transform: translateY(-50%); cursor: pointer;">
                            <i id="password-icon" class="fa-solid fa-eye"></i>
                        </span>
                    </div>
                    @error('password')
                        <p class="text-red-500 mb-4">{{ $message }}</p>
                    @enderror
                    <div class="flex items-center mb-4">
                        <input type="checkbox" name="remember" id="remember" class="mr-2 rounded">
                        <label for="remember" class="text-sm text-gray-600">Remember Me</label>
                    </div>
                    <button type="submit" class="bg-gradient-to-r from-[#00ADEE] to-[#3E458E] text-white w-full p-2 rounded-lg">Login</button>
                </form>
            </div>
        </div>
        <img class="absolute top-1/2 left-1/2 h-screen transform -translate-x-1/2 -translate-y-1/2 opacity-35 z-10" src="{{ url('landlord/images/abstractbg.png') }}" alt="lock">
    </div>

    <script src="{{url('js/passwordToggle.js')}}"></script>
</body>
</html>
