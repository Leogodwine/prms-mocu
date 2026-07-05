@extends('layouts.app')

@section('title', 'My profile')

@section('content')
@php
    $roleLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', $user->role ?? 'User'));
    $initials  = collect(preg_split('/\s+/', trim((string) $user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))
        ->implode('');
@endphp

<x-prms-greeting-banner eyebrow="Account" subtitle="Personal details, contact information, and account settings.">
    <a href="{{ route('profile.edit') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
        <i class="far fa-edit me-2" aria-hidden="true"></i> Edit profile
    </a>
</x-prms-greeting-banner>

<div class="row g-3">
    {{-- Identity card --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="mx-auto d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary fw-bold rounded-circle mb-3"
                     style="width: 96px; height: 96px; font-size: 2rem;">
                    {{ $initials ?: 'U' }}
                </div>
                <h2 class="h5 fw-bold mb-1">{{ $user->name }}</h2>
                <div class="small text-muted mb-2">{{ $user->email }}</div>
                <span class="badge rounded-pill bg-brand-soft text-primary fw-semibold">
                    {{ $roleLabel }}
                </span>
                @if (!empty($user->account_status))
                    <div class="mt-3">
                        <span class="badge rounded-pill bg-success-subtle text-success fw-semibold text-uppercase">
                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;" aria-hidden="true"></i>
                            {{ $user->account_status }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <i class="far fa-id-card me-2" aria-hidden="true"></i> Profile details
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Full name</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $user->name }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Email address</dt>
                    <dd class="col-sm-8">{{ $user->email }}</dd>

                    @if ($user->isStudentUser())
                        <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Reg. no</dt>
                        <dd class="col-sm-8"><code>{{ $user->regNo() ?? '—' }}</code></dd>
                    @elseif (! empty($user->staff_id) || ! empty($user->login_id))
                        <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Staff ID</dt>
                        <dd class="col-sm-8"><code>{{ $user->staff_id ?? $user->login_id }}</code></dd>
                    @endif

                    <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Phone number</dt>
                    <dd class="col-sm-8">{{ $user->phone_number ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Department</dt>
                    <dd class="col-sm-8">{{ $user->department ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Programme</dt>
                    <dd class="col-sm-8">{{ $user->programme ?: '—' }}</dd>

                    @if (!empty($user->year_of_study))
                        <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Year of study</dt>
                        <dd class="col-sm-8">Year {{ $user->year_of_study }}</dd>
                    @endif

                    @if ($user->studentProfile)
                        <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Gender</dt>
                        <dd class="col-sm-8">{{ $user->studentProfile->genderLabel() }}</dd>
                    @endif

                    <dt class="col-sm-4 text-muted fw-normal small text-uppercase">Role</dt>
                    <dd class="col-sm-8">{{ $roleLabel }}</dd>
                </dl>
            </div>
            <div class="card-footer bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    <i class="far fa-clock me-1" aria-hidden="true"></i>
                    Last updated {{ optional($user->updated_at)->diffForHumans() ?? '—' }}
                </small>
                <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm">
                    <i class="far fa-edit me-1" aria-hidden="true"></i> Edit profile
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
