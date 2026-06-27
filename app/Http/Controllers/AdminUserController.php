<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkImportUsersRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\AdminNewUserCredentialsNotification;
use App\Support\Audit;
use App\Support\PrmsListFilters;
use App\Support\StaffProfileProvisioner;
use App\Support\StudentAcademicRecordSync;
use App\Support\StudentProfileProvisioner;
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
        $header = fgetcsv($handle); // name, email, login_id, role, department, programme

        $count = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($header, $data);
            
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'login_id' => $row['login_id'],
                    'role' => $row['role'] ?? 'student',
                    'department' => $row['department'] ?? null,
                    'programme' => $row['programme'] ?? null,
                    'password' => \Illuminate\Support\Facades\Hash::make('P@ssw0rd123'),
                    'must_change_password' => true,
                    'enrollment_status' => 'active',
                    'account_status' => 'active',
                ]
            );

            // Create profile
            if ($user->role === 'student') {
                \App\Models\Student::firstOrCreate(['user_id' => $user->id], [
                    'registration_number' => $user->login_id,
                ]);
            } elseif (in_array($user->role, ['supervisor', 'coordinator', 'hod'])) {
                \App\Models\Staff::firstOrCreate(['user_id' => $user->id], [
                    'staff_number' => $user->login_id,
                ]);
            }

            $count++;
        }
        fclose($handle);

        return back()->with('status', "Successfully imported {$count} users.");
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

        $statusMessage = 'User created. Sign-in details were sent to their email and in-app notifications. You also have an in-app notification with the username and temporary password.';

        try {
            $user->notify(new AccountCreatedNotification($validated['login_id'], $tempPassword));
        } catch (\Throwable $e) {
            report($e);
            $statusMessage = 'User created, but the welcome notification could not be sent. Check mail configuration, or share credentials manually: username '
                .$validated['login_id'].', temporary password '.$tempPassword.'. You still have an in-app notification with these credentials.';
        }

        $admin = $request->user();
        if ($admin !== null) {
            try {
                $admin->notify(new AdminNewUserCredentialsNotification(
                    $validated['name'],
                    $validated['email'],
                    $validated['login_id'],
                    $tempPassword
                ));
            } catch (\Throwable $e) {
                report($e);
            }
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
            'role' => $user->role,
            'account_status' => $user->account_status,
            'department' => $user->department,
            'programme' => $user->programme,
            'year_of_study' => $user->year_of_study,
        ];

        $user->role = $validated['role'];
        $user->account_status = $validated['account_status'];

        if ($request->user()->role === 'admin' && $user->isStudentUser()) {
            if (Schema::hasColumn('users', 'department')) {
                $user->department = $validated['department'] ?? null;
            }
            if (Schema::hasColumn('users', 'programme')) {
                $user->programme = $validated['programme'] ?? null;
            }
            if (Schema::hasColumn('users', 'year_of_study')) {
                $user->year_of_study = $validated['year_of_study'] ?? null;
            }
        }

        $user->save();

        if ($request->user()->role === 'admin' && $user->isStudentUser()) {
            StudentProfileProvisioner::ensureStudentProfile($user);
            $user->refresh();
            StudentAcademicRecordSync::syncLinkedStudentRowFromUser($user);
        }

        if ($request->user()->role === 'admin') {
            StaffProfileProvisioner::syncFromUser($user->fresh());
        }

        $new = [
            'role' => $user->role,
            'account_status' => $user->account_status,
            'department' => $user->department,
            'programme' => $user->programme,
            'year_of_study' => $user->year_of_study,
        ];

        Audit::log($request, 'admin.user_updated', 'User', (string) $user->id, $old, $new);

        return back()->with('status', 'User updated successfully.');
    }
}

