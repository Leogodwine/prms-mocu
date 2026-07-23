@extends('layouts.app')

@section('title', 'Academic deadlines')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><a href="{{ route('coordinator.index') }}">{{ __('Coordinator workspace') }}</a></li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ __('Deadlines') }}</span></li>
@endsection

@section('content')

@php
    $totalDeadlines  = $deadlineStats['total'] ?? $deadlines->total();
    $activeCount = $deadlineStats['active'] ?? 0;
    $upcomingCount = $deadlineStats['upcoming'] ?? 0;
    $closedCount = $deadlineStats['closed'] ?? 0;
@endphp

<x-prms-greeting-banner subtitle="Define timelines for research proposal chapters, project submissions, and research-report chapters across academic years. Apply globally or restrict to a specific group.">
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold"
            data-bs-toggle="modal" data-bs-target="#createDeadlineModal">
        <i class="far fa-clock me-2" aria-hidden="true"></i>
        Set new deadline
    </button>
    <a href="{{ route('coordinator.index') }}" class="btn btn-light border rounded-pill px-3">
        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Workspace
    </a>
    <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
        <i class="fas fa-th-large me-1" aria-hidden="true"></i> Dashboard
    </a>
</x-prms-greeting-banner>

{{-- ────────── Stats ────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="prms-stat-card">
            <div class="stat-label">Total deadlines</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($totalDeadlines) }}</div>
                <div class="bg-primary-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px;">
                    <i class="far fa-calendar-alt" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="prms-stat-card" style="--prms-primary: var(--prms-color-success-500);">
            <div class="stat-label">Active now</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($activeCount) }}</div>
                <div class="bg-success-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px; color: var(--prms-color-success-500);">
                    <i class="fas fa-play-circle" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="prms-stat-card" style="--prms-primary: var(--prms-color-warning-500);">
            <div class="stat-label">Upcoming</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($upcomingCount) }}</div>
                <div class="bg-warning-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px; color: var(--prms-color-warning-500);">
                    <i class="far fa-hourglass" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="prms-stat-card" style="--prms-primary: var(--prms-color-danger-500);">
            <div class="stat-label">Closed</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($closedCount) }}</div>
                <div class="bg-danger-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px; color: var(--prms-color-danger-500);">
                    <i class="far fa-calendar-times" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ────────── Timelines table ────────── --}}
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h3 class="card-title h6 fw-bold mb-0 d-flex align-items-center">
            <i class="fas fa-stream text-primary me-2" aria-hidden="true"></i>
            Active timelines
        </h3>
        <small class="text-muted">{{ $totalDeadlines }} total</small>
    </div>

    <div class="card-body p-0">
        <x-prms-table-pagination-toolbar :paginator="$deadlines" noun="timelines" />
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col">Stage</th>
                        <th scope="col">Academic year</th>
                        <th scope="col">Applies to</th>
                        <th scope="col">Window</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deadlines as $deadline)
                        @php
                            $end = $deadline->end_time;
                            $start = $deadline->start_time;

                            if ($end && $now->isAfter($end)) {
                                $statusKey  = 'closed';
                                $statusTone = 'bg-danger';
                                $statusIcon = 'fas fa-times-circle';
                            } elseif ($start && $now->isBefore($start)) {
                                $statusKey  = 'upcoming';
                                $statusTone = 'bg-warning';
                                $statusIcon = 'far fa-hourglass';
                            } else {
                                $statusKey  = 'active';
                                $statusTone = 'bg-success';
                                $statusIcon = 'fas fa-circle';
                            }

                            $stageIcon = match (true) {
                                str_starts_with($deadline->stage_name, 'proposal_')   => 'far fa-file-alt',
                                str_starts_with($deadline->stage_name, 'research_')   => 'fas fa-book-open',
                                str_starts_with($deadline->stage_name, 'project_')    => 'fas fa-laptop-code',
                                default => 'far fa-bookmark',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="bg-brand-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center"
                                          style="width: 36px; height: 36px;">
                                        <i class="{{ $stageIcon }}" aria-hidden="true"></i>
                                    </span>
                                    <span class="fw-semibold text-strong">
                                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $deadline->stage_name)) }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <code class="bg-surface-soft px-2 py-1 rounded small text-strong">
                                    {{ $deadline->academic_year ?: '—' }}
                                </code>
                            </td>
                            <td>
                                @if ($deadline->projectGroup)
                                    <span class="badge bg-info">
                                        <i class="fas fa-users me-1" aria-hidden="true"></i>
                                        {{ $deadline->projectGroup->name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-users me-1" aria-hidden="true"></i>
                                        All students
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($start)
                                    <div class="small text-muted">
                                        <i class="far fa-calendar-plus me-1" aria-hidden="true"></i>
                                        {{ $start->format('M d, Y · H:i') }}
                                    </div>
                                @endif
                                <div class="fw-semibold text-strong small">
                                    <i class="far fa-calendar-check me-1" aria-hidden="true"></i>
                                    {{ optional($end)->format('M d, Y · H:i') ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $statusTone }}">
                                    <i class="{{ $statusIcon }} me-1" aria-hidden="true" @if ($statusKey === 'active') style="font-size: 0.45rem;" @endif></i>
                                    {{ \Illuminate\Support\Str::title($statusKey) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button"
                                            class="btn btn-light btn-sm border"
                                            title="Edit deadline"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editDeadlineModal-{{ $deadline->id }}">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-light btn-sm border text-danger"
                                            title="Delete deadline"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteDeadlineModal-{{ $deadline->id }}">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                                     style="width: 64px; height: 64px;">
                                    <i class="far fa-calendar-times text-muted" aria-hidden="true" style="font-size: 1.4rem;"></i>
                                </div>
                                <h4 class="h6 fw-bold text-strong">No deadlines configured yet</h4>
                                <p class="text-muted small mb-3">
                                    Create your first deadline to start the academic timeline.
                                </p>
                                <button type="button" class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#createDeadlineModal">
                                    <i class="far fa-clock me-1" aria-hidden="true"></i>
                                    Set new deadline
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-top py-3">
            <x-prms-table-pagination-footer :paginator="$deadlines" />
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     CREATE DEADLINE MODAL
   ════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="createDeadlineModal" tabindex="-1" aria-labelledby="createDeadlineModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content border-0">
            <div class="modal-header bg-surface-soft">
                <h2 class="modal-title h5 fw-bold text-strong" id="createDeadlineModalTitle">
                    <i class="far fa-clock text-primary me-2" aria-hidden="true"></i>
                    Set a new deadline
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('coordinator.deadlines.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">
                        A deadline can be applied <strong>globally</strong> (to all students in the
                        academic year) or restricted to a <strong>specific project group</strong>.
                        Group-level deadlines override the global deadline for that group.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="create_stage_name" class="form-label">Academic stage</label>
                            <select id="create_stage_name" name="stage_name"
                                    class="form-select @error('stage_name') is-invalid @enderror" required>
                                <option value="" disabled @selected(! old('stage_name'))>Select stage…</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage }}" @selected(old('stage_name') === $stage)>
                                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $stage)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('stage_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="create_academic_year" class="form-label">Academic year</label>
                            <input id="create_academic_year" type="text" name="academic_year"
                                   value="{{ old('academic_year') }}"
                                   placeholder="e.g. 2025/2026"
                                   class="form-control @error('academic_year') is-invalid @enderror">
                            @error('academic_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="create_project_group_id" class="form-label">
                                Project group <span class="text-muted small">(optional)</span>
                            </label>
                            <select id="create_project_group_id" name="project_group_id"
                                    class="form-select @error('project_group_id') is-invalid @enderror">
                                <option value="">All students</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" @selected(old('project_group_id') == $group->id)>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('project_group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="create_start_time" class="form-label">
                                Window opens <span class="text-muted small">(optional)</span>
                            </label>
                            <input id="create_start_time" type="datetime-local" name="start_time"
                                   value="{{ old('start_time') }}"
                                   class="form-control @error('start_time') is-invalid @enderror">
                            @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Leave empty to make this stage open immediately.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="create_end_time" class="form-label">Submission deadline</label>
                            <input id="create_end_time" type="datetime-local" name="end_time"
                                   value="{{ old('end_time') }}"
                                   class="form-control @error('end_time') is-invalid @enderror" required>
                            @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Students cannot submit after this date and time.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1" aria-hidden="true"></i> Save deadline
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     PER-DEADLINE MODALS (edit + delete)
   ════════════════════════════════════════════════════════════════ --}}
@foreach ($deadlines as $deadline)
    {{-- Edit modal --}}
    <div class="modal fade" id="editDeadlineModal-{{ $deadline->id }}" tabindex="-1"
         aria-labelledby="editDeadlineModalTitle-{{ $deadline->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content border-0">
                <div class="modal-header bg-surface-soft">
                    <h2 class="modal-title h5 fw-bold text-strong" id="editDeadlineModalTitle-{{ $deadline->id }}">
                        <i class="fas fa-pen text-primary me-2" aria-hidden="true"></i>
                        Edit deadline
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('coordinator.deadlines.update', $deadline) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_stage_{{ $deadline->id }}" class="form-label">Academic stage</label>
                                <select id="edit_stage_{{ $deadline->id }}" name="stage_name" class="form-select" required>
                                    @foreach ($stages as $stage)
                                        <option value="{{ $stage }}" @selected($deadline->stage_name === $stage)>
                                            {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $stage)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="edit_year_{{ $deadline->id }}" class="form-label">Academic year</label>
                                <input id="edit_year_{{ $deadline->id }}" type="text" name="academic_year"
                                       value="{{ $deadline->academic_year }}"
                                       placeholder="e.g. 2025/2026" class="form-control">
                            </div>

                            <div class="col-12">
                                <label for="edit_group_{{ $deadline->id }}" class="form-label">Project group</label>
                                <select id="edit_group_{{ $deadline->id }}" name="project_group_id" class="form-select">
                                    <option value="">All students</option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}" @selected($deadline->project_group_id == $group->id)>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="edit_start_{{ $deadline->id }}" class="form-label">Window opens</label>
                                <input id="edit_start_{{ $deadline->id }}" type="datetime-local" name="start_time"
                                       value="{{ optional($deadline->start_time)->format('Y-m-d\TH:i') }}"
                                       class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label for="edit_end_{{ $deadline->id }}" class="form-label">Submission deadline</label>
                                <input id="edit_end_{{ $deadline->id }}" type="datetime-local" name="end_time"
                                       value="{{ optional($deadline->end_time)->format('Y-m-d\TH:i') }}"
                                       class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1" aria-hidden="true"></i> Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteDeadlineModal-{{ $deadline->id }}" tabindex="-1"
         aria-labelledby="deleteDeadlineModalTitle-{{ $deadline->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-danger-soft">
                    <h2 class="modal-title h5 fw-bold" id="deleteDeadlineModalTitle-{{ $deadline->id }}"
                        style="color: var(--prms-color-danger-500);">
                        <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                        Delete deadline
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('coordinator.deadlines.destroy', $deadline) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body p-4">
                        <p class="text-strong">
                            You are about to remove the following deadline. This action is logged
                            in the audit trail and cannot be undone.
                        </p>
                        <div class="bg-surface-soft border-soft rounded p-3 small">
                            <div><strong>Stage:</strong>
                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $deadline->stage_name)) }}
                            </div>
                            <div><strong>Academic year:</strong> {{ $deadline->academic_year ?: '—' }}</div>
                            <div><strong>Applies to:</strong>
                                {{ $deadline->projectGroup ? 'Group · ' . $deadline->projectGroup->name : 'All students (global)' }}
                            </div>
                            <div><strong>Deadline:</strong>
                                {{ optional($deadline->end_time)->format('M d, Y · H:i') ?? '—' }}
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            Removing the deadline does not delete any submitted work — only the timeline rule.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1" aria-hidden="true"></i> Yes, delete deadline
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection

@push('scripts')
<script>
    // Re-open the create modal when validation fails so users can fix and resubmit.
    @if ($errors->any() && (old('stage_name') || old('end_time')))
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('createDeadlineModal');
            if (modal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    @endif
</script>
@endpush
