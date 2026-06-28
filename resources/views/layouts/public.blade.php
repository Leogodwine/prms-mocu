<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="theme-color" content="#1572E8">
    <meta name="description" content="Browse approved research, projects, and reports from Moshi Co-operative University.">

    <title>@yield('title', 'Institutional Repository') | {{ config('app.name', 'MoCU-PRMS') }}</title>

    @include('layouts.partials.kaiadmin-styles')
    <link rel="stylesheet" href="{{ asset('css/prms-theme.css') }}?v=19">
    <link rel="stylesheet" href="{{ asset('css/prms-kaiadmin-bridge.css') }}?v=33">

    @stack('styles')
</head>
<body class="prms-public-page prms-kaiadmin-public">

<a href="#prms-public-main" class="prms-skip-link">Skip to main content</a>

<div class="wrapper">
    <div class="main-panel prms-public-main d-flex flex-column">
        <div class="main-header flex-shrink-0">
            <nav class="navbar navbar-header navbar-expand-lg border-bottom prms-public-nav prms-public-topnav" data-background-color="dark" aria-label="Public site navigation">
                <div class="container-fluid">
                    <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="{{ route('home') }}">
                        <img src="{{ asset('images/mocu_logo.png') }}" alt="Moshi Co-operative University" class="prms-brand-logo prms-brand-logo--nav">
                        <span class="prms-eyebrow text-white-50 mb-0" style="font-size: 0.65rem;">MoCU-PRMS</span>
                    </a>

                    <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="publicNav">
                        <ul class="navbar-nav me-auto align-items-lg-center gap-lg-2">
                            <li class="nav-item">
                                <a class="nav-link text-white {{ request()->routeIs('home') ? 'fw-bold opacity-100' : '' }}" href="{{ route('home') }}">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white {{ request()->routeIs('public.research.*') ? 'fw-bold opacity-100' : '' }}" href="{{ route('public.research.index') }}">
                                    Institutional Repository
                                </a>
                            </li>
                        </ul>
                        <ul class="navbar-nav ms-auto align-items-lg-center">
                            @auth
                                <li class="nav-item">
                                    <a class="nav-link text-white {{ request()->routeIs('dashboard') ? 'fw-bold opacity-100' : '' }}" href="{{ route('dashboard') }}">
                                        Dashboard
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a class="nav-link text-white {{ request()->routeIs('login') ? 'fw-bold opacity-100' : '' }}" href="{{ route('login') }}">
                                        Sign in
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </div>
                </div>
            </nav>
        </div>

        <div class="container flex-grow-1" id="prms-public-main">
            <div class="page-inner pt-4">
                @hasSection('hide_page_header')
                @else
                    @include('layouts.partials.prms-page-header', [
                        'headerHomeUrl' => route('home'),
                        'headerDefaultTitle' => 'Institutional Repository',
                    ])
                @endif
                <x-prms-flash-messages />
                @yield('content')
            </div>
        </div>

        <footer class="footer prms-public-footer mt-auto flex-shrink-0">
            <div class="container-fluid text-center text-muted small py-3">
                @include('layouts.partials.prms-copyright')
            </div>
        </footer>
    </div>
</div>

@include('layouts.partials.kaiadmin-scripts', ['full' => false])
<script src="{{ asset('js/prms-auto-filter.js') }}?v=1" defer></script>
@stack('scripts')
</body>
</html>
