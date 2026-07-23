@extends('layouts.app')

@section('title', 'Student records')

@section('content')
<x-prms-greeting-banner :subtitle="'Update programme and year of study for students in '.$deptName.'. Department is fixed to your department; contact the system administrator for transfers.'">
    <a href="{{ route('hod.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
        <i class="fas fa-chart-line me-2" aria-hidden="true"></i> Department overview
    </a>
    <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
    </a>
</x-prms-greeting-banner>

@if (! $department)
    <div class="alert alert-warning" role="status">
        Your staff profile is not linked to a department. Ask an administrator to assign your department before managing student records.
    </div>
@else
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="h6 fw-bold mb-0">
                <i class="fas fa-user-graduate text-primary me-2" aria-hidden="true"></i>
                Students in your department
            </h3>
            <small class="text-muted">{{ $students->total() }} total</small>
        </div>
        <div class="card-body p-0">
            <x-prms-table-pagination-toolbar :paginator="$students" noun="students" />
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Reg. no</th>
                            <th scope="col">Department</th>
                            <th scope="col">Programme</th>
                            <th scope="col">Year</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-strong">{{ $student->name }}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 220px;">{{ $student->email }}</div>
                                </td>
                                <td><code class="small">{{ $student->regNo() ?? '—' }}</code></td>
                                <td>{{ $student->department ?: '—' }}</td>
                                <td>{{ $student->programme ?: '—' }}</td>
                                <td>{{ $student->year_of_study ? 'Year ' . $student->year_of_study : '—' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-light btn-sm border"
                                            data-bs-toggle="modal"
                                            data-bs-target="#hodEditStudent-{{ $student->id }}">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    No students matched your department yet. Ensure student accounts list the correct department, or link them to a programme in this department.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <x-prms-table-pagination-footer :paginator="$students" class="card-footer bg-white border-top" />
    </div>
@endif

@foreach ($students as $student)
    <div class="modal fade" id="hodEditStudent-{{ $student->id }}" tabindex="-1" aria-labelledby="hodEditStudentTitle-{{ $student->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content border-0">
                <div class="modal-header bg-surface-soft">
                    <h2 class="modal-title h5 fw-bold text-strong" id="hodEditStudentTitle-{{ $student->id }}">
                        <i class="fas fa-user-edit text-primary me-2" aria-hidden="true"></i>
                        Update academic record
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('hod.students.update', $student) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="edit_user_id" value="{{ $student->id }}">
                    <div class="modal-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                            <span class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle fw-bold"
                                  style="width: 48px; height: 48px;">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 1)) }}
                            </span>
                            <div>
                                <div class="fw-semibold text-strong">{{ $student->name }}</div>
                                <div class="small text-muted">{{ $student->email }}</div>
                                <code class="text-xs">{{ $student->regNo() ?? '—' }}</code>
                            </div>
                        </div>

                        <input type="hidden" name="department" value="{{ $department->department_name }}">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control" value="{{ $department->department_name }}" disabled>
                            <div class="form-text">Stored on the student account as your official department name.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="hod_programme_{{ $student->id }}" class="form-label fw-semibold">Programme</label>
                                <input type="text" id="hod_programme_{{ $student->id }}" name="programme"
                                       value="{{ (int) old('edit_user_id') === (int) $student->id ? old('programme', $student->programme) : $student->programme }}"
                                       class="form-control @error('programme') is-invalid @enderror"
                                       maxlength="120" placeholder="e.g. BBICT">
                                @error('programme')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                @php
                                    $hodYearVal = (int) old('edit_user_id') === (int) $student->id
                                        ? old('year_of_study', $student->year_of_study)
                                        : $student->year_of_study;
                                    $hodYearVal = $hodYearVal === '' || $hodYearVal === null ? null : (int) $hodYearVal;
                                @endphp
                                <label for="hod_year_{{ $student->id }}" class="form-label fw-semibold">Year of study</label>
                                <select id="hod_year_{{ $student->id }}" name="year_of_study"
                                        class="form-select @error('year_of_study') is-invalid @enderror">
                                    <option value="" @selected($hodYearVal === null)>—</option>
                                    @for ($y = 1; $y <= \App\Http\Requests\StoreAdminUserRequest::MAX_YEAR_OF_STUDY; $y++)
                                        <option value="{{ $y }}" @selected($hodYearVal === $y)>Year {{ $y }}</option>
                                    @endfor
                                </select>
                                @error('year_of_study')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1" aria-hidden="true"></i> Save
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
    @if ($errors->any() && old('edit_user_id'))
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('hodEditStudent-{{ (int) old('edit_user_id') }}');
        if (el && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    });
    @endif
</script>
@endpush
