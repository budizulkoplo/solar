{{-- <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html> --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"> <!--begin::Head-->
@props(['menu'])
<head>
    @include('partials.head')
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}"/>
    @if (isset($csscustom))
        {{ $csscustom }}
    @endif
    @vite(['resources/js/app.js'])
    <!-- Scripts -->
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
</head> <!--end::Head--> <!--begin::Body-->
{{-- sidebar-mini  --}}
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary" id="bdy"> <!--begin::App Wrapper-->
    <div class="app-wrapper"> <!--begin::Header-->
        @include('layouts.navigation')
        <x-sidebar/>{{-- <x-sidebar :hasMenu="$menu"/> --}}
        <main class="app-main"> <!--begin::App Content Header-->
            @if (Hash::check(12345678, auth()->user()->password))
            <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
                <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </symbol>
                <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                </symbol>
                <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </symbol>
            </svg>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"/></svg>
                <div>
                    Demi keamanan data Anda, Dimohon untuk segera mengganti password dengan cara klik gambar profile -> edit (pojok kanan atas).
                </div>
            </div>
            @endif
            {{ $slot }}
        </main> 
        <footer class="app-footer d-flex justify-content-between align-items-center px-3">
            <div>
                <strong>{{ date('Y') }} &copy;</strong>
                <img src="{{ asset('piclogo.png') }}" alt="Developer Logo" height="25">
            </div>
            <div id="jam-indonesia" class="text-muted"></div>
        </footer>

<script>
    function updateJam() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', 
                          day: 'numeric', hour: '2-digit', minute: '2-digit', 
                          second: '2-digit' };
        document.getElementById('jam-indonesia').textContent = 
            now.toLocaleDateString('id-ID', options);
    }
    updateJam();
    setInterval(updateJam, 1000);
</script>


    </div> 
    
</body><!--end::Body-->
    @include('partials.script')
    @if (isset($jscustom))
        {{ $jscustom }}
    @endif
    
</html>
