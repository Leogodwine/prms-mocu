@extends('layouts.app')

@section('title', 'System settings')

@section('content')
    @php
        $map = $configs->pluck('config_value', 'config_key');
        $outputTypeLabels = collect($outputTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()]);
        $levelLabels = collect($academicLevels)->mapWithKeys(fn ($l) => [$l->value => $l->label()]);
    @endphp

    <x-prms-greeting-banner subtitle="Academic lifecycle, deadlines, and global workflow defaults.">
        <a href="{{ route('admin.academic-configuration.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-graduation-cap me-1"></i> Academic configuration
        </a>
        <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#generalSettingsModal">
            <i class="fas fa-calendar-alt me-1" aria-hidden="true"></i> General settings
        </button>
        <form method="POST" action="{{ route('admin.configuration.reevaluate-workflows') }}" class="m-0"
              onsubmit="return confirm('Re-evaluate workflow roles for all students using the current rules?');">
            @csrf
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                <i class="fas fa-sync-alt me-1" aria-hidden="true"></i> Re-evaluate students
            </button>
        </form>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    {{-- Overview --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="prms-stat-card py-3 px-4 h-100">
                <div class="stat-label">Programmes configured</div>
                <div class="stat-value">{{ $programmes->count() }}</div>
                <div class="small text-muted">Each programme defines final year and output type.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="prms-stat-card py-3 px-4 h-100">
                <div class="stat-label">Department overrides</div>
                <div class="stat-value">{{ $departmentRules->count() }}</div>
                <div class="small text-muted">Optional rules by department + academic level.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="prms-stat-card py-3 px-4 h-100">
                <div class="stat-label">Default final year (Bachelor)</div>
                <div class="stat-value">Year {{ $workflowDefaults['final_year']['bachelor'] }}</div>
                <div class="small text-muted">Used when a programme has no explicit final year.</div>
            </div>
        </div>
    </div>

    {{-- Global workflow defaults --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h2 class="h6 fw-bold mb-0">Final-year workflow defaults</h2>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-4">
                These defaults apply when a programme or department rule does not specify a value.
                All final-year students follow the standard workflow: <strong>Proposal → Approval → Execution → Final submission → Evaluation</strong>.
            </p>
            <form method="POST" action="{{ route('admin.configuration.workflow-defaults') }}" class="row g-3 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label for="default_academic_level" class="form-label">Default academic level</label>
                    <select id="default_academic_level" name="default_academic_level" class="form-select @error('default_academic_level') is-invalid @enderror" required>
                        @foreach ($academicLevels as $level)
                            <option value="{{ $level->value }}" @selected(old('default_academic_level', $workflowDefaults['default_academic_level']) === $level->value)>
                                {{ $level->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('default_academic_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="default_workflow_type" class="form-label">Workflow template</label>
                    <select id="default_workflow_type" name="default_workflow_type" class="form-select" required>
                        <option value="STANDARD_RESEARCH_WORKFLOW" @selected(old('default_workflow_type', $workflowDefaults['default_workflow_type']) === 'STANDARD_RESEARCH_WORKFLOW' || old('default_workflow_type', $workflowDefaults['default_workflow_type']) === 'standard')>
                            Standard research workflow (proposal required)
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Save defaults</button>
                </div>
                <div class="col-12"><hr class="my-1"></div>
                <div class="col-12"><span class="prms-eyebrow">Default final year by level</span></div>
                @foreach (['diploma' => 'Diploma', 'bachelor' => 'Bachelor', 'masters' => 'Masters', 'phd' => 'PhD'] as $key => $label)
                    <div class="col-6 col-md-3">
                        <label for="final_year_{{ $key }}" class="form-label">{{ $label }}</label>
                        <input type="number" min="1" max="8" id="final_year_{{ $key }}"
                               name="final_year[{{ $key }}]"
                               value="{{ old('final_year.'.$key, $workflowDefaults['final_year'][$key]) }}"
                               class="form-control @error('final_year.'.$key) is-invalid @enderror" required>
                        @error('final_year.'.$key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </form>
        </div>
    </div>

    {{-- Programme rules --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0">Programme workflow rules</h2>
            @if ($programmes->isEmpty())
                <span class="badge bg-warning-subtle text-warning-emphasis">No programmes — run AcademicStructureSeeder</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if ($programmes->isEmpty())
                <p class="p-4 mb-0 text-muted small">
                    Seed departments and programmes first:
                    <code>php artisan db:seed --class=AcademicStructureSeeder</code>
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Programme</th>
                                <th>Department</th>
                                <th>Level</th>
                                <th>Final year</th>
                                <th>Output type</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($programmes as $programme)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $programme->programme_code }}</div>
                                        <div class="small text-muted">{{ $programme->programme_name }}</div>
                                    </td>
                                    <td class="small">{{ $programme->department?->department_name ?? '—' }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $levelLabels[$programme->academic_level ?? 'bachelor'] ?? 'Bachelor' }}</span></td>
                                    <td>Year {{ $programme->final_year ?? $programme->project_year ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary-emphasis">
                                            {{ $outputTypeLabels[$programme->output_type ?? 'RESEARCH_ONLY'] ?? $programme->output_type }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light border"
                                                data-bs-toggle="modal" data-bs-target="#editProgrammeModal-{{ $programme->id }}">
                                            <i class="fas fa-edit me-1" aria-hidden="true"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Department overrides --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0">Department workflow overrides</h2>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentRuleModal">
                <i class="fas fa-plus me-1" aria-hidden="true"></i> Add rule
            </button>
        </div>
        <div class="card-body p-0">
            @if ($departmentRules->isEmpty())
                <p class="p-4 mb-0 text-muted small">No department overrides. Programme rules apply unless you add a department-level rule here.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Academic level</th>
                                <th>Final year</th>
                                <th>Output type</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($departmentRules as $rule)
                                <tr>
                                    <td>{{ $rule->department?->department_name ?? '—' }}</td>
                                    <td>{{ $levelLabels[$rule->academic_level] ?? $rule->academic_level }}</td>
                                    <td>Year {{ $rule->final_year }}</td>
                                    <td>{{ $outputTypeLabels[$rule->output_type] ?? $rule->output_type }}</td>
                                    <td>
                                        @if ($rule->is_active)
                                            <span class="badge bg-success-subtle text-success-emphasis">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light border me-1"
                                                data-bs-toggle="modal" data-bs-target="#editDeptRuleModal-{{ $rule->id }}">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.configuration.department-rules.destroy', $rule) }}" class="d-inline"
                                              onsubmit="return confirm('Remove this department override?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- General settings summary --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h2 class="h6 fw-bold mb-0">General academic settings</h2>
        </div>
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-4"><strong>Academic year:</strong> {{ $map['academic_year'] ?: '—' }}</div>
                <div class="col-md-4"><strong>Current cycle:</strong> {{ $map['project_cycle'] ?: '—' }}</div>
                <div class="col-md-4"><strong>Min. year for groups:</strong> {{ $map['eligibility_min_year'] ?? '3' }}</div>
                <div class="col-md-4"><strong>Proposal deadline:</strong> {{ $map['deadline_proposal'] ?: '—' }}</div>
                <div class="col-md-4"><strong>Final deadline:</strong> {{ $map['deadline_final'] ?: '—' }}</div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- General settings modal --}}
    <div class="modal fade" id="generalSettingsModal" tabindex="-1" aria-labelledby="generalSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0">
                <form action="{{ route('admin.configuration.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-surface-soft">
                        <h2 class="modal-title h5 fw-bold" id="generalSettingsModalLabel">General academic settings</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="cfg_year">Academic year</label>
                                <input id="cfg_year" name="configs[academic_year]" value="{{ old('configs.academic_year', $map['academic_year'] ?? '') }}"
                                       placeholder="e.g. 2026/2027" class="form-control @error('configs.academic_year') is-invalid @enderror" required>
                                @error('configs.academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="cfg_cycle">Current cycle</label>
                                <input id="cfg_cycle" name="configs[project_cycle]" value="{{ old('configs.project_cycle', $map['project_cycle'] ?? '') }}"
                                       placeholder="e.g. Semester 2" class="form-control @error('configs.project_cycle') is-invalid @enderror" required>
                                @error('configs.project_cycle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="deadline_proposal">Proposal deadline</label>
                                <input type="date" id="deadline_proposal" name="configs[deadline_proposal]"
                                       value="{{ old('configs.deadline_proposal', $map['deadline_proposal'] ?? '') }}"
                                       class="form-control @error('configs.deadline_proposal') is-invalid @enderror" required>
                                @error('configs.deadline_proposal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="deadline_final">Final submission deadline</label>
                                <input type="date" id="deadline_final" name="configs[deadline_final]"
                                       value="{{ old('configs.deadline_final', $map['deadline_final'] ?? '') }}"
                                       class="form-control @error('configs.deadline_final') is-invalid @enderror" required>
                                @error('configs.deadline_final')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="min_year">Minimum year of study (project groups)</label>
                                <input type="number" id="min_year" min="1" max="8" name="configs[eligibility_min_year]"
                                       value="{{ old('configs.eligibility_min_year', $map['eligibility_min_year'] ?? '3') }}"
                                       class="form-control @error('configs.eligibility_min_year') is-invalid @enderror" required>
                                @error('configs.eligibility_min_year')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add department rule --}}
    <div class="modal fade" id="addDepartmentRuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0">
                <form method="POST" action="{{ route('admin.configuration.department-rules.store') }}">
                    @csrf
                    <div class="modal-header bg-surface-soft">
                        <h2 class="modal-title h5 fw-bold">Add department override</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @include('admin.partials.department-workflow-rule-fields', [
                            'departments' => $departments,
                            'academicLevels' => $academicLevels,
                            'outputTypes' => $outputTypes,
                            'prefix' => 'add',
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($programmes as $programme)
        <div class="modal fade" id="editProgrammeModal-{{ $programme->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('admin.configuration.programmes.update', $programme) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-surface-soft">
                            <h2 class="modal-title h5 fw-bold">Edit {{ $programme->programme_code }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="small text-muted mb-3">{{ $programme->programme_name }}</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="prog_level_{{ $programme->id }}">Academic level</label>
                                    <select id="prog_level_{{ $programme->id }}" name="academic_level" class="form-select" required>
                                        @foreach ($academicLevels as $level)
                                            <option value="{{ $level->value }}" @selected(old('academic_level', $programme->academic_level ?? 'bachelor') === $level->value)>
                                                {{ $level->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="prog_duration_{{ $programme->id }}">Duration (years)</label>
                                    <input type="number" min="1" max="8" id="prog_duration_{{ $programme->id }}" name="duration_years"
                                           value="{{ old('duration_years', $programme->duration_years) }}" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="prog_final_{{ $programme->id }}">Final year <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="8" id="prog_final_{{ $programme->id }}" name="final_year"
                                           value="{{ old('final_year', $programme->final_year ?? $programme->project_year ?? 3) }}" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="prog_output_{{ $programme->id }}">Output type <span class="text-danger">*</span></label>
                                    <select id="prog_output_{{ $programme->id }}" name="output_type" class="form-select" required>
                                        @foreach ($outputTypes as $type)
                                            <option value="{{ $type->value }}" @selected(old('output_type', $programme->output_type ?? 'RESEARCH_ONLY') === $type->value)>
                                                {{ $type->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Research = thesis/dissertation. Project = system/product + technical report.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="prog_workflow_{{ $programme->id }}">Workflow</label>
                                    <select id="prog_workflow_{{ $programme->id }}" name="workflow_type" class="form-select" required>
                                        <option value="standard" @selected(old('workflow_type', $programme->workflow_type ?? 'standard') === 'standard')>Standard (proposal required)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="hidden" name="is_project_eligible" value="0">
                                        <input class="form-check-input" type="checkbox" id="prog_project_eligible_{{ $programme->id }}"
                                               name="is_project_eligible" value="1"
                                               @checked(old('is_project_eligible', $programme->is_project_eligible))>
                                        <label class="form-check-label" for="prog_project_eligible_{{ $programme->id }}">
                                            Legacy flag: mark as project-eligible (kept in sync with output type)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save programme rules</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($departmentRules as $rule)
        <div class="modal fade" id="editDeptRuleModal-{{ $rule->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('admin.configuration.department-rules.update', $rule) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-surface-soft">
                            <h2 class="modal-title h5 fw-bold">Edit department override</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            @include('admin.partials.department-workflow-rule-fields', [
                                'departments' => $departments,
                                'academicLevels' => $academicLevels,
                                'outputTypes' => $outputTypes,
                                'prefix' => 'rule_'.$rule->id,
                                'rule' => $rule,
                            ])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update rule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush

@push('scripts')
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.bootstrap) return;
                @if ($errors->has('configs.academic_year') || $errors->has('configs.project_cycle') || $errors->has('configs.deadline_proposal') || $errors->has('configs.deadline_final') || $errors->has('configs.eligibility_min_year'))
                    var general = document.getElementById('generalSettingsModal');
                    if (general) bootstrap.Modal.getOrCreateInstance(general).show();
                @elseif ($errors->has('department_id') || $errors->has('academic_level'))
                    var dept = document.getElementById('addDepartmentRuleModal');
                    if (dept) bootstrap.Modal.getOrCreateInstance(dept).show();
                @endif
            });
        </script>
    @endif
@endpush
