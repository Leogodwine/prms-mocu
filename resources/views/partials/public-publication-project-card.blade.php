@php
    use App\Support\PublicPortalPublication;
    use Illuminate\Support\Str;

    $showcase = PublicPortalPublication::resolveShowcaseSubmission($project);
    $document = $project->documents->first();
    $displayTitle = $showcase?->title ?: $project->title;
    $stageLabel = $showcase
        ? Str::title(str_replace('_', ' ', (string) $showcase->stage))
        : 'Complete Project Document';
    $version = $showcase?->version ?? $document?->version_number ?? 1;
    $publishedOn = $project->published_at?->format('M d, Y') ?? '—';
    $description = trim((string) ($showcase?->description ?: $project->abstract ?: ''));

    $authors = collect();
    if ($project->student) {
        $authors->push($project->student->name);
    }
    if ($project->projectGroup) {
        foreach ($project->projectGroup->members as $member) {
            if (! $authors->contains($member->name)) {
                $authors->push($member->name);
            }
        }
    }
@endphp

<div class="col">
    <article class="card h-100 border-0 shadow-sm public-project-card">
        <div class="card-body d-flex flex-column p-4">
            @if ($showcase)
                <div class="public-project-card__preview mb-3">
                    @include('partials.project-interface-preview', [
                        'submission' => $showcase,
                        'publicProject' => $project,
                        'size' => 'md',
                    ])
                </div>
            @endif

            <h3 class="h5 fw-bold text-strong mb-2 public-project-card__title">
                <a href="{{ route('public.research.show', $project) }}" class="text-strong text-decoration-none publication-title-link">
                    {{ $displayTitle }}
                </a>
            </h3>

            <p class="small text-muted mb-2">
                <i class="far fa-calendar me-1" aria-hidden="true"></i>{{ $publishedOn }}
                <span class="mx-1">·</span>
                <i class="fas fa-layer-group me-1" aria-hidden="true"></i>{{ $stageLabel }}
                <span class="mx-1">·</span>v{{ $version }}
            </p>

            @if ($authors->isNotEmpty())
                <p class="small text-strong mb-2 publication-authors">
                    {{ $authors->map(fn ($name) => Str::title($name))->join(', ') }}
                </p>
            @endif

            @if ($description !== '')
                @include('partials.public-publication-description', [
                    'text' => $description,
                    'id' => 'project-'.$project->id,
                    'showQuoteIcon' => true,
                    'class' => 'mb-3',
                ])
            @endif

            <div class="mt-auto pt-1">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge bg-success">
                        <i class="fas fa-check-circle me-1" aria-hidden="true"></i>Published
                    </span>
                    <span class="badge bg-success">
                        <i class="fas fa-globe me-1" aria-hidden="true"></i>In repository
                    </span>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if ($showcase && $showcase->isProjectShowcase())
                        <a href="{{ route('public.research.showcase', $project) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1" aria-hidden="true"></i>Preview
                        </a>
                    @endif
                    @if ($document)
                        <a href="{{ route('public.research.download', $project) }}" class="btn btn-light btn-sm border">
                            <i class="fas fa-download me-1" aria-hidden="true"></i>Complete document
                        </a>
                    @endif
                    @if ($showcase?->file_path)
                        <a href="{{ route('public.research.source-download', $project) }}" class="btn btn-light btn-sm border">
                            <i class="fas fa-file-archive me-1" aria-hidden="true"></i>Source code
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </article>
</div>
