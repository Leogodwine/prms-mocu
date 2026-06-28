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
    <link rel="stylesheet" href="{{ asset('css/prms-theme.css') }}?v=19">
    <link rel="stylesheet" href="{{ asset('css/prms-kaiadmin-bridge.css') }}?v=14">
    <link rel="stylesheet" href="{{ asset('css/prms-welcome.css') }}?v=35">
    @stack('styles')
</head>
<body class="prms-landing">

    <a href="#prms-landing-main" class="prms-skip-link">{{ __('Skip to main content') }}</a>

    <header class="main-header prms-landing-header position-fixed top-0 start-0 end-0 w-100" style="z-index: 1030;">
        <nav class="navbar navbar-header navbar-expand border-bottom prms-public-nav prms-public-topnav w-100" data-background-color="dark" aria-label="{{ __('Site navigation') }}">
            <div class="prms-landing-nav-inner">
                <a class="navbar-brand prms-landing-nav-logo d-flex align-items-center text-white flex-shrink-0" href="{{ route('home') }}">
                    <img src="{{ asset('images/mocu_logo.png') }}" alt="" class="prms-brand-logo prms-brand-logo--nav" decoding="async" aria-hidden="true">
                    <span class="prms-landing-nav-product">{{ config('app.name', 'MoCU-PRMS') }}</span>
                </a>

                <div class="navbar-collapse justify-content-end prms-landing-nav-menu" id="landingNav">
                    <ul class="navbar-nav align-items-lg-center gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link text-white {{ request()->routeIs('home') ? 'fw-bold' : '' }}" href="{{ route('home') }}">{{ __('Home') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white {{ request()->routeIs('public.research.*') ? 'fw-bold' : '' }}" href="{{ route('public.research.index') }}">
                                {{ __('Repository') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            @auth
                                <a class="nav-link text-white {{ request()->routeIs('dashboard') ? 'fw-bold' : '' }}" href="{{ route('dashboard') }}">
                                    {{ __('Dashboard') }}
                                </a>
                            @else
                                <a class="nav-link text-white {{ request()->routeIs('login') ? 'fw-bold' : '' }}" href="{{ route('login') }}">
                                    {{ __('Login') }}
                                </a>
                            @endauth
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main id="prms-landing-main">
        @yield('content')
        @yield('sections')
    </main>

    <footer class="text-center">
        <div class="container">
            <p class="mb-0 small opacity-75">
                &copy; {{ date('Y') }} {{ __('Moshi Co-operative University (MoCU)') }} · {{ config('app.name', 'MoCU-PRMS') }}
            </p>
        </div>
    </footer>

    @include('layouts.partials.kaiadmin-scripts', ['full' => false])
    <script src="{{ asset('js/prms-auto-filter.js') }}?v=1" defer></script>
    @stack('scripts')
</body>
</html>
