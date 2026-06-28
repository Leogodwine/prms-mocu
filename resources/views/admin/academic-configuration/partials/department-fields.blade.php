@php
    $department = $department ?? null;
@endphp
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_code">Department code</label>
        <input id="{{ $prefix }}_code" name="department_code" class="form-control" required maxlength="20"
               value="{{ old('department_code', $department?->department_code) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label" for="{{ $prefix }}_name">Department name</label>
        <input id="{{ $prefix }}_name" name="department_name" class="form-control" required maxlength="100"
               value="{{ old('department_name', $department?->department_name) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_hod">Head of department</label>
        <input id="{{ $prefix }}_hod" name="head_of_department" class="form-control" maxlength="100"
               value="{{ old('head_of_department', $department?->head_of_department) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_email">Contact email</label>
        <input id="{{ $prefix }}_email" type="email" name="contact_email" class="form-control"
               value="{{ old('contact_email', $department?->contact_email) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_prog_type">Default programme type</label>
        <input id="{{ $prefix }}_prog_type" name="default_programme_type" class="form-control" maxlength="40"
               placeholder="e.g. bachelor"
               value="{{ old('default_programme_type', $department?->default_programme_type) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_rule_type">Final-year rule type</label>
        <select id="{{ $prefix }}_rule_type" name="final_year_rule_type" class="form-select" required>
            @foreach ($finalYearRuleTypes as $type)
                <option value="{{ $type->value }}" @selected(old('final_year_rule_type', $department?->final_year_rule_type ?? 'PROGRAMME_DEFINED') === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_fixed_year">Fixed final year</label>
        <input type="number" min="1" max="8" id="{{ $prefix }}_fixed_year" name="fixed_final_year" class="form-control"
               value="{{ old('fixed_final_year', $department?->fixed_final_year) }}">
        <div class="form-text">Required when rule type is Fixed year.</div>
    </div>
    <div class="col-md-4">
        <div class="form-check mt-4">
            <input type="hidden" name="supports_project" value="0">
            <input class="form-check-input" type="checkbox" id="{{ $prefix }}_supports_project" name="supports_project" value="1"
                   @checked(old('supports_project', $department?->supports_project ?? true))>
            <label class="form-check-label" for="{{ $prefix }}_supports_project">Supports project output</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check mt-4">
            <input type="hidden" name="supports_research" value="0">
            <input class="form-check-input" type="checkbox" id="{{ $prefix }}_supports_research" name="supports_research" value="1"
                   @checked(old('supports_research', $department?->supports_research ?? true))>
            <label class="form-check-label" for="{{ $prefix }}_supports_research">Supports research output</label>
        </div>
    </div>
</div>
