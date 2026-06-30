@extends('layouts.app')

@section('title', 'Programme management')

@section('content')
    <x-prms-greeting-banner subtitle="Define programme duration, final year, output type, and allowed project years.">
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProgrammeModal"
                @if($departmentsList->isEmpty()) disabled @endif>
            <i class="fas fa-plus me-1"></i> Add programme
        </button>
    </x-prms-greeting-banner>

    @include('admin.academic-configuration.partials.nav', ['current' => 'programmes'])

    @if ($departmentsList->isEmpty())
        <div class="alert alert-warning">Create at least one department before adding programmes.</div>
    @endif

    <div class="card border-0 shadow-sm">
        <x-prms-table-pagination-toolbar :paginator="$programmes" noun="programmes" />
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Programme</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Duration</th>
                        <th>Final year</th>
                        <th>Output</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programmes as $programme)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $programme->programme_code }}</div>
                                <div class="small text-muted">{{ $programme->programme_name }}</div>
                            </td>
                            <td class="small">{{ $programme->department?->department_name ?? '—' }}</td>
                            <td>{{ ucfirst($programme->academic_level ?? 'bachelor') }}</td>
                            <td>{{ $programme->duration_years ?? '—' }} yrs</td>
                            <td>Year {{ $programme->final_year ?? '—' }}</td>
                            <td><span class="badge bg-primary-subtle text-primary-emphasis">{{ $programme->output_type ?? 'RESEARCH_ONLY' }}</span></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editProgModal-{{ $programme->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.academic-configuration.programmes.destroy', $programme) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this programme?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No programmes configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-top py-3">
            <x-prms-table-pagination-footer :paginator="$programmes" />
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="addProgrammeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <form method="POST" action="{{ route('admin.academic-configuration.programmes.store') }}">
                    @csrf
                    <div class="modal-header bg-surface-soft">
                        <h2 class="modal-title h5 fw-bold">Add programme</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @include('admin.academic-configuration.partials.programme-fields', ['programme' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create programme</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @foreach ($programmes as $programme)
        <div class="modal fade" id="editProgModal-{{ $programme->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('admin.academic-configuration.programmes.update', $programme) }}">
                        @csrf @method('PUT')
                        <div class="modal-header bg-surface-soft">
                            <h2 class="modal-title h5 fw-bold">Edit {{ $programme->programme_code }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            @include('admin.academic-configuration.partials.programme-fields')
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save programme</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush
