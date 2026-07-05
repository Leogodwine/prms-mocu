@extends('layouts.app')

@section('title', 'Coordinator workspace')

@section('content')
    <x-prms-greeting-banner subtitle="Manage student eligibility, project groups, supervisor assignments, grading schemes, and deadlines.">
        <x-slot:meta>
            <p class="small text-muted mb-0 d-flex align-items-center gap-2 flex-wrap">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-brand-soft text-primary"
                      style="width: 2rem; height: 2rem;">
                    <i class="fas fa-users" style="font-size: 0.75rem;" aria-hidden="true"></i>
                </span>
                <span><strong class="text-strong">{{ $groups->count() }}</strong> formed groups</span>
            </p>
        </x-slot:meta>
        <a href="{{ route('coordinator.rubrics.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-clipboard-check me-2" aria-hidden="true"></i> Grading schemes
        </a>
        <a href="{{ route('coordinator.deadlines') }}" class="btn btn-light border rounded-pill px-3">
            <i class="far fa-calendar-alt me-1" aria-hidden="true"></i> Deadlines
        </a>
        <a href="{{ route('coordinator.similarities.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-clone me-1" aria-hidden="true"></i> Similar projects
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-primary mb-0 d-flex align-items-center">
                        <i class="fas fa-user-plus text-primary me-2" aria-hidden="true"></i>
                        Groups &amp; individuals
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('coordinator.index') }}" class="row g-2 align-items-end mb-4 bg-surface-soft p-3 rounded-3 border-soft">
                        @csrf
                        <input type="hidden" name="_filter_action" value="apply">
                        <div class="col-md-5">
                            <select name="programme_id" class="form-select form-select-sm" aria-label="Programme">
                                <option value="">All programmes</option>
                                @foreach ($programmes as $prog)
                                    <option value="{{ $prog->id }}" @selected($filters['progId'] == $prog->id)>{{ $prog->programme_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="year" class="form-select form-select-sm" aria-label="Year of study">
                                <option value="">All years</option>
                                @foreach ([1, 2, 3, 4] as $y)
                                    <option value="{{ $y }}" @selected($filters['year'] == $y)>Year {{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-control form-control-sm bg-light text-muted">
                                Department:
                                <span class="fw-semibold text-strong">
                                    {{ $coordinatorDepartment?->department_name ?? 'Not assigned' }}
                                </span>
                            </div>
                        </div>
                    </form>

                    @php
                        $eligibleStudentPool = $eligibleStudentsAll ?? collect($eligibleStudents->items());
                        $eligibleCount = $eligibleStudentPool->count();
                        $withoutGenderCount = $eligibleStudentPool->filter(fn ($s) => $s->normalizedGender() === null)->count();
                    @endphp

                    <div class="mb-4">
                        <div class="btn-group w-100 coordinator-formation-method" role="group" aria-label="Formation method">
                            <input type="radio" class="btn-check" name="formation_method" id="formation_method_manual"
                                   value="manual" autocomplete="off" checked>
                            <label class="btn btn-outline-primary fw-semibold" for="formation_method_manual">
                                <i class="fas fa-hand-pointer me-1" aria-hidden="true"></i> Manual
                            </label>
                            <input type="radio" class="btn-check" name="formation_method" id="formation_method_auto"
                                   value="auto" autocomplete="off">
                            <label class="btn btn-outline-primary fw-semibold" for="formation_method_auto">
                                <i class="fas fa-magic me-1" aria-hidden="true"></i> Auto-group
                            </label>
                        </div>
                    </div>

                    <div id="formation-panel-auto" class="d-none mb-4 p-3 border border-soft rounded-3 bg-surface-soft">
                        <h4 class="h6 fw-bold text-strong mb-2">Auto-group filtered students</h4>
                        <p class="text-muted small mb-3">
                            Forms groups for all <strong>{{ $eligibleCount }}</strong> eligible final-year student(s) with a programme, keeping students within the same programme and same year of study while balancing gender where gender is recorded.
                            With size 2, an odd total leaves one group of 3. With size 3, remainders are merged into the last group or a pair group.
                        </p>
                        @if ($withoutGenderCount > 0)
                            <div class="alert alert-warning py-2 small mb-3">
                                {{ $withoutGenderCount }} student(s) have no gender on file — auto-grouping will still run, but gender balance may be limited.
                            </div>
                        @endif
                        <form method="POST" action="{{ route('coordinator.groups.auto-form') }}" id="coordinator-auto-form"
                              onsubmit="return confirm('Create groups for all {{ $eligibleCount }} eligible student(s) with the selected settings?');">
                            @csrf
                            @if ($filters['progId'])
                                <input type="hidden" name="programme_id" value="{{ $filters['progId'] }}">
                            @endif
                            @if ($filters['year'])
                                <input type="hidden" name="year" value="{{ $filters['year'] }}">
                            @endif
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="auto_group_size">Students per group</label>
                                <select id="auto_group_size" name="group_size" class="form-select" required>
                                    <option value="1">1</option>
                                    <option value="2" selected>2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="auto_name_prefix">Name prefix</label>
                                <input id="auto_name_prefix" name="name_prefix" class="form-control"
                                       placeholder="e.g. BBICT/2026 (groups become BBICT/2026/G01, G02…)"
                                       maxlength="80">
                                <div class="form-text">Optional. Leave blank to use programme code and current year.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold"
                                    {{ $eligibleCount < 1 ? 'disabled' : '' }}>
                                <i class="fas fa-users-cog me-2" aria-hidden="true"></i>
                                Auto-group {{ $eligibleCount }} student{{ $eligibleCount === 1 ? '' : 's' }}
                            </button>
                        </form>
                    </div>

                    <div id="formation-panel-manual">
                    <form method="POST" action="{{ route('coordinator.groups.store') }}" id="coordinator-formation-form">
                        @csrf

                        <fieldset class="mb-4">
                            <legend class="form-label fw-semibold mb-2">Formation type</legend>
                            <div class="btn-group w-100 coordinator-formation-method" role="group" aria-label="Formation type">
                                <input type="radio" class="btn-check" name="formation_type" id="formation_type_group"
                                       value="group" autocomplete="off" checked>
                                <label class="btn btn-outline-primary fw-semibold" for="formation_type_group">
                                    <i class="fas fa-users me-1" aria-hidden="true"></i> Group (2+ students)
                                </label>
                                <input type="radio" class="btn-check" name="formation_type" id="formation_type_individual"
                                       value="individual" autocomplete="off">
                                <label class="btn btn-outline-primary fw-semibold" for="formation_type_individual">
                                    <i class="fas fa-user me-1" aria-hidden="true"></i> Individual
                                </label>
                            </div>
                            <p class="text-muted small mb-0 mt-2" id="formation-type-help-group">
                                Form a multi-student project group from the same programme, then assign one supervisor to the whole group.
                            </p>
                            <p class="text-muted small mb-0 mt-2 d-none" id="formation-type-help-individual">
                                Link a single student to a supervisor using a one-member project record.
                            </p>
                        </fieldset>

                        <div class="mb-3">
                            <label class="form-label" for="group_name">Label</label>
                            <input id="group_name" name="name" placeholder="e.g. BBICT/2024/G14" required
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text" id="name-hint-group">Required for groups. Shown in lists and reports.</div>
                            <div class="form-text d-none" id="name-hint-individual">Optional. If left blank, a name is generated from the student.</div>
                        </div>

                        <div id="formation-panel-group">
                            <label class="form-label mb-2">Select final-year students (minimum 2, same programme and year)</label>
                            <p class="text-muted small mb-2">
                                Only active final-year students with a programme are shown for assignment.
                            </p>
                            <div class="border border-soft rounded-3 mb-4 formation-student-list bg-white" style="max-height: 350px; overflow-y: auto;">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle mb-0 coordinator-eligible-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="text-center" style="width: 2.5rem;">
                                                    <span class="visually-hidden">Select</span>
                                                </th>
                                                <th scope="col">Student</th>
                                                <th scope="col">Registration</th>
                                                <th scope="col">Gender</th>
                                                <th scope="col">Programme</th>
                                                <th scope="col" class="text-nowrap">Year</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($eligibleStudents as $student)
                                                <tr data-programme-id="{{ $student->programme_id ?? '' }}">
                                                    <td class="text-center">
                                                        <input class="form-check-input group-student-cb m-0" type="checkbox" name="student_ids[]"
                                                               value="{{ $student->user->id }}" id="std_{{ $student->id }}"
                                                               data-programme-id="{{ $student->programme_id ?? '' }}">
                                                    </td>
                                                    <td>
                                                        <label class="mb-0 fw-semibold text-strong" for="std_{{ $student->id }}">{{ $student->full_name }}</label>
                                                    </td>
                                                    <td class="text-muted small">
                                                        <code class="small bg-surface-soft px-1 rounded">{{ $student->registration_number }}</code>
                                                    </td>
                                                    <td class="small text-muted">{{ $student->genderLabel() }}</td>
                                                    <td class="small">{{ optional($student->programme)->programme_code ?? '—' }}</td>
                                                    <td class="small text-muted">Year {{ $student->year_of_study }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-5 text-muted">
                                                        <i class="fas fa-user-slash opacity-25 mb-2 d-block" style="font-size: 2.5rem;" aria-hidden="true"></i>
                                                        <p class="mb-0 small">No active final-year students with a programme were found.</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @error('student_ids') <div class="text-danger mb-3 small">{{ $message }}</div> @enderror
                        </div>

                        <div id="formation-panel-individual" class="d-none">
                            <label class="form-label" for="individual_student_id">Student</label>
                            <select id="individual_student_id" name="student_id"
                                    class="form-select mb-4 @error('student_id') is-invalid @enderror">
                                <option value="">Choose a student…</option>
                                @foreach ($eligibleStudents as $student)
                                    <option value="{{ $student->user->id }}">{{ $student->full_name }}
                                        — {{ $student->registration_number }}
                                        @if ($student->programme)
                                            ({{ $student->programme->programme_code }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id') <div class="text-danger mb-3 small">{{ $message }}</div> @enderror
                        </div>

                        <button class="btn btn-primary w-100 py-3 fw-semibold" type="submit" id="formation-submit"
                                {{ $eligibleStudents->count() < 1 ? 'disabled' : '' }}>
                            <i class="fas fa-plus-circle me-2" aria-hidden="true"></i>
                            <span id="formation-submit-label">Create project group</span>
                        </button>
                    </form>
                    </div>{{-- #formation-panel-manual --}}
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="h6 fw-bold text-primary mb-0 d-flex align-items-center">
                        <i class="fas fa-user-shield text-success me-2" aria-hidden="true"></i>
                        Supervisor assignment
                    </h3>
                    <form action="{{ route('coordinator.groups.auto-assign') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill"
                                onclick="return confirm('Assign unassigned groups to supervisors based on workload?')">
                            <i class="fas fa-robot me-1" aria-hidden="true"></i> Auto-assign
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('coordinator.supervisor.assign') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="assign_group">Target group or individual</label>
                            <select id="assign_group" name="project_group_id"
                                    class="form-select @error('project_group_id') is-invalid @enderror" required>
                                <option value="">Choose…</option>
                                @foreach ($groups as $group)
                                    @php $mc = $group->members->count(); @endphp
                                    <option value="{{ $group->id }}">{{ $group->name }}
                                        @if ($mc === 1)
                                            (individual)
                                        @else
                                            ({{ $mc }} members)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('project_group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <div class="form-control form-control-sm bg-light text-muted">
                                Supervisor department:
                                <span class="fw-semibold text-strong">
                                    {{ $coordinatorDepartment?->department_name ?? 'Not assigned' }}
                                </span>
                            </div>
                            <div class="form-text">
                                Only supervisors from the coordinator department are shown.
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="assign_supervisor">Supervisor</label>
                            <select id="assign_supervisor" name="supervisor_id"
                                    class="form-select @error('supervisor_id') is-invalid @enderror"
                                    data-placeholder="Choose a supervisor…"
                                    required>
                                <option value="">Choose a supervisor…</option>
                                @forelse ($supervisors as $staff)
                                    <option value="{{ $staff->user->id }}"
                                            data-department-id="{{ $staff->department_id ?? '' }}">{{ $staff->full_name }}
                                        ({{ $staff->staff_number }})@if (optional($staff->department)->department_name)
                                            — {{ $staff->department->department_name }}
                                        @endif
                                    </option>
                                @empty
                                    <option value="" disabled>No active supervisors found. Add supervisor accounts in User management.</option>
                                @endforelse
                            </select>
                            @error('supervisor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-success w-100 py-3 fw-semibold" type="submit">
                            <i class="fas fa-user-check me-2" aria-hidden="true"></i> Assign now
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-primary mb-0 d-flex align-items-center">
                        <i class="fas fa-list text-primary me-2" aria-hidden="true"></i>
                        Recent formations
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 400px; overflow-y: auto;">
                        @forelse ($groups as $group)
                            <div class="p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                    <h4 class="h6 fw-bold mb-0 text-strong">{{ $group->name }}</h4>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        @php $mc = $group->members->count(); @endphp
                                        <span class="badge bg-info">{{ $mc === 1 ? 'Individual' : $mc . ' members' }}</span>
                                        <a href="{{ route('coordinator.groups.show', $group) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill"
                                           title="View group members and supervisor">
                                            <i class="fas fa-eye me-1" aria-hidden="true"></i>View
                                        </a>
                                    </div>
                                </div>
                                <div class="small text-muted d-flex align-items-center mb-2">
                                    <i class="fas fa-user me-2 text-primary" aria-hidden="true"></i>
                                    Supervisor:
                                    <span class="ms-1 fw-semibold {{ optional(optional($group->supervisorAssignment)->supervisor)->name ? 'text-success' : 'text-danger' }}">
                                        {{ optional(optional($group->supervisorAssignment)->supervisor)->name ?? 'Unassigned' }}
                                    </span>
                                </div>
                                <div class="small text-muted d-flex align-items-start">
                                    <i class="fas fa-users me-2 mt-1" aria-hidden="true"></i>
                                    <span>{{ $group->members->pluck('name')->join(', ') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="far fa-folder-open opacity-25 mb-2 d-block" style="font-size: 2.5rem;" aria-hidden="true"></i>
                                <p class="mb-0 small">No groups created yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .coordinator-formation-method .btn {
        border-color: var(--prms-primary);
        color: var(--prms-primary);
        background-color: var(--prms-surface, #fff);
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .coordinator-formation-method .btn:hover,
    .coordinator-formation-method .btn:focus-visible {
        background-color: var(--prms-primary);
        border-color: var(--prms-primary);
        color: #fff;
    }

    .coordinator-formation-method .btn-check:checked + .btn,
    .coordinator-formation-method .btn-check:checked + .btn.btn-outline-primary,
    .coordinator-formation-method .btn-check:active + .btn {
        background-color: var(--prms-primary) !important;
        border-color: var(--prms-primary) !important;
        color: #fff !important;
        z-index: 1;
    }

    .coordinator-formation-method .btn-check:checked + .btn i,
    .coordinator-formation-method .btn-check:active + .btn i {
        color: #fff !important;
    }

    .coordinator-formation-method .btn-check:checked + .btn:hover,
    .coordinator-formation-method .btn-check:checked + .btn:focus,
    .coordinator-formation-method .btn-check:checked + .btn:focus-visible,
    .coordinator-formation-method .btn-check:checked + .btn:hover i,
    .coordinator-formation-method .btn-check:checked + .btn:focus i {
        background-color: #1e40af !important;
        border-color: #1e40af !important;
        color: #fff !important;
    }

    .coordinator-formation-method .btn-check:focus + .btn {
        box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.35);
    }

    .coordinator-eligible-table tbody tr:nth-child(odd) {
        background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.07);
    }
    .coordinator-eligible-table tbody tr:nth-child(even) {
        background-color: var(--bs-body-bg, #fff);
    }
    .coordinator-eligible-table tbody tr:hover {
        background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.12);
    }
    .coordinator-eligible-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.08);
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const manualPanel = document.getElementById('formation-panel-manual');
        const autoPanel = document.getElementById('formation-panel-auto');
        const methodRadios = document.querySelectorAll('input[name="formation_method"]');

        function syncMethod() {
            const auto = document.getElementById('formation_method_auto')?.checked;
            if (!manualPanel || !autoPanel) return;
            manualPanel.classList.toggle('d-none', auto);
            autoPanel.classList.toggle('d-none', !auto);
        }

        methodRadios.forEach(function (r) { r.addEventListener('change', syncMethod); });
        syncMethod();
    })();

    (function () {
        const form = document.getElementById('coordinator-formation-form');
        if (!form) return;

        const radios = form.querySelectorAll('input[name="formation_type"]');
        const panelGroup = document.getElementById('formation-panel-group');
        const panelInd = document.getElementById('formation-panel-individual');
        const nameInput = document.getElementById('group_name');
        const selectInd = document.getElementById('individual_student_id');
        const groupCbs = form.querySelectorAll('.group-student-cb');
        const helpGroup = document.getElementById('formation-type-help-group');
        const helpInd = document.getElementById('formation-type-help-individual');
        const hintGroup = document.getElementById('name-hint-group');
        const hintInd = document.getElementById('name-hint-individual');
        const submitLabel = document.getElementById('formation-submit-label');

        function sync() {
            const ind = document.getElementById('formation_type_individual').checked;
            panelGroup.classList.toggle('d-none', ind);
            panelInd.classList.toggle('d-none', !ind);
            helpGroup.classList.toggle('d-none', ind);
            helpInd.classList.toggle('d-none', !ind);
            hintGroup.classList.toggle('d-none', ind);
            hintInd.classList.toggle('d-none', !ind);
            nameInput.required = !ind;
            if (ind) {
                nameInput.removeAttribute('required');
            } else {
                nameInput.setAttribute('required', 'required');
            }
            groupCbs.forEach(function (cb) {
                cb.disabled = ind;
                if (ind) cb.checked = false;
            });
            if (selectInd) {
                selectInd.disabled = !ind;
                if (!ind) selectInd.value = '';
            }
            submitLabel.textContent = ind ? 'Create individual & assign later' : 'Create project group';
            syncProgrammeSelection();
        }

        function syncProgrammeSelection() {
            const ind = document.getElementById('formation_type_individual').checked;
            if (ind) {
                return;
            }

            const checked = Array.from(groupCbs).filter(function (cb) { return cb.checked; });
            const lockedProgramme = checked.length > 0
                ? String(checked[0].getAttribute('data-programme-id') ?? '')
                : null;

            groupCbs.forEach(function (cb) {
                if (lockedProgramme === null) {
                    cb.disabled = false;
                    return;
                }

                const sameProgramme = String(cb.getAttribute('data-programme-id') ?? '') === lockedProgramme;
                cb.disabled = !sameProgramme;
            });
        }

        radios.forEach(function (r) { r.addEventListener('change', sync); });
        groupCbs.forEach(function (cb) {
            cb.addEventListener('change', syncProgrammeSelection);
        });
        sync();
    })();

    (function () {
        const deptFilter = document.getElementById('assign_supervisor_department');
        const supSel = document.getElementById('assign_supervisor');
        if (!deptFilter || !supSel) return;

        const placeholderText = String(supSel.getAttribute('data-placeholder') || 'Choose a supervisor…');
        const originalOptions = Array.from(supSel.querySelectorAll('option[data-department-id]')).map(function (opt) {
            return {
                value: String(opt.value || ''),
                text: opt.textContent || '',
                departmentId: String(opt.getAttribute('data-department-id') ?? ''),
            };
        });

        function rebuildSupervisorOptions(filteredOptions, selectedValue) {
            supSel.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = filteredOptions.length > 0
                ? placeholderText
                : 'No supervisors available for this department';
            supSel.appendChild(placeholder);

            filteredOptions.forEach(function (item) {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.text;
                option.setAttribute('data-department-id', item.departmentId);
                supSel.appendChild(option);
            });

            if (selectedValue !== '' && filteredOptions.some(function (item) { return item.value === selectedValue; })) {
                supSel.value = selectedValue;
            } else {
                supSel.value = '';
            }
        }

        function applySupervisorDepartmentFilter() {
            const v = String(deptFilter.value || '');
            const currentValue = String(supSel.value || '');
            const filteredOptions = originalOptions.filter(function (opt) {
                return v === '' || opt.departmentId === v;
            });

            rebuildSupervisorOptions(filteredOptions, currentValue);
            supSel.classList.toggle('border-warning', v !== '' && filteredOptions.length === 0);
        }

        deptFilter.addEventListener('change', applySupervisorDepartmentFilter);
        applySupervisorDepartmentFilter();
    })();
</script>
@endpush
