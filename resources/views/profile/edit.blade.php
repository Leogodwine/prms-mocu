@extends('layouts.app')

@section('title', 'Edit profile')

@section('content')
@php
    $isAdminAccount = $user->isAdminUser();
@endphp

<x-prms-greeting-banner eyebrow="Account" subtitle="Update your personal details, contact information, and password.">
    <a href="{{ route('profile.show') }}" class="btn btn-light border rounded-pill px-3">
        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back to profile
    </a>
</x-prms-greeting-banner>

@if ($isAdminAccount)
    <div class="alert alert-info d-flex gap-2 align-items-start mb-4">
        <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
        <div class="small mb-0">
            As a system administrator, you can update your phone number and password here.
            Name, email, department, programme, and gender must be changed by another administrator.
        </div>
    </div>
@endif

<form action="{{ route('profile.update') }}" method="POST" novalidate>
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <i class="far fa-id-card me-2" aria-hidden="true"></i> Personal details
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if ($isAdminAccount)
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    <p class="fw-semibold mb-2 small text-uppercase text-muted">Managed by another administrator</p>
                                    <dl class="row small mb-0">
                                        <dt class="col-sm-3">Full name</dt>
                                        <dd class="col-sm-9 fw-semibold">{{ $user->name }}</dd>
                                        <dt class="col-sm-3">Email</dt>
                                        <dd class="col-sm-9">{{ $user->email }}</dd>
                                        <dt class="col-sm-3">Department</dt>
                                        <dd class="col-sm-9">{{ $user->department ?: '—' }}</dd>
                                        <dt class="col-sm-3">Programme</dt>
                                        <dd class="col-sm-9">{{ $user->programme ?: '—' }}</dd>
                                        @if ($user->studentProfile)
                                            <dt class="col-sm-3">Gender</dt>
                                            <dd class="col-sm-9">{{ $user->studentProfile->genderLabel() }}</dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                        @else
                            <div class="col-md-12">
                                <label for="name" class="form-label fw-semibold">Full name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       value="{{ old('name', $user->name) }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       maxlength="120" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email address <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email"
                                       value="{{ old('email', $user->email) }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       maxlength="180" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        <div class="col-md-{{ $isAdminAccount ? '12' : '6' }}">
                            <label for="phone_number" class="form-label fw-semibold">Phone number</label>
                            <input type="text" id="phone_number" name="phone_number"
                                   value="{{ old('phone_number', $user->phone_number) }}"
                                   class="form-control @error('phone_number') is-invalid @enderror"
                                   maxlength="32" placeholder="e.g. +255 7XX XXX XXX">
                            @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if (! $isAdminAccount)
                            @if ($user->isStudentUser())
                                <div class="col-12">
                                    <div class="alert alert-light border mb-0">
                                        <p class="fw-semibold mb-2 small text-uppercase text-muted">Academic record (read-only)</p>
                                        <dl class="row small mb-0">
                                            <dt class="col-sm-3">Department</dt>
                                            <dd class="col-sm-9">{{ $user->department ?: '—' }}</dd>
                                            <dt class="col-sm-3">Programme</dt>
                                            <dd class="col-sm-9">{{ $user->programme ?: '—' }}</dd>
                                            <dt class="col-sm-3">Year of study</dt>
                                            <dd class="col-sm-9">{{ $user->year_of_study ? 'Year ' . $user->year_of_study : '—' }}</dd>
                                            @if ($user->studentProfile)
                                                <dt class="col-sm-3">Gender</dt>
                                                <dd class="col-sm-9">{{ $user->studentProfile->genderLabel() }}</dd>
                                            @endif
                                        </dl>
                                        <p class="text-muted small mb-0 mt-2">
                                            To change department, programme, year of study, or gender, contact your administrator or Head of Department.
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label for="department" class="form-label fw-semibold">Department</label>
                                    <input type="text" id="department" name="department"
                                           value="{{ old('department', $user->department) }}"
                                           class="form-control @error('department') is-invalid @enderror"
                                           maxlength="120">
                                    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="programme" class="form-label fw-semibold">Programme</label>
                                    <input type="text" id="programme" name="programme"
                                           value="{{ old('programme', $user->programme) }}"
                                           class="form-control @error('programme') is-invalid @enderror"
                                           maxlength="120">
                                    @error('programme')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">
                    <i class="fas fa-lock me-2" aria-hidden="true"></i> Change password
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Leave the password fields blank to keep your current password.
                    </p>

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">Current password</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">New password</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password" minlength="8">
                        <div class="form-text">At least 8 characters.</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-0">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" autocomplete="new-password" minlength="8">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="{{ route('profile.show') }}" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1" aria-hidden="true"></i> Save changes
        </button>
    </div>
</form>
@endsection
