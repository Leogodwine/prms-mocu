@extends('layouts.guest')

@section('title', 'Update password')

@section('content')
<div class="card-header">
    <div class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle mb-3"
         style="width: 56px; height: 56px;">
        <i class="fas fa-user-shield" aria-hidden="true" style="font-size: 1.4rem;"></i>
    </div>
    <h2 class="fw-bold">Action required</h2>
    <p class="text-muted small mb-0">You're using a temporary password. Please update it to continue.</p>
</div>

<div class="card-body">
    <form action="{{ route('password.force.update') }}" method="POST" novalidate>
        @csrf
        @method('PUT')

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

        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
            Update and continue
        </button>
    </form>

    <div class="mt-3 text-center">
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link p-0 small text-muted text-decoration-none">
                Sign out and try later
            </button>
        </form>
    </div>
</div>
@endsection
