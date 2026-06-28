@extends('layouts.app')

@section('title', 'Academic configuration settings')

@section('content')
    <x-prms-greeting-banner subtitle="Manage departments, programmes, academic levels, and eligibility rules — no code changes required.">
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('admin.configuration.index') }}" class="btn btn-light border rounded-pill px-3">
                <i class="fas fa-cog me-1"></i> General system settings
            </a>
        @endif
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    @include('admin.academic-configuration.partials.nav', ['current' => 'index'])

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="prms-stat-card p-4 h-100">
                <div class="stat-label">Departments</div>
                <div class="stat-value">{{ $stats['departments'] }}</div>
                <a href="{{ route('admin.academic-configuration.departments.index') }}" class="small">Manage departments →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="prms-stat-card p-4 h-100">
                <div class="stat-label">Programmes</div>
                <div class="stat-value">{{ $stats['programmes'] }}</div>
                <a href="{{ route('admin.academic-configuration.programmes.index') }}" class="small">Manage programmes →</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="prms-stat-card p-4 h-100">
                <div class="stat-label">Student accounts</div>
                <div class="stat-value">{{ $stats['students'] }}</div>
                <a href="{{ route('admin.academic-configuration.preview.index') }}" class="small">Check eligibility →</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h2 class="h6 fw-bold mb-0">Standard final-year workflow</h2>
        </div>
        <div class="card-body">
            <p class="small text-muted">All final-year eligible students follow this sequence regardless of department:</p>
            <ol class="mb-0">
                @foreach (\App\Enums\WorkflowType::StandardResearch->stages() as $stage)
                    <li>{{ $stage }}</li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h2 class="h6 fw-bold mb-0">Rule resolution order</h2>
        </div>
        <div class="card-body small">
            <ol class="mb-0">
                <li>Student profile (year, level, programme, department)</li>
                <li>Department settings (final-year rule type, project/research support)</li>
                <li>Department level override (when rule type is LEVEL_BASED)</li>
                <li>Programme settings (final year, output type, allowed project years)</li>
                <li>Academic level defaults (Diploma, Bachelor, Masters, PhD)</li>
                <li>Global workflow defaults in system settings</li>
            </ol>
        </div>
    </div>
@endsection
