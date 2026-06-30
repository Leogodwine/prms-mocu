@php
    $navId = $navId ?? 'siteNav';
    $innerClass = $innerClass ?? '';
@endphp
<nav class="navbar navbar-header navbar-expand-sm border-bottom prms-public-nav prms-public-topnav prms-site-topnav w-100"
     data-background-color="dark"
     aria-label="{{ $ariaLabel ?? __('Site navigation') }}">
    <div class="prms-site-nav-inner {{ $innerClass }}">
        <a class="navbar-brand prms-site-nav-brand d-flex align-items-center gap-2 text-white flex-shrink-0" href="{{ route('home') }}">
            <img src="{{ asset('images/mocu_logo.png') }}" alt="Moshi Co-operative University" class="prms-brand-logo prms-brand-logo--nav" decoding="async">
            <span class="prms-site-nav-product">{{ config('app.name', 'MoCU-PRMS') }}</span>
        </a>

        <button class="navbar-toggler prms-site-nav-toggler border-0 text-white"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $navId }}"
                aria-controls="{{ $navId }}"
                aria-expanded="false"
                aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse prms-site-nav-menu" id="{{ $navId }}">
            <ul class="navbar-nav ms-sm-auto align-items-sm-center gap-sm-1">
                <li class="nav-item">
                    <a class="nav-link text-white {{ request()->routeIs('home') ? 'fw-bold opacity-100' : '' }}" href="{{ route('home') }}">{{ __('Home') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white {{ request()->routeIs('public.research.*') ? 'fw-bold opacity-100' : '' }}" href="{{ route('public.research.index') }}">
                        {{ __('Repository') }}
                    </a>
                </li>
                <li class="nav-item">
                    @auth
                        <a class="nav-link text-white {{ request()->routeIs('dashboard') ? 'fw-bold opacity-100' : '' }}" href="{{ route('dashboard') }}">
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a class="nav-link text-white {{ request()->routeIs('login') ? 'fw-bold opacity-100' : '' }}" href="{{ route('login') }}">
                            {{ __('Login') }}
                        </a>
                    @endauth
                </li>
            </ul>
        </div>
    </div>
</nav>
