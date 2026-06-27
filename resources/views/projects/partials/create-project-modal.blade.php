{{-- FR-02: New project form (opened from projects index as a modal) --}}
<div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered prms-create-project-dialog">
        <div class="modal-content border-0 shadow d-flex flex-column h-100 prms-create-project-content">
            <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data" novalidate
                  class="d-flex flex-column min-h-0 h-100 flex-grow-1">
                @csrf
                <div class="modal-header bg-surface-soft border-bottom flex-shrink-0">
                    <div>
                        <h2 class="modal-title h5 fw-bold text-strong mb-0" id="createProjectModalLabel">
                            <i class="fas fa-folder-plus text-primary me-2" aria-hidden="true"></i>
                            Create a new project
                        </h2>
                        <p class="text-muted small mb-0 mt-1">Save as a draft and finish later, or submit for review with a proposal document.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-0 flex-grow-1 min-h-0 d-flex flex-column">
                    <div class="prms-create-project-scroll flex-grow-1 overflow-y-auto px-4 py-4">
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <strong>We couldn’t save your project:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="project_type">Project Type</label>
                            <select class="form-select" id="project_type" name="project_type" required>
                                @foreach (['Proposal','Dissertation','Thesis','Project Report','Research Paper'] as $type)
                                    <option value="{{ $type }}" @selected(old('project_type', 'Proposal') === $type)>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="title">Project Title</label>
                            <input class="form-control" id="title" name="title" type="text" maxlength="500" value="{{ old('title') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="abstract">Research Abstract</label>
                            <textarea class="form-control" id="abstract" name="abstract" rows="4">{{ old('abstract') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="proposal_document">
                                Proposal Document (PDF, DOC, DOCX)
                                <span class="text-muted fw-normal">(optional for Draft, required for Submit)</span>
                            </label>
                            <input
                                class="form-control mt-1"
                                type="file"
                                id="proposal_document"
                                name="proposal_document"
                                accept=".pdf,.doc,.docx"
                            >
                            <div class="form-text">Maximum size: 10MB.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="keywords">Keywords</label>
                            <input class="form-control" id="keywords" name="keywords" type="text" maxlength="255" value="{{ old('keywords') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="research_area">Research Area</label>
                            <input class="form-control" id="research_area" name="research_area" type="text" maxlength="100" value="{{ old('research_area') }}">
                        </div>

                        @php
                            $studentAcademic = $studentAcademic ?? null;
                            $lockedAcademic = is_array($studentAcademic)
                                && (($studentAcademic['department'] ?? null) || ($studentAcademic['programme'] ?? null));
                        @endphp

                        @if ($lockedAcademic)
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Department</label>
                                <input class="form-control" type="text"
                                       value="{{ $studentAcademic['department']?->department_name ?? '—' }}" readonly>
                                @if ($studentAcademic['department'] ?? null)
                                    <input type="hidden" name="department_id" value="{{ $studentAcademic['department']->id }}">
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Programme</label>
                                <input class="form-control" type="text"
                                       value="{{ $studentAcademic['programme']?->programme_name ?? '—' }}" readonly>
                                @if ($studentAcademic['programme'] ?? null)
                                    <input type="hidden" name="program_id" value="{{ $studentAcademic['programme']->id }}">
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Year of study</label>
                                <input class="form-control" type="text"
                                       value="{{ $studentAcademic['year_of_study'] ? 'Year '.$studentAcademic['year_of_study'] : '—' }}" readonly>
                                <div class="form-text">
                                    Research year for this programme: year {{ $studentAcademic['research_year'] }}.
                                    @if ($studentAcademic['includes_project_track'])
                                        Diploma students complete proposal, report, and project.
                                    @else
                                        Complete proposal and research report in this year.
                                    @endif
                                </div>
                            </div>
                            @if (! empty($studentAcademic['research_year_block']))
                                <div class="col-12">
                                    <div class="alert alert-warning small mb-0 py-2">
                                        {{ $studentAcademic['research_year_block'] }}
                                    </div>
                                </div>
                            @endif
                        @else
                        @if ($faculties->isNotEmpty())
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="faculty_id">Faculty</label>
                                <select class="form-select" id="faculty_id" name="faculty_id">
                                    <option value="">— Select —</option>
                                    @foreach ($faculties as $faculty)
                                        <option value="{{ $faculty->id }}" @selected((string) old('faculty_id') === (string) $faculty->id)>
                                            {{ $faculty->faculty_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($departments->isNotEmpty())
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="department_id">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">— Select —</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>
                                            {{ $department->department_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($programs->isNotEmpty())
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="program_id">Program</label>
                                <select class="form-select" id="program_id" name="program_id">
                                    <option value="">— Select —</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}" @selected((string) old('program_id') === (string) $program->id)>
                                            {{ $program->programme_name ?? ($program->program_name ?? 'Programme #'.$program->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($academicYears->isNotEmpty())
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="academic_year_id">Academic Year</label>
                                <select class="form-select" id="academic_year_id" name="academic_year_id">
                                    <option value="">— Select —</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}" @selected(
                                            (string) old('academic_year_id') === (string) $year->id ||
                                            (string) old('academic_year_id') === '' && $currentYear && (int) $year->id === (int) $currentYear->id
                                        )>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($semesters->isNotEmpty())
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="semester_id">Semester</label>
                                <select class="form-select" id="semester_id" name="semester_id">
                                    <option value="">— Select —</option>
                                    @foreach ($semesters as $sem)
                                        <option value="{{ $sem->id }}" @selected(
                                            (string) old('semester_id') === (string) $sem->id ||
                                            (string) old('semester_id') === '' && $currentSemester && (int) $sem->id === (int) $currentSemester->id
                                        )>
                                            {{ $sem->semester_name }}
                                        </option>
                                    @endforeach
                            </select>
                        </div>
                        @endif
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="supervisor_id">Supervisor <span class="text-muted fw-normal">(optional)</span></label>
                            <select class="form-select" id="supervisor_id" name="supervisor_id">
                                <option value="">— Unassigned —</option>
                                @foreach ($supervisors as $supervisor)
                                    <option value="{{ $supervisor->id }}" @selected((string) old('supervisor_id') === (string) $supervisor->id)>
                                        {{ $supervisor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="co_supervisor_id">Co-supervisor <span class="text-muted fw-normal">(optional)</span></label>
                            <select class="form-select" id="co_supervisor_id" name="co_supervisor_id">
                                <option value="">— Unassigned —</option>
                                @foreach ($supervisors as $supervisor)
                                    <option value="{{ $supervisor->id }}" @selected((string) old('co_supervisor_id') === (string) $supervisor->id)>
                                        {{ $supervisor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 prms-create-project-options d-flex flex-wrap align-items-center">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="preview_enabled" name="preview_enabled" value="1"
                                    {{ old('preview_enabled', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="preview_enabled">
                                    Enable online preview
                                </label>
                            </div>
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="collaboration_enabled" name="collaboration_enabled" value="1"
                                    {{ old('collaboration_enabled', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="collaboration_enabled">
                                    Enable inline collaboration
                                </label>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="modal-footer flex-wrap gap-2 border-top flex-shrink-0" style="background-color: var(--prms-surface, #fff);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <div class="d-flex flex-wrap gap-2 ms-auto">
                        <button type="submit" name="submission_mode" value="Draft" class="btn btn-light border"
                                @disabled(! empty($studentAcademic['research_year_block'] ?? null))>
                            <i class="far fa-save me-1" aria-hidden="true"></i> Save draft
                        </button>
                        <button type="submit" name="submission_mode" value="Submitted" class="btn btn-primary"
                                @disabled(! empty($studentAcademic['research_year_block'] ?? null))>
                            <i class="fas fa-paper-plane me-1" aria-hidden="true"></i> Submit for review
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .prms-create-project-dialog {
        max-height: calc(100vh - 1.5rem);
        margin-top: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .prms-create-project-content {
        max-height: min(calc(100vh - 2rem), 920px);
        min-height: 0;
    }
    .prms-create-project-scroll {
        -webkit-overflow-scrolling: touch;
        background-color: var(--prms-surface, #fff);
    }
    /* Kaiadmin pads .form-check — one row, aligned with other form rows */
    #createProjectModal .prms-create-project-options {
        column-gap: 2rem;
        row-gap: 0.5rem;
    }
    #createProjectModal .prms-create-project-options .form-check {
        display: flex !important;
        align-items: center;
        flex-wrap: nowrap;
        gap: 0.5rem;
        background-color: transparent !important;
        padding: 0.25rem 0 !important;
        margin-bottom: 0 !important;
    }
    #createProjectModal .prms-create-project-options .form-check-input {
        float: none;
        margin-top: 0;
        position: relative;
        flex-shrink: 0;
    }
    #createProjectModal .prms-create-project-options .form-check-label {
        color: var(--prms-text, inherit);
        white-space: nowrap;
        margin-bottom: 0;
        padding-top: 0.05rem;
    }
</style>
@endpush
@endonce
