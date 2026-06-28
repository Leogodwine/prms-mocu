@php
    $headerHomeUrl = $headerHomeUrl ?? route('dashboard');
    $headerDefaultTitle = $headerDefaultTitle ?? 'Dashboard';
@endphp
<div class="page-header">
    <h3 class="page-title mb-0">@yield('title', $headerDefaultTitle)</h3>
    <ul class="breadcrumbs ms-auto mb-0">
        <li class="nav-home">
            <a href="{{ $headerHomeUrl }}" aria-label="{{ __('Home') }}">
                <i class="icon-home" aria-hidden="true"></i>
                <span class="prms-breadcrumb-label">{{ __('Home') }}</span>
            </a>
        </li>
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @else
            <li class="separator"><i class="icon-arrow-right"></i></li>
            <li class="nav-item">
                <span class="text-muted">@yield('title', $headerDefaultTitle)</span>
            </li>
        @endif
    </ul>
</div>
