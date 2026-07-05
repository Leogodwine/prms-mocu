@extends('layouts.app')

@section('title', 'Grading scheme evaluation')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><a href="{{ route('supervisor.index') }}">{{ __('Supervisor workspace') }}</a></li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ __('Evaluation') }}</span></li>
@endsection

@section('content')
    @php
        $activeRubric = $systemRubric ?? $rubrics->first();
    @endphp

    <x-prms-greeting-banner :subtitle="($submission->title ?: 'Submission') . ' · Stage: ' . \Illuminate\Support\Str::title(str_replace('_', ' ', $submission->stage)) . ' · Version ' . $submission->version">
        <a href="{{ route('supervisor.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back to queue
        </a>
    </x-prms-greeting-banner>

    @if ($rubrics->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <i class="fas fa-clipboard-list text-muted opacity-50" style="font-size: 4rem;" aria-hidden="true"></i>
                <h5 class="fw-bold mt-3">No grading schemes available</h5>
                <p class="text-muted">Ask your coordinator to publish a grading scheme before you score this submission.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if ($presentationGroupGrading ?? false)
                    <div class="alert alert-light border small mb-4" role="note">
                        <i class="fas fa-users me-1 text-primary" aria-hidden="true"></i>
                        <strong>Group presentation.</strong>
                        Score the <strong>group as a whole</strong>, then grade <strong>each member individually</strong> using the same grading scheme.
                    </div>
                @endif

                <form method="POST" action="{{ route('supervisor.evaluate.store', $submission) }}" id="grading-scheme-form">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-bold d-block">Grading scheme</label>
                        @if ($systemRubric)
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-primary rounded-pill">Applied to all supervisors</span>
                                <span class="fw-semibold text-strong">{{ $systemRubric->name }} ({{ $systemRubric->total_marks ?? 100 }} pts)</span>
                            </div>
                            <input type="hidden" name="evaluation_rubric_id" value="{{ $systemRubric->id }}">
                            <p class="form-text mb-0">Coordinators set this scheme for every supervisor.</p>
                        @else
                            <select id="evaluation_rubric_id" name="evaluation_rubric_id" class="form-select" required>
                                @foreach ($rubrics as $rubric)
                                    <option value="{{ $rubric->id }}"
                                        @selected((int) old('evaluation_rubric_id', $existing?->evaluation_rubric_id ?? $activeRubric?->id) === (int) $rubric->id)>
                                        {{ $rubric->name }} ({{ $rubric->total_marks ?? 100 }} pts)
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Select the grading scheme for this evaluation.</div>
                        @endif
                    </div>

                    @if ($presentationGroupGrading ?? false)
                        @include('supervisor.partials.grading-criteria-block', [
                            'prefix' => 'group',
                            'rubric' => $activeRubric,
                            'existing' => $existingGroup,
                            'title' => 'Group grade',
                            'subtitle' => $submission->projectGroup?->name ?? 'Project group',
                        ])

                        <hr class="my-4">

                        <h3 class="h6 fw-bold text-strong mb-3">Individual member grades</h3>
                        <div class="accordion" id="memberGradingAccordion">
                            @foreach ($groupMembers as $member)
                                <div class="accordion-item border-0 mb-3 shadow-sm rounded overflow-hidden">
                                    <h4 class="accordion-header" id="member-heading-{{ $member->id }}">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#member-panel-{{ $member->id }}"
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                aria-controls="member-panel-{{ $member->id }}">
                                            {{ $member->name }}
                                            <span class="text-muted small ms-2">{{ $member->displayIdentifier() }}</span>
                                        </button>
                                    </h4>
                                    <div id="member-panel-{{ $member->id }}"
                                         class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                         aria-labelledby="member-heading-{{ $member->id }}"
                                         data-bs-parent="#memberGradingAccordion">
                                        <div class="accordion-body">
                                            @include('supervisor.partials.grading-criteria-block', [
                                                'prefix' => 'members['.$member->id.']',
                                                'rubric' => $activeRubric,
                                                'existing' => $existingMembers[$member->id] ?? null,
                                            ])
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        @include('supervisor.partials.grading-criteria-block', [
                            'prefix' => 'scores',
                            'rubric' => $activeRubric,
                            'existing' => $existing,
                            'title' => 'Submission grade',
                            'nested' => false,
                        ])
                    @endif

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <button type="submit" name="status" value="draft" class="btn btn-outline-primary">
                            <i class="fas fa-save me-1"></i> Save draft
                        </button>
                        <button type="submit" name="status" value="finalized" class="btn btn-success">
                            <i class="fas fa-check-circle me-1"></i> Finalize evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
    (function () {
        function recalcBlock(block) {
            const body = block.querySelector('[data-criteria-body]');
            const totalEl = block.querySelector('[data-total-score]');
            if (!body || !totalEl) return;

            let total = 0;
            body.querySelectorAll('[data-criterion-row]').forEach((row) => {
                const score = parseFloat(row.querySelector('[data-role="score"]')?.value) || 0;
                const weight = parseFloat(row.querySelector('[data-role="weight"]')?.value) || 0;
                const weighted = (score * weight) / 100;
                const weightedEl = row.querySelector('[data-role="weighted"]');
                if (weightedEl) {
                    weightedEl.textContent = weighted.toFixed(2);
                }
                total += weighted;
            });
            totalEl.textContent = total.toFixed(2);
        }

        document.querySelectorAll('[data-grading-block]').forEach((block) => {
            block.querySelectorAll('input[data-role]').forEach((input) => {
                input.addEventListener('input', function () {
                    recalcBlock(block);
                });
            });
            recalcBlock(block);
        });
    })();
    </script>
    @endpush
@endsection
