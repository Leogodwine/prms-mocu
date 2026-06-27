@if ($submissions->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-inbox fa-2x opacity-25 mb-2 d-block" aria-hidden="true"></i>
            {{ $emptyMessage ?? 'No project submissions found.' }}
        </div>
    </div>
@else
    @php
        $groupedSubmissions = \App\Support\StudentStageProgress::groupSubmissionsForDisplay($submissions);
    @endphp

    @foreach ($groupedSubmissions as $group)
        @include('partials.submission-project-grid-group', [
            'group' => $group,
            'showReview' => $showReview ?? false,
            'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
        ])
    @endforeach

    @if (method_exists($submissions, 'hasPages') && $submissions->hasPages())
        <div class="d-flex justify-content-center mt-2 px-2">
            {{ $submissions->withQueryString()->links() }}
        </div>
    @endif
@endif
