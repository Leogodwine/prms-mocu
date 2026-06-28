@php
    $rule = $rule ?? null;
    $deptId = old('department_id', $rule?->department_id);
    $level = old('academic_level', $rule?->academic_level ?? 'bachelor');
    $finalYear = old('final_year', $rule?->final_year ?? 3);
    $outputType = old('output_type', $rule?->output_type ?? 'RESEARCH_ONLY');
    $workflowType = old('workflow_type', $rule?->workflow_type ?? 'standard');
    $isActive = old('is_active', $rule?->is_active ?? true);
@endphp

<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}_department_id">Department</label>
    <select id="{{ $prefix }}_department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
        <option value="" disabled @selected(! $deptId)>Select department…</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected((int) $deptId === (int) $department->id)>
                {{ $department->department_name }} ({{ $department->department_code }})
            </option>
        @endforeach
    </select>
    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}_academic_level">Academic level</label>
    <select id="{{ $prefix }}_academic_level" name="academic_level" class="form-select @error('academic_level') is-invalid @enderror" required>
        @foreach ($academicLevels as $academicLevel)
            <option value="{{ $academicLevel->value }}" @selected($level === $academicLevel->value)>
                {{ $academicLevel->label() }}
            </option>
        @endforeach
    </select>
    @error('academic_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-6">
        <label class="form-label" for="{{ $prefix }}_final_year">Final year</label>
        <input type="number" min="1" max="8" id="{{ $prefix }}_final_year" name="final_year"
               value="{{ $finalYear }}" class="form-control @error('final_year') is-invalid @enderror" required>
        @error('final_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-6">
        <label class="form-label" for="{{ $prefix }}_output_type">Output type</label>
        <select id="{{ $prefix }}_output_type" name="output_type" class="form-select @error('output_type') is-invalid @enderror" required>
            @foreach ($outputTypes as $type)
                <option value="{{ $type->value }}" @selected($outputType === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @error('output_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}_workflow_type">Workflow</label>
    <select id="{{ $prefix }}_workflow_type" name="workflow_type" class="form-select" required>
        <option value="standard" @selected($workflowType === 'standard')>Standard (proposal required)</option>
    </select>
</div>

<div class="form-check">
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" type="checkbox" id="{{ $prefix }}_is_active" name="is_active" value="1" @checked($isActive)>
    <label class="form-check-label" for="{{ $prefix }}_is_active">Rule is active</label>
</div>
