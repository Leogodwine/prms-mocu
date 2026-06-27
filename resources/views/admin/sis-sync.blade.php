@extends('layouts.app')

@section('title', 'SIS sync logs')

@section('content')
    <x-prms-greeting-banner subtitle="Import runs between PRMS and the student information system.">
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <div class="card mb-4">
        <div class="card-header bg-transparent border-bottom py-3">
            <h3 class="h6 fw-bold text-strong mb-0">Run synchronization</h3>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                SIS import updates user accounts and student profiles, including <strong>gender</strong> when the source file provides
                <code>gender</code> or <code>sex</code>. Use backfill after a sync if older rows only have gender inside <code>sis_data</code>.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.sis-sync.run') }}" class="m-0"
                      onsubmit="return confirm('Run SIS student sync from storage/app/sis/students.json?');">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-sync-alt me-1" aria-hidden="true"></i> Sync students now
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.sis-sync.backfill-gender') }}" class="m-0"
                      onsubmit="return confirm('Copy gender from sis_data into students.gender for rows that are missing it?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="fas fa-venus-mars me-1" aria-hidden="true"></i> Backfill gender
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="alert alert-light border d-flex align-items-start gap-2 mb-4">
        <i class="fas fa-terminal text-info mt-1" aria-hidden="true"></i>
        <div class="small text-muted">
            <div class="mb-1">CLI equivalents:</div>
            <code class="bg-dark text-white rounded px-2 py-1 d-inline-block mb-1">php artisan sis:sync-students</code><br>
            <code class="bg-dark text-white rounded px-2 py-1 d-inline-block">php artisan prms:backfill-student-gender</code>
            <span class="d-block mt-1">Add <code>--dry-run</code> to the backfill command to preview changes.</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-transparent border-bottom py-3">
            <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                <i class="fas fa-sync-alt me-2" aria-hidden="true"></i>
                Synchronization history
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th class="ps-4">Run time</th>
                            <th>Status</th>
                            <th class="text-center">Added</th>
                            <th class="text-center">Updated</th>
                            <th class="text-center">Inactive</th>
                            <th>Triggered by</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="ps-4 fw-semibold text-strong">
                                    {{ $log->sync_timestamp?->format('M d, Y') }}
                                    <div class="text-muted fw-normal small">{{ $log->sync_timestamp?->format('H:i:s') }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $log->sync_status === 'success' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                        {{ strtoupper($log->sync_status) }}
                                    </span>
                                    @if ($log->error_message)
                                        <div class="text-danger small mt-1" style="max-width: 250px;">{{ $log->error_message }}</div>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-success">{{ $log->records_added }}</td>
                                <td class="text-center fw-bold text-info">{{ $log->records_updated }}</td>
                                <td class="text-center text-muted fw-bold">{{ $log->records_deactivated }}</td>
                                <td>
                                    <span class="d-flex align-items-center text-muted">
                                        <i class="far fa-user-circle me-1" aria-hidden="true"></i>
                                        {{ $log->initiated_by }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-ban fs-1 mb-2 d-block opacity-25" aria-hidden="true"></i>
                                    <p class="mb-0">No synchronization events found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent border-top py-3">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
