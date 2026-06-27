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
    $workType = strtolower((string) ($project->project_type ?? 'research'));
    $fileExt = $document?->file_name
        ? Str::upper(pathinfo($document->file_name, PATHINFO_EXTENSION) ?: 'PDF')
        : 'PDF';

    $formatLabel = match ($workType) {
        'proposal' => 'Proposal',
        'project' => $fileExt,
        default => $fileExt,
    };

    $year = $project->published_at?->format('Y') ?? '—';
    $sourceLine = $authors->map(fn ($name) => Str::title($name))->join(', ')
        .' - MoCU PRMS Repository, '.$year;

    $citedBy = (int) ($project->citation_count ?? 0);
    $downloads = (int) ($document?->download_count ?? 0);
    $snippet = $project->abstract
        ? Str::limit($project->abstract, 280)
        : 'Approved '.($workType === 'proposal' ? 'research proposal' : 'research report').' from Moshi Co-operative University.';
@endphp

<article class="gs-result">
    <div class="gs-result-format">[{{ $formatLabel }}]</div>
    <h3 class="gs-result-title">
        <a href="{{ route('public.research.show', $project) }}">{{ $project->title }}</a>
    </h3>
    <p class="gs-result-source">{{ $sourceLine }}</p>
    <p class="gs-result-snippet">{{ $snippet }}</p>
    <div class="gs-result-actions" aria-label="Publication actions">
        <a href="{{ route('public.research.show', $project) }}#citations">Cite</a>
        <span class="gs-action-sep" aria-hidden="true">·</span>
        <span class="gs-muted-action">Cited by {{ number_format($citedBy) }}</span>
        @if ($document && ! ($document->embargo_until && $document->embargo_until->isFuture()))
            <span class="gs-action-sep" aria-hidden="true">·</span>
            <a href="{{ route('public.research.download', $project) }}">Download</a>
        @endif
        <span class="gs-action-sep" aria-hidden="true">·</span>
        <a href="{{ route('public.research.show', $project) }}">View record</a>
        @if ($downloads > 0)
            <span class="gs-action-sep" aria-hidden="true">·</span>
            <span class="gs-muted-action">{{ number_format($downloads) }} downloads</span>
        @endif
    </div>
</article>
