@extends('layouts.app')

@section('title', 'Grading schemes')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><a href="{{ route('coordinator.index') }}">{{ __('Coordinator workspace') }}</a></li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ __('Grading schemes') }}</span></li>
@endsection

@section('content')
    <x-prms-greeting-banner subtitle="Configure criteria, weightings, and descriptors supervisors use when scoring proposals and research outputs.">
        <x-slot:meta>
            <p class="small text-muted mb-0 d-flex align-items-center gap-2 flex-wrap">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-brand-soft text-primary"
                      style="width: 2rem; height: 2rem;">
                    <i class="fas fa-chart-bar" style="font-size: 0.75rem;" aria-hidden="true"></i>
                </span>
                <span><strong class="text-strong">{{ $rubrics->count() }}</strong> active scheme definitions</span>
            </p>
        </x-slot:meta>
        <a href="{{ route('coordinator.rubrics.create') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-plus me-2" aria-hidden="true"></i> Create scheme
        </a>
        <a href="{{ route('coordinator.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back
        </a>
    </x-prms-greeting-banner>

    <div class="row g-4">
        @forelse ($rubrics as $rubric)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 card-interactive">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="bg-brand-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center p-3">
                                <i class="fas fa-tasks" aria-hidden="true"></i>
                            </div>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <h3 class="h5 fw-bold text-strong mb-2">{{ $rubric->name }}</h3>
                        <p class="text-muted small mb-4">{{ $rubric->description }}</p>

                        <div class="bg-surface-soft p-3 rounded-3 mb-4 border-soft">
                            <div class="prms-eyebrow mb-2">Sample criteria</div>
                            @foreach (collect($rubric->criteria)->take(3) as $criterion)
                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                    <span class="text-strong">{{ $criterion['name'] }}</span>
                                    <span class="fw-semibold">{{ $criterion['weight'] }}%</span>
                                </div>
                            @endforeach
                            @if (count($rubric->criteria) > 3)
                                <div class="text-center mt-2">
                                    <span class="text-muted small">+ {{ count($rubric->criteria) - 3 }} more</span>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                            <span class="fw-semibold text-strong">Maximum score: {{ $rubric->total_marks }} marks</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light btn-sm border rounded-circle p-2" disabled title="Edit (route pending)">
                                    <i class="fas fa-pen" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm border rounded-circle p-2 text-danger" disabled title="Delete (route pending)">
                                    <i class="far fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 text-center p-5 bg-surface-soft">
                    <i class="fas fa-clipboard-check text-primary opacity-25 mb-3 d-block" style="font-size: 4rem;" aria-hidden="true"></i>
                    <h4 class="h5 fw-bold text-strong mb-2">No grading schemes yet</h4>
                    <p class="text-muted mx-auto mb-4" style="max-width: 400px;">
                        Create your first grading scheme to score and grade student projects and proposals.
                    </p>
                    <a href="{{ route('coordinator.rubrics.create') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-plus me-2" aria-hidden="true"></i> Create scheme
                    </a>
                </div>
            </div>
        @endforelse
    </div>
@endsection
