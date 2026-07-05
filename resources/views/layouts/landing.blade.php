<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1572E8">
    <meta name="description" content="{{ __('Project and research management for Moshi Co-operative University — proposals, theses, dissertations, and system projects.') }}">

    <title>@yield('title', 'Welcome') | {{ config('app.name', 'MoCU-PRMS') }}</title>

    @include('layouts.partials.kaiadmin-styles')
    <link rel="stylesheet" href="{{ asset('css/prms-theme.css') }}?v=41">
    <link rel="stylesheet" href="{{ asset('css/prms-kaiadmin-bridge.css') }}?v=45">
    <link rel="stylesheet" href="{{ asset('css/prms-welcome.css') }}?v=38">
    @stack('styles')
</head>
<body class="prms-landing">

    <a href="#prms-landing-main" class="prms-skip-link">{{ __('Skip to main content') }}</a>

    <header class="main-header prms-landing-header position-fixed top-0 start-0 end-0 w-100" style="z-index: 1030;">
        @include('layouts.partials.prms-site-topnav', [
            'navId' => 'landingNav',
            'innerClass' => 'prms-landing-nav-inner',
            'alwaysExpanded' => true,
        ])
    </header>

    <main id="prms-landing-main">
        @yield('content')
        @yield('sections')
    </main>

    <footer class="text-center">
        <div class="container">
            @include('layouts.partials.prms-copyright', ['muted' => false, 'class' => 'opacity-75'])
        </div>
    </footer>

    @include('layouts.partials.kaiadmin-scripts', ['full' => false])
    <script src="{{ asset('js/prms-auto-filter.js') }}?v=1" defer></script>
    @stack('scripts')
</body>
</html>
