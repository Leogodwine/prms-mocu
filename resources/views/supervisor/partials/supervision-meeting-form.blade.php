@php
    $preselectedTargets = $preselectedTargets ?? collect(old('targets', []));
    $applyToAllOld = $applyToAllOld ?? (bool) old('apply_to_all', false);
    $expandTargetMenu = $expandTargetMenu ?? ($errors->any() || $preselectedTargets->isNotEmpty());
@endphp

<form action="{{ route('supervisor.logs.store') }}" method="POST" id="newSupervisionMeetingForm">
    @csrf
    <div class="mb-3">
        <label class="form-label mb-2 fw-semibold" id="supervision-target-label">Student / Group</label>
        <div class="form-check mb-2">
            <input type="checkbox"
                   class="form-check-input"
                   id="apply_to_all"
                   name="apply_to_all"
                   value="1"
                   @checked($applyToAllOld)>
            <label class="form-check-label" for="apply_to_all">
                Apply to all assigned students and groups
            </label>
        </div>
        <div id="supervision-target-panel" class="@if($applyToAllOld) opacity-50 @endif">
            <div id="supervision-target-chips" class="d-flex flex-wrap gap-1 mb-2 @if($preselectedTargets->isEmpty()) d-none @endif" aria-live="polite"></div>

            <div class="supervision-target-picker border rounded-3 overflow-hidden bg-white">
                <button type="button"
                        class="supervision-target-trigger w-100 border-0 bg-white text-start d-flex align-items-center justify-content-between px-3 py-2 @error('targets') is-invalid @enderror"
                        id="supervision-target-trigger"
                        data-bs-toggle="collapse"
                        data-bs-target="#supervisionTargetMenu"
                        aria-expanded="{{ $expandTargetMenu ? 'true' : 'false' }}"
                        aria-controls="supervisionTargetMenu"
                        @disabled($applyToAllOld)>
                    <span class="text-truncate pe-2 @if($preselectedTargets->isEmpty()) text-muted @endif" id="supervision-target-summary">
                        @if ($preselectedTargets->isNotEmpty())
                            {{ $preselectedTargets->count() }} selected
                        @else
                            Select one or more students / groups
                        @endif
                    </span>
                    <i class="fas fa-chevron-down text-muted supervision-target-chevron flex-shrink-0 @if($expandTargetMenu) supervision-target-chevron--open @endif" aria-hidden="true"></i>
                </button>

                <div class="collapse border-top @if($expandTargetMenu) show @endif" id="supervisionTargetMenu">
                    <div class="supervision-target-menu bg-white">
                        <div class="p-2 border-bottom bg-surface-soft">
                            <div class="btn-group btn-group-sm w-100 supervision-target-filter" role="group" aria-label="Filter targets">
                                <input type="radio" class="btn-check" name="supervision_target_filter" id="target_filter_all" value="all" checked>
                                <label class="btn btn-outline-primary" for="target_filter_all">All</label>
                                @if ($groups->isNotEmpty())
                                    <input type="radio" class="btn-check" name="supervision_target_filter" id="target_filter_groups" value="groups">
                                    <label class="btn btn-outline-primary" for="target_filter_groups">Groups only</label>
                                @endif
                                @if ($students->isNotEmpty())
                                    <input type="radio" class="btn-check" name="supervision_target_filter" id="target_filter_students" value="students">
                                    <label class="btn btn-outline-primary" for="target_filter_students">Individuals only</label>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 border-bottom">
                            <span class="small text-muted">Select one or more</span>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-link btn-sm p-0" id="select-visible-supervision-targets">Select all shown</button>
                                <span class="text-muted small" aria-hidden="true">·</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="clear-all-supervision-targets">Clear all</button>
                            </div>
                        </div>

                        @if ($groups->isNotEmpty() && $students->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2 px-3 py-2 border-bottom">
                                <button type="button" class="btn btn-sm btn-light border rounded-pill" id="select-all-groups-only">
                                    <i class="fas fa-users me-1" aria-hidden="true"></i> All groups
                                </button>
                                <button type="button" class="btn btn-sm btn-light border rounded-pill" id="select-all-students-only">
                                    <i class="fas fa-user me-1" aria-hidden="true"></i> All individuals
                                </button>
                            </div>
                        @endif

                        <div class="supervision-target-list">
                            @if ($groups->isNotEmpty())
                                <div class="supervision-target-section" data-target-section="groups">
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="small fw-semibold text-muted text-uppercase">Groups</span>
                                    </div>
                                    @foreach ($groups as $group)
                                        <div class="supervision-target-option d-flex align-items-start gap-2 px-3 py-2" data-target-kind="group">
                                            <input type="checkbox"
                                                   class="form-check-input supervision-target-cb mt-1 flex-shrink-0"
                                                   name="targets[]"
                                                   id="target-group-{{ $group->id }}"
                                                   value="group:{{ $group->id }}"
                                                   data-target-kind="group"
                                                   data-target-label="{{ $group->name }}"
                                                   @checked($preselectedTargets->contains('group:'.$group->id))
                                                   @disabled($applyToAllOld)>
                                            <label class="small mb-0 flex-grow-1" for="target-group-{{ $group->id }}">{{ $group->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($students->isNotEmpty())
                                <div class="supervision-target-section @if($groups->isNotEmpty()) border-top @endif" data-target-section="students">
                                    <div class="px-3 pt-2 pb-1">
                                        <span class="small fw-semibold text-muted text-uppercase">Individual students</span>
                                    </div>
                                    @foreach ($students as $student)
                                        @php
                                            $studentLabel = $student->name . ($student->registration_number ? ' ('.$student->registration_number.')' : '');
                                        @endphp
                                        <div class="supervision-target-option d-flex align-items-start gap-2 px-3 py-2" data-target-kind="student">
                                            <input type="checkbox"
                                                   class="form-check-input supervision-target-cb mt-1 flex-shrink-0"
                                                   name="targets[]"
                                                   id="target-student-{{ $student->id }}"
                                                   value="student:{{ $student->id }}"
                                                   data-target-kind="student"
                                                   data-target-label="{{ $studentLabel }}"
                                                   @checked($preselectedTargets->contains('student:'.$student->id))
                                                   @disabled($applyToAllOld)>
                                            <label class="small mb-0 flex-grow-1" for="target-student-{{ $student->id }}">
                                                {{ $student->name }}
                                                @if ($student->registration_number)
                                                    <span class="text-muted">({{ $student->registration_number }})</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($groups->isEmpty() && $students->isEmpty())
                                <p class="small text-muted mb-0 px-3 py-3">No assigned students or groups yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">Open the list to pick groups, individuals, or both.</p>
        </div>
        @error('targets') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label for="meeting_date" class="form-label mb-2 fw-semibold">Meeting date</label>
            <input type="date" name="meeting_date" id="meeting_date" class="form-control @error('meeting_date') is-invalid @enderror" value="{{ old('meeting_date', date('Y-m-d')) }}" required>
            @error('meeting_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="meeting_start_time" class="form-label mb-2 fw-semibold">Start time</label>
            <input type="time" name="meeting_start_time" id="meeting_start_time" class="form-control @error('meeting_start_time') is-invalid @enderror" value="{{ old('meeting_start_time', '09:00') }}" required>
            @error('meeting_start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="meeting_end_time" class="form-label mb-2 fw-semibold">End time</label>
            <input type="time" name="meeting_end_time" id="meeting_end_time" class="form-control @error('meeting_end_time') is-invalid @enderror" value="{{ old('meeting_end_time', '10:00') }}" required>
            @error('meeting_end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <label for="progressRange" class="form-label mb-2 fw-semibold">Progress status (%)</label>
            <input type="range" class="form-range" name="progress_score" min="0" max="100" step="5" id="progressRange" value="{{ old('progress_score', 50) }}">
            <div class="text-center fw-bold text-primary" id="rangeValue">{{ old('progress_score', 50) }}%</div>
            @error('progress_score') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-3 mt-3">
        <label for="summary" class="form-label mb-2 fw-semibold">Meeting notes</label>
        <textarea name="summary" id="summary" class="form-control @error('summary') is-invalid @enderror" rows="4" placeholder="Briefly describe the topics discussed during the meeting." required>{{ old('summary') }}</textarea>
        @error('summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-0">
        <label for="next_steps" class="form-label mb-2 fw-semibold">Agreed actions</label>
        <textarea name="next_steps" id="next_steps" class="form-control @error('next_steps') is-invalid @enderror" rows="3" placeholder="List the tasks or objectives to be completed before the next meeting.">{{ old('next_steps') }}</textarea>
        @error('next_steps') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-end pt-4 mt-4 border-top">
        <a href="{{ route('supervisor.logs') }}" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2" aria-hidden="true"></i> Save meeting
        </button>
    </div>
</form>

@push('scripts')
<script>
    (function () {
        const applyAll = document.getElementById('apply_to_all');
        const panel = document.getElementById('supervision-target-panel');
        const trigger = document.getElementById('supervision-target-trigger');
        const menu = document.getElementById('supervisionTargetMenu');
        const summary = document.getElementById('supervision-target-summary');
        const chipsEl = document.getElementById('supervision-target-chips');
        const checkboxes = document.querySelectorAll('.supervision-target-cb');
        const sections = document.querySelectorAll('.supervision-target-section');
        const filterRadios = document.querySelectorAll('input[name="supervision_target_filter"]');
        const selectVisibleBtn = document.getElementById('select-visible-supervision-targets');
        const clearAllBtn = document.getElementById('clear-all-supervision-targets');
        const selectAllGroupsBtn = document.getElementById('select-all-groups-only');
        const selectAllStudentsBtn = document.getElementById('select-all-students-only');
        const placeholder = 'Select one or more students / groups';

        function currentFilter() {
            const checked = document.querySelector('input[name="supervision_target_filter"]:checked');
            return checked ? checked.value : 'all';
        }

        function isVisibleOption(option) {
            return option && !option.classList.contains('d-none') && option.offsetParent !== null;
        }

        function visibleCheckboxes() {
            return Array.from(checkboxes).filter((cb) => {
                const option = cb.closest('.supervision-target-option');
                return !cb.disabled && isVisibleOption(option);
            });
        }

        function selectedCheckboxes() {
            return Array.from(checkboxes).filter((cb) => cb.checked && !cb.disabled);
        }

        function applyFilter() {
            const filter = currentFilter();
            sections.forEach((section) => {
                const kind = section.getAttribute('data-target-section');
                const show = filter === 'all'
                    || (filter === 'groups' && kind === 'groups')
                    || (filter === 'students' && kind === 'students');
                section.classList.toggle('d-none', !show);
            });
        }

        function renderChips() {
            if (!chipsEl) return;
            const selected = selectedCheckboxes();
            chipsEl.innerHTML = '';

            if (selected.length === 0) {
                chipsEl.classList.add('d-none');
                return;
            }

            chipsEl.classList.remove('d-none');
            selected.forEach((cb) => {
                const label = cb.getAttribute('data-target-label') || cb.value;
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'btn btn-sm btn-light border rounded-pill supervision-target-chip';
                chip.setAttribute('data-target-id', cb.id);
                chip.setAttribute('aria-label', 'Remove ' + label);
                const text = document.createElement('span');
                text.className = 'text-truncate';
                text.style.maxWidth = '14rem';
                text.textContent = label;
                const icon = document.createElement('i');
                icon.className = 'fas fa-times ms-1';
                icon.setAttribute('aria-hidden', 'true');
                chip.append(text, icon);
                chip.addEventListener('click', () => {
                    cb.checked = false;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                });
                chipsEl.appendChild(chip);
            });
        }

        function updateSummary() {
            if (!summary) return;
            const selected = selectedCheckboxes();
            if (selected.length === 0) {
                summary.textContent = placeholder;
                summary.classList.add('text-muted');
                renderChips();
                return;
            }
            summary.classList.remove('text-muted');
            if (selected.length === 1) {
                summary.textContent = selected[0].getAttribute('data-target-label') || '1 selected';
            } else {
                const groupCount = selected.filter((cb) => cb.getAttribute('data-target-kind') === 'group').length;
                const studentCount = selected.length - groupCount;
                const parts = [];
                if (groupCount > 0) parts.push(groupCount + ' group' + (groupCount === 1 ? '' : 's'));
                if (studentCount > 0) parts.push(studentCount + ' individual' + (studentCount === 1 ? '' : 's'));
                summary.textContent = parts.join(', ');
            }
            renderChips();
        }

        function setCheckedForKind(kind, checked) {
            checkboxes.forEach((cb) => {
                if (!cb.disabled && cb.getAttribute('data-target-kind') === kind) {
                    cb.checked = checked;
                }
            });
            updateSummary();
        }

        function syncApplyToAllState() {
            const disabled = applyAll && applyAll.checked;
            if (panel) {
                panel.classList.toggle('opacity-50', disabled);
            }
            if (trigger) {
                trigger.disabled = disabled;
            }
            checkboxes.forEach((cb) => {
                cb.disabled = disabled;
                if (disabled) {
                    cb.checked = false;
                }
            });
            if (disabled && menu && window.bootstrap) {
                window.bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();
            }
            updateSummary();
        }

        filterRadios.forEach((radio) => {
            radio.addEventListener('change', applyFilter);
        });

        checkboxes.forEach((cb) => {
            cb.addEventListener('change', updateSummary);
        });

        if (selectVisibleBtn) {
            selectVisibleBtn.addEventListener('click', (event) => {
                event.preventDefault();
                if (applyAll && applyAll.checked) return;
                visibleCheckboxes().forEach((cb) => { cb.checked = true; });
                updateSummary();
            });
        }

        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', (event) => {
                event.preventDefault();
                if (applyAll && applyAll.checked) return;
                checkboxes.forEach((cb) => { cb.checked = false; });
                updateSummary();
            });
        }

        if (selectAllGroupsBtn) {
            selectAllGroupsBtn.addEventListener('click', (event) => {
                event.preventDefault();
                if (applyAll && applyAll.checked) return;
                setCheckedForKind('group', true);
            });
        }

        if (selectAllStudentsBtn) {
            selectAllStudentsBtn.addEventListener('click', (event) => {
                event.preventDefault();
                if (applyAll && applyAll.checked) return;
                setCheckedForKind('student', true);
            });
        }

        if (menu && trigger) {
            menu.addEventListener('show.bs.collapse', () => {
                trigger.setAttribute('aria-expanded', 'true');
                trigger.classList.add('supervision-target-trigger--open');
            });
            menu.addEventListener('hide.bs.collapse', () => {
                trigger.setAttribute('aria-expanded', 'false');
                trigger.classList.remove('supervision-target-trigger--open');
            });
            if (menu.classList.contains('show')) {
                trigger.classList.add('supervision-target-trigger--open');
            }
        }

        applyFilter();
        if (applyAll) {
            applyAll.addEventListener('change', syncApplyToAllState);
            syncApplyToAllState();
        } else {
            updateSummary();
        }
    })();

    const range = document.getElementById('progressRange');
    const badge = document.getElementById('rangeValue');
    if (range && badge) {
        range.addEventListener('input', () => {
            badge.textContent = range.value + '%';
        });
    }
</script>
@endpush

@push('styles')
<style>
    .form-range::-webkit-slider-thumb { background: var(--prms-primary); }

    .supervision-target-trigger {
        min-height: calc(1.5em + 0.75rem + 2px);
        cursor: pointer;
    }
    .supervision-target-trigger:disabled {
        background-color: var(--bs-secondary-bg);
        cursor: not-allowed;
        opacity: 1;
    }
    .supervision-target-trigger--open .supervision-target-chevron,
    .supervision-target-chevron--open {
        transform: rotate(180deg);
    }
    .supervision-target-chevron {
        transition: transform 0.2s ease;
    }
    .supervision-target-filter .btn {
        font-size: 0.78rem;
    }
    .supervision-target-option:hover {
        background-color: var(--bs-tertiary-bg);
    }
    .supervision-target-option .form-check-input,
    .supervision-target-option label {
        cursor: pointer;
    }
    .supervision-target-chip {
        max-width: 100%;
    }
</style>
@endpush
