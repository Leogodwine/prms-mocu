@extends('layouts.app')

@section('title', 'My projects')

@section('content')

<x-prms-greeting-banner subtitle="Create a new project or proposal, define your problem statement, and track each submission through supervision.">
@if (! empty($canCreateProjects))
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#createProjectModal">
        <i class="fas fa-plus-circle me-2" aria-hidden="true"></i>
        New project/proposal creation
    </button>
@endif
    <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
    </a>
</x-prms-greeting-banner>

<div class="card">
    <div class="card-body p-0">
        @if ($projects->isEmpty())
            <div class="p-5 text-center">
                <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                     style="width: 72px; height: 72px;">
                    <i class="fas fa-project-diagram text-muted" aria-hidden="true" style="font-size: 1.6rem;"></i>
                </div>
                <h3 class="h6 fw-bold text-strong">No projects yet</h3>
                @if (! empty($canCreateProjects))
                    <p class="text-muted small mb-3">Create your first project to start tracking submissions and supervision.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                        <i class="fas fa-plus me-1" aria-hidden="true"></i>
                        Create a project
                    </button>
                @else
                    <p class="text-muted small mb-0">You do not have access to create projects or proposals in PRMS yet.</p>
                @endif
            </div>
        @else
            <x-prms-table-pagination-toolbar :paginator="$projects" noun="projects" />
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Tracking number</th>
                            <th scope="col">Title</th>
                            <th scope="col">Type</th>
                            <th scope="col">Stage</th>
                            <th scope="col">Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="fw-semibold text-strong text-decoration-none">
                                        {{ $project->project_code ?? '—' }}
                                        <i class="fas fa-external-link-alt ms-1 small text-muted" aria-hidden="true"></i>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="text-reset text-decoration-none">
                                        <div class="fw-semibold text-strong">{{ $project->title }}</div>
                                    </a>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $project->project_type ?? '—' }}</span>
                                </td>
                                <td>
                                    @php
                                        $stage = $project->current_stage ?? 'Draft';
                                        $stageBadge = match (\Illuminate\Support\Str::lower($stage)) {
                                            'draft'      => 'bg-secondary',
                                            'submitted'  => 'bg-warning',
                                            'approved', 'archived' => 'bg-success',
                                            'rejected'   => 'bg-danger',
                                            default      => 'bg-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $stageBadge }}">{{ $stage }}</span>
                                </td>
                                <td>
                                    @if ($project->submission_deadline)
                                        <span class="text-muted small">
                                            {{ \Carbon\Carbon::parse($project->submission_deadline)->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-faint">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent border-top py-3">
                <x-prms-table-pagination-footer :paginator="$projects" />
            </div>
        @endif
    </div>
</div>

@if (! empty($canCreateProjects))
    @include('projects.partials.create-project-modal')
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var shouldOpen = @json($errors->any() || request()->query('modal') === 'create');
        if (!shouldOpen) return;
        var el = document.getElementById('createProjectModal');
        if (el && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    });
</script>
@endpush
