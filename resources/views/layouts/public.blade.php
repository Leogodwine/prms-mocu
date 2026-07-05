<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="theme-color" content="#1572E8">
    <meta name="description" content="Browse approved research, projects, and reports from Moshi Co-operative University.">

    <title>@yield('title', 'Repository') | {{ config('app.name', 'MoCU-PRMS') }}</title>

    @include('layouts.partials.kaiadmin-styles')
    <link rel="stylesheet" href="{{ asset('css/prms-theme.css') }}?v=42">
    <link rel="stylesheet" href="{{ asset('css/prms-kaiadmin-bridge.css') }}?v=45">

    @stack('styles')
</head>
<body class="prms-public-page prms-kaiadmin-public">

<a href="#prms-public-main" class="prms-skip-link">Skip to main content</a>

<div class="wrapper">
    <div class="main-panel prms-public-main d-flex flex-column">
        <div class="main-header flex-shrink-0 prms-public-site-header">
            @include('layouts.partials.prms-site-topnav', [
                'navId' => 'publicNav',
                'innerClass' => 'prms-public-nav-inner',
                'ariaLabel' => 'Public site navigation',
                'alwaysExpanded' => true,
            ])
        </div>

        <div class="container flex-grow-1" id="prms-public-main">
            <div class="page-inner pt-4">
                @hasSection('hide_page_header')
                @else
                    @include('layouts.partials.prms-page-header', [
                        'headerHomeUrl' => route('home'),
                        'headerDefaultTitle' => 'Repository',
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
<x-prms-toast-host />
<script src="{{ asset('js/prms-auto-filter.js') }}?v=1" defer></script>
@stack('scripts')
</body>
</html>
