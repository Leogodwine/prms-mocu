@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

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
    if ($authors->isEmpty()) {
        $authors->push('MoCU Scholar');
    }

    $document = $project->documents->first();
    $previewUrl = $document?->preview_file_path && Storage::disk('public')->exists($document->preview_file_path)
        ? Storage::disk('public')->url($document->preview_file_path)
        : null;

    $workType = strtolower((string) ($project->project_type ?? 'research'));
    $publicationType = match (true) {
        $workType === 'proposal' => 'Proposal',
        $workType === 'project' && $previewUrl !== null => 'Source',
        $workType === 'project' => 'Book',
        default => 'Article',
    };

    $publishedLabel = $project->published_at ? $project->published_at->format('M Y') : '—';
    $doiSuffix = Str::lower(Str::replace(['PRJ-', '-', '_'], '', (string) ($project->project_code ?: 'rp'.$project->id)));
    $doi = '10.64751/'.$doiSuffix;
    $isbn = trim((string) ($project->ethical_clearance_number ?? ''));
@endphp

<article class="card border-0 shadow-sm public-publication-card h-100">
    <div class="card-body p-4">
        <div class="row g-3 align-items-start">
            <div class="{{ $previewUrl ? 'col-md-8' : 'col-12' }}">
                <h3 class="h5 fw-bold text-strong mb-2">
                    <a href="{{ route('public.research.show', $project) }}" class="text-strong text-decoration-none publication-title-link">
                        {{ $project->title }}
                    </a>
                </h3>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-brand-soft text-primary publication-type-badge">
                        {{ $publicationType }}
                    </span>
                    <span class="small text-muted publication-meta">
                        {{ $publishedLabel }}
                    </span>
                    <span class="small text-muted publication-meta">DOI: {{ $doi }}</span>
                    @if ($isbn !== '')
                        <span class="small text-muted publication-meta">ISBN: {{ $isbn }}</span>
                    @endif
                </div>

                <p class="small text-strong mb-2 publication-authors">
                    {{ $authors->map(fn ($name) => Str::title($name))->join(' ') }}
                </p>

                @if ($project->abstract)
                    @include('partials.public-publication-description', [
                        'text' => $project->abstract,
                        'id' => 'pub-'.$project->id,
                    ])
                @endif
            </div>

            @if ($previewUrl)
                <div class="col-md-4">
                    <a href="{{ route('public.research.show', $project) }}"
                       class="d-block rounded-3 border overflow-hidden publication-preview-link"
                       title="Preview image for {{ $project->title }}">
                        <img src="{{ $previewUrl }}"
                             alt="Preview image for {{ $project->title }}"
                             class="w-100 publication-preview-image"
                             style="aspect-ratio: 4/3; object-fit: cover;">
                    </a>
                </div>
            @endif
        </div>
    </div>
</article>
