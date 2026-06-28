@extends('layouts.app')

@section('title', 'Eligibility preview')

@section('content')
    <x-prms-greeting-banner subtitle="Select a student and run the rule engine to preview role, workflow, and output assignment."></x-prms-greeting-banner>

    @include('admin.academic-configuration.partials.nav', ['current' => 'preview'])

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h2 class="h6 fw-bold mb-0">Check eligibility</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.academic-configuration.preview.check') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Student account</label>
                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                <option value="" disabled @selected(! old('user_id', $selectedUserId ?? null))>Select student…</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected((int) old('user_id', $selectedUserId ?? 0) === (int) $student->id)>
                                        {{ $student->name }} — {{ $student->login_id }} (Y{{ $student->year_of_study ?? '?' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Check eligibility
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @if (! empty($evaluation))
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h2 class="h6 fw-bold mb-0">Result: {{ $evaluation['student_name'] }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 small">
                            <div class="col-md-6"><strong>Assigned workflow role:</strong><br>
                                <span class="badge bg-primary">{{ $evaluation['workflow_role_label'] }}</span>
                                <code class="ms-1">{{ $evaluation['workflow_role']->value }}</code>
                            </div>
                            <div class="col-md-6"><strong>Mapped user role:</strong><br>{{ $evaluation['mapped_user_role'] }}</div>
                            <div class="col-md-6"><strong>Workflow type:</strong><br>{{ $evaluation['workflow_type_label'] }}</div>
                            <div class="col-md-6"><strong>Output type:</strong><br>{{ $evaluation['output_type_label'] }}</div>
                            <div class="col-md-6"><strong>Output track:</strong><br>{{ $evaluation['output_track']?->label() ?? 'Not yet chosen (BOTH_ALLOWED)' }}</div>
                            <div class="col-md-6"><strong>Final year status:</strong><br>
                                @if ($evaluation['in_final_year'])
                                    <span class="text-success fw-semibold">Final-year eligible</span> (year {{ $evaluation['year_of_study'] }} ≥ {{ $evaluation['final_year'] }})
                                @else
                                    <span class="text-warning fw-semibold">Not final year</span> (year {{ $evaluation['year_of_study'] ?? '—' }} / required {{ $evaluation['final_year'] }})
                                @endif
                            </div>
                            <div class="col-12"><strong>Available tracks:</strong>
                                @if ($evaluation['available_tracks'] === [])
                                    <span class="text-muted">None</span>
                                @else
                                    @foreach ($evaluation['available_tracks'] as $track)
                                        <span class="badge bg-light text-dark border me-1">{{ $track }}</span>
                                    @endforeach
                                @endif
                            </div>
                            @if ($evaluation['workflow_block'])
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0 py-2">{{ $evaluation['workflow_block'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h2 class="h6 fw-bold mb-0">Standard workflow stages</h2>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0 small">
                            @foreach ($evaluation['workflow_stages'] as $stage)
                                <li>{{ $stage }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h2 class="h6 fw-bold mb-0">Rule trace</h2>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush small">
                            @foreach ($evaluation['rule_trace'] as $step)
                                <li class="list-group-item">
                                    <strong>{{ $step['source'] }}:</strong> {{ $step['detail'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-muted text-center py-5">
                        Select a student and click <strong>Check eligibility</strong> to preview how rules apply.
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
