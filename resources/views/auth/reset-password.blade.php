@extends('layouts.guest')

@section('title', 'Set new password')

@section('content')
<div class="card-header">
    <div class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle mb-3"
         style="width: 56px; height: 56px;">
        <i class="fas fa-shield-alt" aria-hidden="true" style="font-size: 1.4rem;"></i>
    </div>
    <h2 class="fw-bold">Choose a new password</h2>
    <p class="text-muted small mb-0">Use a strong password with letters, numbers, and a symbol.</p>
</div>

<div class="card-body">
    <form action="{{ route('password.update') }}" method="POST" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Minimum 8 characters"
                required autofocus autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback d-block mt-1" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="form-control"
                placeholder="Repeat the password"
                required autocomplete="new-password">
        </div>

        @error('email')
            <div class="alert alert-danger small mb-3" role="alert">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Reset password</button>
    </form>
</div>
@endsection
