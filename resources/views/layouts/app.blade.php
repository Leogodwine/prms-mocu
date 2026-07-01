<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.prms-chrome-init')
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1572E8">

    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'MoCU-PRMS') }}</title>

    @include('layouts.partials.kaiadmin-styles')
    @php $kai = asset(config('prms.kaiadmin_assets', 'vendor/prms-mocu/assets')); @endphp
    <link rel="stylesheet" href="{{ $kai }}/css/demo.css">
    <link rel="stylesheet" href="{{ asset('css/prms-theme.css') }}?v=38">
    <link rel="stylesheet" href="{{ asset('css/prms-kaiadmin-bridge.css') }}?v=53">

    @stack('styles')
</head>
<body data-prms-role="{{ auth()->user()->role ?? '' }}">
<a href="#prms-main" class="prms-skip-link">Skip to main content</a>

<div class="wrapper">
    {{-- ────────── Sidebar (KaiAdmin) ────────── --}}
    <div class="sidebar sidebar-style-2" data-background-color="dark" aria-label="Primary navigation">
        <div class="sidebar-logo">
            <div class="logo-header" data-background-color="dark">
                @include('layouts.partials.prms-sidebar-brand', ['logoClass' => 'prms-brand-logo--sidebar'])
                <div class="nav-toggle">
                    <button type="button" class="btn btn-toggle toggle-sidebar" aria-label="Toggle sidebar">
                        <i class="gg-menu-right"></i>
                    </button>
                    <button type="button" class="btn btn-toggle sidenav-toggler" aria-label="Minimize sidebar">
                        <i class="gg-menu-left"></i>
                    </button>
                </div>
                <button type="button" class="topbar-toggler more" aria-label="More">
                    <i class="gg-more-vertical-alt"></i>
                </button>
            </div>
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
            <div class="sidebar-content">
                <ul class="nav nav-primary">
                    @include('layouts.partials.prms-sidebar-nav')
                </ul>
            </div>
        </div>
    </div>

    {{-- ────────── Main panel ────────── --}}
    <div class="main-panel">
        <div class="main-header">
            <div class="main-header-logo">
                <div class="logo-header prms-mobile-toolbar" data-background-color="dark">
                    @if (auth()->check() && ! request()->routeIs('dashboard'))
                        <a href="{{ route('dashboard') }}"
                           class="btn btn-toggle prms-mobile-back d-lg-none"
                           aria-label="{{ __('Back to dashboard') }}">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        </a>
                    @endif
                    <div class="nav-toggle">
                        <button type="button" class="btn btn-toggle toggle-sidebar" aria-label="Toggle sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button type="button" class="btn btn-toggle sidenav-toggler" aria-label="Open navigation menu">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <span class="prms-sidebar-brand-name prms-mobile-toolbar-brand">{{ config('app.name', 'MoCU-PRMS') }}</span>
                </div>
            </div>

            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom" aria-label="Top navigation">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        @if (auth()->check())
                            @include('layouts.partials.prms-quick-nav')

                            <li class="nav-item d-none d-md-flex align-items-center me-2" id="prms-nav-date" title="Today">
                                <span class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-brand-soft text-primary"
                                      style="font-size: 0.78rem; font-weight: 600; line-height: 1;">
                                    <i class="far fa-calendar-alt" aria-hidden="true"></i>
                                    <span id="prms-nav-date-label">{{ now()->format('l, F j Y') }}</span>
                                </span>
                            </li>

                            <li class="nav-item topbar-icon">
                                <a href="{{ route('notifications.index') }}"
                                   class="nav-link position-relative @if (request()->routeIs('notifications.*')) active @endif"
                                   aria-label="Notifications"
                                   @if (request()->routeIs('notifications.*')) aria-current="page" @endif>
                                    <i class="far fa-bell"></i>
                                    @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                                    @if ($unread > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.55rem;">
                                            {{ $unread > 9 ? '9+' : $unread }}
                                        </span>
                                    @endif
                                </a>
                            </li>

                            <li class="nav-item topbar-user dropdown">
                                <a class="dropdown-toggle profile-pic d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <span class="avatar-img rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width:2.25rem;height:2.25rem;font-size:0.85rem;">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <span class="profile-username d-none d-md-inline text-start" style="line-height:1.1;">
                                        <span class="op-7 d-block small">Signed in</span>
                                        <span class="fw-bold">{{ auth()->user()->name }}</span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-user animated fadeIn shadow" style="min-width: 240px;">
                                    <li class="px-3 py-2 border-bottom">
                                        <div class="fw-semibold text-strong">{{ auth()->user()->name }}</div>
                                        <div class="small text-muted">{{ auth()->user()->email }}</div>
                                        <div class="mt-1">
                                            <span class="badge rounded-pill bg-brand-soft text-primary fw-semibold">
                                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', auth()->user()->role ?? 'User')) }}
                                            </span>
                                        </div>
                                    </li>
                                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="far fa-user me-2 text-muted"></i> Profile</a></li>
                                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="far fa-edit me-2 text-muted"></i> Profile update</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="far fa-window-restore me-2 text-muted"></i> Dashboard</a></li>
                                    <li><a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="far fa-bell me-2 text-muted"></i> Notifications</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('prms-logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                                        </a>
                                        <form id="prms-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container" id="prms-main">
            <div class="page-inner">
                @include('layouts.partials.prms-page-header', [
                    'headerHomeUrl' => route('dashboard'),
                    'headerDefaultTitle' => 'Dashboard',
                ])

                <div class="row">
                    <div class="col-12">
                        <x-prms-flash-messages />
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <div class="container-fluid text-center py-3">
                @include('layouts.partials.prms-copyright')
            </div>
        </footer>
    </div>

    @include('layouts.partials.kaiadmin-customizer')
</div>

@if (auth()->check())
    @include('layouts.partials.prms-quick-nav-modal')
@endif

@stack('modals')

@include('layouts.partials.kaiadmin-scripts', ['full' => true])
<script src="{{ asset('js/prms-header.js') }}?v=6"></script>
<script src="{{ asset('js/prms-sidebar.js') }}?v=4"></script>

@if (auth()->check())
    <script src="{{ asset('js/prms-quick-nav.js') }}?v=6" defer></script>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" crossorigin="anonymous" defer></script>

<script>
    (function () {
        const label = document.getElementById('prms-nav-date-label');
        if (!label) return;

        const render = () => {
            label.textContent = new Date().toLocaleDateString(undefined, {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });
        };

        render();

        const now = new Date();
        const nextMidnight = new Date(now);
        nextMidnight.setHours(24, 0, 5, 0);
        setTimeout(function tick() {
            render();
            setTimeout(tick, 24 * 60 * 60 * 1000);
        }, nextMidnight - now);
    })();
</script>

@stack('scripts')
<script src="{{ asset('js/prms-modals.js') }}?v=1" defer></script>
<script src="{{ asset('js/prms-auto-filter.js') }}?v=1" defer></script>
<script src="{{ asset('js/prms-customizer.js') }}?v=3" defer></script>
</body>
</html>
