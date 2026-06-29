@extends('layouts.app')

@section('title', $project->project_code ?? 'Project details')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item">
        <a href="{{ auth()->user()->role === 'admin' ? route('dashboard') : route('projects.index') }}">
            {{ auth()->user()->role === 'admin' ? __('Dashboard') : __('My projects') }}
        </a>
    </li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ \Illuminate\Support\Str::limit($project->project_code ?? $project->title, 32) }}</span></li>
@endsection

@section('content')
    <x-prms-greeting-banner eyebrow="Research project" :title="$project->title" :showHello="false">
        <x-slot:meta>
            <div class="d-flex flex-wrap gap-2 align-items-center small">
                @if ($project->project_code)
                    <span class="badge bg-primary rounded-pill">{{ $project->project_code }}</span>
                @endif
                @if ($project->project_type)
                    <span class="badge bg-light text-dark border rounded-pill">{{ $project->project_type }}</span>
                @endif
                @if ($project->current_stage)
                    <span class="badge bg-info text-dark rounded-pill">{{ $project->current_stage }}</span>
                @endif
            </div>
        </x-slot:meta>
        <a href="{{ auth()->user()->role === 'admin' ? route('dashboard') : route('projects.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>
            {{ auth()->user()->role === 'admin' ? __('Back to dashboard') : __('All projects') }}
        </a>
        @if (auth()->user()->role === 'supervisor')
            <a href="{{ route('supervisor.index') }}" class="btn btn-primary rounded-pill px-3">
                <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i> Review submissions
            </a>
        @endif
    </x-prms-greeting-banner>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0">Student</h3>
                </div>
                <div class="card-body">
                    @if ($project->student)
                        <p class="fw-semibold text-strong mb-1">{{ $project->student->name }}</p>
                        <p class="text-muted small mb-0 text-break">
                            <i class="far fa-envelope me-1" aria-hidden="true"></i>{{ $project->student->email }}
                        </p>
                    @else
                        <p class="text-muted small mb-0">No student linked to this record.</p>
                    @endif
                </div>
            </div>

            @if ($project->supervisor)
                <div class="card mt-3 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h3 class="h6 fw-bold text-strong mb-0">Supervisor on record</h3>
                    </div>
                    <div class="card-body">
                        <p class="fw-semibold text-strong mb-1">{{ $project->supervisor->name }}</p>
                        <p class="text-muted small mb-0 text-break">{{ $project->supervisor->email }}</p>
                    </div>
                </div>
            @endif

            @if (($project->contributors ?? collect())->isNotEmpty() || ! empty($canManageContributors))
                <div class="card mt-3 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between gap-2">
                        <h3 class="h6 fw-bold text-strong mb-0">Contributors</h3>
                        @if (! empty($canManageContributors))
                            <span class="badge bg-light text-muted border rounded-pill">Group project</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success small py-2 mb-3">{{ session('status') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger small py-2 mb-3">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (($project->contributors ?? collect())->isEmpty())
                            <p class="text-muted small mb-3">No contributors listed yet. Add group members who helped build this project.</p>
                        @else
                            <ul class="list-unstyled mb-3">
                                @foreach ($project->contributors as $contributor)
                                    <li class="d-flex align-items-center justify-content-between gap-2 py-2{{ $loop->last ? '' : ' border-bottom' }}">
                                        <div class="min-w-0">
                                            <div class="fw-semibold small text-strong">{{ $contributor->name }}</div>
                                            <div class="text-muted small text-truncate">{{ $contributor->email }}</div>
                                        </div>
                                        @if (! empty($canManageContributors))
                                            <form action="{{ route('projects.contributors.destroy', [$project, $contributor]) }}" method="POST" class="flex-shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove contributor">
                                                    <i class="fas fa-times" aria-hidden="true"></i>
                                                    <span class="visually-hidden">Remove</span>
                                                </button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (! empty($canManageContributors))
                            @if (($eligibleContributors ?? collect())->isEmpty())
                                <p class="text-muted small mb-0">All group members are already listed, or your group has no other students.</p>
                            @else
                                <form action="{{ route('projects.contributors.store', $project) }}" method="POST" class="d-flex flex-column gap-2">
                                    @csrf
                                    <label class="form-label small fw-semibold mb-0" for="contributor-user-id">Add group member</label>
                                    <div class="input-group input-group-sm">
                                        <select id="contributor-user-id" name="user_id" class="form-select" required>
                                            <option value="" selected disabled>Select a student…</option>
                                            @foreach ($eligibleContributors as $candidate)
                                                <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                                            Add
                                        </button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            <div class="card mt-3 border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0">Record</h3>
                </div>
                <div class="card-body small text-muted">
                    <p class="mb-2"><strong class="text-strong">Created:</strong> {{ $project->created_at?->format('M j, Y g:i a') }}</p>
                    <p class="mb-0"><strong class="text-strong">Updated:</strong> {{ $project->updated_at?->format('M j, Y g:i a') }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @if ($showSimilarityPanel ?? false)
                @include('projects.partials.similar-projects', [
                    'project' => $project,
                    'similarProjects' => $similarProjects ?? collect(),
                    'ollamaReachable' => $ollamaReachable ?? true,
                ])
            @endif

            @if ($project->keywords)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h3 class="h6 fw-bold text-strong mb-0">Working title / short name</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-strong">{{ $project->keywords }}</p>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0">Problem statement</h3>
                </div>
                <div class="card-body">
                    @if ($project->abstract)
                        <div class="text-strong" style="white-space: pre-wrap;">{{ $project->abstract }}</div>
                    @else
                        <p class="text-muted small mb-0">No problem statement was stored for this project.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
