<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1572E8">

    <title>@yield('title', 'Sign in') | {{ config('app.name', 'MoCU-PRMS') }}</title>

    @include('layouts.partials.kaiadmin-styles')
    <link rel="stylesheet" href="{{ asset('css/prms-theme.css') }}?v=57">
    <link rel="stylesheet" href="{{ asset('css/prms-kaiadmin-bridge.css') }}?v=65">
    <link rel="stylesheet" href="{{ asset('css/prms-welcome.css') }}?v=39">
</head>
<body class="prms-guest-page prms-landing">

    <a href="#prms-auth-main" class="prms-skip-link">{{ __('Skip to main content') }}</a>

    <header class="main-header prms-landing-header position-fixed top-0 start-0 end-0 w-100" style="z-index: 1030;">
        @include('layouts.partials.prms-site-topnav', [
            'navId' => 'guestNav',
            'innerClass' => 'prms-landing-nav-inner',
            'alwaysExpanded' => true,
        ])
    </header>

    <div class="prms-auth-page-shell">
        <div class="prms-auth-page-main">
            <div class="login-box prms-auth-shell" id="prms-auth-main">
                <main class="prms-auth-card card border-0" role="main">
                    <div class="prms-auth-main-panel">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <footer class="text-center">
            <div class="container">
                @include('layouts.partials.prms-copyright', ['muted' => false, 'class' => 'opacity-75'])
            </div>
        </footer>
    </div>

    @include('layouts.partials.kaiadmin-scripts', ['full' => false])
    <x-prms-flash-messages />
    <x-prms-toast-host />
    @stack('scripts')
</body>
</html>
