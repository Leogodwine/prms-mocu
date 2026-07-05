@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
<div class="card-header">
    <div class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle mb-3"
         style="width: 56px; height: 56px;">
        <i class="fas fa-key" aria-hidden="true" style="font-size: 1.4rem;"></i>
    </div>
    <h2 class="fw-bold">Reset your password</h2>
    <p class="text-muted small mb-0">Enter the email tied to your account and we'll send you a secure reset link.</p>
</div>

<div class="card-body">
    <form action="{{ route('password.email') }}" method="POST" novalidate>
        @csrf

        <div class="mb-3">
            <label for="login" class="form-label">Registered email</label>
            <div class="input-group prms-auth-input-group">
                <span class="input-group-text"><i class="far fa-envelope" aria-hidden="true"></i></span>
                <input
                    id="login"
                    type="email"
                    name="login"
                    class="form-control @error('login') is-invalid @enderror"
                    value="{{ old('login') }}"
                    placeholder="you@mocu.ac.tz"
                    required autofocus autocomplete="email">
            </div>
            @error('login')
                <div class="invalid-feedback d-block mt-1" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill mb-3">
            Send reset link
        </button>

        <div class="text-center">
            <a href="{{ route('login') }}" class="small fw-semibold">
                <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>
                Back to sign in
            </a>
        </div>
    </form>
</div>
@endsection
