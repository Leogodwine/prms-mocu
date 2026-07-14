@extends('layouts.app')

@section('title', 'User management')

@section('content')

@php
    use App\Support\PrmsAccountIdentifierFormat;

    $roleLabels = [
        'admin'             => ['label' => 'Admin',             'tone' => 'bg-danger'],
        'hod'               => ['label' => 'Head of Dept.',     'tone' => 'bg-info'],
        'coordinator'       => ['label' => 'Coordinator',       'tone' => 'bg-primary'],
        'supervisor'        => ['label' => 'Supervisor',        'tone' => 'bg-success'],
        'project_student'   => ['label' => 'Project student',   'tone' => 'bg-warning'],
        'research_student'  => ['label' => 'Research student',  'tone' => 'bg-warning'],
        'normal_student'    => ['label' => 'Student',           'tone' => 'bg-warning'],
        'student'           => ['label' => 'Student',           'tone' => 'bg-warning'],
    ];

    $statusLabels = [
        'active'    => ['label' => 'Active',    'tone' => 'bg-success'],
        'inactive'  => ['label' => 'Inactive',  'tone' => 'bg-secondary'],
        'suspended' => ['label' => 'Suspended', 'tone' => 'bg-warning'],
        'locked'    => ['label' => 'Locked',    'tone' => 'bg-danger'],
    ];
@endphp

<x-prms-greeting-banner subtitle="Provision accounts, assign roles, monitor access, bulk-import users, or delete multiple accounts at once.">
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="fas fa-user-plus me-2" aria-hidden="true"></i>
        Create account
    </button>
    <button type="button" class="btn btn-light border rounded-pill px-3 d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
        <span class="material-symbols-outlined me-1 prms-md-icon-inline" aria-hidden="true">upload_file</span>
        Bulk import
    </button>
</x-prms-greeting-banner>

{{-- User table and modals --}}
{{-- ────────── Stats (clickable → filtered user list) ────────── --}}
@php
    $fQ = $filters['q'] ?? '';
    $fRole = $filters['role'] ?? '';
    $fStatus = $filters['status'] ?? '';
    $fPendingPw = ! empty($filters['must_change_password']);
    $isCohortTotal = ($fQ === '' && $fRole === '' && $fStatus === '' && ! $fPendingPw);
    $isCohortActive = ($fStatus === 'active' && $fQ === '' && $fRole === '' && ! $fPendingPw);
    $isCohortSuspended = ($fStatus === 'suspended_locked' && $fQ === '' && $fRole === '' && ! $fPendingPw);
    $isCohortPending = ($fPendingPw && $fQ === '' && $fRole === '' && $fStatus === '');
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <a href="{{ $filterResetUrl }}"
           class="prms-stat-card prms-stat-card--clickable text-reset text-decoration-none d-block h-100 @if ($isCohortTotal) prms-stat-card--selected @endif"
           style="--prms-stat-ring: var(--prms-color-primary-500);"
           @if ($isCohortTotal) aria-current="page" @endif>
            <div class="stat-label">Total accounts</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($stats['total']) }}</div>
                <div class="bg-primary-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px;">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('admin.users.index', ['status' => 'active']) }}"
           class="prms-stat-card prms-stat-card--clickable text-reset text-decoration-none d-block h-100 @if ($isCohortActive) prms-stat-card--selected @endif"
           style="--prms-primary: var(--prms-color-success-500); --prms-stat-ring: var(--prms-color-success-500);"
           @if ($isCohortActive) aria-current="page" @endif>
            <div class="stat-label">Active</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($stats['active']) }}</div>
                <div class="bg-success-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px; color: var(--prms-color-success-500);">
                    <i class="fas fa-user-check" aria-hidden="true"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('admin.users.index', ['status' => 'suspended_locked']) }}"
           class="prms-stat-card prms-stat-card--clickable text-reset text-decoration-none d-block h-100 @if ($isCohortSuspended) prms-stat-card--selected @endif"
           style="--prms-primary: var(--prms-color-warning-500); --prms-stat-ring: var(--prms-color-warning-500);"
           @if ($isCohortSuspended) aria-current="page" @endif>
            <div class="stat-label">Suspended &amp; locked</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($stats['suspended']) }}</div>
                <div class="bg-warning-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px; color: var(--prms-color-warning-500);">
                    <i class="fas fa-user-lock" aria-hidden="true"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('admin.users.index', ['must_change_password' => 1]) }}"
           class="prms-stat-card prms-stat-card--clickable text-reset text-decoration-none d-block h-100 @if ($isCohortPending) prms-stat-card--selected @endif"
           style="--prms-primary: var(--prms-color-info-500); --prms-stat-ring: var(--prms-color-info-500);"
           @if ($isCohortPending) aria-current="page" @endif>
            <div class="stat-label">Waiting reset password</div>
            <div class="d-flex align-items-center justify-content-between">
                <div class="stat-value">{{ number_format($stats['pending_reset']) }}</div>
                <div class="bg-info-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                     style="width: 40px; height: 40px; color: var(--prms-color-info-500);">
                    <i class="fas fa-key" aria-hidden="true"></i>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- ────────── User table ────────── --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title h6 fw-bold mb-3 d-flex align-items-center">
            <i class="fas fa-users-cog text-primary me-2" aria-hidden="true"></i>
            System users
        </h3>
        <form method="GET"
              action="{{ route('admin.users.index') }}"
              id="prmsUserFilterForm"
              class="prms-filter-toolbar">
            <div class="position-relative flex-grow-1" style="min-width: 220px;">
                <i class="fas fa-search position-absolute text-muted" aria-hidden="true"
                   style="left: 0.85rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       class="form-control form-control-sm ps-4"
                       placeholder="Name, email, reg. no., staff email, dept…"
                       aria-label="Search users"
                       autocomplete="off">
            </div>
            <select name="role"
                    class="form-select form-select-sm prms-user-filter-auto prms-filter-toolbar__field flex-shrink-0"
                    style="width: 160px;"
                    aria-label="Filter by role">
                <option value="">All roles</option>
                @foreach ($roles as $r)
                    <option value="{{ $r }}" @selected($filters['role'] === $r)>
                        {{ $roleLabels[$r]['label'] ?? \Illuminate\Support\Str::title(str_replace('_',' ',$r)) }}
                    </option>
                @endforeach
            </select>
            <select name="status"
                    class="form-select form-select-sm prms-user-filter-auto prms-filter-toolbar__field flex-shrink-0"
                    style="width: 180px;"
                    aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="suspended_locked" @selected(($filters['status'] ?? '') === 'suspended_locked')>Suspended or locked</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($filters['status'] === $s)>
                        {{ $statusLabels[$s]['label'] ?? \Illuminate\Support\Str::title($s) }}
                    </option>
                @endforeach
            </select>
            @if (! empty($filters['must_change_password']))
                <input type="hidden" name="must_change_password" value="1">
            @endif
            <div class="d-flex flex-nowrap gap-2 ms-auto flex-shrink-0 prms-filter-toolbar__actions">
                <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                    <i class="fas fa-filter me-1" aria-hidden="true"></i>
                    Apply
                </button>
                <a href="{{ $filterResetUrl }}"
                   class="btn btn-sm btn-light border text-nowrap @if (! ($hasActiveFilters ?? false)) disabled @endif"
                   @if (! ($hasActiveFilters ?? false)) aria-disabled="true" tabindex="-1" @endif>
                    Clear
                </a>
            </div>
        </form>
        @if ($hasActiveFilters ?? false)
            <div class="px-3 pb-2 d-flex flex-wrap gap-2 align-items-center small">
                <span class="text-muted">Showing filtered results:</span>
                @if ($filters['q'] !== '')
                    <span class="badge bg-light text-dark border">Search: {{ $filters['q'] }}</span>
                @endif
                @if ($filters['role'] !== '')
                    <span class="badge bg-light text-dark border">Role: {{ $roleLabels[$filters['role']]['label'] ?? $filters['role'] }}</span>
                @endif
                @if ($filters['status'] !== '')
                    <span class="badge bg-light text-dark border">
                        Status: {{ $filters['status'] === 'suspended_locked' ? 'Suspended or locked' : ($statusLabels[$filters['status']]['label'] ?? $filters['status']) }}
                    </span>
                @endif
                @if (! empty($filters['must_change_password']))
                    <span class="badge bg-light text-dark border">Waiting reset password</span>
                @endif
            </div>
        @endif
    </div>

    <div class="card-body p-0">
        <x-prms-table-pagination-toolbar :paginator="$users" noun="users" />
        <div id="prmsBulkUserActions" class="d-none align-items-center justify-content-between flex-wrap gap-2 px-3 py-2 border-bottom bg-surface-soft">
            <span class="small text-muted">
                <span id="prmsSelectedUserCount">0</span> selected
            </span>
            <button type="button"
                    id="prmsBulkDeleteUsersBtn"
                    class="btn btn-sm btn-outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#bulkDeleteUsersModal"
                    disabled>
                <i class="fas fa-trash-alt me-1" aria-hidden="true"></i>
                Delete selected
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col" style="width: 42px;">
                            <input type="checkbox" class="form-check-input" id="prmsSelectAllUsers" aria-label="Select all users on this page">
                        </th>
                        <th scope="col">User</th>
                        <th scope="col">Reg. no / Staff email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $role = $roleLabels[$user->role] ?? ['label' => \Illuminate\Support\Str::title(str_replace('_',' ', $user->role)), 'tone' => 'bg-secondary'];
                            $status = $statusLabels[$user->account_status] ?? ['label' => \Illuminate\Support\Str::title($user->account_status), 'tone' => 'bg-secondary'];
                        @endphp
                        <tr>
                            <td>
                                @if ((int) auth()->id() !== (int) $user->id)
                                    <input type="checkbox"
                                           class="form-check-input prms-user-select"
                                           name="user_ids[]"
                                           value="{{ $user->id }}"
                                           aria-label="Select {{ $user->name }}">
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle fw-bold flex-shrink-0"
                                          style="width: 38px; height: 38px;">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-body">{{ $user->name }}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 240px;">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code class="bg-surface-soft px-2 py-1 rounded small fw-normal">{{ $user->displayIdentifier() }}</code>
                            </td>
                            <td>
                                <span class="badge {{ $role['tone'] }}">{{ $role['label'] }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $status['tone'] }}">
                                    @if ($user->account_status === 'active')
                                        <i class="fas fa-circle me-1" aria-hidden="true" style="font-size: 0.45rem;"></i>
                                    @endif
                                    {{ $status['label'] }}
                                </span>
                                @if ($user->must_change_password)
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-key me-1" aria-hidden="true"></i>
                                        Pending reset
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    @if ((int) auth()->id() === (int) $user->id)
                                        <span class="small text-muted" title="Another administrator must edit your account">
                                            <a href="{{ route('profile.edit') }}">My profile</a>
                                        </span>
                                    @else
                                        <button type="button"
                                                class="btn btn-light btn-sm border"
                                                title="Edit role &amp; status"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUserModal-{{ $user->id }}">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-light btn-sm border"
                                                title="Generate reset password"
                                                data-bs-toggle="modal"
                                                data-bs-target="#resetPasswordModal-{{ $user->id }}">
                                            <i class="fas fa-key" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-light btn-sm border text-danger"
                                                title="Delete user"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteUserModal-{{ $user->id }}">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                                     style="width: 64px; height: 64px;">
                                    <i class="fas fa-user-slash text-muted" aria-hidden="true" style="font-size: 1.4rem;"></i>
                                </div>
                                <h4 class="h6 fw-bold text-strong">No users match your filters</h4>
                                <p class="text-muted small mb-3">
                                    @if ($filters['q'] || $filters['role'] || $filters['status'])
                                        Try clearing your filters or creating a new account.
                                    @else
                                        Create the first system user to get started.
                                    @endif
                                </p>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                    <i class="fas fa-user-plus me-1" aria-hidden="true"></i>
                                    Create account
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-prms-table-pagination-footer :paginator="$users" class="card-footer bg-white border-top" />
</div>

