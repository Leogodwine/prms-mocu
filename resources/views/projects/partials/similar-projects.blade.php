@php
    $status = $project->similarity_status ?? null;
    $statusLabels = [
        'processing' => ['label' => 'Checking…', 'class' => 'bg-info'],
        'completed' => ['label' => 'Matches found', 'class' => 'bg-warning'],
        'clear' => ['label' => 'No strong matches', 'class' => 'bg-success'],
        'unavailable' => ['label' => 'Service offline', 'class' => 'bg-danger'],
        'disabled' => ['label' => 'Checks disabled', 'class' => 'bg-secondary'],
        'skipped' => ['label' => 'Not enough text', 'class' => 'bg-secondary'],
        'failed' => ['label' => 'Check failed', 'class' => 'bg-danger'],
    ];
    $badge = $statusLabels[$status] ?? null;
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h3 class="h6 fw-bold text-strong mb-0">
                <i class="fas fa-clone me-2 text-primary" aria-hidden="true"></i>
                Similarity review (admin)
            </h3>
            <p class="text-muted small mb-0 mt-1">Background checks against other projects — not visible to students.</p>
        </div>
        @if ($badge)
            <span class="badge rounded-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span>
        @endif
    </div>
    <div class="card-body">
        @if (! ($ollamaReachable ?? true))
            <div class="alert alert-warning small mb-3">
                <strong>Ollama is not reachable.</strong>
                Start the service with <code>ollama serve</code>, install Mistral with <code>ollama pull mistral</code>,
                and run <code>php artisan queue:work</code>.
            </div>
        @elseif ($status === 'unavailable' || $status === 'failed')
            <div class="alert alert-danger small mb-3">
                The last similarity check could not complete. Use the button below to retry after confirming Ollama is running.
            </div>
        @endif

        @if ($similarProjects->isEmpty())
            <p class="text-muted small mb-3">
                @if ($status === 'processing')
                    A background check is in progress. Refresh this page in a moment.
                @elseif (! ($ollamaReachable ?? true))
                    No results until the similarity service is available.
                @else
                    No similar projects above the configured threshold were found.
                @endif
            </p>
        @else
            <ul class="list-group list-group-flush mb-0">
                @foreach ($similarProjects as $match)
                    @php
                        $other = $match->project;
                        $meta = $match->meta;
                        $score = $meta->similarity_score ?? 0;
                        $risk = $meta->risk_level ?? 'low';
                        $riskClass = match ($risk) {
                            'high' => 'danger',
                            'medium' => 'warning',
                            default => 'secondary',
                        };
                    @endphp
                    <li class="list-group-item px-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div class="min-w-0 flex-grow-1">
                                <a href="{{ route('projects.show', $other) }}" class="fw-semibold text-strong text-decoration-none">
                                    {{ $other->project_code ?? 'Project #'.$other->id }}
                                </a>
                                <p class="small text-muted mb-1">{{ \Illuminate\Support\Str::limit($other->title, 120) }}</p>
                                @if ($meta?->summary)
                                    <p class="small mb-1">{{ $meta->summary }}</p>
                                @endif
                                @if (! empty($meta?->overlap_areas))
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($meta->overlap_areas as $area)
                                            <span class="badge bg-light text-dark border">{{ $area }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge bg-{{ $riskClass }} rounded-pill">{{ number_format($score, 0) }}% similar</span>
                                @if ($other->student)
                                    <p class="small text-muted mb-0 mt-1">{{ $other->student->name }}</p>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <form action="{{ route('admin.projects.similarity.rerun', $project) }}" method="POST" class="mt-3 d-flex flex-wrap gap-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-sync me-1" aria-hidden="true"></i> Re-run check
            </button>
            <button type="submit" name="sync" value="1" class="btn btn-sm btn-light border">
                Run now (wait)
            </button>
        </form>
    </div>
</div>
