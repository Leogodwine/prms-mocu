@php
    $programme = $programme ?? null;
    $allowedYears = old('allowed_project_years', $programme?->allowed_project_years ? implode(',', $programme->allowed_project_years) : '');
@endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Programme code</label>
        <input name="programme_code" class="form-control" required maxlength="20"
               value="{{ old('programme_code', $programme?->programme_code) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">Programme name</label>
        <input name="programme_name" class="form-control" required maxlength="100"
               value="{{ old('programme_name', $programme?->programme_name) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select" required>
            @foreach ($departmentsList as $dept)
                <option value="{{ $dept->id }}" @selected((int) old('department_id', $programme?->department_id) === (int) $dept->id)>
                    {{ $dept->department_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Academic level</label>
        <select name="academic_level" class="form-select" required>
            @foreach ($academicLevels as $level)
                <option value="{{ $level->value }}" @selected(old('academic_level', $programme?->academic_level ?? 'bachelor') === $level->value)>
                    {{ $level->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Duration (years)</label>
        <input type="number" min="1" max="8" name="duration_years" class="form-control" required
               value="{{ old('duration_years', $programme?->duration_years ?? 3) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Final year</label>
        <input type="number" min="1" max="8" name="final_year" class="form-control" required
               value="{{ old('final_year', $programme?->final_year ?? 3) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Allowed project years</label>
        <input name="allowed_project_years" class="form-control" placeholder="e.g. 2,3,4"
               value="{{ $allowedYears }}">
        <div class="form-text">Comma-separated years when project track is allowed.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Output type</label>
        <select name="output_type" class="form-select" required>
            @foreach ($outputTypes as $type)
                <option value="{{ $type->value }}" @selected(old('output_type', $programme?->output_type ?? 'RESEARCH_ONLY') === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Certificate programmes and all diplomas except DBICT are saved as no research or project. Only DBICT may conduct a project.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Workflow type</label>
        <select name="workflow_type" class="form-select" required>
            @foreach ($workflowTypes as $wf)
                <option value="{{ $wf->value }}" @selected(old('workflow_type', $programme?->workflow_type ?? $wf->value) === $wf->value)>
                    {{ $wf->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_project_eligible" value="0">
            <input class="form-check-input" type="checkbox" name="is_project_eligible" value="1"
                   @checked(old('is_project_eligible', $programme?->is_project_eligible ?? false))>
            <label class="form-check-label">Legacy: mark as project-eligible</label>
        </div>
    </div>
</div>
