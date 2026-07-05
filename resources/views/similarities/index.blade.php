@extends('layouts.app')

@section('title', 'Similar projects & research')

@section('breadcrumb')
    @if (! empty($breadcrumb_parent_url))
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="{{ $breadcrumb_parent_url }}">{{ $breadcrumb_parent_label }}</a></li>
    @endif
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ __('Similar projects & research') }}</span></li>
@endsection

@section('content')

<x-prms-greeting-banner :subtitle="$subtitle">
    <form method="POST" action="{{ route($index_route) }}" class="d-flex align-items-center gap-2">
        @csrf
        <input type="hidden" name="_filter_action" value="apply">
        <label for="min_score" class="small text-muted mb-0">Min. score</label>
        <input type="number" name="min_score" id="min_score" class="form-control form-control-sm" style="width: 5rem;"
               min="0" max="100" value="{{ (int) $minScore }}">
    </form>
</x-prms-greeting-banner>

@if (! ($ollamaEnabled ?? true))
    <div class="alert alert-secondary small">
        Similarity checks are disabled in configuration (<code>OLLAMA_ENABLED=false</code>).
    </div>
@elseif (! $ollamaReachable)
    <div class="alert alert-warning d-flex align-items-start gap-2">
        <i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i>
        <div>
            <strong>Ollama is not reachable.</strong>
            Start the service with <code>ollama serve</code> and install Mistral with <code>ollama pull mistral</code>.
            Ensure <code>php artisan queue:work</code> is running for automatic checks.
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if ($pairs->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="fas fa-check-circle fa-2x mb-3 text-success" aria-hidden="true"></i>
                <p class="mb-0">No similarity pairs above {{ (int) $minScore }}%.</p>
            </div>
        @else
            <x-prms-table-pagination-toolbar :paginator="$pairs" noun="pairs" />
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Project A</th>
                            <th>Project B</th>
                            <th class="text-end">Score</th>
                            <th>Risk</th>
                            <th>Summary</th>
                            <th>Checked</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pairs as $pair)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $pair->project) }}" class="fw-semibold text-decoration-none">
                                        {{ $pair->project?->project_code ?? '#'.$pair->project_id }}
                                    </a>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($pair->project?->title, 50) }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('projects.show', $pair->similarProject) }}" class="fw-semibold text-decoration-none">
                                        {{ $pair->similarProject?->project_code ?? '#'.$pair->similar_project_id }}
                                    </a>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($pair->similarProject?->title, 50) }}</div>
                                </td>
                                <td class="text-end fw-bold">{{ number_format($pair->similarity_score, 0) }}%</td>
                                <td>
                                    @php
                                        $riskClass = match ($pair->risk_level) {
                                            'high' => 'danger',
                                            'medium' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $riskClass }}">{{ ucfirst($pair->risk_level) }}</span>
                                </td>
                                <td class="small">{{ \Illuminate\Support\Str::limit($pair->summary, 80) }}</td>
                                <td class="small text-muted">{{ $pair->analyzed_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-prms-table-pagination-footer :paginator="$pairs" class="border-top" />
        @endif
    </div>
</div>

@if ($showBatchCommand ?? false)
    <p class="text-muted small mt-3 mb-0">
        Batch scan: <code>php artisan projects:check-similarities --sync</code>
    </p>
@endif
@endsection
