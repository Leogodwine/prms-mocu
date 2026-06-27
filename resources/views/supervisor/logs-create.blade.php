@extends('layouts.app')

@section('title', 'New supervision meeting')

@section('content')
    @php
        $preselectedTargets = collect(old('targets', []));
        if ($preselectedTargets->isEmpty()) {
            if (request()->filled('group')) {
                $preselectedTargets = collect(['group:' . request('group')]);
            } elseif (request()->filled('student')) {
                $preselectedTargets = collect(['student:' . request('student')]);
            }
        }
        $applyToAllOld = (bool) old('apply_to_all', false);
        $expandTargetMenu = $errors->any() || $preselectedTargets->isNotEmpty() || request()->hasAny(['group', 'student']);
    @endphp

    <x-prms-greeting-banner
        title="New supervision meeting"
        :show-hello="false"
        subtitle="Record a supervision session, progress update, and agreed actions.">
        <a href="{{ route('supervisor.logs') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back to meetings
        </a>
        <a href="{{ route('supervisor.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i> Review submissions
        </a>
    </x-prms-greeting-banner>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i> Please correct the following:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @include('supervisor.partials.supervision-meeting-form', [
                'preselectedTargets' => $preselectedTargets,
                'applyToAllOld' => $applyToAllOld,
                'expandTargetMenu' => $expandTargetMenu,
            ])
        </div>
    </div>
@endsection
