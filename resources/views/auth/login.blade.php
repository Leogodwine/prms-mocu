@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
@php
    $helpDeskEmail = config('prms.help_desk.email', 'icthelpdesk@mocu.ac.tz');
    $helpDeskLabel = config('prms.help_desk.label', 'ICT Help Desk');
@endphp

<div class="card-header prms-auth-card-header border-0 pt-4 px-4 pb-0">
    <a href="{{ route('home') }}" class="prms-auth-main-brand">
        <img src="{{ asset('images/mocu_logo.png') }}"
             alt="{{ __('Moshi Co-operative University') }}"
             class="prms-brand-logo prms-brand-logo--auth-panel"
             decoding="async"
             loading="eager">
    </a>
    <h1 class="prms-auth-heading h2 mb-2">Welcome back</h1>
    <p class="prms-auth-lead text-muted mb-0">
        Sign in to {{ config('app.name', 'MoCU-PRMS') }} to manage proposals, research, and project submissions.
    </p>
</div>

<div class="card-body prms-auth-card-body px-4 pt-3 pb-4">
    @if (session('status'))
        <div class="alert alert-success prms-auth-alert border-0 small mb-4 d-flex gap-2 align-items-start" role="status">
            <i class="fas fa-circle-check mt-1 flex-shrink-0" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form id="prms-login-form"
          action="{{ route('login.store') }}"
          method="POST"
          novalidate
          class="prms-auth-form"
          aria-describedby="prms-auth-security-notice">
        @csrf

        <div class="mb-4">
            <label for="login_id" class="form-label prms-auth-label">University email or registration number</label>
            <div class="input-group prms-auth-input-group">
                <span class="input-group-text" id="login_id-addon"><i class="far fa-user" aria-hidden="true"></i></span>
                <input
                    id="login_id"
                    type="text"
                    name="login_id"
                    class="form-control @error('login_id') is-invalid @enderror"
                    value="{{ old('login_id') }}"
                    placeholder="Staff: you@mocu.ac.tz · Students: MoCU/REG/1134/24"
                    required
                    autofocus
                    autocomplete="username"
                    aria-describedby="login_id-addon login_id-hint @error('login_id') login_id-error @enderror"
                    @error('login_id') aria-invalid="true" @enderror>
            </div>
            <p id="login_id-hint" class="form-text prms-auth-hint mb-0">Staff: university email only. Students: Registration number.</p>
            @error('login_id')
                <div id="login_id-error" class="invalid-feedback d-block mt-2" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-2">
            <label for="password" class="form-label prms-auth-label">Password</label>
            <div class="input-group prms-auth-input-group">
                <span class="input-group-text" id="password-addon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                    minlength="1"
                    aria-describedby="password-addon prms-caps-lock-warning @error('password') password-error @enderror"
                    @error('password') aria-invalid="true" @enderror>
                <button type="button"
                        class="btn prms-auth-password-toggle"
                        id="prms-password-toggle"
                        aria-label="Show password"
                        aria-pressed="false"
                        aria-controls="password">
                    <i class="far fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            <div id="prms-caps-lock-warning"
                 class="prms-auth-caps-warning small d-none mt-2"
                 role="status"
                 aria-live="polite"
                 aria-hidden="true">
                <i class="fas fa-keyboard me-1" aria-hidden="true"></i>
                Caps Lock is on
            </div>
            @error('password')
                <div id="password-error" class="invalid-feedback d-block mt-2" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 mt-3">
            <div class="form-check m-0">
                <input class="form-check-input prms-auth-check"
                       type="checkbox"
                       id="remember"
                       name="remember"
                       value="1"
                       @checked(old('remember'))>
                <label class="form-check-label small" for="remember">Remember me</label>
            </div>
            <a href="{{ route('password.request') }}" class="prms-auth-inline-link small fw-semibold">Forgot password?</a>
        </div>

        <button type="submit"
                id="prms-login-submit"
                class="btn btn-lg w-100 prms-auth-submit text-white fw-semibold mb-3">
            <span class="prms-auth-submit-spinner spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
            <span class="prms-auth-submit-label">{{ __('Sign in') }}</span>
        </button>

        <nav class="prms-auth-wayfinding" aria-label="{{ __('Related links') }}">
            <a href="{{ route('home') }}" class="prms-auth-inline-link fw-semibold">{{ __('Home') }}</a>
            <span class="prms-auth-wayfinding-sep" aria-hidden="true">·</span>
            <a href="{{ route('public.research.index') }}" class="prms-auth-inline-link fw-semibold">{{ __('Repository') }}</a>
        </nav>
    </form>

    <footer class="prms-auth-meta text-center mt-4 pt-3 border-top" aria-label="{{ __('Sign-in help and security') }}">
        <p id="prms-auth-security-notice" class="prms-auth-security-notice small text-muted mb-2">
            <i class="fas fa-shield-alt me-1 text-primary" aria-hidden="true"></i>
            Secure sign-in. Never share your password or verification codes.
        </p>
        <p class="small mb-1">
            Need help?
            <a href="mailto:{{ $helpDeskEmail }}" class="prms-auth-inline-link fw-semibold">{{ $helpDeskLabel }}</a>
        </p>
        <!-- <p class="prms-auth-browser-note small text-muted mb-0">
            Optimised for current versions of Chrome, Edge, Firefox, and Safari.
        </p> -->
    </footer>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/prms-auth-login.js') }}?v=1" defer></script>
@endpush
