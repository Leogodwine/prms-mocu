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

    @if (method_exists($submissions, 'total'))
        <x-prms-table-pagination-toolbar :paginator="$submissions" :noun="$paginationNoun ?? 'submissions'" class="card border-0 shadow-sm border-bottom rounded-0 rounded-top" />
    @endif

    @foreach ($groupedSubmissions as $group)
        @include('partials.submission-project-grid-group', [
            'group' => $group,
            'showReview' => $showReview ?? false,
            'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
        ])
    @endforeach

    @if (method_exists($submissions, 'hasPages'))
        <div class="d-flex justify-content-center mt-2 px-2 pb-2">
            <x-prms-table-pagination-footer :paginator="$submissions" />
        </div>
    @endif
@endif
