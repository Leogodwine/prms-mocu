@extends('layouts.app')

@section('title', 'User management')

@section('content')

@php
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

<x-prms-greeting-banner subtitle="Provision accounts, assign roles, monitor access, and bulk-import users from the Student Information System.">
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="fas fa-user-plus me-2" aria-hidden="true"></i>
        Create account
    </button>
    <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
        <i class="fas fa-file-csv me-1" aria-hidden="true"></i>
        Bulk import
    </button>
    <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>
        Dashboard
    </a>
</x-prms-greeting-banner>

{{-- User table and modals --}}
{{-- ────────── Stats (clickable → filtered user list) ────────── --}}
@php
    $fQ = $filters['q'] ?? '';
    $fRole = $filters['role'] ?? '';
    $fStatus = $filters['status'] ?? '';
    $fPendingPw = ! empty($filters['must_change_password']);
    $preserveGet = [];
    if ($fQ !== '') {
        $preserveGet['q'] = $fQ;
    }
    if ($fRole !== '') {
        $preserveGet['role'] = $fRole;
    }
    $isCohortTotal = ($fQ === '' && $fRole === '' && $fStatus === '' && ! $fPendingPw);
    $isCohortActive = ($fStatus === 'active' && ! $fPendingPw);
    $isCohortSuspended = ($fStatus === 'suspended_locked');
    $isCohortPending = $fPendingPw;
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
        <a href="{{ route('admin.users.index', ['apply_status' => 'active']) }}"
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
        <a href="{{ route('admin.users.index', ['apply_status' => 'suspended_locked']) }}"
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
        <a href="{{ route('admin.users.index', ['apply_pending' => 1]) }}"
           class="prms-stat-card prms-stat-card--clickable text-reset text-decoration-none d-block h-100 @if ($isCohortPending) prms-stat-card--selected @endif"
           style="--prms-primary: var(--prms-color-info-500); --prms-stat-ring: var(--prms-color-info-500);"
           @if ($isCohortPending) aria-current="page" @endif>
            <div class="stat-label">Awaiting password reset</div>
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
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <h3 class="card-title h6 fw-bold mb-0 d-flex align-items-center">
                    <i class="fas fa-users-cog text-primary me-2" aria-hidden="true"></i>
                    System users
                </h3>
            </div>
            <div class="col-md-7">
                <form method="POST" action="{{ route('admin.users.index') }}" class="d-flex flex-wrap gap-2 justify-content-md-end">
                    @csrf
                    <input type="hidden" name="_filter_action" value="apply">
                    @if (! empty($filters['must_change_password']))
                        <input type="hidden" name="must_change_password" value="1">
                    @endif
                    <div class="position-relative flex-grow-1" style="min-width: 180px;">
                        <i class="fas fa-search position-absolute text-muted" aria-hidden="true"
                           style="left: 0.85rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                        <input type="text" name="q" value="{{ $filters['q'] }}"
                               class="form-control form-control-sm ps-4"
                               placeholder="Search name, email, or reg. no…"
                               aria-label="Search users">
                    </div>
                    <select name="role" class="form-select form-select-sm" style="max-width: 160px;" aria-label="Filter by role">
                        <option value="">All roles</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r }}" @selected($filters['role'] === $r)>
                                {{ $roleLabels[$r]['label'] ?? \Illuminate\Support\Str::title(str_replace('_',' ',$r)) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="max-width: 180px;" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="suspended_locked" @selected(($filters['status'] ?? '') === 'suspended_locked')>Suspended or locked</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected($filters['status'] === $s)>
                                {{ $statusLabels[$s]['label'] ?? \Illuminate\Support\Str::title($s) }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Reg. no / Staff ID</th>
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
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle fw-bold flex-shrink-0"
                                          style="width: 38px; height: 38px;">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-strong">{{ $user->name }}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 240px;">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code class="bg-surface-soft px-2 py-1 rounded small text-strong">{{ $user->displayIdentifier() }}</code>
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
                            <td colspan="5" class="text-center py-5">
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

    @if ($users->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} users
            </small>
            {{ $users->links() }}
        </div>
    @endif
</div>

@endsection

@push('modals')
@php
    use App\Http\Requests\StoreAdminUserRequest;

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
                                <div class="col-md-6">
                                    <label for="create_registration_number" class="form-label">Registration number <span class="text-danger">*</span></label>
                                    <input id="create_registration_number" type="text" name="login_id"
                                           value="{{ $createFormFailed && $createRoleIsStudent ? old('login_id') : '' }}"
                                           class="form-control prms-student-login-id {{ $createFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           placeholder="MoCU/REG/1134/24"
                                           @if(! $createRoleIsStudent) disabled @endif>
                                    @if ($createFormFailed && $errors->has('login_id'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('login_id') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label for="create_department" class="form-label">Department <span class="text-danger">*</span></label>
                                    <input id="create_department" type="text" name="department"
                                           value="{{ $createFormFailed ? old('department') : '' }}"
                                           class="form-control {{ $createFormFailed && $errors->has('department') ? 'is-invalid' : '' }}"
                                           placeholder="e.g. Computing &amp; ICT"
                                           @if(! $createRoleIsStudent) disabled @endif>
                                    @if ($createFormFailed && $errors->has('department'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('department') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label for="create_programme" class="form-label">Programme <span class="text-danger">*</span></label>
                                    <input id="create_programme" type="text" name="programme"
                                           value="{{ $createFormFailed ? old('programme') : '' }}"
                                           class="form-control {{ $createFormFailed && $errors->has('programme') ? 'is-invalid' : '' }}"
                                           placeholder="e.g. BBICT">
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
                                        @for ($y = 1; $y <= 8; $y++)
                                            <option value="{{ $y }}" @selected($createYear === $y)>Year {{ $y }}</option>
                                        @endfor
                                    </select>
                                    @if ($createFormFailed && $errors->has('year_of_study'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('year_of_study') }}</div>
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
                                <div class="col-md-6">
                                    <label for="create_staff_id" class="form-label">Staff ID <span class="text-danger">*</span></label>
                                    <input id="create_staff_id" type="text" name="login_id"
                                           value="{{ $createFormFailed && $createRoleIsStaff ? old('login_id') : '' }}"
                                           class="form-control prms-staff-login-id {{ $createFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           placeholder="MoCU/STAFF/2024/001"
                                           @if(! $createRoleIsStaff) disabled @endif>
                                    @if ($createFormFailed && $errors->has('login_id'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('login_id') }}</div>
                                    @endif
                                </div>

                                <div class="col-md-6 prms-staff-department-field" @if($createFormRole === 'admin') hidden @endif>
                                    <label for="create_staff_department" class="form-label">Department <span class="text-danger">*</span></label>
                                    <input id="create_staff_department" type="text" name="department"
                                           value="{{ $createFormFailed ? old('department') : '' }}"
                                           class="form-control prms-staff-department {{ $createFormFailed && $errors->has('department') ? 'is-invalid' : '' }}"
                                           placeholder="e.g. Computing &amp; ICT"
                                           @if(! $createRoleIsStaff || $createFormRole === 'admin') disabled @endif>
                                    @if ($createFormFailed && $errors->has('department'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('department') }}</div>
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
                            <div class="col-md-6">
                                <label for="edit_phone_{{ $user->id }}" class="form-label">Phone number</label>
                                <input type="text" id="edit_phone_{{ $user->id }}" name="phone_number"
                                       value="{{ $editPhone }}"
                                       class="form-control {{ $editFormFailed && $errors->has('phone_number') ? 'is-invalid' : '' }}"
                                       maxlength="30" placeholder="Optional">
                                @if ($editFormFailed && $errors->has('phone_number'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('phone_number') }}</div>
                                @endif
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
                                <div class="col-md-6">
                                    <label for="edit_registration_number_{{ $user->id }}" class="form-label">Registration number <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_registration_number_{{ $user->id }}" name="login_id"
                                           value="{{ $editLoginId }}"
                                           class="form-control prms-student-login-id {{ $editFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           maxlength="80" placeholder="MoCU/REG/1134/24"
                                           @if(! $editRoleIsStudent) disabled @endif>
                                    @if ($editFormFailed && $errors->has('login_id'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('login_id') }}</div>
                                    @endif
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
                                        @for ($y = 1; $y <= 8; $y++)
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
                                <div class="col-md-6">
                                    <label for="edit_staff_id_{{ $user->id }}" class="form-label">Staff ID <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_staff_id_{{ $user->id }}" name="login_id"
                                           value="{{ $editLoginId }}"
                                           class="form-control prms-staff-login-id {{ $editFormFailed && $errors->has('login_id') ? 'is-invalid' : '' }}"
                                           maxlength="80" placeholder="MoCU/STAFF/2024/001"
                                           @if(! $editRoleIsStaff) disabled @endif>
                                    @if ($editFormFailed && $errors->has('login_id'))
                                        <div class="invalid-feedback d-block">{{ $errors->first('login_id') }}</div>
                                    @endif
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-surface-soft">
                <h2 class="modal-title h5 fw-bold text-strong" id="bulkImportModalTitle">
                    <i class="fas fa-file-csv text-primary me-2" aria-hidden="true"></i>
                    Bulk user import
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.bulk-import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info d-flex gap-2 align-items-start mb-0">
                        <i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
                        <div class="small">
                            <p class="mb-1 fw-semibold">Student</p>
                            <code class="d-block mb-3">name, email, login_id, role, department, programme, year_of_study</code>

                            <p class="mb-1 fw-semibold">Staff</p>
                            <code class="d-block mb-0">name, email, login_id, role, department</code>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV file</label>
                        <input id="csv_file" type="file" name="csv_file"
                               class="form-control @error('csv_file') is-invalid @enderror"
                               accept=".csv,text/csv" required>
                        @error('csv_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Maximum size: 2 MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1" aria-hidden="true"></i> Start import
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
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
                } else {
                    studentLogin.removeAttribute('name');
                }
                setFieldEnabled(studentLogin, showStudent);
            }

            if (staffLogin) {
                if (showStaff) {
                    staffLogin.setAttribute('name', 'login_id');
                } else {
                    staffLogin.removeAttribute('name');
                }
                setFieldEnabled(staffLogin, showStaff);
            }

            if (studentDepartment) {
                if (showStudent) {
                    studentDepartment.setAttribute('name', 'department');
                } else {
                    studentDepartment.removeAttribute('name');
                }
                setFieldEnabled(studentDepartment, showStudent);
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

            studentFields.forEach(function (field) {
                if (field === studentLogin || field === studentDepartment) {
                    return;
                }
                setFieldEnabled(field, showStudent);
            });

            staffFields.forEach(function (field) {
                if (field === staffLogin || field === staffDepartment) {
                    return;
                }
                setFieldEnabled(field, showStaff);
            });
        }

        const createForm = document.querySelector('#createUserModal form');
        if (createForm) {
            const createRole = createForm.querySelector('#create_role');
            if (createRole) {
                createRole.addEventListener('change', function () {
                    syncUserFormFields(createForm);
                });
            }
            createForm.addEventListener('submit', function () {
                syncUserFormFields(createForm);
            });
            syncUserFormFields(createForm);
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
    });
</script>
@endpush
