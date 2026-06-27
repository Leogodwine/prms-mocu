@php
    $group = $submission->projectGroup;
    $members = $group?->members ?? collect();
    $memberCount = $members->count();
    $isGroup = $memberCount > 1;
    $authors = $isGroup
        ? $members->pluck('name')->map(fn ($name) => \Illuminate\Support\Str::title($name))->join(', ')
        : \Illuminate\Support\Str::title(
            optional($submission->student)->name ?? ($members->first()?->name ?? '—')
        );
    $submissionType = $isGroup ? 'Group' : 'Individual';
    $groupNo = $isGroup ? ($group->name ?? '—') : '—';
    $submittedOn = optional($submission->submitted_at)?->format('d M Y')
        ?? optional($submission->created_at)?->format('d M Y')
        ?? '—';

    if (! empty($useCoordinatorFinalStatus)) {
        $statusLabel = $submission->coordinator_approved_at ? 'Approved' : 'Pending';
        $statusBadge = $submission->coordinator_approved_at ? 'bg-success' : 'bg-warning text-dark';
    } else {
        $statusLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', (string) ($submission->status ?? 'pending')));
        $statusBadge = match (strtolower((string) $submission->status)) {
            'approved' => 'bg-success',
            'pending', 'submitted', 'under_review' => 'bg-warning text-dark',
            'rejected', 'needs_revision' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    $title = $submission->title ?: \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $submission->stage));
    $stageLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $submission->stage));
    $docIcon = \App\Support\SubmissionFileAccess::documentIconMeta(
        $submission->mime_type,
        $submission->original_filename
    );
    $consentSubmission = ($consentSubmissions ?? [])[$submission->id] ?? null;
@endphp
<tr>
    <td class="ps-4">
        <div class="d-flex align-items-center gap-2">
            <i class="{{ $docIcon['icon'] }} {{ $docIcon['class'] }} flex-shrink-0"
               style="font-size:1.1rem;"
               title="{{ $docIcon['label'] }}"
               aria-hidden="true"></i>
            <span class="fw-semibold text-strong">{{ $title }}</span>
        </div>
    </td>
    <td class="small text-muted">
        <span class="text-strong">{{ $stageLabel }}</span>
    </td>
    <td>{{ $authors }}</td>
    <td>{{ $submissionType }}</td>
    <td>
        @if ($groupNo !== '—')
            <code class="small bg-surface-soft px-2 py-1 rounded">{{ $groupNo }}</code>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="text-nowrap">{{ $submittedOn }}</td>
    <td>
        <span class="badge rounded-pill {{ $statusBadge }}">{{ $statusLabel }}</span>
    </td>
    <td class="text-end text-nowrap">
        @if ($submission->file_path || $submission->isProjectShowcase())
            <a href="{{ route('student.submissions.preview', $submission) }}"
               class="btn btn-link btn-sm text-primary text-decoration-none px-1"
               target="_blank" rel="noopener noreferrer">
                <i class="fas fa-eye me-1" aria-hidden="true"></i>View
            </a>
            <span class="text-muted" aria-hidden="true">·</span>
            <a href="{{ route('student.submissions.download', $submission) }}"
               class="btn btn-link btn-sm text-primary text-decoration-none px-1">
                <i class="fas fa-download me-1" aria-hidden="true"></i>Download
            </a>
        @else
            <span class="text-muted small">No file</span>
        @endif
        @if (! empty($showReview))
            <span class="text-muted" aria-hidden="true">·</span>
            <button type="button"
                    class="btn btn-link btn-sm text-primary text-decoration-none px-1"
                    data-bs-toggle="modal"
                    data-bs-target="#prmsReviewModal-{{ $submission->id }}">
                <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i>Review
            </button>
        @endif
        @if (! empty($showConsentReview) && $consentSubmission)
            <span class="text-muted" aria-hidden="true">·</span>
            <button type="button"
                    class="btn btn-link btn-sm text-primary text-decoration-none px-1"
                    data-bs-toggle="modal"
                    data-bs-target="#prmsCoordinatorConsentModal-{{ $consentSubmission->id }}">
                <i class="fas fa-file-signature me-1" aria-hidden="true"></i>Consent
                @if (! $consentSubmission->coordinator_approved_at)
                    <span class="badge bg-warning text-dark ms-1">Pending</span>
                @endif
            </button>
        @endif
        @if (! empty($showFinalize) && ! $submission->coordinator_approved_at)
            <span class="text-muted" aria-hidden="true">·</span>
            <form action="{{ route('coordinator.submissions.approve', $submission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-success text-decoration-none px-1 fw-semibold">
                    <i class="fas fa-check-double me-1" aria-hidden="true"></i>Finalize
                </button>
            </form>
        @endif
    </td>
</tr>
