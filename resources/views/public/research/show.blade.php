@extends('layouts.public')

@section('title', $project->title)

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item">
        <a href="{{ route('public.research.index') }}">{{ __('Repository') }}</a>
    </li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item">
        <span class="text-muted">{{ \Illuminate\Support\Str::limit($project->title, 48) }}</span>
    </li>
@endsection

@section('content')
<div class="py-3">

    <div class="row g-4 g-lg-5">
        {{-- ────────── Main content ────────── --}}
        <div class="col-lg-8">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-primary">{{ optional($project->projectType)->type_name ?? 'Research' }}</span>
                <span class="badge bg-secondary">
                    <i class="far fa-calendar me-1" aria-hidden="true"></i>
                    {{ $project->published_at ? $project->published_at->format('F Y') : '—' }}
                </span>
            </div>

            <h1 class="h2 fw-bold text-strong mb-4" style="letter-spacing: -0.02em;">
                {{ $project->title }}
            </h1>

            <div class="card mb-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h5 fw-bold text-primary mb-3">Abstract</h2>
                    <p class="text-muted" style="font-size: 1.05rem; line-height: var(--prms-lh-loose);">
                        {{ $project->abstract ?: 'No abstract available for this publication.' }}
                    </p>

                    <hr class="my-4">

                    <h2 class="h6 fw-bold text-strong mb-3">Document</h2>

                    @if ($document)
                        @if ($isUnderEmbargo)
                            <div class="alert alert-warning mb-0 d-flex gap-3 align-items-start" role="alert">
                                <i class="fas fa-clock mt-1" aria-hidden="true"></i>
                                <div>
                                    <strong>Under embargo until {{ $document->embargo_until->format('F j, Y') }}.</strong><br>
                                    <span class="small">The full text will become publicly available after this date.</span>
                                </div>
                            </div>
                        @else
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <a href="{{ route('public.research.download', $project) }}" class="btn btn-primary">
                                    <i class="fas fa-download me-2" aria-hidden="true"></i>
                                    Download
                                    {{ \Illuminate\Support\Str::upper(pathinfo($document->file_name, PATHINFO_EXTENSION) ?: 'PDF') }}
                                </a>
                                <small class="text-muted">
                                    <i class="far fa-eye me-1" aria-hidden="true"></i>
                                    {{ $document->download_count }} downloads ·
                                    {{ number_format(($document->file_size ?? 0) / 1024, 1) }} KB
                                </small>
                            </div>
                        @endif
                    @else
                        <div class="bg-surface-soft border-soft rounded-3 p-5 text-center">
                            <i class="far fa-file-alt text-muted mb-2" aria-hidden="true" style="font-size: 2rem;"></i>
                            <p class="text-muted small mb-0">
                                No public document is currently attached to this record.<br>
                                Contact the Research Office for institutional access.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ────────── Sidebar ────────── --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-strong mb-4">Publication info</h2>

                    <dl class="mb-0">
                        <dt class="prms-eyebrow">Lead author</dt>
                        <dd class="fw-semibold text-strong d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-user-graduate text-primary" aria-hidden="true"></i>
                            {{ optional($project->student)->name ?? 'MoCU Scholar' }}
                        </dd>

                        @if ($project->projectGroup)
                            <dt class="prms-eyebrow">Research group</dt>
                            <dd class="fw-semibold text-strong d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-users text-primary" aria-hidden="true"></i>
                                {{ $project->projectGroup->name }}
                            </dd>
                            @if ($project->projectGroup->members->count() > 1)
                                <dd>
                                    <ul class="small text-muted mb-3" style="margin-left: 1.65rem;">
                                        @foreach ($project->projectGroup->members as $member)
                                            <li>{{ $member->name }}</li>
                                        @endforeach
                                    </ul>
                                </dd>
                            @endif
                        @endif

                        <dt class="prms-eyebrow">Project type</dt>
                        <dd class="fw-semibold text-strong d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-tag text-primary" aria-hidden="true"></i>
                            {{ optional($project->projectType)->type_name ?? 'Research' }}
                        </dd>

                        <dt class="prms-eyebrow">Publication year</dt>
                        <dd class="fw-semibold text-strong d-flex align-items-center gap-2 mb-3">
                            <i class="far fa-calendar-alt text-primary" aria-hidden="true"></i>
                            {{ $project->published_at ? $project->published_at->format('F Y') : '—' }}
                        </dd>

                        <dt class="prms-eyebrow">Recorded citations</dt>
                        <dd class="fw-semibold text-strong d-flex align-items-center gap-2 mb-0">
                            <i class="fas fa-quote-right text-primary" aria-hidden="true"></i>
                            {{ $project->citation_count ?? 0 }} {{ ($project->citation_count ?? 0) === 1 ? 'citation' : 'citations' }}
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card" id="citations">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-strong mb-3">How to cite</h2>

                    <ul class="nav nav-pills nav-fill small mb-3" id="citation-tabs" role="tablist">
                        @foreach (['apa' => 'APA', 'mla' => 'MLA', 'chicago' => 'Chicago', 'harvard' => 'Harvard'] as $key => $label)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                        id="citation-tab-{{ $key }}"
                                        data-bs-toggle="pill"
                                        data-bs-target="#citation-{{ $key }}"
                                        type="button"
                                        role="tab"
                                        aria-controls="citation-{{ $key }}"
                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $label }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach ($citations as $key => $text)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                 id="citation-{{ $key }}"
                                 role="tabpanel"
                                 aria-labelledby="citation-tab-{{ $key }}">
                                <div class="bg-surface-soft border-soft rounded p-3 small text-strong mb-2 citation-text" data-citation-key="{{ $key }}">
                                    {{ $text }}
                                </div>
                                <button class="btn btn-sm btn-outline-primary w-100 copy-citation"
                                        data-target="#citation-{{ $key }} .citation-text"
                                        type="button">
                                    <i class="far fa-copy me-1" aria-hidden="true"></i>
                                    Copy {{ \Illuminate\Support\Str::upper($key) }} citation
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('.copy-citation');
    if (!trigger) return;

    const targetSelector = trigger.getAttribute('data-target');
    const target = document.querySelector(targetSelector);
    if (!target) return;

    const text = target.innerText.trim();

    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    } else {
        const range = document.createRange();
        range.selectNode(target);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        try { document.execCommand('copy'); } catch (e) {}
        window.getSelection().removeAllRanges();
    }

    const original = trigger.innerHTML;
    trigger.innerHTML = '<i class="fas fa-check me-1" aria-hidden="true"></i> Copied';
    trigger.classList.add('btn-success');
    trigger.classList.remove('btn-outline-primary');
    setTimeout(() => {
        trigger.innerHTML = original;
        trigger.classList.remove('btn-success');
        trigger.classList.add('btn-outline-primary');
    }, 1500);
});
</script>
@endpush
@endsection
