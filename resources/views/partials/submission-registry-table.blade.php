<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 submission-registry-table">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="ps-4">Title</th>
                    <th scope="col">Stage</th>
                    <th scope="col">Author(s) / Group Members</th>
                    <th scope="col">Submission Type</th>
                    <th scope="col">Group No.</th>
                    <th scope="col">Date Submitted</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                    @include('partials.submission-registry-row', [
                        'submission' => $submission,
                        'showFinalize' => $showFinalize ?? false,
                        'showReview' => $showReview ?? false,
                        'showConsentReview' => $showConsentReview ?? false,
                        'consentSubmissions' => $consentSubmissions ?? [],
                        'useCoordinatorFinalStatus' => $useCoordinatorFinalStatus ?? false,
                    ])
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-2x opacity-25 mb-2 d-block" aria-hidden="true"></i>
                            {{ $emptyMessage ?? 'No submissions found.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (isset($submissions) && method_exists($submissions, 'hasPages') && $submissions->hasPages())
        <div class="card-footer bg-transparent border-top py-3 d-flex justify-content-center">
            {{ $submissions->withQueryString()->links() }}
        </div>
    @endif
</div>
