@extends('layouts.app')

@section('title', ($isOverview ?? false) ? 'Project & research' : \Illuminate\Support\Str::title($workspaceType) . ' workspace')

@push('styles')
<style>
    /* Horizontal step-by-step progress bar — chapters flow left → right
       with a connector line between markers. Wraps gracefully on narrow
       screens. Used in the proposal/research workspace journey card. */
    .prms-stepper {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.25rem 0;
    }
    .prms-step {
        flex: 1 1 0;
        min-width: min(160px, 100%);
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.4rem 0;
    }
    /* Connector line between steps. Hidden on the last step. */
    .prms-step:not(.is-last)::after {
        content: '';
        flex: 1 1 auto;
        height: 2px;
        margin-left: 0.65rem;
        background: var(--prms-border, #e5e7eb);
        border-radius: 2px;
        align-self: center;
    }
    /* Once a step is done, the trailing connector turns green to reinforce
       the "completed up to here" narrative. */
    .prms-step.is-done:not(.is-last)::after {
        background: var(--prms-color-success-500, #22c55e);
    }

    .prms-step-marker {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        background: #f1f5f9;
        color: #6b7280;
        border: 2px solid var(--prms-border, #e5e7eb);
        transition: transform 120ms ease, box-shadow 120ms ease;
    }

    /* States */
    .prms-step.is-done .prms-step-marker {
        background: var(--prms-color-success-500, #22c55e);
        color: #ffffff;
        border-color: var(--prms-color-success-500, #22c55e);
    }
    .prms-step.is-current .prms-step-marker {
        background: var(--prms-primary, #0a2b6b);
        color: #ffffff;
        border-color: var(--prms-primary, #0a2b6b);
        box-shadow: 0 0 0 4px rgba(10, 43, 107, 0.15);
        transform: scale(1.04);
    }
    .prms-step.is-pending .prms-step-marker {
        background: #fff7e6;
        color: #b45309;
        border-color: #f59e0b;
    }
    .prms-step.is-returned .prms-step-marker {
        background: #fef3c7;
        color: #92400e;
        border-color: #f59e0b;
    }
    .prms-step.is-rejected .prms-step-marker {
        background: #fee2e2;
        color: #b91c1c;
        border-color: #ef4444;
    }
    .prms-step.is-draft .prms-step-marker {
        background: #e2e8f0;
        color: #334155;
        border-color: #94a3b8;
    }
    .prms-step.is-locked .prms-step-marker {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
    }

    .prms-step-text { line-height: 1.2; min-width: 0; }
    .prms-step-title {
        display: block;
        font-weight: 600;
        font-size: 0.82rem;
        color: var(--prms-text, #1f2937);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .prms-step-status {
        display: block;
        font-size: 0.62rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--prms-text-muted, #6b7280);
        margin-top: 2px;
    }
    .prms-step.is-done .prms-step-status   { color: var(--prms-color-success-700, #15803d); }
    .prms-step.is-current .prms-step-status{ color: var(--prms-primary, #0a2b6b); }
    .prms-step.is-pending .prms-step-status,
    .prms-step.is-returned .prms-step-status { color: #b45309; }
    .prms-step.is-rejected .prms-step-status { color: #b91c1c; }
    .prms-step.is-draft .prms-step-status { color: #475569; }
    .prms-step.is-locked .prms-step-status { color: #94a3b8; }

    /* On tablets and phones, stack vertically with vertical connectors. */
    @media (max-width: 767.98px) {
        .prms-stepper { flex-direction: column; }
        .prms-step { min-width: 0; }
        .prms-step:not(.is-last)::after {
            position: absolute;
            top: 38px; left: 19px;
            width: 2px; height: calc(100% - 30px);
            margin: 0;
        }
        .prms-step { padding-left: 0; }
    }

    /* File dropzones — valid markup uses <label for> + hidden input, not
       <button> wrapping <input> (that breaks clicks in browsers). */
    .prms-file-dropzone label { cursor: pointer; }
    .cursor-pointer { cursor: pointer; }
    .prms-file-dropzone.prms-dropzone--drag {
        border-style: solid !important;
        border-color: var(--prms-primary, #0a2b6b) !important;
        background-color: var(--prms-primary-soft-strong, #e8eef9) !important;
    }
</style>
@endpush

@section('content')
    @php
        $workspaceBannerSubtitle = ($isOverview ?? false)
            ? 'Track research proposal, research report, and project progress by chapter. Open a track from the sidebar to upload or revise work.'
            : match ($workspaceType) {
                'proposal' => 'Submit and revise your research proposal chapters. Send each stage to your supervisor and act on their feedback.',
                'research' => 'Draft and submit your research report chapters. Track approval status and revisions in one place.',
                'project'  => 'Build your complete system, progress and final presentations, consent letter, and complete project document.',
                default    => 'Send each stage to your supervisor, read feedback, and resubmit revisions all from one place.',
            };
        $showStudentConsentForm = ! ($isOverview ?? false)
            && strtolower((string) $workspaceType) === 'project'
            && ($stages ?? collect())->contains(
                fn ($s) => \App\Support\StudentStageProgress::isConsentLetterStage($s->stage_name)
            );
    @endphp
    <x-prms-greeting-banner :subtitle="$workspaceBannerSubtitle">
        @if ($projectGroup)
            <span class="d-inline-flex align-items-center gap-2 bg-brand-soft text-primary rounded-pill px-3 py-2 fw-semibold">
                <i class="fas fa-users" aria-hidden="true"></i>
                {{ $projectGroup->name }}
            </span>
        @endif
        @if ($showStudentConsentForm)
            <button type="button"
                    class="btn btn-primary rounded-pill px-4 fw-semibold"
                    data-bs-toggle="modal"
                    data-bs-target="#prmsConsentFormModal">
                <i class="fas fa-file-signature me-2" aria-hidden="true"></i> Consent form
            </button>
        @endif
    </x-prms-greeting-banner>

    @if ($showStudentConsentForm)
        @include('student.partials.consent-form-panel', [
            'activeStage' => $currentStage ?? $stages->firstWhere('id', (int) old('stage_id')),
            'isProjectWorkspace' => true,
            'stages' => $stages,
        ])
    @endif

    @if ($isOverview ?? false)
        @include('student.partials.stage-stepper', [
            'trackLabel' => 'Proposal',
            'trackIcon' => 'far fa-file-alt',
            'trackType' => 'proposal',
            'stages' => $proposalStages,
            'latestByStage' => $latestByStage,
        ])

        @if (\App\Support\StudentResearchEligibility::hasTrack($user, 'research'))
            @include('student.partials.stage-stepper', [
                'trackLabel' => 'Research / thesis',
                'trackIcon' => 'fas fa-book-open',
                'trackType' => 'research',
                'stages' => $researchStages,
                'latestByStage' => $latestByStage,
            ])
        @endif

        @if (\App\Support\StudentResearchEligibility::hasTrack($user, 'project'))
            @include('student.partials.stage-stepper', [
                'trackLabel' => 'Project',
                'trackIcon' => 'fas fa-laptop-code',
                'trackType' => 'project',
                'stages' => $projectStages,
                'latestByStage' => $latestByStage,
            ])
        @endif
    @else
        @include('student.partials.stage-stepper', [
            'trackLabel' => \Illuminate\Support\Str::title($workspaceType),
            'trackIcon' => strtolower((string) $workspaceType) === 'project' ? 'fas fa-laptop-code' : null,
            'trackType' => $workspaceType,
            'stages' => $stages,
            'latestByStage' => $latestByStage,
        ])
    @endif

    <div class="row g-4">
        <div class="col-12">
            @if (!($isOverview ?? false) && ($user->isStudentUser() || $user->role === 'coordinator'))
                @php
                    $isProjectWorkspace = strtolower((string) $workspaceType) === 'project';
                    $activeStage = $currentStage ?? $stages->firstWhere('id', (int) old('stage_id'));
                    $activeStageName = strtolower((string) ($activeStage->stage_name ?? ''));
                    $activeUploadBlock = $activeStage
                        ? ($stageUploadBlocks[$activeStage->id] ?? null)
                        : null;
                    $isCompleteSystemStage = $isProjectWorkspace
                        && $activeStage
                        && \App\Support\StudentStageProgress::isCompleteSystemStage($activeStage->stage_name);
                    $isPresentationStageSelected = $activeStage
                        && \App\Support\StudentStageProgress::isPresentationStage($activeStage->stage_name);
                    $isConsentLetterStage = $activeStage
                        && \App\Support\StudentStageProgress::isConsentLetterStage($activeStage->stage_name);
                    $isFinalPresentationStage = $activeStage
                        && \App\Support\StudentStageProgress::isFinalPresentationStage($activeStage->stage_name);
                    $documentLabel  = $isCompleteSystemStage ? 'System source code (folder zipped)' : 'Document file';
                    $documentHint   = $isCompleteSystemStage
                        ? 'ZIP, RAR, 7Z, TAR, TAR.GZ · Max 200 MB · Zip the whole project folder before uploading.'
                        : ($isPresentationStageSelected
                            ? 'PDF, PPT, PPTX, DOC, DOCX · Max 10 MB · Presentation slides or consent letter document.'
                            : 'PDF, DOC, DOCX, ZIP · Max 10 MB');
                    $documentAccept = $isCompleteSystemStage
                        ? '.zip,.rar,.7z,.tar,.gz,.tgz,application/zip,application/x-rar-compressed,application/x-7z-compressed,application/x-tar,application/gzip'
                        : ($isPresentationStageSelected
                            ? '.pdf,.ppt,.pptx,.doc,.docx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'
                            : '.pdf,.doc,.docx,.zip,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip');
                    $headerLabel = $isConsentLetterStage
                        ? 'Request final presentation consent'
                        : ($isCompleteSystemStage
                            ? 'Submit your system & home page'
                            : ($isPresentationStageSelected ? 'Submit presentation or consent letter' : 'Submit a new document'));
                @endphp

                <div class="card mb-4" id="prms-submit-stage-card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-cloud-upload-alt text-primary me-2" aria-hidden="true"></i>
                        <h3 class="card-title h6 fw-bold">{{ $headerLabel }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($isProjectWorkspace && ! $isCompleteSystemStage && ! $isPresentationStageSelected)
                            <div class="alert alert-info d-flex align-items-start gap-2" role="note">
                                <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                                <div class="small mb-0">
                                    After <strong>Complete System</strong> approval, upload <strong>three progress presentations</strong>,
                                    the <strong>Final Presentation Consent Letter</strong>, your <strong>Final Presentation</strong>,
                                    and the <strong>Complete Project Document</strong>. Your supervisor signs the consent
                                    and forwards it to the coordinator; once the coordinator finalizes consent, approved
                                    work is published to the repository.
                                </div>
                            </div>
                        @elseif ($isConsentLetterStage)
                            <div class="alert alert-info d-flex align-items-start gap-2" role="note">
                                <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                                <div class="small mb-0">
                                    Enter your proposed <strong>final presentation date</strong> and send the request to your supervisor.
                                    Your supervisor will review the details, sign the consent form in the system, and send it back to you.
                                    The coordinator then signs to finalize consent before repository release.
                                </div>
                            </div>
                        @elseif ($isPresentationStageSelected && ! $isConsentLetterStage && ! $isFinalPresentationStage)
                            <div class="alert alert-info d-flex align-items-start gap-2" role="note">
                                <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                                <div class="small mb-0">
                                    Complete <strong>three progress presentations</strong>, then submit the
                                    <strong>Final Presentation Consent Letter</strong>, your <strong>Final Presentation</strong>,
                                    and the <strong>Complete Project Document</strong>. Your supervisor signs and forwards
                                    consent to the coordinator before the complete project document can be finalized.
                                </div>
                            </div>
                        @elseif ($isFinalPresentationStage)
                            <div class="alert alert-info d-flex align-items-start gap-2" role="note">
                                <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                                <div class="small mb-0">
                                    Upload your <strong>Final Presentation</strong> slides or document after the consent
                                    letter is approved. Then submit the <strong>Complete Project Document</strong> for
                                    final review.
                                </div>
                            </div>
                        @elseif ($isCompleteSystemStage)
                            <div class="alert alert-info d-flex align-items-start gap-2" role="note">
                                <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                                <div class="small mb-0">
                                    Upload the <strong>complete system folder</strong> as a single zipped archive plus a
                                    <strong>home-page screenshot</strong> and a <strong>short description</strong> so reviewers
                                    and readers understand what the system does before they open it.
                                </div>
                            </div>
                        @endif

                        @if ($isCompleteSystemStage)
                            <p class="small text-muted mb-4">
                                Provide your system name, a brief bio, optional live demo and video links, then attach
                                documentation, the home page screenshot, and the full source archive below.
                            </p>
                        @endif

                        @if (filled($activeUploadBlock))
                            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                                <i class="fas fa-lock mt-1" aria-hidden="true"></i>
                                <div class="small mb-0">{{ $activeUploadBlock }}</div>
                            </div>
                        @endif

                        <form action="{{ route('student.submissions.store') }}" method="POST" enctype="multipart/form-data" novalidate @if(filled($activeUploadBlock)) aria-disabled="true" @endif>
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="stage_id">Academic stage</label>
                                    <select id="stage_id" name="stage_id"
                                            class="form-select @error('stage_id') is-invalid @enderror" required>
                                        <option value="">Select current stage…</option>
                                        @foreach ($stages as $stage)
                                            <option value="{{ $stage->id }}" @selected(old('stage_id', $currentStageId) == $stage->id)>
                                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $stage->stage_name)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('stage_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    @if ($isConsentLetterStage)
                                        <label class="form-label" for="presentation_date">Proposed presentation date <span class="text-danger">*</span></label>
                                        <input id="presentation_date" name="presentation_date" type="date"
                                               value="{{ old('presentation_date') }}"
                                               min="{{ now()->toDateString() }}"
                                               class="form-control @error('presentation_date') is-invalid @enderror" required>
                                        @error('presentation_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @else
                                        <label class="form-label" for="submission_title">{{ $isCompleteSystemStage ? 'System name' : 'Submission title' }}</label>
                                        <input id="submission_title" name="title" type="text" value="{{ old('title') }}"
                                               placeholder="{{ $isCompleteSystemStage ? 'e.g. MoCU Library Management System' : 'e.g. Chapter 1 — first draft' }}"
                                               class="form-control @error('title') is-invalid @enderror" required>
                                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @endif
                                </div>

                                @if ($isCompleteSystemStage)
                                    <div class="col-12">
                                        <label class="form-label" for="submission_description">
                                            Short description / system bio
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea id="submission_description" name="description" rows="4"
                                                  class="form-control @error('description') is-invalid @enderror"
                                                  maxlength="2000" required
                                                  placeholder="A few sentences describing what the system does, the problem it solves, the target users, and the key features. This helps readers understand the system before opening it.">{{ old('description') }}</textarea>
                                        <div class="form-text">Minimum 30 characters · Max 2000.</div>
                                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="demo_url">
                                            <i class="fas fa-globe me-1 text-primary" aria-hidden="true"></i>
                                            Live demo URL
                                            <span class="text-muted small">(optional)</span>
                                        </label>
                                        <input id="demo_url" name="demo_url" type="url" maxlength="500"
                                               value="{{ old('demo_url') }}"
                                               placeholder="https://my-system.example.com"
                                               class="form-control @error('demo_url') is-invalid @enderror">
                                        <div class="form-text">Public URL to a hosted/live version of the system. We embed it inside the showcase so reviewers can interact with it.</div>
                                        @error('demo_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="video_url">
                                            <i class="fab fa-youtube me-1 text-primary" aria-hidden="true"></i>
                                            Video walkthrough URL
                                            <span class="text-muted small">(optional)</span>
                                        </label>
                                        <input id="video_url" name="video_url" type="url" maxlength="500"
                                               value="{{ old('video_url') }}"
                                               placeholder="YouTube, Vimeo, or Loom link"
                                               class="form-control @error('video_url') is-invalid @enderror">
                                        <div class="form-text">Recorded demo / pitch video. YouTube, Vimeo, and Loom links are embedded directly.</div>
                                        @error('video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="prms-documentation-file">
                                            <i class="far fa-file-alt me-1 text-primary" aria-hidden="true"></i>
                                            Documentation / user manual
                                            <span class="text-muted small">(optional)</span>
                                        </label>
                                        {{-- Never nest <input type="file"> inside <button> — browsers break clicks.
                                             Use label + drag target instead. --}}
                                        <div class="prms-dropzone prms-file-dropzone w-100 @error('documentation') is-invalid border-danger @enderror">
                                            <input name="documentation" type="file" id="prms-documentation-file"
                                                   class="visually-hidden"
                                                   accept=".pdf,.doc,.docx,.md,.txt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/markdown,text/plain"
                                                   onchange="prmsHandleDoc(this)">
                                            <label for="prms-documentation-file" class="mb-0 w-100 cursor-pointer">
                                                <i class="far fa-file-pdf text-primary mb-2" aria-hidden="true" style="font-size: 1.6rem;"></i>
                                                <div class="fw-semibold text-strong" id="prms-doc-name" data-empty-label="Drop documentation here or click to browse">Drop documentation here or click to browse</div>
                                                <div class="small text-muted mt-1">PDF, DOC, DOCX, MD, TXT · Max 20 MB · Reviewers can preview it inline.</div>
                                            </label>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                            <label for="prms-documentation-file" class="btn btn-sm btn-outline-secondary mb-0">Choose file</label>
                                            <span class="small text-muted" id="prms-documentation-status">No file chosen</span>
                                        </div>
                                        @error('documentation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    @include('student.partials.home-page-screenshot')

                                    <div class="col-12">
                                        <label class="form-label" for="prms-document-file">
                                            {{ $documentLabel }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="prms-dropzone prms-file-dropzone w-100 @error('document') is-invalid border-danger @enderror">
                                            <input name="document" type="file" id="prms-document-file" required
                                                   accept="{{ $documentAccept }}"
                                                   class="visually-hidden"
                                                   onchange="updateFileName(this)">
                                            <label for="prms-document-file" class="mb-0 w-100 cursor-pointer">
                                                <i class="fas fa-file-archive text-primary mb-2" aria-hidden="true" style="font-size: 1.8rem;"></i>
                                                <div class="fw-semibold text-strong" id="fileName" data-empty-label="Drop your archive here or click to browse">Drop your archive here or click to browse</div>
                                                <div class="small text-muted mt-1">{{ $documentHint }}</div>
                                            </label>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                            <label for="prms-document-file" class="btn btn-sm btn-outline-secondary mb-0">Choose file</label>
                                            <span class="small text-muted" id="prms-document-status">No file chosen</span>
                                        </div>
                                        @error('document') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                @elseif ($isConsentLetterStage)
                                    <div class="col-12">
                                        <div class="border rounded-3 p-3 bg-light">
                                            <p class="small text-muted mb-2">
                                                <i class="fas fa-file-signature text-primary me-1" aria-hidden="true"></i>
                                                No file upload is required. Your supervisor and coordinator will sign the consent form digitally in PRMS.
                                            </p>
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#prmsConsentFormModal">
                                                <i class="fas fa-eye me-1" aria-hidden="true"></i> Preview consent form
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-12">
                                        <label class="form-label" for="prms-document-file">{{ $documentLabel }}</label>
                                        <div class="prms-dropzone prms-file-dropzone w-100 @error('document') is-invalid border-danger @enderror">
                                            <input name="document" type="file" id="prms-document-file" required
                                                   accept="{{ $documentAccept }}"
                                                   class="visually-hidden"
                                                   onchange="updateFileName(this)">
                                            <label for="prms-document-file" class="mb-0 w-100 cursor-pointer">
                                                <i class="fas fa-cloud-upload-alt text-primary mb-2" aria-hidden="true" style="font-size: 1.8rem;"></i>
                                                <div class="fw-semibold text-strong" id="fileName" data-empty-label="Drop file here or click to browse">Drop file here or click to browse</div>
                                                <div class="small text-muted mt-1">{{ $documentHint }}</div>
                                            </label>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                            <label for="prms-document-file" class="btn btn-sm btn-outline-secondary mb-0">Choose file</label>
                                            <span class="small text-muted" id="prms-document-status">No file chosen</span>
                                        </div>
                                        @error('document') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                @endif

                                @if (! $isProjectWorkspace && ($onlyOfficeConfigured ?? false))
                                    <div class="col-12">
                                        <div class="border rounded-3 p-3" style="background: var(--prms-surface-soft);">
                                            <p class="small text-muted mb-2 mb-md-0">
                                                <i class="fas fa-file-word text-primary me-1" aria-hidden="true"></i>
                                                Start a new chapter without uploading a file — create a blank Word document and write directly in the editor.
                                            </p>
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#createBlankSubmissionModal" @disabled(filled($activeUploadBlock))>
                                                <i class="fas fa-plus me-1" aria-hidden="true"></i> Create blank Word document
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-12 d-flex justify-content-end mt-2">
                                    <button class="btn btn-primary" type="submit" @disabled(filled($activeUploadBlock))>
                                        <i class="fas fa-paper-plane me-1" aria-hidden="true"></i>
                                        Send to supervisor
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @php
                $activeStageForList = $currentStage ?? ($stages ?? collect())->firstWhere('id', (int) old('stage_id'));
                $isCompleteSystemListStage = strtolower((string) ($workspaceType ?? '')) === 'project'
                    && $activeStageForList
                    && \App\Support\StudentStageProgress::isCompleteSystemStage($activeStageForList->stage_name);

                $submissionsHeading = ($isOverview ?? false) || $isCompleteSystemListStage
                    ? 'My submissions'
                    : (isset($currentStage)
                        ? \App\Support\StudentStageProgress::shortStageLabel($currentStage->stage_name).' submissions'
                        : match ($workspaceType) {
                            'proposal' => 'Proposal submissions',
                            'research' => 'Research report submissions',
                            'project'  => 'Project submissions',
                            default    => 'My submissions',
                        });

                $submissionTotal = method_exists($submissions, 'total') ? $submissions->total() : $submissions->count();

                $submissionSource = $submissions instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
                    ? $submissions->getCollection()
                    : collect($submissions);

                if ($isOverview ?? false) {
                    $submissionSections = collect($availableTracks ?? \App\Support\StudentStageProgress::tracksForUser($user))
                        ->mapWithKeys(fn (string $track) => [
                            $track => [
                                'label' => \App\Support\StudentStageProgress::trackNavLabel($track),
                                'items' => \App\Support\StudentStageProgress::filterSubmissionsForTrack($submissionSource, $track),
                            ],
                        ])
                        ->filter(fn (array $section) => $section['items']->isNotEmpty());
                } else {
                $submissionSections = collect([
                    (string) $workspaceType => [
                        'label' => $submissionsHeading,
                        'items' => $submissions,
                        'total' => method_exists($submissions, 'total') ? $submissions->total() : $submissions->count(),
                    ],
                ]);
                }

                $submissionDocIcons = $submissionSource
                    ->take(4)
                    ->map(fn ($item) => \App\Support\SubmissionFileAccess::documentIconMeta(
                        $item->mime_type,
                        $item->original_filename
                    ))
                    ->values();
            @endphp

            <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
                <h3 class="h6 fw-bold text-strong d-flex align-items-center mb-0">
                    <i class="fas fa-history text-primary me-2" aria-hidden="true"></i>
                    {{ $submissionsHeading }}
                </h3>
                @if ($submissionTotal > 0)
                    <div class="d-flex align-items-center gap-1"
                         title="{{ $submissionTotal }} {{ $submissionTotal === 1 ? 'submission' : 'submissions' }}">
                        @foreach ($submissionDocIcons as $icon)
                            <span class="badge bg-secondary d-inline-flex align-items-center justify-content-center p-2 rounded-circle"
                                  style="width: 2rem; height: 2rem;"
                                  title="{{ $icon['label'] }}">
                                <i class="{{ $icon['icon'] }} {{ $icon['class'] }}" aria-hidden="true"></i>
                                <span class="visually-hidden">{{ $icon['label'] }}</span>
                            </span>
                        @endforeach
                        @if ($submissionTotal > $submissionDocIcons->count())
                            <span class="badge bg-secondary rounded-pill">+{{ $submissionTotal - $submissionDocIcons->count() }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <x-prms-table-pagination-toolbar :paginator="$submissions" noun="submissions" />

            @foreach ($submissionSections as $sectionTrack => $section)
            @php
                $useGroupedProjectHistory = in_array($sectionTrack, ['project'], true)
                    || ($workspaceType ?? '') === 'project';
                $displayGroups = $useGroupedProjectHistory
                    ? \App\Support\StudentStageProgress::groupSubmissionsForDisplay($section['items'])
                    : null;
            @endphp

            @if ($useGroupedProjectHistory)
                @forelse ($displayGroups as $group)
                    @include('student.partials.submission-history-group', [
                        'group' => $group,
                        'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
                    ])
                @empty
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                                 style="width: 72px; height: 72px;">
                                <i class="far fa-folder-open text-muted" aria-hidden="true" style="font-size: 1.6rem;"></i>
                            </div>
                            <h4 class="h6 fw-bold text-strong">No submissions yet</h4>
                            <p class="text-muted small mb-0">When you upload a file for this workspace, it will appear here with status and supervisor comments.</p>
                        </div>
                    </div>
                @endforelse
            @else
            @forelse ($section['items'] as $submission)
                @include('student.partials.submission-history-card', [
                    'submission' => $submission,
                    'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
                ])
            @empty
                @if (! ($isOverview ?? false))
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                                 style="width: 72px; height: 72px;">
                                <i class="far fa-folder-open text-muted" aria-hidden="true" style="font-size: 1.6rem;"></i>
                            </div>
                            <h4 class="h6 fw-bold text-strong">No submissions yet</h4>
                            <p class="text-muted small mb-0">When you upload a file for this workspace, it will appear here with status and supervisor comments.</p>
                        </div>
                    </div>
                @endif
            @endforelse
            @endif
            @endforeach

            <x-prms-table-pagination-footer :paginator="$submissions" />

            @if (($isOverview ?? false) && $submissionSections->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                             style="width: 72px; height: 72px;">
                            <i class="far fa-folder-open text-muted" aria-hidden="true" style="font-size: 1.6rem;"></i>
                        </div>
                        <h4 class="h6 fw-bold text-strong">No submissions yet</h4>
                        <p class="text-muted small mb-0">Open a track from the sidebar to upload proposal chapters, research report chapters, or project deliverables.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @if (! ($isOverview ?? false) && ($onlyOfficeConfigured ?? false))
        <div class="modal fade" id="createBlankSubmissionModal" tabindex="-1" aria-labelledby="createBlankSubmissionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('student.submissions.create-blank') }}" method="POST" class="modal-content border-0 shadow">
                    @csrf
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-semibold" id="createBlankSubmissionModalLabel">Create blank Word document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">A new .docx file will be created for the selected stage and opened in the Word editor.</p>
                        <div class="mb-3">
                            <label for="blank-stage-id" class="form-label fw-semibold">Academic stage</label>
                            <select id="blank-stage-id" name="stage_id" class="form-select" required>
                                <option value="">Select stage…</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage->id }}" @selected(old('stage_id', $currentStageId) == $stage->id)>
                                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $stage->stage_name)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label for="blank-submission-title" class="form-label fw-semibold">Submission title</label>
                            <input type="text" name="title" id="blank-submission-title" class="form-control"
                                   value="{{ old('title', 'Untitled Document') }}" required maxlength="255"
                                   placeholder="e.g. Proposal Chapter 1">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" @disabled(filled($activeUploadBlock ?? null))>
                            <i class="fas fa-file-word me-1" aria-hidden="true"></i> Create &amp; open editor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Inline document preview modal (shared by every submission card) --}}
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
    function updateFileName(input) {
        const el = document.getElementById('fileName');
        const status = document.getElementById('prms-document-status');
        const empty = el ? el.getAttribute('data-empty-label') : '';
        const file = input.files && input.files[0];
        if (el) el.textContent = file ? file.name : (empty || 'Choose a file');
        if (status) status.textContent = file ? file.name : 'No file chosen';
    }

    function prmsHandleDoc(input) {
        const nameEl = document.getElementById('prms-doc-name');
        const statusEl = document.getElementById('prms-documentation-status');
        const empty = nameEl ? nameEl.getAttribute('data-empty-label') : '';
        const file = input.files && input.files[0];
        if (file) {
            if (nameEl) nameEl.textContent = file.name;
            if (statusEl) statusEl.textContent = file.name;
        } else {
            if (nameEl) nameEl.textContent = empty || 'Drop documentation here or click to browse';
            if (statusEl) statusEl.textContent = 'No file chosen';
        }
    }

    function prmsHandleHomePageScreenshot(input) {
        const label = document.querySelector('.prms-home-page-filename');
        const preview = document.querySelector('.prms-home-page-preview');
        const icon = document.querySelector('.prms-home-page-icon');
        const statusEl = document.querySelector('.prms-home-page-status');
        const empty = label ? label.getAttribute('data-empty-label') : '';
        const file = input.files && input.files[0];

        if (!file) {
            if (label) label.textContent = empty || 'Drop screenshot here or click to browse';
            if (preview) { preview.classList.add('d-none'); preview.removeAttribute('src'); }
            if (icon) icon.classList.remove('d-none');
            if (statusEl) statusEl.textContent = 'No file chosen';
            return;
        }

        if (label) label.textContent = file.name;
        if (statusEl) statusEl.textContent = file.name;

        if (preview && /^image\//.test(file.type)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (icon) icon.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        }
    }

    function prmsHandleInterfaceScreenshot(input) {
        const row = input.closest('.prms-interface-shot-row');
        if (!row) return;

        const label = row.querySelector('.prms-interface-filename');
        const preview = row.querySelector('.prms-interface-preview');
        const icon = row.querySelector('.prms-interface-icon');
        const statusEl = row.querySelector('.prms-interface-status');
        const empty = label ? label.getAttribute('data-empty-label') : '';
        const file = input.files && input.files[0];

        if (!file) {
            if (label) label.textContent = empty || 'Drop screenshot or click to browse';
            if (preview) { preview.classList.add('d-none'); preview.removeAttribute('src'); }
            if (icon) icon.classList.remove('d-none');
            if (statusEl) statusEl.textContent = 'No file chosen';
            return;
        }

        if (label) label.textContent = file.name;
        if (statusEl) statusEl.textContent = file.name;

        if (preview && /^image\//.test(file.type)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (icon) icon.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        }
    }

    function prmsReindexInterfaceRows() {
        const container = document.getElementById('prms-interface-screenshots');
        if (!container) return;

        container.querySelectorAll('.prms-interface-shot-row').forEach((row, index) => {
            row.dataset.index = String(index);
            row.id = 'prms-interface-shot-' + index;

            const select = row.querySelector('.prms-interface-select');
            const custom = row.querySelector('.prms-interface-custom-wrap input');
            const file = row.querySelector('.prms-interface-file');

            if (select) {
                select.name = 'interface_screenshots[' + index + '][interface]';
                select.id = 'interface-select-' + index;
            }
            if (custom) {
                custom.name = 'interface_screenshots[' + index + '][custom_label]';
                custom.id = 'interface-custom-' + index;
            }
            if (file) {
                file.name = 'interface_screenshots[' + index + '][image]';
                file.id = 'interface-file-' + index;
                const fileLabel = row.querySelector('label[for^="interface-file-"]');
                if (fileLabel) fileLabel.setAttribute('for', 'interface-file-' + index);
            }

            const removeBtn = row.querySelector('.prms-remove-interface-shot');
            if (removeBtn) {
                removeBtn.closest('.d-flex')?.classList.toggle('d-none', index === 0);
            }
        });
    }

    function prmsToggleCustomInterface(select) {
        const row = select.closest('.prms-interface-shot-row');
        if (!row) return;
        const wrap = row.querySelector('.prms-interface-custom-wrap');
        if (!wrap) return;
        wrap.classList.toggle('d-none', select.value !== 'other');
    }

    function prmsAssignFileToInput(input, file) {
        if (!input || !file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.prms-file-dropzone').forEach(function (zone) {
            const input = zone.querySelector('input[type="file"]');
            if (!input) return;

            zone.addEventListener('dragenter', function (e) {
                e.preventDefault();
                zone.classList.add('prms-dropzone--drag');
            });
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                zone.classList.add('prms-dropzone--drag');
            });
            zone.addEventListener('dragleave', function (e) {
                if (!zone.contains(e.relatedTarget)) {
                    zone.classList.remove('prms-dropzone--drag');
                }
            });
            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                zone.classList.remove('prms-dropzone--drag');
                const f = e.dataTransfer.files && e.dataTransfer.files[0];
                if (f) prmsAssignFileToInput(input, f);
            });
        });

        /* Restore filenames after validation errors (old() keeps text fields only). */
        const docInput = document.getElementById('prms-document-file');
        if (docInput && docInput.files && docInput.files[0]) updateFileName(docInput);
        const manInput = document.getElementById('prms-documentation-file');
        if (manInput && manInput.files && manInput.files[0]) prmsHandleDoc(manInput);

        const shotContainer = document.getElementById('prms-interface-screenshots');
        const shotTemplate = document.getElementById('prms-interface-shot-template');
        const addShotBtn = document.getElementById('prms-add-interface-shot');

        if (shotContainer) {
            shotContainer.querySelectorAll('.prms-interface-select').forEach((select) => {
                prmsToggleCustomInterface(select);
                select.addEventListener('change', () => prmsToggleCustomInterface(select));
            });

            shotContainer.addEventListener('click', (event) => {
                const removeBtn = event.target.closest('.prms-remove-interface-shot');
                if (!removeBtn) return;
                const row = removeBtn.closest('.prms-interface-shot-row');
                if (row) {
                    row.remove();
                    prmsReindexInterfaceRows();
                }
            });
        }

        if (addShotBtn && shotContainer && shotTemplate) {
            addShotBtn.addEventListener('click', () => {
                const index = shotContainer.querySelectorAll('.prms-interface-shot-row').length;
                const html = shotTemplate.innerHTML.replaceAll('__INDEX__', String(index));
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const row = wrapper.firstElementChild;
                if (!row) return;
                shotContainer.appendChild(row);
                prmsReindexInterfaceRows();
                const select = row.querySelector('.prms-interface-select');
                if (select) {
                    prmsToggleCustomInterface(select);
                    select.addEventListener('change', () => prmsToggleCustomInterface(select));
                }
            });
        }
    });

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

            if (!previewUrl) {
                showFallback('Preview unavailable', ext);
                return;
            }

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
                    .catch(() => {
                        textEl.classList.add('d-none');
                        showFallback('Could not load file', ext);
                    });
                return;
            }

            showFallback('Inline preview not available', ext);
        });

        modal.addEventListener('hidden.bs.modal', function () {
            hideAll();
        });
    })();
</script>
@endpush
