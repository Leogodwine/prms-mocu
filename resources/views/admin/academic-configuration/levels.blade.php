@extends('layouts.app')

@section('title', 'Academic level configuration')

@section('content')
    <x-prms-greeting-banner subtitle="Configure Diploma, Bachelor, Masters, and PhD defaults used when programme rules are absent."></x-prms-greeting-banner>

    @include('admin.academic-configuration.partials.nav', ['current' => 'levels'])

    <div class="row g-3">
        @foreach ($levelSettings as $setting)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h2 class="h6 fw-bold mb-0">{{ $setting->levelEnum()->label() }}</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.academic-configuration.levels.update', $setting) }}">
                            @csrf @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">Default final year</label>
                                <input type="number" min="1" max="8" name="final_year_default" class="form-control" required
                                       value="{{ old('final_year_default', $setting->final_year_default) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Final stage definition</label>
                                <input name="final_stage_definition" class="form-control" maxlength="255"
                                       value="{{ old('final_stage_definition', $setting->final_stage_definition) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Workflow complexity</label>
                                <select name="workflow_complexity" class="form-select" required>
                                    @foreach (['simplified', 'standard', 'extended'] as $c)
                                        <option value="{{ $c }}" @selected(old('workflow_complexity', $setting->workflow_complexity) === $c)>{{ ucfirst($c) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Default output type</label>
                                <select name="default_output_type" class="form-select" required>
                                    @foreach ($outputTypes as $type)
                                        <option value="{{ $type->value }}" @selected(old('default_output_type', $setting->defaultOutputType()) === $type->value)>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="hidden" name="supports_project" value="0">
                                        <input class="form-check-input" type="checkbox" name="supports_project" value="1"
                                               @checked(old('supports_project', $setting->supportsProject()))>
                                        <label class="form-check-label">Supports project</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="hidden" name="supports_research" value="0">
                                        <input class="form-check-input" type="checkbox" name="supports_research" value="1"
                                               @checked(old('supports_research', $setting->supportsResearch()))>
                                        <label class="form-check-label">Supports research</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Save {{ $setting->levelEnum()->label() }} rules</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
