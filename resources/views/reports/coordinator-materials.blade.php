@extends('layouts.app')

@php
    $titles = [
        'all' => 'All materials',
        'pending' => 'Pending supervisor review',
        'approved' => 'Approved work',
        'rejected' => 'Rejected',
        'reviewed' => 'Reviewed (approved or rejected)',
    ];
    $pageTitle = $titles[$statusFilter] ?? 'Materials';
@endphp

@section('title', $pageTitle)

@section('content')
    <x-prms-greeting-banner subtitle="Submission materials for your coordinated groups. Use the filters below to match the analytics KPI cards.">
        <a href="{{ route('reports.coordinator.export') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-download me-2" aria-hidden="true"></i> Export CSV
        </a>
        <a href="{{ route('reports.coordinator') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-chart-pie me-1" aria-hidden="true"></i> Analytics
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
        @foreach (['all' => 'All', 'pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'reviewed' => 'Reviewed'] as $key => $label)
            <form method="POST" action="{{ route('reports.coordinator.materials') }}" class="d-inline">
                @csrf
                <input type="hidden" name="_filter_action" value="apply">
                <input type="hidden" name="status" value="{{ $key }}">
                <button type="submit" class="btn btn-sm rounded-pill {{ $statusFilter === $key ? 'btn-primary' : 'btn-light border' }}">
                    {{ $label }}
                </button>
            </form>
        @endforeach
        <a href="{{ $filterResetUrl }}" class="btn btn-sm btn-light border rounded-pill ms-1">
            <i class="fas fa-undo me-1" aria-hidden="true"></i> Reset
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <x-prms-table-pagination-toolbar :paginator="$submissions" noun="submissions" />
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Group</th>
                        <th scope="col">Student</th>
                        <th scope="col">Stage</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Submitted</th>
                        <th scope="col" class="text-end">File</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td class="fw-semibold text-strong">{{ $submission->title }}</td>
                            <td>{{ optional($submission->projectGroup)->name ?? '—' }}</td>
                            <td>{{ optional($submission->student)->name ?? '—' }}</td>
                            <td><span class="text-muted small">{{ str($submission->stage ?? '')->replace('_', ' ')->title() }}</span></td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border">
                                    {{ str($submission->status ?? '')->replace('_', ' ')->title() ?: '—' }}
                                </span>
                            </td>
                            <td class="text-end text-muted small">
                                {{ optional($submission->submitted_at)?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="text-end">
                                @if ($submission->file_path)
                                    <a href="{{ route('student.submissions.preview', $submission) }}" class="btn btn-sm btn-outline-primary rounded-pill" target="_blank" rel="noopener">
                                        Preview
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No submissions in this view.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-top py-3">
            <x-prms-table-pagination-footer :paginator="$submissions" />
        </div>
    </div>
@endsection
