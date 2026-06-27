<a href="{{ route('dashboard') }}" class="logo d-flex align-items-center gap-2 text-decoration-none">
    <img src="{{ asset('images/mocu_logo.png') }}"
         alt="Moshi Co-operative University"
         class="prms-brand-logo {{ $logoClass ?? 'prms-brand-logo--sidebar' }}">
    <span class="prms-sidebar-brand-name">{{ config('app.name', 'MoCU-PRMS') }}</span>
</a>
