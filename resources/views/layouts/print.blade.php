<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Document') | {{ config('app.name', 'MoCU-PRMS') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/prms-mocu/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/prms-mocu/assets/css/all.min.css') }}">
    @stack('styles')
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-white">
    @yield('content')
    <footer class="text-center text-muted small py-3 no-print">
        @include('layouts.partials.prms-copyright')
    </footer>
    @stack('scripts')
</body>
</html>
