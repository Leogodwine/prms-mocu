<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeleteUsersRequest;
use App\Http\Requests\BulkImportUsersRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use App\Support\AdminUserCreatedNotifier;
use App\Support\Audit;
use App\Support\PrmsEventNotifier;
use App\Support\SafeReport;
use App\Support\StaffProfileProvisioner;
use App\Support\StudentAcademicRecordSync;
use App\Support\StudentProfileProvisioner;
use App\Support\StudentWorkflowAssigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function bulkImport(BulkImportUsersRequest $request): RedirectResponse
    {
        $request->validated();

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = array_map(static fn ($col) => trim((string) $col), fgetcsv($handle) ?: []);

        if ($header === []) {
            fclose($handle);

            return back()->withErrors(['csv_file' => 'The CSV file is empty or has no header row.']);
        }

        $staffRoles = StaffProfileProvisioner::staffProfileRoles();
        $imported = 0;
        $skipped = 0;
        $userInAppNotified = 0;
        $userEmailNotified = 0;
        $userNotifyFailed = 0;
        $rowNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($data, static fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }

            /** @var array<string, string>|false $row */
            $row = array_combine($header, $data);
            if ($row === false) {
                $skipped++;

                continue;
            }

            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $loginId = trim((string) ($row['login_id'] ?? ''));

            if ($email === '' || $name === '' || $loginId === '') {
                $skipped++;

                continue;
            }

            if (User::query()->where('email', $email)->exists() || User::query()->where('login_id', $loginId)->exists()) {
                $skipped++;

                continue;
            }

            $role = $this->normalizeBulkImportRole(trim((string) ($row['role'] ?? '')));
            if (! in_array($role, StoreAdminUserRequest::BULK_IMPORT_ROLES, true)) {
                $skipped++;

                continue;
            }

            $isStudent = StoreAdminUserRequest::isStudentFormRole($role);
            $department = trim((string) ($row['department'] ?? '')) ?: null;
            $programme = trim((string) ($row['programme'] ?? '')) ?: null;
            $yearOfStudyRaw = trim((string) ($row['year_of_study'] ?? ''));
            $yearOfStudy = $yearOfStudyRaw !== '' ? (int) $yearOfStudyRaw : null;

            if ($isStudent) {
                if ($department === null || $programme === null || $yearOfStudy === null || $yearOfStudy < 1 || $yearOfStudy > StoreAdminUserRequest::MAX_YEAR_OF_STUDY) {
                    $skipped++;

                    continue;
                }
            } elseif ($department === null) {
                $skipped++;

                continue;
            }

            $tempPassword = Str::password(12);

            try {
                $user = DB::transaction(function () use (
                    $request,
                    $name,
                    $email,
                    $loginId,
                    $role,
                    $isStudent,
                    $department,
                    $programme,
                    $yearOfStudy,
                    $tempPassword,
                    $staffRoles
                ): User {
                    $user = User::query()->create([
                        'name' => $name,
                        'email' => $email,
                        'login_id' => $loginId,
                        'role' => $isStudent ? StoreAdminUserRequest::FORM_STUDENT_ROLE : $role,
                        'department' => $department,
                        'programme' => $isStudent ? $programme : null,
                        'year_of_study' => $isStudent ? $yearOfStudy : null,
                        'enrollment_status' => 'active',
                        'account_status' => 'active',
                        'password' => $tempPassword,
                        'must_change_password' => true,
                        'notify_email_new_submission' => true,
                        'notify_email_submission_reviewed' => true,
                    ]);

                    if ($isStudent) {
                        if (Schema::hasColumn('users', 'registration_number')) {
                            $user->registration_number = $loginId;
                        }
                        if (Schema::hasColumn('users', 'staff_id')) {
                            $user->staff_id = null;
                        }
                        $user->save();

                        StudentProfileProvisioner::ensureStudentProfile($user);
                        $user->refresh();
                        StudentAcademicRecordSync::syncLinkedStudentRowFromUser($user);
                        StudentWorkflowAssigner::syncForUser($user->fresh());
                    } elseif (in_array($role, $staffRoles, true)) {
                        if (Schema::hasColumn('users', 'staff_id')) {
                            $user->staff_id = $loginId;
                        }
                        if (Schema::hasColumn('users', 'registration_number')) {
                            $user->registration_number = null;
                        }
                        $user->save();

                        StaffProfileProvisioner::syncFromUser($user);
                    }

                    Audit::log($request, 'admin.user_created', 'User', (string) $user->id, null, [
                        'role' => $user->role,
                        'login_id' => $user->login_id,
                        'source' => 'bulk_import',
                    ]);

                    return $user->fresh();
                });

                $notifyResult = AdminUserCreatedNotifier::notify(
                    $user,
                    $loginId,
                    $tempPassword,
                    $request->user(),
                    AdminUserCreatedNotifier::SOURCE_BULK_IMPORT,
                );

                if ($notifyResult['user_in_app']) {
                    $userInAppNotified++;
                }
                if ($notifyResult['user_email']) {
                    $userEmailNotified++;
                }
                if (! $notifyResult['user_in_app'] && ! $notifyResult['user_email']) {
                    $userNotifyFailed++;
                }

                $imported++;
            } catch (\Throwable $e) {
                SafeReport::call($e);

                fclose($handle);

                return back()->withErrors([
                    'csv_file' => "Import stopped at CSV row {$rowNumber}: ".$e->getMessage(),
                ]);
            }
        }

        fclose($handle);

        if ($imported === 0 && $skipped === 0) {
            return back()->withErrors(['csv_file' => 'No valid user rows were found in the CSV file.']);
        }

        $message = "Successfully imported {$imported} user(s).";
        if ($imported > 0) {
            $message .= " Each imported user received individual temporary credentials by in-app notification ({$userInAppNotified} succeeded)"
                ." and email ({$userEmailNotified} succeeded).";
            $message .= ' Every active administrator received individual in-app alerts in the notification panel.';
        }
        if ($userNotifyFailed > 0) {
            $message .= " {$userNotifyFailed} user(s) could not be notified — check mail configuration or share credentials manually.";
        }
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped (duplicate account, invalid role, or missing required fields).";
        }

        return back()->with('status', $message);
    }

    private function normalizeBulkImportRole(string $role): string
    {
        $role = strtolower(str_replace([' ', '-'], '_', $role));

        return match ($role) {
            'student', 'normal_student', 'research_student', 'project_student' => StoreAdminUserRequest::FORM_STUDENT_ROLE,
            'head_of_dept', 'head_of_department', 'hod' => 'hod',
            default => $role,
        };
    }
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->boolean('reset_filters')) {
            return redirect()->route('admin.users.index');
        }

        $defaults = [
            'q' => '',
            'role' => '',
            'status' => '',
            'must_change_password' => '',
        ];

        $filters = $this->sanitizeAdminUserFilters([
            'q' => $request->query('q', ''),
            'role' => $request->query('role', ''),
            'status' => $request->query('status', ''),
            'must_change_password' => $request->query('must_change_password', ''),
        ]);

        $search = $filters['q'];
        $roleFilter = $filters['role'];
        $statusFilter = $filters['status'];
        $pendingPasswordFilter = (bool) $filters['must_change_password'];

        $allowedRoles = ['admin', 'hod', 'coordinator', 'supervisor', 'project_student', 'research_student', 'normal_student'];
        $allowedStatuses = ['active', 'inactive', 'suspended', 'locked'];

        $usersQuery = User::query();

        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('login_id', 'like', "%{$search}%");

                if (Schema::hasColumn('users', 'registration_number')) {
                    $q->orWhere('registration_number', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('users', 'staff_id')) {
                    $q->orWhere('staff_id', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('users', 'department')) {
                    $q->orWhere('department', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('users', 'programme')) {
                    $q->orWhere('programme', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('users', 'phone_number')) {
                    $q->orWhere('phone_number', 'like', "%{$search}%");
                }

                if (Schema::hasTable('students')) {
                    $q->orWhereHas('studentProfile', function ($studentQuery) use ($search) {
                        $studentQuery->where('registration_number', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")
                            ->orWhere('university_email', 'like', "%{$search}%");
                    });
                }
            });
        }

        if (in_array($roleFilter, $allowedRoles, true)) {
            $usersQuery->where('role', $roleFilter);
        }

        if ($pendingPasswordFilter) {
            $usersQuery->where('must_change_password', true);
        }

        if ($statusFilter === 'suspended_locked') {
            $usersQuery->whereIn('account_status', ['suspended', 'locked']);
        } elseif (in_array($statusFilter, $allowedStatuses, true)) {
            $usersQuery->where('account_status', $statusFilter);
        }

        if (Schema::hasTable('students')) {
            $usersQuery->with('studentProfile');
        }

        $users = $usersQuery->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'         => User::query()->count(),
            'active'        => User::query()->where('account_status', 'active')->count(),
            'suspended'     => User::query()->whereIn('account_status', ['suspended', 'locked'])->count(),
            'pending_reset' => User::query()->where('must_change_password', true)->count(),
        ];

        $hasActiveFilters = $filters['q'] !== ''
            || $filters['role'] !== ''
            || $filters['status'] !== ''
            || $pendingPasswordFilter;

        return view('admin.users', [
            'users' => $users,
            'roles' => $allowedRoles,
            'formRoles' => StoreAdminUserRequest::FORM_ROLES,
            'statuses' => $allowedStatuses,
            'stats' => $stats,
            'filters' => $filters,
            'hasActiveFilters' => $hasActiveFilters,
            'filterResetUrl' => route('admin.users.index', ['reset_filters' => 1]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function sanitizeAdminUserFilters(array $filters): array
    {
        $allowedRoles = ['admin', 'hod', 'coordinator', 'supervisor', 'project_student', 'research_student', 'normal_student'];
        $allowedStatuses = ['active', 'inactive', 'suspended', 'locked', 'suspended_locked'];

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'role' => in_array($filters['role'] ?? '', $allowedRoles, true) ? $filters['role'] : '',
            'status' => in_array($filters['status'] ?? '', $allowedStatuses, true) ? $filters['status'] : '',
            'must_change_password' => filter_var($filters['must_change_password'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '',
        ];
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) optional($request->user())->id === (int) $user->id) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        $snapshot = [
            'name' => $user->name,
            'email' => $user->email,
            'login_id' => $user->login_id,
            'role' => $user->role,
        ];

        $user->delete();

        try {
            Audit::log($request, 'admin.user_deleted', 'User', (string) $user->id, $snapshot, null);
        } catch (\Throwable $e) {
            SafeReport::call($e);
        }

        try {
            PrmsEventNotifier::notifyAccountDeleted($snapshot, $request->user());
        } catch (\Throwable $e) {
            SafeReport::call($e);
        }

        return back()->with('status', "User “{$snapshot['name']}” has been deleted.");
    }

    public function bulkDestroy(BulkDeleteUsersRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $deleted = 0;
            $skippedMissing = 0;
            $failed = 0;
            $deletedNames = [];

            foreach ($validated['user_ids'] as $userId) {
                $userId = (int) $userId;

                $user = User::query()->find($userId);
                if ($user === null) {
                    $skippedMissing++;

                    continue;
                }

                $snapshot = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'login_id' => $user->login_id,
                    'role' => $user->role,
                ];

                try {
                    DB::transaction(function () use ($user): void {
                        $user->delete();
                    });

                    try {
                        Audit::log($request, 'admin.user_deleted', 'User', (string) $userId, $snapshot, [
                            'source' => 'bulk_delete',
                        ]);
                    } catch (\Throwable $e) {
                        SafeReport::call($e);
                    }

                    $deletedNames[] = (string) $snapshot['name'];
                    $deleted++;
                } catch (\Throwable $e) {
                    SafeReport::call($e);
                    $failed++;
                }
            }

            if ($deleted > 0) {
                try {
                    PrmsEventNotifier::notifyBulkAccountsDeleted($deletedNames, $request->user());
                } catch (\Throwable $e) {
                    SafeReport::call($e);
                }
            }

            if ($deleted === 0) {
                $message = $failed > 0
                    ? 'No users were deleted. One or more accounts could not be removed because they are linked to other records.'
                    : 'No users were deleted.';

                return redirect()->route('admin.users.index', $request->only(['q', 'role', 'status', 'must_change_password']))
                    ->withErrors(['delete' => $message]);
            }

            $message = "Deleted {$deleted} user(s).";
            if ($skippedMissing > 0) {
                $message .= " {$skippedMissing} selected user(s) were already removed.";
            }
            if ($failed > 0) {
                $message .= " {$failed} account(s) could not be deleted.";
            }

            return redirect()->route('admin.users.index', $request->only(['q', 'role', 'status', 'must_change_password']))
                ->with('status', $message);
        } catch (\Throwable $e) {
            SafeReport::call($e);

            return redirect()->route('admin.users.index', $request->only(['q', 'role', 'status', 'must_change_password']))
                ->withErrors(['delete' => 'Bulk delete failed. Check that migrations are up to date and review storage/logs/laravel.log for details.']);
        }
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        try {
            return $this->createAdminUser($request);
        } catch (\Throwable $e) {
            SafeReport::call($e);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Account creation failed. Check that migrations are up to date and review storage/logs/laravel.log for details.']);
        }
    }

    private function createAdminUser(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $formRole = (string) $validated['role'];
        $isStudent = StoreAdminUserRequest::isStudentFormRole($formRole);

        $tempPassword = Str::password(12);

        $yearOfStudy = $isStudent ? ($validated['year_of_study'] ?? null) : null;

        $user = DB::transaction(function () use ($request, $validated, $formRole, $isStudent, $yearOfStudy, $tempPassword) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'login_id' => $validated['login_id'],
                'role' => $isStudent ? StoreAdminUserRequest::FORM_STUDENT_ROLE : $formRole,
                'department' => $validated['department'] ?? null,
                'programme' => $isStudent ? ($validated['programme'] ?? null) : null,
                'year_of_study' => $yearOfStudy,
                'enrollment_status' => 'active',
                'account_status' => 'active',
                'password' => $tempPassword,
                'must_change_password' => true,
                'notify_email_new_submission' => true,
                'notify_email_submission_reviewed' => true,
            ]);

            if ($isStudent) {
                if (Schema::hasColumn('users', 'registration_number')) {
                    $user->registration_number = $validated['login_id'];
                }
                if (Schema::hasColumn('users', 'staff_id')) {
                    $user->staff_id = null;
                }
                $user->save();

                StudentProfileProvisioner::ensureStudentProfile($user);
                $user->refresh();
                StudentAcademicRecordSync::syncLinkedStudentRowFromUser($user);
                try {
                    StudentWorkflowAssigner::syncForUser($user->fresh());
                } catch (\Throwable $e) {
                    SafeReport::call($e);
                }
            } elseif (in_array($formRole, StaffProfileProvisioner::staffProfileRoles(), true)) {
                if (Schema::hasColumn('users', 'staff_id')) {
                    $user->staff_id = $validated['login_id'];
                }
                if (Schema::hasColumn('users', 'registration_number')) {
                    $user->registration_number = null;
                }
                $user->save();
                StaffProfileProvisioner::syncFromUser($user);
            } elseif ($formRole === 'admin') {
                if (Schema::hasColumn('users', 'staff_id')) {
                    $user->staff_id = $validated['login_id'];
                }
                if (Schema::hasColumn('users', 'registration_number')) {
                    $user->registration_number = null;
                }
                $user->save();
            }

            try {
                Audit::log($request, 'admin.user_created', 'User', (string) $user->id, null, [
                    'role' => $user->role,
                    'login_id' => $user->login_id,
                ]);
            } catch (\Throwable $e) {
                SafeReport::call($e);
            }

            return $user->fresh();
        });

        $statusMessage = 'User created. Sign-in details were sent to the new user and all administrators received in-app notifications with the username and temporary password.';

        $notifyResult = AdminUserCreatedNotifier::notify(
            $user,
            $validated['login_id'],
            $tempPassword,
            $request->user(),
        );

        if (! $notifyResult['user_in_app'] && ! $notifyResult['user_email']) {
            $statusMessage = 'User created, but notifications could not be sent. Check mail configuration, or share credentials manually: username '
                .$validated['login_id'].', temporary password '.$tempPassword.'.';
        } elseif (! $notifyResult['user_email']) {
            $statusMessage = 'User created. In-app credentials were delivered, but email could not be sent. Check mail configuration, or share credentials manually if needed.';
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', $statusMessage);
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $isSelf = (int) $request->user()->id === (int) $user->id;

        if ($isSelf) {
            return back()->withErrors([
                'error' => 'You cannot edit your own account here. Ask another administrator, or update your profile from My profile.',
            ]);
        }

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'login_id' => $user->login_id,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'account_status' => $user->account_status,
            'department' => $user->department,
            'programme' => $user->programme,
            'year_of_study' => $user->year_of_study,
        ];

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->login_id = $validated['login_id'];
        $user->phone_number = $validated['phone_number'] ?? null;
        $user->account_status = $validated['account_status'];

        $formRole = (string) $validated['role'];
        $isStudent = StoreAdminUserRequest::isStudentFormRole($formRole);
        $staffRoles = StaffProfileProvisioner::staffProfileRoles();

        if ($isStudent) {
            $user->role = StoreAdminUserRequest::FORM_STUDENT_ROLE;
        } else {
            $user->role = $formRole;
        }

        if (Schema::hasColumn('users', 'registration_number') || Schema::hasColumn('users', 'staff_id')) {
            if ($isStudent) {
                if (Schema::hasColumn('users', 'registration_number')) {
                    $user->registration_number = $validated['login_id'];
                }
                if (Schema::hasColumn('users', 'staff_id')) {
                    $user->staff_id = null;
                }
            } elseif (in_array($formRole, $staffRoles, true) || $formRole === 'admin') {
                if (Schema::hasColumn('users', 'staff_id')) {
                    $user->staff_id = $validated['login_id'];
                }
                if (Schema::hasColumn('users', 'registration_number')) {
                    $user->registration_number = null;
                }
            } else {
                if (Schema::hasColumn('users', 'registration_number')) {
                    $user->registration_number = null;
                }
                if (Schema::hasColumn('users', 'staff_id')) {
                    $user->staff_id = null;
                }
            }
        }

        if ($request->user()->role === 'admin') {
            if (Schema::hasColumn('users', 'department')) {
                $user->department = $validated['department'] ?? null;
            }
            if (Schema::hasColumn('users', 'programme')) {
                $user->programme = $isStudent ? ($validated['programme'] ?? null) : null;
            }
            if (Schema::hasColumn('users', 'year_of_study')) {
                $user->year_of_study = $isStudent ? ($validated['year_of_study'] ?? null) : null;
            }
        }

        $user->save();

        if ($request->user()->role === 'admin') {
            StaffProfileProvisioner::syncFromUser($user->fresh());
        }

        if ($request->user()->role === 'admin' && $isStudent) {
            StudentProfileProvisioner::ensureStudentProfile($user);
            $user->refresh();
            StudentAcademicRecordSync::syncLinkedStudentRowFromUser($user);
            StudentWorkflowAssigner::syncForUser($user->fresh());
        }

        $new = [
            'name' => $user->name,
            'email' => $user->email,
            'login_id' => $user->login_id,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'account_status' => $user->account_status,
            'department' => $user->department,
            'programme' => $user->programme,
            'year_of_study' => $user->year_of_study,
        ];

        Audit::log($request, 'admin.user_updated', 'User', (string) $user->id, $old, $new);

        PrmsEventNotifier::notifyAccountUpdated($user, $request->user());

        return back()->with('status', 'User updated successfully.');
    }
}

