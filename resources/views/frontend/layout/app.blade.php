<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'MancoSync') — Baca Manga & Comic Online</title>
    <meta name="description" content="@yield('meta_description', 'Baca manga, manhwa, dan manhua terbaru secara gratis di MancoSync. Update setiap hari!')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/frontend/app.css') }}?v={{ filemtime(public_path('css/frontend/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/frontend/portal.css') }}?v={{ filemtime(public_path('css/frontend/portal.css')) }}">
    @stack('styles')
</head>
<body>

    {{-- Splash Screen --}}
    @include('frontend.partials.splash')

    {{-- Main Website Wrapper --}}
    <div id="main-site" style="display:none;">

        {{-- Navbar --}}
        @include('frontend.partials.navbar')

        {{-- Page Content --}}
        <main class="site-main">
            @yield('content')
        </main>

        {{-- Footer --}}
        @include('frontend.partials.footer')

        {{-- Mobile Bottom Nav --}}
        @include('frontend.partials.mobile-nav')

    </div>

    {{-- Theme Toggle Floating Button --}}
    <button id="theme-toggle" class="theme-toggle-btn" title="Ganti Tema" aria-label="Toggle Dark/Light Mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/frontend/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