@endsection

@push('modals')
@php
    use App\Http\Requests\StoreAdminUserRequest;
    use App\Support\PrmsSms;

    $errors = $errors ?? new Illuminate\Support\ViewErrorBag();

    $formRoles = StoreAdminUserRequest::FORM_ROLES;
    $studentRoleValues = StoreAdminUserRequest::STUDENT_ROLES;
    $staffFormRoles = StoreAdminUserRequest::STAFF_FORM_ROLES;
    $formRoleLabels = [
        'admin' => 'Admin',
        'hod' => 'Head of Dept.',
        'coordinator' => 'Coordinator',
        'supervisor' => 'Supervisor',
        'student' => 'Student',
    ];
    $createFormFailed = old('form_context') === 'create';
    $createFormRole = $createFormFailed ? (string) old('role', '') : '';
    $createRoleIsStudent = StoreAdminUserRequest::isStudentFormRole($createFormRole);
    $createRoleIsStaff = in_array($createFormRole, $staffFormRoles, true);
@endphp
{{-- ════════════════════════════════════════════════════════════════
     CREATE USER MODAL
   ════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-surface-soft">
                <h2 class="modal-title h5 fw-bold text-strong" id="createUserModalTitle">
                    <i class="fas fa-user-plus text-primary me-2" aria-hidden="true"></i>
                    Create a new account
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
                @csrf
                <input type="hidden" name="form_context" value="create">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">
                        A random temporary password is generated and sent to the user by <strong>email</strong> and <strong>in-app notification</strong> together with their account identifier (reg. no or staff ID).
                        The user must set a new password at first login.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="create_name" class="form-label">Full name <span class="text-danger">*</span></label>
                            <input id="create_name" type="text" name="name" value="{{ $createFormFailed ? old('name') : '' }}"
                                   class="form-control {{ $createFormFailed && $errors->has('name') ? 'is-invalid' : '' }}"
                                   placeholder="John Doe">
                            @if ($createFormFailed && $errors->has('name'))
                                <div class="invalid-feedback d-block">{{ $errors->first('name') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label for="create_email" class="form-label">Email address <span class="text-danger">*</span></label>
                            <input id="create_email" type="email" name="email" value="{{ $createFormFailed ? old('email') : '' }}"
                                   class="form-control {{ $createFormFailed && $errors->has('email') ? 'is-invalid' : '' }}"
                                   placeholder="john.doe@mocu.ac.tz" autocomplete="off">
                            @if ($createFormFailed && $errors->has('email'))
                                <div class="invalid-feedback d-block">{{ $errors->first('email') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6" data-prms-phone-group>
                            <label for="create_phone" class="form-label">Phone number <span class="text-danger">*</span></label>
                            <input id="create_phone" type="tel" name="phone_number" inputmode="numeric"
                                   value="{{ $createFormFailed ? old('phone_number') : '' }}"
                                   class="form-control prms-phone-field {{ $createFormFailed && $errors->has('phone_number') ? 'is-invalid' : '' }}"
                                   maxlength="16" placeholder="{{ \App\Support\PrmsSms::E164_EXAMPLE }}"
                                   required autocomplete="tel">
                            <div class="invalid-feedback prms-phone-feedback {{ $createFormFailed && $errors->has('phone_number') ? 'd-block' : '' }}" @if($createFormFailed && $errors->has('phone_number')) data-server-error @endif>
                                @if ($createFormFailed && $errors->has('phone_number'))
                                    {{ $errors->first('phone_number') }}
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="create_role" class="form-label">System role <span class="text-danger">*</span></label>
                            <select id="create_role" name="role"
                                    class="form-select {{ $createFormFailed && $errors->has('role') ? 'is-invalid' : '' }}">
                                <option value="" disabled @selected($createFormRole === '')>Select role…</option>
                                @foreach ($formRoles as $r)
                                    <option value="{{ $r }}" @selected($createFormRole === $r)>
                                        {{ $formRoleLabels[$r] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $r)) }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($createFormFailed && $errors->has('role'))
                                <div class="invalid-feedback d-block">{{ $errors->first('role') }}</div>
                            @endif
                        </div>

                        <div class="col-12 prms-user-fields-student" @if(! $createRoleIsStudent) hidden @endif>
                            <div class="row g-3">
                                <div class="col-md-6" data-prms-identifier-group>
                                    <label for="create_registration_number" class="form-label">Registration number <span class="text-danger">*</span></label>
                                    <input id="create_registration_number" type="text"
                                           @if ($createRoleIsStudent) name="login_id" @endif
                                           value="{{ $createFormFailed && $createRoleIsStudent ? old('login_id') : '' }}"
                                           class="form-control prms-account-identifier-field prms-student-login-id {{ $createFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           placeholder="{{ PrmsAccountIdentifierFormat::STUDENT_EXAMPLE }}"
                                           maxlength="80" autocomplete="off"
                                           @if($createRoleIsStudent) required @endif
                                           @if(! $createRoleIsStudent) disabled @endif>
                                    <div class="invalid-feedback prms-identifier-feedback {{ $createFormFailed && $errors->has('login_id') ? 'd-block' : '' }}" @if($createFormFailed && $errors->has('login_id')) data-server-error @endif>
                                        @if ($createFormFailed && $errors->has('login_id'))
                                            {{ $errors->first('login_id') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="create_department" class="form-label">Department <span class="text-danger">*</span></label>
                                    @php $createDepartment = $createFormFailed ? old('department') : ''; @endphp
                                    <select id="create_department" name="department"
                                            class="form-select {{ $createFormFailed && $errors->has('department') ? 'is-invalid' : '' }}"
                                            @if(! $createRoleIsStudent) disabled @endif>
                                        <option value="">Select department…</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->department_code }}"
                                                    data-department-id="{{ $department->id }}"
                                                    @selected($createDepartment === $department->department_code || $createDepartment === $department->department_name)>
                                                {{ $department->department_name }} ({{ $department->department_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($createFormFailed && $errors->has('department'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('department') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label for="create_programme" class="form-label">Programme <span class="text-danger">*</span></label>
                                    @php $createProgramme = $createFormFailed ? old('programme') : ''; @endphp
                                    <select id="create_programme" name="programme"
                                            class="form-select {{ $createFormFailed && $errors->has('programme') ? 'is-invalid' : '' }}"
                                            @if(! $createRoleIsStudent) disabled @endif>
                                        <option value="">Select programme…</option>
                                        @foreach ($programmes as $programme)
                                            <option value="{{ $programme->programme_code }}"
                                                    data-department-id="{{ $programme->department_id ?? '' }}"
                                                    @selected($createProgramme === $programme->programme_code || $createProgramme === $programme->programme_name)>
                                                {{ $programme->programme_code }} — {{ $programme->programme_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($createFormFailed && $errors->has('programme'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('programme') }}</div>
                                    @endif
                                </div>

                                @php
                                    $createYear = $createFormFailed ? old('year_of_study') : null;
                                    $createYear = $createYear === '' || $createYear === null ? null : (int) $createYear;
                                @endphp
                                <div class="col-md-6">
                                    <label for="create_year_of_study" class="form-label">Year of study <span class="text-danger">*</span></label>
                                    <select id="create_year_of_study" name="year_of_study"
                                            class="form-select {{ $createFormFailed && $errors->has('year_of_study') ? 'is-invalid' : '' }}">
                                        <option value="" @selected($createYear === null)>—</option>
                                        @for ($y = 1; $y <= StoreAdminUserRequest::MAX_YEAR_OF_STUDY; $y++)
                                            <option value="{{ $y }}" @selected($createYear === $y)>Year {{ $y }}</option>
                                        @endfor
                                    </select>
                                    @if ($createFormFailed && $errors->has('year_of_study'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('year_of_study') }}</div>
                                    @endif
                                </div>

                                @php
                                    $createGender = $createFormFailed ? old('gender') : null;
                                @endphp
                                <div class="col-md-6">
                                    <label for="create_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select id="create_gender" name="gender"
                                            class="form-select {{ $createFormFailed && $errors->has('gender') ? 'is-invalid' : '' }}"
                                            @if(! $createRoleIsStudent) disabled @endif>
                                        <option value="" @selected($createGender === null || $createGender === '')>—</option>
                                        <option value="male" @selected($createGender === 'male')>Male</option>
                                        <option value="female" @selected($createGender === 'female')>Female</option>
                                    </select>
                                    @if ($createFormFailed && $errors->has('gender'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('gender') }}</div>
                                    @endif
                                </div>

                                <div class="col-12">
                                    <p class="form-text mb-0">
                                        Research student, project student, or standard student access is assigned automatically from department, programme, and year of study.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 prms-user-fields-staff" @if(! $createRoleIsStaff) hidden @endif>
                            <div class="row g-3">
                                <div class="col-md-6" data-prms-identifier-group>
                                    <label for="create_staff_id" class="form-label">Staff ID <span class="text-danger">*</span></label>
                                    <input id="create_staff_id" type="text"
                                           @if ($createRoleIsStaff) name="login_id" @endif
                                           value="{{ $createFormFailed && $createRoleIsStaff ? old('login_id') : '' }}"
                                           class="form-control prms-account-identifier-field prms-staff-login-id {{ $createFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           placeholder="{{ PrmsAccountIdentifierFormat::STAFF_EXAMPLE }}"
                                           maxlength="80" autocomplete="off"
                                           data-prms-allow-admin-legacy="{{ $createFormRole === 'admin' ? '1' : '0' }}"
                                           @if($createRoleIsStaff) required @endif
                                           @if(! $createRoleIsStaff) disabled @endif>
                                    <div class="invalid-feedback prms-identifier-feedback {{ $createFormFailed && $errors->has('login_id') ? 'd-block' : '' }}" @if($createFormFailed && $errors->has('login_id')) data-server-error @endif>
                                        @if ($createFormFailed && $errors->has('login_id'))
                                            {{ $errors->first('login_id') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6 prms-staff-department-field" @if($createFormRole === 'admin') hidden @endif>
                                    <label for="create_staff_department" class="form-label">Department <span class="text-danger">*</span></label>
                                    @php $createStaffDepartment = $createFormFailed ? old('department') : ''; @endphp
                                    <select id="create_staff_department" name="department"
                                            class="form-select prms-staff-department {{ $createFormFailed && $errors->has('department') ? 'is-invalid' : '' }}"
                                            @if(! $createRoleIsStaff || $createFormRole === 'admin') disabled @endif>
                                        <option value="">Select department…</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->department_code }}"
                                                    @selected($createStaffDepartment === $department->department_code || $createStaffDepartment === $department->department_name)>
                                                {{ $department->department_name }} ({{ $department->department_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($createFormFailed && $errors->has('department'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('department') }}</div>
                                    @endif
                                </div>

                                @php
                                    $createStaffGender = $createFormFailed ? old('gender') : null;
                                @endphp
                                <div class="col-md-6">
                                    <label for="create_staff_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select id="create_staff_gender" name="gender"
                                            class="form-select {{ $createFormFailed && $errors->has('gender') ? 'is-invalid' : '' }}"
                                            @if(! $createRoleIsStaff) disabled @endif>
                                        <option value="" @selected($createStaffGender === null || $createStaffGender === '')>—</option>
                                        <option value="male" @selected($createStaffGender === 'male')>Male</option>
                                        <option value="female" @selected($createStaffGender === 'female')>Female</option>
                                    </select>
                                    @if ($createFormFailed && $errors->has('gender'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('gender') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1" aria-hidden="true"></i> Create account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     PER-USER MODALS (edit + delete)
   ════════════════════════════════════════════════════════════════ --}}
@foreach ($users as $user)
    @if ((int) auth()->id() !== (int) $user->id)
    {{-- Edit modal --}}
    <div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="editUserModalTitle-{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-surface-soft">
                    <h2 class="modal-title h5 fw-bold text-strong" id="editUserModalTitle-{{ $user->id }}">
                        <i class="fas fa-user-edit text-primary me-2" aria-hidden="true"></i>
                        Edit account
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_context" value="edit">
                    <input type="hidden" name="edit_user_id" value="{{ $user->id }}">
                    @php
                        $editFormFailed = old('form_context') === 'edit' && (int) old('edit_user_id') === (int) $user->id;
                        $editName = $editFormFailed ? old('name', $user->name) : $user->name;
                        $editEmail = $editFormFailed ? old('email', $user->email) : $user->email;
                        $editLoginId = $editFormFailed ? old('login_id', $user->login_id) : $user->login_id;
                        $editPhone = $editFormFailed ? old('phone_number', $user->phone_number) : $user->phone_number;
                        $editRole = $editFormFailed ? old('role', $user->role) : $user->role;
                        $editStatus = $editFormFailed ? old('account_status', $user->account_status) : $user->account_status;
                        $editDepartment = $editFormFailed ? old('department', $user->department) : $user->department;
                        $editProgramme = $editFormFailed ? old('programme', $user->programme) : $user->programme;
                        $editYearRaw = $editFormFailed ? old('year_of_study', $user->year_of_study) : $user->year_of_study;
                        $editYear = $editYearRaw === '' || $editYearRaw === null ? null : (int) $editYearRaw;
                        $editFormRole = StoreAdminUserRequest::isStudentFormRole((string) $editRole) ? 'student' : (string) $editRole;
                        $editRoleIsStudent = $editFormRole === 'student';
                        $editRoleIsStaff = in_array($editFormRole, $staffFormRoles, true);
                    @endphp
                    <div class="modal-body p-4">
                        <p class="fw-semibold small text-uppercase text-muted mb-3">Account details</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="edit_name_{{ $user->id }}" class="form-label">Full name <span class="text-danger">*</span></label>
                                <input type="text" id="edit_name_{{ $user->id }}" name="name"
                                       value="{{ $editName }}"
                                       class="form-control {{ $editFormFailed && $errors->has('name') ? 'is-invalid' : '' }}"
                                       maxlength="120" placeholder="John Doe">
                                @if ($editFormFailed && $errors->has('name'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('name') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="edit_email_{{ $user->id }}" class="form-label">Email address <span class="text-danger">*</span></label>
                                <input type="email" id="edit_email_{{ $user->id }}" name="email"
                                       value="{{ $editEmail }}"
                                       class="form-control {{ $editFormFailed && $errors->has('email') ? 'is-invalid' : '' }}"
                                       maxlength="255" autocomplete="off">
                                @if ($editFormFailed && $errors->has('email'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('email') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6" data-prms-phone-group>
                                <label for="edit_phone_{{ $user->id }}" class="form-label">Phone number <span class="text-danger">*</span></label>
                                <input type="tel" id="edit_phone_{{ $user->id }}" name="phone_number" inputmode="numeric"
                                       value="{{ $editPhone }}"
                                       class="form-control prms-phone-field {{ $editFormFailed && $errors->has('phone_number') ? 'is-invalid' : '' }}"
                                       maxlength="16" placeholder="{{ \App\Support\PrmsSms::E164_EXAMPLE }}"
                                       required autocomplete="tel">
                                <div class="invalid-feedback prms-phone-feedback {{ $editFormFailed && $errors->has('phone_number') ? 'd-block' : '' }}" @if($editFormFailed && $errors->has('phone_number')) data-server-error @endif>
                                    @if ($editFormFailed && $errors->has('phone_number'))
                                        {{ $errors->first('phone_number') }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_role_{{ $user->id }}" class="form-label">System role <span class="text-danger">*</span></label>
                                <select id="edit_role_{{ $user->id }}" name="role"
                                        class="form-select prms-edit-role {{ $editFormFailed && $errors->has('role') ? 'is-invalid' : '' }}">
                                    @foreach ($formRoles as $r)
                                        <option value="{{ $r }}" @selected($editFormRole === $r)>
                                            {{ $formRoleLabels[$r] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $r)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($editFormFailed && $errors->has('role'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('role') }}</div>
                                @endif
                                @if ($editRoleIsStudent && ! $editFormFailed && in_array($user->role, ['project_student', 'research_student', 'normal_student'], true))
                                    <div class="form-text">Assigned access: {{ $roleLabels[$user->role]['label'] ?? $user->role }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="prms-user-fields-student mb-4" @if(! $editRoleIsStudent) hidden @endif>
                            <p class="fw-semibold small text-uppercase text-muted mb-3">Student record</p>
                            <div class="row g-3">
                                <div class="col-md-6" data-prms-identifier-group>
                                    <label for="edit_registration_number_{{ $user->id }}" class="form-label">Registration number <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_registration_number_{{ $user->id }}"
                                           @if ($editRoleIsStudent) name="login_id" @endif
                                           value="{{ $editLoginId }}"
                                           class="form-control prms-account-identifier-field prms-student-login-id {{ $editFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           maxlength="80" placeholder="{{ PrmsAccountIdentifierFormat::STUDENT_EXAMPLE }}"
                                           autocomplete="off"
                                           @if($editRoleIsStudent) required @endif
                                           @if(! $editRoleIsStudent) disabled @endif>
                                    <div class="invalid-feedback prms-identifier-feedback {{ $editFormFailed && $errors->has('login_id') ? 'd-block' : '' }}" @if($editFormFailed && $errors->has('login_id')) data-server-error @endif>
                                        @if ($editFormFailed && $errors->has('login_id'))
                                            {{ $errors->first('login_id') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_department_{{ $user->id }}" class="form-label">Department <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_department_{{ $user->id }}" name="department"
                                           value="{{ $editDepartment }}"
                                           class="form-control {{ $editFormFailed && $errors->has('department') ? 'is-invalid' : '' }}"
                                           maxlength="120" placeholder="Department name"
                                           @if(! $editRoleIsStudent) disabled @endif>
                                    @if ($editFormFailed && $errors->has('department'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('department') }}</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_programme_{{ $user->id }}" class="form-label">Programme <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_programme_{{ $user->id }}" name="programme"
                                           value="{{ $editProgramme }}"
                                           class="form-control {{ $editFormFailed && $errors->has('programme') ? 'is-invalid' : '' }}"
                                           maxlength="120" placeholder="e.g. BBICT"
                                           @if(! $editRoleIsStudent) disabled @endif>
                                    @if ($editFormFailed && $errors->has('programme'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('programme') }}</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_year_{{ $user->id }}" class="form-label">Year of study <span class="text-danger">*</span></label>
                                    <select id="edit_year_{{ $user->id }}" name="year_of_study"
                                            class="form-select {{ $editFormFailed && $errors->has('year_of_study') ? 'is-invalid' : '' }}"
                                            @if(! $editRoleIsStudent) disabled @endif>
                                        <option value="" @selected($editYear === null)>—</option>
                                        @for ($y = 1; $y <= StoreAdminUserRequest::MAX_YEAR_OF_STUDY; $y++)
                                            <option value="{{ $y }}" @selected($editYear === $y)>Year {{ $y }}</option>
                                        @endfor
                                    </select>
                                    @if ($editFormFailed && $errors->has('year_of_study'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('year_of_study') }}</div>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <p class="form-text mb-0">Research, project, or standard student access is assigned automatically from these fields.</p>
                                </div>
                            </div>
                        </div>

                        <div class="prms-user-fields-staff mb-4" @if(! $editRoleIsStaff) hidden @endif>
                            <p class="fw-semibold small text-uppercase text-muted mb-3">Staff record</p>
                            <div class="row g-3">
                                <div class="col-md-6" data-prms-identifier-group>
                                    <label for="edit_staff_id_{{ $user->id }}" class="form-label">Staff ID <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_staff_id_{{ $user->id }}"
                                           @if ($editRoleIsStaff) name="login_id" @endif
                                           value="{{ $editLoginId }}"
                                           class="form-control prms-account-identifier-field prms-staff-login-id {{ $editFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           maxlength="80" placeholder="{{ PrmsAccountIdentifierFormat::STAFF_EXAMPLE }}"
                                           autocomplete="off"
                                           data-prms-allow-admin-legacy="{{ $editFormRole === 'admin' ? '1' : '0' }}"
                                           @if($editRoleIsStaff) required @endif
                                           @if(! $editRoleIsStaff) disabled @endif>
                                    <div class="invalid-feedback prms-identifier-feedback {{ $editFormFailed && $errors->has('login_id') ? 'd-block' : '' }}" @if($editFormFailed && $errors->has('login_id')) data-server-error @endif>
                                        @if ($editFormFailed && $errors->has('login_id'))
                                            {{ $errors->first('login_id') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 prms-staff-department-field" @if($editFormRole === 'admin') hidden @endif>
                                    <label for="edit_staff_department_{{ $user->id }}" class="form-label">Department <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_staff_department_{{ $user->id }}" name="department"
                                           value="{{ $editDepartment }}"
                                           class="form-control prms-staff-department {{ $editFormFailed && $errors->has('department') ? 'is-invalid' : '' }}"
                                           maxlength="120" placeholder="Department name"
                                           @if(! $editRoleIsStaff || $editFormRole === 'admin') disabled @endif>
                                    @if ($editFormFailed && $errors->has('department'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('department') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <p class="fw-semibold small text-uppercase text-muted mb-3">Access &amp; status</p>
                        <div class="mb-2">
                            <label for="edit_status_{{ $user->id }}" class="form-label">Account status <span class="text-danger">*</span></label>
                            <select id="edit_status_{{ $user->id }}" name="account_status"
                                    class="form-select {{ $editFormFailed && $errors->has('account_status') ? 'is-invalid' : '' }}">
                                @foreach ($statuses as $s)
                                    <option value="{{ $s }}" @selected($editStatus === $s)>
                                        {{ $statusLabels[$s]['label'] ?? \Illuminate\Support\Str::title($s) }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($editFormFailed && $errors->has('account_status'))
                                <div class="invalid-feedback d-block">{{ $errors->first('account_status') }}</div>
                            @endif
                            <div class="form-text">
                                Use <strong>Suspended</strong> for temporary holds and <strong>Locked</strong>
                                for security incidents.
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            Students cannot change academic fields on their own profile; keep them aligned with official records.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1" aria-hidden="true"></i> Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reset password modal --}}
    <div class="modal fade" id="resetPasswordModal-{{ $user->id }}" tabindex="-1" aria-labelledby="resetPasswordModalTitle-{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-surface-soft">
                    <h2 class="modal-title h5 fw-bold text-strong" id="resetPasswordModalTitle-{{ $user->id }}">
                        <i class="fas fa-key text-primary me-2" aria-hidden="true"></i>
                        Reset password
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="mb-3">
                            Generate a new temporary password for <strong>{{ $user->name }}</strong>
                            and send it by in-app notification and email.
                        </p>
                        <ul class="small text-muted mb-0">
                            <li>The user must change this password at next sign-in.</li>
                            <li>Use this when a user has forgotten their password.</li>
                            <li>If email delivery fails, share the temporary password manually from the confirmation message.</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1" aria-hidden="true"></i> Generate &amp; send reset password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="deleteUserModalTitle-{{ $user->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header bg-danger-soft">
                        <h2 class="modal-title h5 fw-bold text-strong" id="deleteUserModalTitle-{{ $user->id }}" style="color: var(--prms-color-danger-500);">
                            <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                            Delete user
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body p-4">
                            <p class="text-strong">
                                You are about to permanently delete the following account.
                                This action is logged in the audit trail and cannot be undone.
                            </p>
                            <div class="bg-surface-soft border-soft rounded p-3 small">
                                <div><strong>Name:</strong> {{ $user->name }}</div>
                                <div><strong>Email:</strong> {{ $user->email }}</div>
                                <div><strong>{{ $user->displayIdentifierLabel() }}:</strong> <code>{{ $user->displayIdentifier() }}</code></div>
                                <div><strong>Role:</strong> {{ $roleLabels[$user->role]['label'] ?? $user->role }}</div>
                            </div>
                            <p class="text-muted small mt-3 mb-0">
                                Consider <em>suspending</em> the account instead if you may need it back later.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-1" aria-hidden="true"></i> Yes, delete user
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- ════════════════════════════════════════════════════════════════
     BULK IMPORT MODAL
   ════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 prms-bulk-import-modal">
            <div class="modal-header bg-surface-soft border-0 pb-0">
                <div class="d-flex align-items-start gap-3">
                    <div class="prms-md-icon-badge prms-md-icon-badge--primary" aria-hidden="true">
                        <span class="material-symbols-outlined">group_add</span>
                    </div>
                    <div>
                        <h2 class="modal-title h5 fw-bold text-strong mb-1" id="bulkImportModalTitle">Bulk user import</h2>
                        <p class="text-muted small mb-0">Create many student or staff accounts from one file.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.bulk-import') }}" method="POST" enctype="multipart/form-data" id="prmsBulkImportForm">
                @csrf
                <div class="modal-body p-4 pt-3">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <article class="prms-md-surface-card h-100">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="prms-md-icon-badge prms-md-icon-badge--student" aria-hidden="true">
                                        <span class="material-symbols-outlined">school</span>
                                    </div>
                                    <h3 class="h6 fw-bold mb-0">Student rows</h3>
                                </div>
                                <p class="small text-muted mb-2">Required columns</p>
                                <code class="d-block small mb-3 prms-md-code-block">name, email, reg_no, phone_number, role, department, programme, year_of_study, gender</code>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="prms-md-chip">
                                        <span class="material-symbols-outlined" aria-hidden="true">tag</span>
                                        {{ PrmsAccountIdentifierFormat::STUDENT_EXAMPLE }}
                                    </span>
                                    <span class="prms-md-chip">
                                        <span class="material-symbols-outlined" aria-hidden="true">phone_iphone</span>
                                        {{ PrmsSms::E164_EXAMPLE }}
                                    </span>
                                    <span class="prms-md-chip prms-md-chip--muted">
                                        <span class="material-symbols-outlined" aria-hidden="true">wc</span>
                                        male / female
                                    </span>
                                </div>
                            </article>
                        </div>
                        <div class="col-md-6">
                            <article class="prms-md-surface-card h-100">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="prms-md-icon-badge prms-md-icon-badge--staff" aria-hidden="true">
                                        <span class="material-symbols-outlined">badge</span>
                                    </div>
                                    <h3 class="h6 fw-bold mb-0">Staff rows</h3>
                                </div>
                                <p class="small text-muted mb-2">Required columns</p>
                                <code class="d-block small mb-3 prms-md-code-block">name, email, staff_email, phone_number, role, department, gender</code>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="prms-md-chip">
                                        <span class="material-symbols-outlined" aria-hidden="true">mail</span>
                                        University sign-in email
                                    </span>
                                    <span class="prms-md-chip">
                                        <span class="material-symbols-outlined" aria-hidden="true">phone_iphone</span>
                                        {{ PrmsSms::E164_EXAMPLE }}
                                    </span>
                                    <span class="prms-md-chip prms-md-chip--muted">
                                        <span class="material-symbols-outlined" aria-hidden="true">info</span>
                                        Omit staff_email to reuse email
                                    </span>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="mb-2">
                        <span class="form-label fw-semibold d-block mb-2">Import file <span class="text-danger">*</span></span>
                        <label for="import_file" class="prms-md-upload-zone @error('import_file') is-invalid @enderror" id="prmsBulkImportDropzone" tabindex="0" role="button" aria-describedby="prmsBulkImportFileHint">
                            <span class="material-symbols-outlined prms-md-upload-zone__icon" aria-hidden="true">cloud_upload</span>
                            <span class="prms-md-upload-zone__title">Tap to choose a file</span>
                            <span class="prms-md-upload-zone__hint" id="prmsBulkImportFileHint">CSV, TXT, XML, or PDF</span>
                            <span class="prms-md-upload-zone__name" id="prmsBulkImportFileName">No file selected</span>
                        </label>
                        <input id="import_file" type="file" name="import_file" class="visually-hidden"
                               accept=".csv,.txt,.xml,.pdf,text/csv,text/plain,application/xml,application/pdf" required>
                        @error('import_file')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border d-inline-flex align-items-center" data-bs-dismiss="modal">
                        <span class="material-symbols-outlined me-1 prms-md-icon-inline" aria-hidden="true">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                        <span class="material-symbols-outlined me-1 prms-md-icon-inline" aria-hidden="true">upload</span>
                        Start import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk delete modal --}}
<div class="modal fade" id="bulkDeleteUsersModal" tabindex="-1" aria-labelledby="bulkDeleteUsersModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-surface-soft">
                <h2 class="modal-title h5 fw-bold text-strong" id="bulkDeleteUsersModalTitle" style="color: var(--prms-color-danger-500);">
                    <i class="fas fa-trash-alt me-2" aria-hidden="true"></i>
                    Delete selected users
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="prmsBulkDeleteUsersForm" method="POST" action="{{ route('admin.users.bulk-delete') }}">
                @csrf
                @foreach (request()->only(['q', 'role', 'status', 'must_change_password']) as $filterKey => $filterValue)
                    @if ($filterValue !== null && $filterValue !== '')
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
                    @endif
                @endforeach
                <div class="modal-body p-4">
                    <p class="mb-2">
                        You are about to permanently delete <strong id="prmsBulkDeleteCount">0</strong> account(s).
                    </p>
                    <p class="text-muted small mb-3">This action cannot be undone. Your own account is never included.</p>
                    <div id="prmsBulkDeleteUserList" class="small bg-surface-soft rounded p-3 mb-0" style="max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1" aria-hidden="true"></i>
                        Yes, delete selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('styles')
<style>
    a.prms-stat-card--clickable {
        transition: box-shadow 0.15s ease, transform 0.15s ease;
        cursor: pointer;
    }
    a.prms-stat-card--clickable:hover {
        box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.08);
        transform: translateY(-1px);
    }
    a.prms-stat-card--clickable.prms-stat-card--selected {
        box-shadow: inset 0 0 0 2px var(--prms-stat-ring, var(--prms-color-primary-500));
    }
    a.prms-stat-card--clickable:focus-visible {
        outline: 2px solid var(--prms-stat-ring, var(--prms-color-primary-500));
        outline-offset: 2px;
    }

    .prms-md-icon-inline {
        font-size: 1.125rem;
        line-height: 1;
        vertical-align: middle;
        font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    }

    .prms-bulk-import-modal {
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.14);
    }

    .prms-md-icon-badge {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .prms-md-icon-badge .material-symbols-outlined {
        font-size: 1.35rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    }

    .prms-md-icon-badge--primary {
        background: color-mix(in srgb, var(--prms-color-primary-500) 14%, white);
        color: var(--prms-color-primary-500);
    }

    .prms-md-icon-badge--student {
        background: color-mix(in srgb, var(--prms-color-warning-500) 16%, white);
        color: var(--prms-color-warning-500);
    }

    .prms-md-icon-badge--staff {
        background: color-mix(in srgb, var(--prms-color-success-500) 14%, white);
        color: var(--prms-color-success-500);
    }

    .prms-md-surface-card {
        background: var(--prms-surface-elevated, #fff);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 4px 16px rgba(15, 23, 42, 0.04);
    }

    .prms-md-code-block {
        background: rgba(15, 23, 42, 0.04);
        border-radius: 0.65rem;
        padding: 0.55rem 0.65rem;
        word-break: break-word;
    }

    .prms-md-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--prms-color-primary-600, #1266d4);
        background: color-mix(in srgb, var(--prms-color-primary-500) 10%, white);
        border: 1px solid color-mix(in srgb, var(--prms-color-primary-500) 18%, white);
    }

    .prms-md-chip--muted {
        color: #475569;
        background: rgba(15, 23, 42, 0.04);
        border-color: rgba(15, 23, 42, 0.08);
        font-weight: 500;
    }

    .prms-md-chip .material-symbols-outlined {
        font-size: 0.95rem;
        font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .prms-md-upload-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-height: 9.5rem;
        padding: 1.25rem;
        border: 2px dashed color-mix(in srgb, var(--prms-color-primary-500) 35%, #cbd5e1);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(21, 114, 232, 0.04) 0%, rgba(255, 255, 255, 0.9) 100%);
        cursor: pointer;
        text-align: center;
        transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    }

    .prms-md-upload-zone:hover,
    .prms-md-upload-zone:focus-within {
        border-color: var(--prms-color-primary-500);
        background: color-mix(in srgb, var(--prms-color-primary-500) 6%, white);
        box-shadow: 0 8px 24px rgba(21, 114, 232, 0.08);
    }

    .prms-md-upload-zone.is-invalid {
        border-color: var(--prms-color-danger-500);
        background: color-mix(in srgb, var(--prms-color-danger-500) 6%, white);
    }

    .prms-md-upload-zone__icon {
        font-size: 2.4rem;
        color: var(--prms-color-primary-500);
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 48;
    }

    .prms-md-upload-zone__title {
        font-weight: 700;
        color: #0f172a;
    }

    .prms-md-upload-zone__hint,
    .prms-md-upload-zone__name {
        font-size: 0.8125rem;
        color: #64748b;
    }

    .prms-md-upload-zone__name {
        margin-top: 0.15rem;
        font-weight: 600;
        color: var(--prms-color-primary-600, #1266d4);
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/prms-phone-field.js') }}?v=1" defer></script>
<script src="{{ asset('js/prms-account-identifier-field.js') }}?v=1" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bulkImportFile = document.getElementById('import_file');
        const bulkImportFileName = document.getElementById('prmsBulkImportFileName');
        const bulkImportDropzone = document.getElementById('prmsBulkImportDropzone');

        if (bulkImportFile && bulkImportFileName) {
            bulkImportFile.addEventListener('change', function () {
                const file = bulkImportFile.files && bulkImportFile.files[0];
                bulkImportFileName.textContent = file ? file.name : 'No file selected';
            });
        }

        if (bulkImportDropzone && bulkImportFile) {
            bulkImportDropzone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    bulkImportFile.click();
                }
            });
        }

        const filterForm = document.getElementById('prmsUserFilterForm');
        if (filterForm) {
            const searchInput = filterForm.querySelector('input[name="q"]');
            let searchTimer = null;

            filterForm.querySelectorAll('.prms-user-filter-auto').forEach(function (select) {
                select.addEventListener('change', function () {
                    filterForm.submit();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(function () {
                        filterForm.submit();
                    }, 450);
                });

                searchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        window.clearTimeout(searchTimer);
                        filterForm.submit();
                    }
                });
            }
        }

        const studentFormRole = @json(\App\Http\Requests\StoreAdminUserRequest::FORM_STUDENT_ROLE);
        const staffFormRoles = @json($staffFormRoles);

        function isStudentRole(role) {
            return role === studentFormRole;
        }

        function isStaffRole(role) {
            return staffFormRoles.includes(role);
        }

        function setFieldEnabled(field, enabled) {
            if (!field) {
                return;
            }
            field.disabled = !enabled;
        }

        function syncCreateProgrammeOptions() {
            const departmentSelect = document.getElementById('create_department');
            const programmeSelect = document.getElementById('create_programme');

            if (!departmentSelect || !programmeSelect) {
                return;
            }

            const selectedDepartmentId = departmentSelect.selectedOptions[0]?.getAttribute('data-department-id') || '';
            const currentValue = programmeSelect.value;
            let currentStillVisible = currentValue === '';

            Array.from(programmeSelect.options).forEach(function (option) {
                if (option.value === '') {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const optionDepartmentId = option.getAttribute('data-department-id') || '';
                const visible = selectedDepartmentId === '' || optionDepartmentId === selectedDepartmentId;
                option.hidden = !visible;
                option.disabled = !visible;

                if (visible && option.value === currentValue) {
                    currentStillVisible = true;
                }
            });

            if (!currentStillVisible) {
                programmeSelect.value = '';
            }
        }

        function syncUserFormFields(form) {
            if (!form) {
                return;
            }

            const roleSelect = form.querySelector('[name="role"]');
            if (!roleSelect) {
                return;
            }

            const role = roleSelect.value;
            const studentPanel = form.querySelector('.prms-user-fields-student');
            const staffPanel = form.querySelector('.prms-user-fields-staff');
            const studentLogin = form.querySelector('.prms-student-login-id');
            const staffLogin = form.querySelector('.prms-staff-login-id');
            const studentGender = form.querySelector('#create_gender, [id^="edit_gender_"]');
            const staffGender = form.querySelector('#create_staff_gender');
            const studentDepartment = form.querySelector('#create_department, [id^="edit_department_"]');
            const staffDepartment = form.querySelector('.prms-staff-department');
            const staffDepartmentWrap = form.querySelector('.prms-staff-department-field');
            const studentFields = form.querySelectorAll('.prms-user-fields-student input, .prms-user-fields-student select');
            const staffFields = form.querySelectorAll('.prms-user-fields-staff input, .prms-user-fields-staff select');

            const showStudent = isStudentRole(role);
            const showStaff = isStaffRole(role);

            if (studentPanel) {
                studentPanel.hidden = !showStudent;
            }
            if (staffPanel) {
                staffPanel.hidden = !showStaff;
            }

            if (studentLogin) {
                if (showStudent) {
                    studentLogin.setAttribute('name', 'login_id');
                    studentLogin.required = true;
                } else {
                    studentLogin.removeAttribute('name');
                    studentLogin.required = false;
                }
                setFieldEnabled(studentLogin, showStudent);
            }

            if (staffLogin) {
                if (showStaff) {
                    staffLogin.setAttribute('name', 'login_id');
                    staffLogin.required = true;
                } else {
                    staffLogin.removeAttribute('name');
                    staffLogin.required = false;
                }
                staffLogin.dataset.prmsAllowAdminLegacy = role === 'admin' ? '1' : '0';
                setFieldEnabled(staffLogin, showStaff);
            }

            if (studentDepartment) {
                if (showStudent) {
                    studentDepartment.setAttribute('name', 'department');
                } else {
                    studentDepartment.removeAttribute('name');
                }
                setFieldEnabled(studentDepartment, showStudent);
                if (studentDepartment.id === 'create_department') {
                    syncCreateProgrammeOptions();
                }
            }

            if (staffDepartment) {
                const staffDeptRequired = showStaff && role !== 'admin';
                if (staffDeptRequired) {
                    staffDepartment.setAttribute('name', 'department');
                } else {
                    staffDepartment.removeAttribute('name');
                }
                setFieldEnabled(staffDepartment, staffDeptRequired);
            }

            if (staffDepartmentWrap) {
                staffDepartmentWrap.hidden = !showStaff || role === 'admin';
            }

            if (studentGender) {
                if (showStudent) {
                    studentGender.setAttribute('name', 'gender');
                } else {
                    studentGender.removeAttribute('name');
                }
                setFieldEnabled(studentGender, showStudent);
            }

            if (staffGender) {
                if (showStaff) {
                    staffGender.setAttribute('name', 'gender');
                } else {
                    staffGender.removeAttribute('name');
                }
                setFieldEnabled(staffGender, showStaff);
            }

            studentFields.forEach(function (field) {
                if (field === studentLogin || field === studentDepartment || field === studentGender) {
                    return;
                }
                setFieldEnabled(field, showStudent);
            });

            staffFields.forEach(function (field) {
                if (field === staffLogin || field === staffDepartment || field === staffGender) {
                    return;
                }
                setFieldEnabled(field, showStaff);
            });

            if (window.PrmsAccountIdentifierField) {
                window.PrmsAccountIdentifierField.initAll(form);
            }
        }

        const createForm = document.querySelector('#createUserModal form');
        if (createForm) {
            const createRole = createForm.querySelector('#create_role');
            if (createRole) {
                createRole.addEventListener('change', function () {
                    syncUserFormFields(createForm);
                });
            }
            const createDepartment = createForm.querySelector('#create_department');
            if (createDepartment) {
                createDepartment.addEventListener('change', syncCreateProgrammeOptions);
            }
            createForm.addEventListener('submit', function () {
                syncUserFormFields(createForm);
            });
            syncUserFormFields(createForm);
            syncCreateProgrammeOptions();
        }

        document.querySelectorAll('[id^="editUserModal-"] form').forEach(function (form) {
            const roleSelect = form.querySelector('.prms-edit-role, [id^="edit_role_"]');
            if (roleSelect) {
                roleSelect.addEventListener('change', function () {
                    syncUserFormFields(form);
                });
            }
            form.addEventListener('submit', function () {
                syncUserFormFields(form);
            });
            syncUserFormFields(form);
        });

        @if ($errors->has('import_file'))
            const bulkImportModal = document.getElementById('bulkImportModal');
            if (bulkImportModal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(bulkImportModal).show();
            }
        @endif

        @if ($errors->any() && old('form_context') === 'create')
            const createModal = document.getElementById('createUserModal');
            if (createModal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(createModal).show();
            }
        @endif

        @if ($errors->any() && old('form_context') === 'edit' && old('edit_user_id'))
            const editModal = document.getElementById('editUserModal-{{ (int) old('edit_user_id') }}');
            if (editModal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(editModal).show();
            }
        @endif

        const selectAllUsers = document.getElementById('prmsSelectAllUsers');
        const userCheckboxes = Array.from(document.querySelectorAll('.prms-user-select'));
        const bulkActions = document.getElementById('prmsBulkUserActions');
        const selectedCountEl = document.getElementById('prmsSelectedUserCount');
        const bulkDeleteBtn = document.getElementById('prmsBulkDeleteUsersBtn');
        const bulkDeleteForm = document.getElementById('prmsBulkDeleteUsersForm');
        const bulkDeleteCount = document.getElementById('prmsBulkDeleteCount');
        const bulkDeleteUserList = document.getElementById('prmsBulkDeleteUserList');
        const bulkDeleteModal = document.getElementById('bulkDeleteUsersModal');

        function selectedUserCheckboxes() {
            return userCheckboxes.filter(function (checkbox) {
                return checkbox.checked;
            });
        }

        function syncBulkUserSelection() {
            const selected = selectedUserCheckboxes();
            const count = selected.length;

            if (bulkActions) {
                bulkActions.classList.toggle('d-none', count === 0);
                bulkActions.classList.toggle('d-flex', count > 0);
            }
            if (selectedCountEl) {
                selectedCountEl.textContent = String(count);
            }
            if (bulkDeleteBtn) {
                bulkDeleteBtn.disabled = count === 0;
            }
            if (selectAllUsers && userCheckboxes.length > 0) {
                selectAllUsers.checked = count > 0 && count === userCheckboxes.length;
                selectAllUsers.indeterminate = count > 0 && count < userCheckboxes.length;
            }
        }

        userCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', syncBulkUserSelection);
        });

        if (selectAllUsers) {
            selectAllUsers.addEventListener('change', function () {
                userCheckboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAllUsers.checked;
                });
                syncBulkUserSelection();
            });
        }

        if (bulkDeleteModal && bulkDeleteForm) {
            bulkDeleteModal.addEventListener('show.bs.modal', function () {
                bulkDeleteForm.querySelectorAll('input[type="hidden"][name="user_ids[]"]').forEach(function (input) {
                    input.remove();
                });

                const selected = selectedUserCheckboxes();
                if (bulkDeleteCount) {
                    bulkDeleteCount.textContent = String(selected.length);
                }
                if (bulkDeleteUserList) {
                    bulkDeleteUserList.innerHTML = '';
                }

                selected.forEach(function (checkbox) {
                    const row = checkbox.closest('tr');
                    const nameCell = row ? row.querySelector('.fw-semibold') : null;
                    const label = nameCell ? nameCell.textContent.trim() : ('User #' + checkbox.value);

                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'user_ids[]';
                    hidden.value = checkbox.value;
                    bulkDeleteForm.appendChild(hidden);

                    if (bulkDeleteUserList) {
                        const item = document.createElement('div');
                        item.className = 'mb-1';
                        item.textContent = label;
                        bulkDeleteUserList.appendChild(item);
                    }
                });
            });
        }

        syncBulkUserSelection();
    });
</script>
@endpush
