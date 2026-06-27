@php
    $sortedStudents = $students instanceof \Illuminate\Support\Collection
        ? $students->sortBy('name')->values()
        : collect($students)->sortBy('name')->values();
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th scope="col" class="ps-4">Student</th>
                <th scope="col">Reg. no</th>
                <th scope="col">Gender</th>
                <th scope="col">Programme</th>
                <th scope="col" class="text-nowrap">Year</th>
                <th scope="col">Department</th>
                <th scope="col">Contact</th>
                <th scope="col" class="text-end pe-4">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sortedStudents as $student)
                @php
                    $profile = $student->studentProfile;
                    $regNo = $student->regNo() ?? '—';
                    $gender = $profile?->genderLabel() ?? '—';
                    $programme = $profile?->programme?->programme_code
                        ?? $profile?->programme?->programme_name
                        ?? $student->programme
                        ?? '—';
                    $year = $profile?->year_of_study ?? $student->year_of_study;
                    $department = $student->department ?? data_get($profile?->sis_data, 'department') ?? '—';
                    $phone = $student->phone_number ?? $profile?->phone_number;
                    $enrollment = $student->enrollment_status ?? $profile?->enrollment_status;
                @endphp
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle fw-bold flex-shrink-0"
                                  style="width: 36px; height: 36px; font-size: 0.85rem;">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-strong">{{ $student->name }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 220px;">
                                    {{ $student->email ?: '—' }}
                                </div>
                                @if ($enrollment)
                                    <span class="badge rounded-pill bg-light text-muted border mt-1">
                                        {{ \Illuminate\Support\Str::title($enrollment) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <code class="small bg-surface-soft px-1 rounded">{{ $regNo }}</code>
                    </td>
                    <td class="small text-muted">{{ $gender }}</td>
                    <td class="small">{{ $programme }}</td>
                    <td class="small text-muted">
                        {{ $year ? 'Year '.$year : '—' }}
                    </td>
                    <td class="small text-muted">{{ $department }}</td>
                    <td class="small text-muted">
                        @if ($phone)
                            <div class="text-nowrap">
                                <i class="fas fa-phone-alt me-1 opacity-50" aria-hidden="true"></i>
                                {{ $phone }}
                            </div>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                            <a href="{{ route('supervisor.logs.create', ['student' => $student->id]) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                Meeting history
                            </a>
                            <a href="{{ route('presentation-consent.download', ['student' => $student->id]) }}"
                               class="btn btn-sm btn-outline-secondary rounded-pill"
                               title="Download consent form">
                                <i class="fas fa-file-signature" aria-hidden="true"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted small">
                        No students in this assignment yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
