@extends('layouts.app')

@section('title', 'Grading scheme evaluation')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><a href="{{ route('supervisor.index') }}">{{ __('Supervisor workspace') }}</a></li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ __('Evaluation') }}</span></li>
@endsection

@section('content')
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
                <form method="POST" action="{{ route('supervisor.evaluate.store', $submission) }}" id="grading-scheme-form">
                    @csrf

                    <div class="mb-4">
                        <label for="evaluation_rubric_id" class="form-label fw-bold">Grading scheme</label>
                        <select id="evaluation_rubric_id" name="evaluation_rubric_id" class="form-select" required>
                            @foreach ($rubrics as $rubric)
                                <option
                                    value="{{ $rubric->id }}"
                                    data-criteria='@json($rubric->criteria)'
                                    data-total="{{ $rubric->total_marks ?? 100 }}"
                                    @selected($existing && (int) old('evaluation_rubric_id', $existing->evaluation_rubric_id) === $rubric->id)
                                >
                                    {{ $rubric->name }} ({{ $rubric->total_marks ?? 100 }} pts)
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Selecting a grading scheme loads its criteria below.</div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%;">Criterion</th>
                                    <th style="width: 12%;">Weighting (%)</th>
                                    <th style="width: 14%;">Score (0–100)</th>
                                    <th style="width: 14%;">Weighted</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody id="criteria-rows"></tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="3" class="text-end">Total weighted score</th>
                                    <th><span id="total-score" class="fw-bold text-primary">0.00</span> / 100</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mb-4">
                        <label for="general_comments" class="form-label fw-bold">General Comments</label>
                        <textarea
                            id="general_comments"
                            name="general_comments"
                            class="form-control"
                            rows="4"
                            placeholder="Overall feedback for the student..."
                        >{{ old('general_comments', $existing?->general_comments) }}</textarea>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
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
        const select = document.getElementById('evaluation_rubric_id');
        const rowsBody = document.getElementById('criteria-rows');
        const totalEl = document.getElementById('total-score');
        if (!select || !rowsBody) return;

        const existingScores = @json($existing?->scores ?? []);
        const existingMap = {};
        if (Array.isArray(existingScores)) {
            existingScores.forEach((row) => {
                if (row && row.criterion) {
                    existingMap[row.criterion] = row;
                }
            });
        }

        function recalcTotal() {
            let total = 0;
            rowsBody.querySelectorAll('[data-criterion-row]').forEach((row) => {
                const score = parseFloat(row.querySelector('[data-role="score"]').value) || 0;
                const weight = parseFloat(row.querySelector('[data-role="weight"]').value) || 0;
                const weighted = (score * weight) / 100;
                row.querySelector('[data-role="weighted"]').textContent = weighted.toFixed(2);
                total += weighted;
            });
            totalEl.textContent = total.toFixed(2);
        }

        function renderCriteria() {
            const opt = select.options[select.selectedIndex];
            if (!opt) {
                rowsBody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">Select a grading scheme to load criteria.</td></tr>';
                return;
            }
            let criteria = [];
            try {
                criteria = JSON.parse(opt.dataset.criteria || '[]');
            } catch (e) {
                criteria = [];
            }

            if (!Array.isArray(criteria) || criteria.length === 0) {
                rowsBody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">This grading scheme has no criteria configured.</td></tr>';
                return;
            }

            rowsBody.innerHTML = criteria.map((c, idx) => {
                const name = (c.name || '').replace(/"/g, '&quot;');
                const weight = c.weight ?? 0;
                const description = (c.description || '').replace(/"/g, '&quot;');
                const prior = existingMap[name] || {};
                const priorScore = prior.score ?? '';
                const priorComments = prior.comments ?? '';
                return `
                    <tr data-criterion-row>
                        <td>
                            <div class="fw-bold">${name}</div>
                            <small class="text-muted">${description}</small>
                            <input type="hidden" name="scores[${idx}][criterion]" value="${name}">
                        </td>
                        <td>
                            <input type="number" min="0" max="100" step="0.1"
                                   class="form-control form-control-sm" name="scores[${idx}][weight]"
                                   data-role="weight" value="${weight}">
                        </td>
                        <td>
                            <input type="number" min="0" max="100" step="0.1"
                                   class="form-control form-control-sm" name="scores[${idx}][score]"
                                   data-role="score" value="${priorScore}" placeholder="0–100" required>
                        </td>
                        <td><span data-role="weighted">0.00</span></td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                   name="scores[${idx}][comments]" maxlength="1000"
                                   value="${priorComments.replace(/"/g, '&quot;')}"
                                   placeholder="Optional notes...">
                        </td>
                    </tr>
                `;
            }).join('');

            rowsBody.querySelectorAll('input[data-role]').forEach((input) => {
                input.addEventListener('input', recalcTotal);
            });

            recalcTotal();
        }

        select.addEventListener('change', renderCriteria);
        renderCriteria();
    })();
    </script>
    @endpush
@endsection
