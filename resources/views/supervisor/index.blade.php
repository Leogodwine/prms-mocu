@extends('layouts.app')

@section('title', 'Supervisor workspace')

@section('content')
    @php
        $queueLabels = [
            'pending' => 'Action required',
            'reviewed' => 'Reviewed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        $qf = $queueFilter ?? 'all';
        $queueLabel = $queueLabels[$qf] ?? null;
        $defaultFilters = ['queue' => 'all', 'type' => '', 'stage' => '', 'q' => ''];
        $hasActiveFilters = collect($filters ?? [])->diffAssoc(collect($defaultFilters))->isNotEmpty();
        $queueSummary = $submissions->count().' '.($submissions->count() === 1 ? 'submission' : 'submissions')
            .($hasActiveFilters ? ' (filtered)' : ' in your queue');
        if ($queueLabel) {
            $queueSummary = $queueLabel.': '.$queueSummary;
        }
    @endphp
    <x-prms-greeting-banner subtitle="Review work from your assigned students, leave constructive feedback, and progress each stage.">
        <x-slot:meta>
            <p class="small text-strong mb-0">{{ $queueSummary }}</p>
            @if ($hasActiveFilters)
                <p class="small mb-0 mt-1">
                    <a href="{{ $filterResetUrl }}" class="fw-semibold">Clear all filters</a>
                </p>
            @endif
        </x-slot:meta>
        <a href="{{ route('reports.supervisor') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-chart-bar me-2" aria-hidden="true"></i> Reports
        </a>
        <a href="{{ route('supervisor.logs') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-clipboard-list me-1" aria-hidden="true"></i> Supervision
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <!-- Review submissions -->
    <div class="row g-4">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h3 class="h6 fw-bold text-primary d-flex align-items-center mb-0">
                    <i class="far fa-clipboard text-primary me-2" aria-hidden="true"></i>
                    Review submissions
                </h3>
                @if ($hasActiveFilters)
                    <a href="{{ $filterResetUrl }}" class="btn btn-sm btn-light border rounded-pill px-3">
                        <i class="fas fa-times me-1" aria-hidden="true"></i> Clear filters
                    </a>
                @endif
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-3">
                    <form method="POST" action="{{ route('supervisor.index') }}">
                        @csrf
                        <input type="hidden" name="_filter_action" value="apply">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6 col-lg-3">
                                <label for="sup-filter-q" class="form-label small text-muted mb-1">Search</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-transparent"><i class="fas fa-search text-muted" aria-hidden="true"></i></span>
                                    <input id="sup-filter-q" name="q" value="{{ $filters['q'] ?? '' }}"
                                           placeholder="Title, student, group, or stage…" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <label for="sup-filter-type" class="form-label small text-muted mb-1">Category</label>
                                <select id="sup-filter-type" name="type" class="form-select form-select-sm">
                                    <option value="">All categories</option>
                                    <option value="proposal" @selected(($filters['type'] ?? '') === 'proposal')>Proposal</option>
                                    <option value="research" @selected(($filters['type'] ?? '') === 'research')>Research</option>
                                    <option value="project" @selected(($filters['type'] ?? '') === 'project')>Project</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <label for="sup-filter-stage" class="form-label small text-muted mb-1">Stage</label>
                                <select id="sup-filter-stage" name="stage" class="form-select form-select-sm">
                                    <option value="">All stages</option>
                                    @foreach ($stages ?? [] as $stageOption)
                                        <option value="{{ $stageOption }}" @selected(($filters['stage'] ?? '') === $stageOption)>{{ $stageOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="sup-filter-queue" class="form-label small text-muted mb-1">Status</label>
                                <select id="sup-filter-queue" name="queue" class="form-select form-select-sm">
                                    <option value="all" @selected(($filters['queue'] ?? 'all') === 'all')>All statuses</option>
                                    <option value="pending" @selected(($filters['queue'] ?? '') === 'pending')>Awaiting review</option>
                                    <option value="approved" @selected(($filters['queue'] ?? '') === 'approved')>Approved</option>
                                    <option value="rejected" @selected(($filters['queue'] ?? '') === 'rejected')>Rejected</option>
                                    <option value="reviewed" @selected(($filters['queue'] ?? '') === 'reviewed')>Reviewed (approved or rejected)</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach (['' => 'All', 'proposal' => 'Proposal', 'research' => 'Research', 'project' => 'Project'] as $typeValue => $typeLabel)
                    <form method="POST" action="{{ route('supervisor.index') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="_filter_action" value="apply">
                        <input type="hidden" name="queue" value="{{ $filters['queue'] ?? 'all' }}">
                        <input type="hidden" name="type" value="{{ $typeValue }}">
                        <input type="hidden" name="stage" value="{{ $filters['stage'] ?? '' }}">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        <button type="submit" class="btn btn-sm rounded-pill {{ ($filters['type'] ?? '') === $typeValue ? 'btn-primary' : 'btn-light border' }}">
                            {{ $typeLabel }}
                        </button>
                    </form>
                @endforeach
                <form method="POST" action="{{ route('supervisor.index') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="_filter_action" value="apply">
                    <input type="hidden" name="queue" value="pending">
                    <input type="hidden" name="type" value="{{ $filters['type'] ?? '' }}">
                    <input type="hidden" name="stage" value="{{ $filters['stage'] ?? '' }}">
                    <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                    <button type="submit" class="btn btn-sm rounded-pill {{ ($filters['queue'] ?? 'all') === 'pending' ? 'btn-warning text-dark' : 'btn-light border' }}">
                        Awaiting review
                    </button>
                </form>
            </div>

            @if ($submissions->isNotEmpty())
                @if (($filters['type'] ?? '') === 'project')
                    @include('partials.submission-project-cards', [
                        'submissions' => $submissions,
                        'showReview' => true,
                        'emptyMessage' => 'No project submissions in your queue.',
                    ])
                @else
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="supSubmissionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Title</th>
                                    <th style="width:180px;">Stage</th>
                                    <th>Author(s) / Group Members</th>
                                    <th style="width:130px;">Submission Type</th>
                                    <th style="width:100px;">Group No.</th>
                                    <th style="width:120px;">Date Submitted</th>
                                    <th style="width:130px;">Status</th>
                                    <th class="pe-3 text-end sup-submission-actions-col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($submissions as $submission)
                                    @php
                                        $statusBadge = match ($submission->status) {
                                            'approved'       => 'bg-success',
                                            'rejected'       => 'bg-danger',
                                            'needs_revision' => 'bg-warning text-dark',
                                            'pending'        => 'bg-warning text-dark',
                                            default          => 'bg-secondary',
                                        };
                                        $statusLabel = match ($submission->status) {
                                            'approved'       => 'Approved',
                                            'rejected'       => 'Rejected',
                                            'needs_revision' => 'Returned for revision',
                                            'pending'        => 'Awaiting review',
                                            default          => \Illuminate\Support\Str::title(str_replace('_', ' ', $submission->status)),
                                        };
                                        $isPdf = str_contains((string) $submission->mime_type, 'pdf')
                                            || str_ends_with(strtolower((string) $submission->original_filename), '.pdf');
                                        $previewExt = strtolower(pathinfo((string) $submission->original_filename, PATHINFO_EXTENSION));
                                        $isWordDoc  = in_array($previewExt, ['doc', 'docx'], true);

                                        $docIcon = \App\Support\SubmissionFileAccess::documentIconMeta(
                                            $submission->mime_type,
                                            $submission->original_filename
                                        );
                                        $stageLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', $submission->stage));

                                        // Author(s)
                                        $group   = $submission->projectGroup;
                                        $members = $group && $group->members->isNotEmpty()
                                            ? $group->members->pluck('name')->join(', ')
                                            : optional($submission->student)->name ?? '—';
                                        $isGroup   = $group && $group->members->count() > 1;
                                        $groupNo   = $group ? $group->name : '—';
                                        $subType   = $isGroup ? 'Group' : 'Individual';
                                    @endphp

                                    {{-- Main data row --}}
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2" style="max-width:280px;">
                                                <i class="{{ $docIcon['icon'] }} {{ $docIcon['class'] }} flex-shrink-0"
                                                   style="font-size:1.15rem;"
                                                   title="{{ $docIcon['label'] }}"
                                                   aria-hidden="true"></i>
                                                <span class="fw-semibold text-strong text-truncate"
                                                      title="{{ $submission->title }}">
                                                    {{ $submission->title }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="small text-muted">
                                            <span class="text-strong">{{ $stageLabel }}</span>
                                            <span class="text-muted"> &middot; v{{ $submission->version }}</span>
                                        </td>
                                        <td>
                                            <div class="small" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                                 title="{{ $members }}">
                                                {{ $members }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $isGroup ? 'bg-primary' : 'bg-secondary' }}">
                                                <i class="fas fa-{{ $isGroup ? 'users' : 'user' }} me-1" aria-hidden="true"></i>
                                                {{ $subType }}
                                            </span>
                                        </td>
                                        <td class="small text-muted">{{ $isGroup ? $groupNo : '—' }}</td>
                                        <td class="small text-muted" style="white-space:nowrap;">
                                            {{ $submission->created_at->format('d M Y') }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="pe-3 text-end text-nowrap sup-submission-actions-cell">
                                            @if ($submission->isProjectShowcase())
                                                @php
                                                    $supStatusLabel = match ($submission->status) {
                                                        'approved'       => 'Approved',
                                                        'rejected'       => 'Rejected',
                                                        'needs_revision' => 'Returned for revision',
                                                        'pending'        => 'Awaiting review',
                                                        default          => \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $submission->status)),
                                                    };
                                                    $supStatusBadge = match ($submission->status) {
                                                        'approved'       => 'bg-success',
                                                        'rejected'       => 'bg-danger',
                                                        'needs_revision' => 'bg-warning text-dark',
                                                        'pending'        => 'bg-warning text-dark',
                                                        default          => 'bg-secondary',
                                                    };
                                                @endphp
                                                <a href="{{ route('student.submissions.showcase', ['submission' => $submission, 'return' => url()->current()]) }}"
                                                   class="btn btn-link btn-sm text-primary text-decoration-none px-1">
                                                    <i class="fas fa-rocket me-1" aria-hidden="true"></i>Showcase
                                                </a>
                                                <span class="text-muted" aria-hidden="true">·</span>
                                            @elseif ($isWordDoc && ($onlyOfficeConfigured ?? false))
                                                <a href="{{ route('student.submissions.editor', $submission) }}"
                                                   class="btn btn-link btn-sm text-primary text-decoration-none px-1">
                                                    <i class="fas fa-file-word me-1" aria-hidden="true"></i>Open in Word
                                                </a>
                                                <span class="text-muted" aria-hidden="true">·</span>
                                            @elseif (! $isWordDoc)
                                                <button type="button"
                                                        class="btn btn-link btn-sm text-primary text-decoration-none px-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#prmsPreviewModal"
                                                        data-preview-url="{{ route('student.submissions.preview', $submission) }}"
                                                        data-download-url="{{ route('student.submissions.download', $submission) }}"
                                                        data-file-name="{{ $submission->original_filename ?: $submission->title }}"
                                                        data-mime-type="{{ $submission->mime_type }}"
                                                        data-extension="{{ $previewExt }}"
                                                        data-is-pdf="{{ $isPdf ? '1' : '0' }}">
                                                    <i class="fas fa-eye me-1" aria-hidden="true"></i>View
                                                </button>
                                                <span class="text-muted" aria-hidden="true">·</span>
                                            @endif
                                            <a href="{{ route('student.submissions.download', $submission) }}"
                                               class="btn btn-link btn-sm text-primary text-decoration-none px-1">
                                                <i class="fas fa-download me-1" aria-hidden="true"></i>Download
                                            </a>
                                            <span class="text-muted" aria-hidden="true">·</span>
                                            <button type="button"
                                                    class="btn btn-link btn-sm text-primary text-decoration-none px-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#prmsReviewModal-{{ $submission->id }}">
                                                <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i>Review
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        @if ($hasActiveFilters)
                            <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                                 style="width: 72px; height: 72px;">
                                <i class="fas fa-filter text-muted" aria-hidden="true" style="font-size: 1.6rem;"></i>
                            </div>
                            <h4 class="h6 fw-bold text-strong">No matching submissions</h4>
                            <p class="text-muted small mb-3" style="max-width: 460px; margin: 0 auto;">
                                Try another category, stage, status, or search term.
                            </p>
                            <a href="{{ $filterResetUrl }}" class="btn btn-sm btn-light border rounded-pill px-3">
                                Reset
                            </a>
                        @else
                            <div class="d-inline-flex align-items-center justify-content-center bg-success-soft rounded-circle mb-3"
                                 style="width: 72px; height: 72px; color: var(--prms-color-success-500);">
                                <i class="fas fa-check-double" aria-hidden="true" style="font-size: 1.6rem;"></i>
                            </div>
                            <h4 class="h6 fw-bold text-primary">All caught up</h4>
                            <p class="text-muted small mb-0" style="max-width: 460px; margin: 0 auto;">
                                You've reviewed every pending submission. New work will appear here as soon as students upload their documents.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($submissions->isNotEmpty())
        @include('partials.document-preview-modal')
        @foreach ($submissions as $submission)
            @include('partials.submission-review-modal', ['submission' => $submission])
        @endforeach
    @endif

    {{-- Inline document preview modal (shared by every queue card) --}}
    <div class="modal fade" id="prmsPreviewModal" tabindex="-1" aria-labelledby="prmsPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="height: 85vh;">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center" id="prmsPreviewModalLabel">
                        <i class="far fa-eye text-primary me-2" aria-hidden="true"></i>
                        <span id="prmsPreviewFileName">Document preview</span>
                    </h5>
                    <div class="d-flex gap-2 ms-auto me-2">
                        <a id="prmsPreviewDownload" href="#" class="btn btn-light btn-sm border">
                            <i class="fas fa-download me-1" aria-hidden="true"></i> Download
                        </a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-light" style="overflow: hidden;">
                    <iframe id="prmsPreviewFrame" title="Document preview" src="about:blank"
                            style="width: 100%; height: 100%; border: 0;"></iframe>

                    <div id="prmsPreviewImageWrap" class="d-none h-100 d-flex align-items-center justify-content-center p-3" style="overflow: auto;">
                        <img id="prmsPreviewImage" alt="Document preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>

                    <pre id="prmsPreviewText" class="d-none h-100 m-0 p-3 bg-white" style="overflow: auto; white-space: pre-wrap; word-break: break-word; font-family: var(--bs-font-monospace, monospace); font-size: 0.85rem; color: var(--prms-text);"></pre>

                    <div id="prmsPreviewFallback" class="d-none h-100 d-flex flex-column align-items-center justify-content-center text-center p-4">
                        <i id="prmsPreviewFallbackIcon" class="far fa-file-alt text-primary mb-3" aria-hidden="true" style="font-size: 3rem;"></i>
                        <h4 class="h6 fw-bold text-strong" id="prmsPreviewFallbackTitle">Inline preview not available</h4>
                        <p class="text-muted mb-3" style="max-width: 420px;" id="prmsPreviewFallbackBody">
                            Your browser can natively preview PDFs, images, and plain-text files.
                            For Word documents, archives (ZIP/RAR/7Z), and other binary formats,
                            download the file to open it in a desktop application.
                        </p>
                        <a id="prmsPreviewFallbackDownload" href="#" class="btn btn-primary">
                            <i class="fas fa-download me-1" aria-hidden="true"></i> Download document
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('prmsPreviewModal');
        if (!modal) return;

        const frame      = document.getElementById('prmsPreviewFrame');
        const imageWrap  = document.getElementById('prmsPreviewImageWrap');
        const imageEl    = document.getElementById('prmsPreviewImage');
        const textEl     = document.getElementById('prmsPreviewText');
        const fallback   = document.getElementById('prmsPreviewFallback');
        const fallbackIcon  = document.getElementById('prmsPreviewFallbackIcon');
        const fallbackTitle = document.getElementById('prmsPreviewFallbackTitle');
        const fallbackBody  = document.getElementById('prmsPreviewFallbackBody');
        const fallbackBtn = document.getElementById('prmsPreviewFallbackDownload');
        const dlBtn      = document.getElementById('prmsPreviewDownload');
        const nameEl     = document.getElementById('prmsPreviewFileName');

        const IMAGE_EXTS = ['png','jpg','jpeg','gif','webp','svg','bmp','ico'];
        const TEXT_EXTS  = ['txt','md','markdown','csv','tsv','log','json','xml','html','htm','css','js','ts','py','php','java','c','cpp','rb','go','rs','sh','yml','yaml','ini','env','sql','vue','jsx','tsx'];
        const OFFICE_EXTS  = ['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp','rtf'];
        const ARCHIVE_EXTS = ['zip','rar','7z','tar','gz','tgz','bz2'];

        function hideAll() {
            frame.classList.add('d-none');
            imageWrap.classList.add('d-none');
            textEl.classList.add('d-none');
            fallback.classList.add('d-none');
            frame.setAttribute('src', 'about:blank');
            imageEl.removeAttribute('src');
            textEl.textContent = '';
        }

        function showFallback(reason, ext) {
            fallback.classList.remove('d-none');
            fallbackIcon.className = 'mb-3 text-primary ' + (
                ARCHIVE_EXTS.includes(ext) ? 'fas fa-file-archive'
                : OFFICE_EXTS.includes(ext) ? 'far fa-file-word'
                : 'far fa-file-alt'
            );
            fallbackIcon.style.fontSize = '3rem';
            fallbackTitle.textContent = reason || 'Inline preview not available';
            if (ARCHIVE_EXTS.includes(ext)) {
                fallbackBody.textContent = 'Archives can\'t be expanded in the browser. Download the file and extract it locally.';
            } else if (OFFICE_EXTS.includes(ext)) {
                fallbackBody.textContent = 'Word/Excel/PowerPoint files require a desktop application or Microsoft 365. Download to open in Word, LibreOffice, or Pages.';
            } else {
                fallbackBody.textContent = 'Your browser can natively preview PDFs, images, and plain-text files. For other formats, download the file to open it in a desktop application.';
            }
        }

        modal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;
            const previewUrl  = trigger.getAttribute('data-preview-url');
            const downloadUrl = trigger.getAttribute('data-download-url');
            const fileName    = trigger.getAttribute('data-file-name') || 'Document preview';
            const isPdfFlag   = trigger.getAttribute('data-is-pdf') === '1';
            const mime        = (trigger.getAttribute('data-mime-type') || '').toLowerCase();
            const ext         = (trigger.getAttribute('data-extension') || (fileName.split('.').pop() || '')).toLowerCase();

            nameEl.textContent = fileName;
            dlBtn.setAttribute('href', downloadUrl || '#');
            fallbackBtn.setAttribute('href', downloadUrl || '#');

            hideAll();

            if (!previewUrl) { showFallback('Preview unavailable', ext); return; }

            const isPdf   = isPdfFlag || ext === 'pdf' || mime.includes('pdf');
            const isImage = mime.startsWith('image/') || IMAGE_EXTS.includes(ext);
            const isText  = mime.startsWith('text/') || TEXT_EXTS.includes(ext);

            if (isPdf) {
                frame.classList.remove('d-none');
                frame.setAttribute('src', previewUrl);
                return;
            }
            if (isImage) {
                imageWrap.classList.remove('d-none');
                imageEl.setAttribute('src', previewUrl);
                return;
            }
            if (isText) {
                textEl.classList.remove('d-none');
                textEl.textContent = 'Loading…';
                fetch(previewUrl, { credentials: 'same-origin' })
                    .then(r => r.ok ? r.text() : Promise.reject(r.status))
                    .then(t => { textEl.textContent = t.length > 200000 ? t.slice(0, 200000) + '\n\n… (truncated, download to see the rest)' : t; })
                    .catch(() => { textEl.classList.add('d-none'); showFallback('Could not load file', ext); });
                return;
            }

            showFallback('Inline preview not available', ext);
        });

        modal.addEventListener('hidden.bs.modal', function () {
            hideAll();
        });
    })();

    (function () {
        const reviewSubmissionId = @json(old('_submission_id'));
        if (!reviewSubmissionId || !window.bootstrap) {
            return;
        }

        const modalEl = document.getElementById('prmsReviewModal-' + reviewSubmissionId);
        if (modalEl) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    })();
</script>
@endpush

@push('styles')
<style>
    #supSubmissionsTable .sup-submission-actions-col,
    #supSubmissionsTable .sup-submission-actions-cell {
        white-space: nowrap;
    }
</style>
@endpush
