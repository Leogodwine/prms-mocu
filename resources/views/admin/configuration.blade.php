@extends('layouts.app')

@section('title', 'System configuration')

@section('content')
    <x-prms-greeting-banner subtitle="Academic year, cycle, deadlines, and eligibility.">
        <button type="button"
                class="btn btn-primary rounded-pill px-4 fw-semibold"
                data-bs-toggle="modal"
                data-bs-target="#academicParamsModal">
            <i class="fas fa-sliders-h me-2" aria-hidden="true"></i>
            Edit configuration
        </button>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    @php
        $map = $configs->pluck('config_value', 'config_key');
    @endphp

    <div class="modal fade" id="academicParamsModal" tabindex="-1" aria-labelledby="academicParamsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.configuration.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom">
                        <h2 class="modal-title h5 fw-bold text-strong" id="academicParamsModalLabel">Academic parameters</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <span class="prms-eyebrow">Project lifecycle</span>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label" for="cfg_year">Academic year</label>
                                    <input id="cfg_year" name="configs[academic_year]" value="{{ $map['academic_year'] ?? '' }}"
                                           placeholder="e.g. 2026/2027" class="form-control @error('configs.academic_year') is-invalid @enderror" required>
                                    @error('configs.academic_year')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="cfg_cycle">Current cycle</label>
                                    <input id="cfg_cycle" name="configs[project_cycle]" value="{{ $map['project_cycle'] ?? '' }}"
                                           placeholder="e.g. Semester 2" class="form-control @error('configs.project_cycle') is-invalid @enderror" required>
                                    @error('configs.project_cycle')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <span class="prms-eyebrow">Critical deadlines</span>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label" for="deadline_proposal">
                                        <i class="far fa-calendar me-1 text-muted" aria-hidden="true"></i> Proposal deadline
                                    </label>
                                    <input type="date" id="deadline_proposal" name="configs[deadline_proposal]"
                                           value="{{ $map['deadline_proposal'] ?? '' }}" class="form-control @error('configs.deadline_proposal') is-invalid @enderror" required>
                                    @error('configs.deadline_proposal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="deadline_final">
                                        <i class="far fa-calendar-check me-1 text-muted" aria-hidden="true"></i> Final submission
                                    </label>
                                    <input type="date" id="deadline_final" name="configs[deadline_final]"
                                           value="{{ $map['deadline_final'] ?? '' }}" class="form-control @error('configs.deadline_final') is-invalid @enderror" required>
                                    @error('configs.deadline_final')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-0">
                            <span class="prms-eyebrow">Eligibility</span>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label" for="min_year">Minimum year of study</label>
                                    <input type="number" id="min_year" min="1" max="6" name="configs[eligibility_min_year]"
                                           value="{{ $map['eligibility_min_year'] ?? '3' }}" class="form-control @error('configs.eligibility_min_year') is-invalid @enderror" required>
                                    <div class="form-text">Students at or above this year may form project groups.</div>
                                    @error('configs.eligibility_min_year')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary rounded-pill px-4 fw-semibold" type="submit">
                            <i class="fas fa-save me-2" aria-hidden="true"></i> Save configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('academicParamsModal');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                }
            });
        </script>
    @endif
@endpush
