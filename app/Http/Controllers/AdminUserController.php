<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkImportUsersRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use App\Support\AdminUserCreatedNotifier;
use App\Support\Audit;
use App\Support\PrmsEventNotifier;
use App\Support\PrmsListFilters;
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

        $studentRoles = StoreAdminUserRequest::STUDENT_ROLES;
        $staffRoles = StaffProfileProvisioner::staffProfileRoles();
        $imported = 0;
        $skipped = 0;
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

            if (User::query()->where('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            $role = $this->normalizeBulkImportRole(trim((string) ($row['role'] ?? 'normal_student')));
            $department = trim((string) ($row['department'] ?? '')) ?: null;
            $programme = trim((string) ($row['programme'] ?? '')) ?: null;
            $yearOfStudy = in_array($role, $studentRoles, true) ? 1 : null;
            $tempPassword = Str::password(12);

            try {
                $user = DB::transaction(function () use (
                    $request,
                    $name,
                    $email,
                    $loginId,
                    $role,
                    $department,
                    $programme,
                    $yearOfStudy,
                    $tempPassword,
                    $studentRoles,
                    $staffRoles
                ): User {
                    $attributes = [
                        'name' => $name,
                        'email' => $email,
                        'login_id' => $loginId,
                        'role' => $role,
                        'department' => $department,
                        'programme' => $programme,
                        'year_of_study' => $yearOfStudy,
                        'enrollment_status' => 'active',
                        'account_status' => 'active',
                        'password' => $tempPassword,
                        'must_change_password' => true,
                        'notify_email_new_submission' => true,
                        'notify_email_submission_reviewed' => true,
                    ];

                    if (Schema::hasColumn('users', 'registration_number') && in_array($role, $studentRoles, true)) {
                        $attributes['registration_number'] = $loginId;
                    }

                    if (Schema::hasColumn('users', 'staff_id') && in_array($role, $staffRoles, true)) {
                        $attributes['staff_id'] = $loginId;
                    }

                    $user = User::query()->create($attributes);

                    if (in_array($role, $studentRoles, true)) {
                        StudentProfileProvisioner::ensureStudentProfile($user);
                    }

                    if (in_array($role, $staffRoles, true)) {
                        StaffProfileProvisioner::syncFromUser($user);
                    }

                    Audit::log($request, 'admin.user_created', 'User', (string) $user->id, null, [
                        'role' => $user->role,
                        'login_id' => $user->login_id,
                        'source' => 'bulk_import',
                    ]);

                    return $user;
                });

                AdminUserCreatedNotifier::notify($user, $loginId, $tempPassword, $request->user());

                $imported++;
            } catch (\Throwable $e) {
                report($e);

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
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped (duplicate email or missing required fields).";
        }

        return back()->with('status', $message);
    }

    private function normalizeBulkImportRole(string $role): string
    {
        $role = strtolower(str_replace([' ', '-'], '_', $role));

        return match ($role) {
            'student' => 'normal_student',
            default => $role,
        };
    }
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->boolean('apply_pending')) {
            session()->flash(PrmsListFilters::sessionKey('admin.users'), [
                'q' => '',
                'role' => '',
                'status' => '',
                'must_change_password' => '1',
            ]);

            return redirect()->route('admin.users.index');
        }

        $defaults = [
            'q' => '',
            'role' => '',
            'status' => '',
            'must_change_password' => '',
        ];

        if ($request->filled('apply_status')) {
            $status = (string) $request->query('apply_status');
            $allowedStatuses = ['active', 'inactive', 'suspended', 'locked', 'suspended_locked'];
            $current = PrmsListFilters::peek($request, 'admin.users', $defaults);
            session()->flash(PrmsListFilters::sessionKey('admin.users'), array_merge($current, [
                'status' => in_array($status, $allowedStatuses, true) ? $status : '',
                'must_change_password' => '',
            ]));

            return redirect()->route('admin.users.index');
        }

        if ($request->filled('apply_q')) {
            $current = PrmsListFilters::peek($request, 'admin.users', $defaults);
            session()->flash(PrmsListFilters::sessionKey('admin.users'), array_merge($current, [
                'q' => trim((string) $request->query('apply_q')),
            ]));

            return redirect()->route('admin.users.index');
        }

        $resolved = PrmsListFilters::resolve(
            $request,
            'admin.users',
            $defaults,
            'admin.users.index',
            [],
            fn (array $filters) => $this->sanitizeAdminUserFilters($filters)
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $filters = $resolved['filters'];
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

        $users = $usersQuery->latest()->paginate(20);

        $stats = [
            'total'         => User::query()->count(),
            'active'        => User::query()->where('account_status', 'active')->count(),
            'suspended'     => User::query()->whereIn('account_status', ['suspended', 'locked'])->count(),
            'pending_reset' => User::query()->where('must_change_password', true)->count(),
        ];

        return view('admin.users', [
            'users' => $users,
            'roles' => $allowedRoles,
            'statuses' => $allowedStatuses,
            'stats' => $stats,
            'filters' => $filters,
            'filterResetUrl' => PrmsListFilters::resetUrl('admin.users.index'),
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

        Audit::log($request, 'admin.user_deleted', 'User', (string) $user->id, $snapshot, null);

        PrmsEventNotifier::notifyAccountDeleted($snapshot, $request->user());

        return back()->with('status', "User “{$snapshot['name']}” has been deleted.");
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tempPassword = Str::password(12);

        $studentRoles = ['project_student', 'research_student', 'normal_student'];
        $yearOfStudy = in_array($validated['role'], $studentRoles, true)
            ? ($validated['year_of_study'] ?? null)
            : null;

        $user = DB::transaction(function () use ($request, $validated, $studentRoles, $yearOfStudy, $tempPassword) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'login_id' => $validated['login_id'],
                'role' => $validated['role'],
                'department' => $validated['department'] ?? null,
                'programme' => $validated['programme'] ?? null,
                'year_of_study' => $yearOfStudy,
                'enrollment_status' => 'active',
                'account_status' => 'active',
                'password' => $tempPassword,
                'must_change_password' => true,
                'notify_email_new_submission' => true,
                'notify_email_submission_reviewed' => true,
            ]);

            if (in_array($validated['role'], $studentRoles, true)) {
                StudentProfileProvisioner::ensureStudentProfile($user);
            }

            if (in_array($validated['role'], StaffProfileProvisioner::staffProfileRoles(), true)) {
                StaffProfileProvisioner::syncFromUser($user);
            }

            Audit::log($request, 'admin.user_created', 'User', (string) $user->id, null, [
                'role' => $user->role,
                'login_id' => $user->login_id,
            ]);

            return $user;
        });

        $statusMessage = 'User created. Sign-in details were sent to the new user and all administrators received in-app notifications with the username and temporary password.';

        try {
            AdminUserCreatedNotifier::notify($user, $validated['login_id'], $tempPassword, $request->user());
        } catch (\Throwable $e) {
            report($e);
            $statusMessage = 'User created, but notifications could not be sent. Check mail configuration, or share credentials manually: username '
                .$validated['login_id'].', temporary password '.$tempPassword.'.';
        }

        return back()->with('status', $statusMessage);
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
        $user->role = $validated['role'];
        $user->account_status = $validated['account_status'];

        $studentRoles = UpdateAdminUserRequest::STUDENT_ROLES;
        $staffRoles = StaffProfileProvisioner::staffProfileRoles();

        if (Schema::hasColumn('users', 'registration_number') || Schema::hasColumn('users', 'staff_id')) {
            if (in_array($validated['role'], $studentRoles, true)) {
                if (Schema::hasColumn('users', 'registration_number')) {
                    $user->registration_number = $validated['login_id'];
                }
                if (Schema::hasColumn('users', 'staff_id')) {
                    $user->staff_id = null;
                }
            } elseif (in_array($validated['role'], $staffRoles, true)) {
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
                $user->programme = $validated['programme'] ?? null;
            }
            if (Schema::hasColumn('users', 'year_of_study')) {
                $user->year_of_study = in_array($validated['role'], $studentRoles, true)
                    ? ($validated['year_of_study'] ?? null)
                    : null;
            }
        }

        $user->save();

        if ($request->user()->role === 'admin') {
            StaffProfileProvisioner::syncFromUser($user->fresh());
        }

        if ($request->user()->role === 'admin' && in_array($validated['role'], $studentRoles, true)) {
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

