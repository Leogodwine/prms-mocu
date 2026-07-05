@extends('layouts.app')

@section('title', 'Create grading scheme')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><a href="{{ route('coordinator.rubrics.index') }}">{{ __('Grading schemes') }}</a></li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ __('Create') }}</span></li>
@endsection

@section('content')
    <x-prms-greeting-banner subtitle="Weighted assessment criteria and descriptors supervisors use to score and grade student work.">
        <a href="{{ route('coordinator.rubrics.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back to list
        </a>
    </x-prms-greeting-banner>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form action="{{ route('coordinator.rubrics.store') }}" method="POST">
                @csrf
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h3 class="h6 fw-bold text-strong mb-4 d-flex align-items-center">
                            <i class="fas fa-info-circle text-primary me-2" aria-hidden="true"></i>
                            Scheme overview
                        </h3>
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label" for="rubric_name">Scheme title</label>
                                <input id="rubric_name" name="name" placeholder="e.g. Research proposal grading scheme"
                                       class="form-control @error('name') is-invalid @enderror" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="total_marks">Maximum score (marks)</label>
                                <input id="total_marks" type="number" name="total_marks" value="100"
                                       class="form-control @error('total_marks') is-invalid @enderror" required>
                                <div class="form-text">Upper bound for the graded total (e.g. 100).</div>
                                @error('total_marks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="rubric_desc">Description</label>
                                <textarea id="rubric_desc" name="description" rows="2"
                                          placeholder="Summarise the assessment purpose, grade scale, and intended use of this scheme…"
                                          class="form-control @error('description') is-invalid @enderror"></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="apply_to_all_supervisors" name="apply_to_all_supervisors"
                                           @checked(old('apply_to_all_supervisors'))>
                                    <label class="form-check-label fw-semibold" for="apply_to_all_supervisors">
                                        Apply to all supervisors
                                    </label>
                                    <div class="form-text">
                                        When enabled, every supervisor must use this scheme for presentation and submission grading.
                                        Any previous system-wide scheme is replaced.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                                <i class="fas fa-list-ul text-primary me-2" aria-hidden="true"></i>
                                Assessment criteria
                            </h3>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="addCriterion()">
                                <i class="fas fa-plus me-1" aria-hidden="true"></i> Add criterion
                            </button>
                        </div>

                        <div id="criteriaContainer">
                            <div class="criterion-row bg-surface-soft p-4 rounded-3 mb-3 border-start border-4 border-primary position-relative">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label small text-muted text-uppercase">Criterion</label>
                                        <input name="criteria[0][name]" placeholder="e.g. Literature review" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted text-uppercase">Weighting (%)</label>
                                        <input type="number" name="criteria[0][weight]" placeholder="30" class="form-control" required>
                                        <div class="form-text small">Share of the overall grade.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-muted text-uppercase">Scoring descriptors</label>
                                        <input name="criteria[0][description]" placeholder="Evidence supervisors should consider when assigning the criterion score" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex gap-2 align-items-start mb-0">
                            <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                            <div class="small">Criterion weightings should total <strong>100%</strong> and be consistent with how partial scores aggregate to the <strong>maximum score (marks)</strong> for this scheme.</div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-semibold">
                                <i class="fas fa-save me-2" aria-hidden="true"></i> Save scheme
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        let criterionIndex = 1;
        function addCriterion() {
            const container = document.getElementById('criteriaContainer');
            const row = document.createElement('div');
            row.className = 'criterion-row bg-surface-soft p-4 rounded-3 mb-3 border-start border-4 border-primary position-relative';
            row.innerHTML = `
                <button type="button" class="btn btn-link text-danger position-absolute top-0 end-0 mt-2 me-2 p-0" onclick="this.closest('.criterion-row').remove()" aria-label="Remove criterion">
                    <i class="fas fa-times fa-lg"></i>
                </button>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small text-muted text-uppercase">Criterion</label>
                        <input name="criteria[${criterionIndex}][name]" placeholder="e.g. Methodology" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase">Weighting (%)</label>
                        <input type="number" name="criteria[${criterionIndex}][weight]" placeholder="40" class="form-control" required>
                        <div class="form-text small">Share of the overall grade.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-muted text-uppercase">Scoring descriptors</label>
                        <input name="criteria[${criterionIndex}][description]" placeholder="Evidence supervisors should consider when assigning the criterion score" class="form-control">
                    </div>
                </div>
            `;
            container.appendChild(row);
            criterionIndex++;
        }
    </script>
    @endpush
@endsection
