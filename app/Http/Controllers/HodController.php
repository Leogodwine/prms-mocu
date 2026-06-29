<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProjectGroup;
use App\Models\User;
use App\Support\Audit;
use App\Support\PrmsEventNotifier;
use App\Support\StudentAcademicRecordSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HodController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hodProfile = $user->staffProfile;
        $deptId = $hodProfile?->department_id;

        $supervisors = \App\Models\Staff::query()
            ->with(['user', 'supervisorAssignments'])
            ->whereHas('user', fn ($q) => $q->where('role', 'supervisor'))
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->get();

        $groups = ProjectGroup::query()
            ->with(['members', 'supervisorAssignment.supervisor'])
            ->when($deptId, function ($q) use ($deptId) {
                $q->whereHas('members.studentProfile.programme', fn ($pq) => $pq->where('department_id', $deptId));
            })
            ->latest()
            ->get();

        $submissionStats = \App\Models\ProjectSubmission::query()
            ->when($deptId, function ($q) use ($deptId) {
                $q->whereHas('student.studentProfile.programme', fn ($pq) => $pq->where('department_id', $deptId));
            })
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('hod.index', [
            'supervisors' => $supervisors,
            'groups' => $groups,
            'submissionStats' => $submissionStats,
            'deptName' => $hodProfile?->department?->department_name ?? 'Department',
        ]);
    }

    public function students(Request $request): View
    {
        $hod = $request->user();
        $hodProfile = $hod->staffProfile;
        $deptId = $hodProfile?->department_id;
        $department = $deptId ? Department::query()->find($deptId) : null;

        $students = User::query()
            ->with(['studentProfile.programme'])
            ->whereIn('role', ['project_student', 'research_student', 'normal_student', 'student'])
            ->when($department, function ($q) use ($department, $deptId) {
                $name = mb_strtolower(trim($department->department_name));
                $code = mb_strtolower(trim((string) $department->department_code));

                $q->where(function ($inner) use ($deptId, $name, $code) {
                    $inner->whereHas('studentProfile.programme', fn ($pq) => $pq->where('department_id', $deptId));

                    $inner->orWhereRaw('LOWER(TRIM(COALESCE(department, ""))) = ?', [$name]);
                    if ($code !== '') {
                        $inner->orWhereRaw('LOWER(TRIM(COALESCE(department, ""))) = ?', [$code]);
                    }
                });
            }, fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('hod.students', [
            'students' => $students,
            'department' => $department,
            'deptName' => $department?->department_name ?? 'Department',
        ]);
    }

    public function updateStudent(Request $request, User $user): RedirectResponse
    {
        if (! $this->hodMayManageStudent($request->user(), $user)) {
            abort(403, 'You cannot update this student.');
        }

        $validated = $request->validate([
            'edit_user_id' => ['nullable', 'integer'],
            'department' => ['required', 'string', 'max:120'],
            'programme' => ['nullable', 'string', 'max:120'],
            'year_of_study' => ['nullable', 'integer', 'between:1,'.\App\Http\Requests\StoreAdminUserRequest::MAX_YEAR_OF_STUDY],
        ]);

        $dept = $request->user()->staffProfile?->department;
        if ($dept === null) {
            throw ValidationException::withMessages(['department' => 'Your account is not linked to a department.']);
        }

        $officialName = trim($dept->department_name);
        $officialCode = trim((string) $dept->department_code);
        $submittedDept = trim($validated['department']);
        $allowed = array_filter([$officialName, $officialCode]);
        if (! in_array($submittedDept, $allowed, true)) {
            throw ValidationException::withMessages(['department' => 'Department must match your assigned department.']);
        }

        $old = [
            'department' => $user->department,
            'programme' => $user->programme,
            'year_of_study' => $user->year_of_study,
        ];

        if (Schema::hasColumn('users', 'department')) {
            $user->department = $submittedDept;
        }
        if (Schema::hasColumn('users', 'programme')) {
            $user->programme = $validated['programme'] ?? null;
        }
        if (Schema::hasColumn('users', 'year_of_study')) {
            $user->year_of_study = $validated['year_of_study'] ?? null;
        }
        $user->save();

        StudentAcademicRecordSync::syncLinkedStudentRowFromUser($user);

        Audit::log($request, 'hod.student_academic_updated', 'User', (string) $user->id, $old, [
            'department' => $user->department,
            'programme' => $user->programme,
            'year_of_study' => $user->year_of_study,
        ]);

        PrmsEventNotifier::notifyStudentAcademicUpdated($user, $request->user());

        return back()->with('status', 'Student academic record updated.');
    }

    private function hodMayManageStudent(User $hod, User $student): bool
    {
        if (! $student->isStudentUser()) {
            return false;
        }

        $deptId = $hod->staffProfile?->department_id;
        if (! $deptId) {
            return false;
        }

        $dept = Department::query()->find($deptId);
        if (! $dept) {
            return false;
        }

        $name = mb_strtolower(trim($dept->department_name));
        $code = mb_strtolower(trim((string) $dept->department_code));
        $uDept = mb_strtolower(trim((string) $student->department));

        if ($uDept === $name || ($code !== '' && $uDept === $code)) {
            return true;
        }

        $progDeptId = $student->studentProfile?->programme?->department_id;

        return (int) $progDeptId === (int) $deptId;
    }
}

