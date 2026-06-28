@extends('layouts.app')

@section('title', 'Backup & recovery')

@section('content')
    <x-prms-greeting-banner subtitle="Create database backups, schedule automatic backups, restore data, and monitor backup status.">
        <a href="{{ route('admin.system-health') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-heartbeat me-1" aria-hidden="true"></i> System monitoring
        </a>
        <form method="POST" action="{{ route('admin.backups.store') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                <i class="fas fa-plus me-1" aria-hidden="true"></i> Create backup now
            </button>
        </form>
    </x-prms-greeting-banner>

    @error('backup')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="prms-stat-card text-center py-3">
                <div class="stat-label">Schedule</div>
                <div class="stat-value h5 mb-0">{{ $settings['auto_enabled'] ? ucfirst($settings['schedule']) : 'Off' }}</div>
                <div class="small text-muted">{{ $settings['auto_enabled'] ? 'Automatic backups enabled' : 'Automatic backups disabled' }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="prms-stat-card text-center py-3">
                <div class="stat-label">Total backups</div>
                <div class="stat-value">{{ $backups->count() }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="prms-stat-card text-center py-3">
                <div class="stat-label">Retention policy</div>
                <div class="stat-value">{{ $settings['retention'] }}</div>
                <div class="small text-muted">Snapshots kept</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h2 class="h6 fw-bold mb-0">Automatic backup schedule</h2>
        </div>
        <div class="card-body">
            <p class="small text-muted">{{ $schedulerNote }}</p>
            <form method="POST" action="{{ route('admin.backups.settings') }}" class="row g-3 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="auto_enabled" name="auto_enabled" value="1"
                               @checked(old('auto_enabled', $settings['auto_enabled']))>
                        <label class="form-check-label" for="auto_enabled">Enable automatic backups</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="schedule" class="form-label">Frequency</label>
                    <select id="schedule" name="schedule" class="form-select">
                        <option value="daily" @selected(old('schedule', $settings['schedule']) === 'daily')>Daily</option>
                        <option value="weekly" @selected(old('schedule', $settings['schedule']) === 'weekly')>Weekly (Sunday)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="time" class="form-label">Time</label>
                    <input type="time" id="time" name="time" class="form-control"
                           value="{{ old('time', $settings['time']) }}">
                </div>
                <div class="col-md-2">
                    <label for="retention" class="form-label">Keep</label>
                    <input type="number" min="1" max="90" id="retention" name="retention" class="form-control"
                           value="{{ old('retention', $settings['retention']) }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Save schedule</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h2 class="h6 fw-bold mb-0">Backup history</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Filename</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Created by</th>
                        <th>Completed</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr>
                            <td class="ps-4"><code>{{ $backup['filename'] }}</code></td>
                            <td>{{ $backup['size_label'] }}</td>
                            <td>
                                <span class="badge rounded-pill {{ $backup['status'] === 'ok' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ strtoupper($backup['status']) }}
                                </span>
                            </td>
                            <td>{{ ucfirst($backup['type']) }}</td>
                            <td>{{ $backup['created_by'] }}</td>
                            <td class="small text-muted">{{ $backup['completed_at'] }}</td>
                            <td class="text-end pe-4 text-nowrap">
                                @if ($backup['has_database'])
                                    <a href="{{ route('admin.backups.download', $backup['id']) }}"
                                       class="btn btn-sm btn-light border">Download SQL</a>
                                @endif
                                <button type="button"
                                        class="btn btn-sm btn-light border"
                                        data-bs-toggle="modal"
                                        data-bs-target="#restoreBackupModal-{{ $backup['id'] }}"
                                        @disabled(! $backup['has_database'])>
                                    Restore
                                </button>
                                <form method="POST" action="{{ route('admin.backups.destroy', $backup['id']) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger"
                                            onclick="return confirm('Delete this backup permanently?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No backups yet. Create your first backup above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('modals')
    @foreach ($backups as $backup)
        @if ($backup['has_database'])
            <div class="modal fade" id="restoreBackupModal-{{ $backup['id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-header bg-danger-soft">
                            <h2 class="modal-title h6 fw-bold">Restore database</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('admin.backups.restore', $backup['id']) }}">
                            @csrf
                            <div class="modal-body">
                                <p class="text-strong">Restore from <code>{{ $backup['filename'] }}</code>?</p>
                                <p class="small text-danger mb-3">
                                    This will overwrite the current database. Create a fresh backup first if you are unsure.
                                </p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="restore_confirm_{{ $backup['id'] }}" name="restore_confirm" required>
                                    <label class="form-check-label" for="restore_confirm_{{ $backup['id'] }}">
                                        I understand this will replace all current data
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Restore database</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
    @endpush
@endsection
