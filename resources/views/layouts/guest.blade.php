<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1572E8">

    <title>@yield('title', 'Sign in') | {{ config('app.name', 'MoCU-PRMS') }}</title>

    @include('layouts.partials.kaiadmin-styles')
    <link rel="stylesheet" href="{{ asset('css/prms-theme.css') }}?v=27">
    <link rel="stylesheet" href="{{ asset('css/prms-kaiadmin-bridge.css') }}?v=14">
</head>
<body class="prms-guest-page">

    <a href="#prms-auth-main" class="prms-skip-link">{{ __('Skip to main content') }}</a>

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

        <footer class="prms-auth-site-footer text-center py-3">
            @include('layouts.partials.prms-copyright')
        </footer>
    </div>

    @include('layouts.partials.kaiadmin-scripts', ['full' => false])
    @stack('scripts')
</body>
</html>
