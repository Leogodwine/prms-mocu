<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCoordinatorRubricRequest;
use App\Http\Requests\StoreStageDeadlineRequest;
use App\Models\ProjectSubmission;
use App\Models\Department;
use App\Models\ProjectGroup;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\EvaluationRubric;
use App\Models\ProjectStage;
use App\Models\SubmissionFeedback;
use App\Support\Audit;
use App\Support\PrmsEventNotifier;
use App\Support\PrmsListFilters;
use App\Support\PrmsTablePagination;
use App\Support\PresentationConsentForm;
use App\Support\RepositoryPublication;
use App\Support\StudentStageProgress;
use App\Support\StudentResearchEligibility;
use App\Support\GroupAutoFormer;
use App\Support\StaffProfileProvisioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    public function rubrics(): View
    {
        $rubrics = EvaluationRubric::latest()->get();
        return view('coordinator.rubrics.index', compact('rubrics'));
    }

    public function createRubric(): View
    {
        return view('coordinator.rubrics.create');
    }

    public function storeRubric(StoreCoordinatorRubricRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rubric = EvaluationRubric::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'total_marks' => $validated['total_marks'],
            'criteria' => $validated['criteria'],
            'is_active' => true,
            'is_system_default' => $request->boolean('apply_to_all_supervisors'),
        ]);

        if ($rubric->is_system_default) {
            EvaluationRubric::setSystemDefault($rubric);
        }

        return redirect()->route('coordinator.rubrics.index')->with('status', 'Grading scheme has been saved successfully.');
    }

    public function setDefaultRubric(Request $request, EvaluationRubric $rubric): RedirectResponse
    {
        EvaluationRubric::setSystemDefault($rubric);

        return redirect()
            ->route('coordinator.rubrics.index')
            ->with('status', '"'.$rubric->name.'" is now the grading scheme for all supervisors.');
    }
    public function index(Request $request): View|RedirectResponse
    {
        $coordinator = $request->user();
        StaffProfileProvisioner::syncFromUser($coordinator);

        $defaults = [
            'programme_id' => '',
            'year' => '',
        ];

        $resolved = PrmsListFilters::resolve(
            $request,
            'coordinator.index',
            $defaults,
            'coordinator.index',
            [],
            fn (array $filters) => $this->sanitizeCoordinatorFilters($filters)
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $filters = $resolved['filters'];
        $deptId = $this->coordinatorDepartmentId($coordinator);
        $coordinatorDepartment = $deptId !== null
            ? Department::query()->find($deptId)
            : null;
        $progId = $filters['programme_id'] !== '' ? $filters['programme_id'] : null;
        $year = (int) ($filters['year'] ?? 0);
        if ($year < 1) {
            $year = null;
        }

        StaffProfileProvisioner::syncAllSupervisorStaffProfiles();

        $eligibleStudentsAll = $this->eligibleStudentsForAssignment($deptId, $progId, $year);

        $eligibleStudents = $this->paginateEligibleStudents($eligibleStudentsAll, $request)
            ->withQueryString();

        $supervisors = \App\Models\Staff::query()
            ->with(['user', 'department'])
            ->where('is_active', true)
            ->when(
                $deptId !== null,
                fn ($q) => $q->where('department_id', $deptId),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->whereHas('user', fn ($q) => $q->where('role', 'supervisor')->where('account_status', 'active'))
            ->orderBy('full_name')
            ->get();

        $groupsQuery = ProjectGroup::query()
            ->with(['members', 'supervisorAssignment.supervisor'])
            ->where('coordinator_id', $coordinator->id)
            ->latest();

        $groups = (clone $groupsQuery)->get();

        $recentGroups = (clone $groupsQuery)
            ->paginate(PrmsTablePagination::perPage($request), ['*'], 'groups_page')
            ->withQueryString();

        $programmes = \App\Models\Program::query()
            ->when(
                $deptId !== null && Schema::hasColumn('programmes', 'department_id'),
                fn ($q) => $q->where('department_id', $deptId),
                fn ($q) => $deptId === null ? $q->whereRaw('1 = 0') : $q
            )
            ->orderBy('programme_code')
            ->get();
        $projectTypes = \App\Models\ProjectType::all();

        return view('coordinator.index', [
            'eligibleStudents' => $eligibleStudents,
            'eligibleStudentsAll' => $eligibleStudentsAll,
            'groups' => $groups,
            'recentGroups' => $recentGroups,
            'supervisors' => $supervisors,
            'coordinatorDepartment' => $coordinatorDepartment,
            'programmes' => $programmes,
            'projectTypes' => $projectTypes,
            'filters' => [
                'deptId' => $deptId,
                'progId' => $progId,
                'year' => $year,
            ],
            'filterResetUrl' => PrmsListFilters::resetUrl('coordinator.index'),
        ]);
    }

    public function showGroup(Request $request, ProjectGroup $group): View
    {
        if ((int) $group->coordinator_id !== (int) $request->user()->id) {
            abort(403);
        }

        $group->load([
            'members.studentProfile.programme',
            'members.studentProfile.department',
            'supervisorAssignment.supervisor.staffProfile.department',
            'coordinator',
        ]);

        $members = $group->members->sortBy('name')->values();
        $supervisor = $group->supervisorAssignment?->supervisor;
        $supervisorStaff = $supervisor?->staffProfile;

        return view('coordinator.groups.show', [
            'group' => $group,
            'members' => $members,
            'supervisor' => $supervisor,
            'supervisorStaff' => $supervisorStaff,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function sanitizeCoordinatorFilters(array $filters): array
    {
        return [
            'programme_id' => $filters['programme_id'] ?? '',
            'year' => $filters['year'] ?? '',
        ];
    }

    private function coordinatorDepartmentId(User $coordinator): ?int
    {
        $coordinator->loadMissing('staffProfile');
        $deptId = (int) ($coordinator->staffProfile?->department_id ?? 0);

        if ($deptId > 0 && Department::query()->whereKey($deptId)->exists()) {
            return $deptId;
        }

        return StaffProfileProvisioner::resolveDepartmentIdFromLabel($coordinator->department);
    }

    /**
     * @return array{deptId: ?int, progId: mixed, year: ?int}
     */
    private function coordinatorFiltersFromRequest(Request $request): array
    {
        $defaults = [
            'programme_id' => '',
            'year' => '',
        ];
        $filters = $this->sanitizeCoordinatorFilters(PrmsListFilters::peek($request, 'coordinator.index', $defaults));
        $year = (int) ($filters['year'] ?? 0);

        return [
            'deptId' => $this->coordinatorDepartmentId($request->user()),
            'progId' => $filters['programme_id'] !== '' ? $filters['programme_id'] : null,
            'year' => $year > 0 ? $year : null,
        ];
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $studentUserRule = Rule::exists('users', 'id')->where(fn ($q) => $q
            ->whereIn('role', ['student', 'normal_student', 'project_student', 'research_student'])
            ->where('enrollment_status', 'active'));

        $formationType = $request->input('formation_type', 'group');

        if ($formationType === 'individual') {
            $validated = $request->validate([
                'formation_type' => ['required', Rule::in(['group', 'individual'])],
                'name' => ['nullable', 'string', 'max:120'],
                'student_id' => ['required', 'integer', $studentUserRule],
            ], [
                'student_id.required' => 'Please select a student.',
            ]);

            $studentUser = User::query()->findOrFail((int) $validated['student_id']);
            $name = trim((string) ($validated['name'] ?? '')) !== ''
                ? $validated['name']
                : $this->defaultIndividualGroupName($studentUser);
            $memberIds = [$studentUser->id];
        } else {
            $validated = $request->validate([
                'formation_type' => ['nullable', Rule::in(['group', 'individual'])],
                'name' => ['required', 'string', 'max:120'],
                'student_ids' => ['required', 'array', 'min:2'],
                'student_ids.*' => ['integer', $studentUserRule],
            ], [
                'name.required' => 'Group name is required.',
                'student_ids.required' => 'Please select students.',
                'student_ids.min' => 'A group must have at least 2 students.',
            ]);

            $name = $validated['name'];
            $memberIds = $validated['student_ids'];
        }

        $this->assertStudentsNotAlreadyGrouped($memberIds);
        $this->assertStudentsEligibleForAssignment($memberIds);

        if ($formationType !== 'individual') {
            $this->assertStudentsSameProgrammeAndYear($memberIds);
        }

        $group = ProjectGroup::create([
            'name' => $name,
            'coordinator_id' => $request->user()->id,
        ]);

        $group->members()->sync($memberIds);

        PrmsEventNotifier::notifyGroupCreated($group->load('members'), $request->user());

        Audit::log(
            $request,
            'coordinator.group_created',
            'ProjectGroup',
            (string) $group->id,
            null,
            [
                'name' => $group->name,
                'formation_type' => $formationType,
                'member_count' => count($memberIds),
            ]
        );

        $message = $formationType === 'individual'
            ? 'Individual supervision record created. Assign a supervisor on the right when ready.'
            : 'Project group created successfully.';

        return redirect()->route('coordinator.index')->with('status', $message);
    }

    public function autoGroupStudents(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_size' => ['required', 'integer', Rule::in([1, 2, 3])],
            'name_prefix' => ['nullable', 'string', 'max:80'],
            'programme_id' => ['nullable'],
            'year' => ['nullable', 'integer'],
        ], [
            'group_size.required' => 'Select how many students each group should have.',
            'group_size.in' => 'Group size must be 1 (individual), 2, or 3.',
        ]);

        $filterState = $this->coordinatorFiltersFromRequest($request);
        $deptId = $filterState['deptId'];
        $progId = $filterState['progId'];
        $year = $filterState['year'];

        $students = $this->eligibleStudentsForAssignment($deptId, $progId, $year);

        if ($students->isEmpty()) {
            return redirect()
                ->route('coordinator.index')
                ->with('warning', 'No eligible students found for auto-grouping with the current filters.');
        }

        $groupSize = (int) $validated['group_size'];
        $former = new GroupAutoFormer;
        $groupsByProgramme = $former->formGroupsByProgrammeAndYear($students, $groupSize);

        if ($groupsByProgramme === []) {
            return back()->with('warning', 'Could not build any groups from the selected students.');
        }

        $namePrefix = trim((string) ($validated['name_prefix'] ?? ''));

        $coordinatorId = $request->user()->id;
        $existingCount = ProjectGroup::query()->where('coordinator_id', $coordinatorId)->count();
        $createdCount = 0;
        $programmeCount = count($groupsByProgramme);

        DB::transaction(function () use (
            $groupsByProgramme,
            $students,
            $groupSize,
            $namePrefix,
            $progId,
            $coordinatorId,
            $existingCount,
            &$createdCount,
            $request
        ) {
            $groupSequence = $existingCount;

            foreach ($groupsByProgramme as $cohortKey => $groupPlans) {
                [$programmeId, $studyYear] = array_pad(explode('|', (string) $cohortKey, 2), 2, null);
                $programmeId = (int) $programmeId;
                $studyYear = (int) $studyYear;
                $cohort = $students->filter(
                    fn (\App\Models\Student $student) => (int) ($student->programme_id ?? 0) === $programmeId
                        && (int) ($student->year_of_study ?? 0) === $studyYear
                );

                $prefix = $namePrefix !== ''
                    ? $namePrefix
                    : $this->defaultAutoGroupPrefix($cohort, $programmeId > 0 ? $programmeId : $progId);

                foreach ($groupPlans as $memberIds) {
                    $this->assertStudentsNotAlreadyGrouped($memberIds);
                    $this->assertStudentsEligibleForAssignment($memberIds);
                    $this->assertStudentsSameProgrammeAndYear($memberIds);

                    $groupSequence++;
                    $name = count($memberIds) === 1 && $groupSize === 1
                        ? $this->defaultIndividualGroupName(User::query()->findOrFail($memberIds[0]))
                        : sprintf('%s/G%02d', rtrim($prefix, '/'), $groupSequence);

                    $group = ProjectGroup::create([
                        'name' => $name,
                        'coordinator_id' => $coordinatorId,
                    ]);

                    $group->members()->sync($memberIds);
                    $createdCount++;

                    PrmsEventNotifier::notifyGroupCreated($group->load('members'), $request->user());

                Audit::log(
                    $request,
                    'coordinator.group_auto_created',
                    'ProjectGroup',
                    (string) $group->id,
                    null,
                    [
                        'name' => $group->name,
                        'group_size' => $groupSize,
                        'member_count' => count($memberIds),
                        'member_ids' => $memberIds,
                        'programme_id' => $programmeId > 0 ? $programmeId : null,
                        'year_of_study' => $studyYear > 0 ? $studyYear : null,
                    ]
                );
                }
            }
        });

        $withoutGender = $students->filter(fn ($student) => $student->normalizedGender() === null)->count();
        $message = $groupSize === 1
            ? "Created {$createdCount} individual supervision record(s). Assign supervisors when ready."
            : "Auto-formed {$createdCount} group(s) with up to {$groupSize} students each within the same programme, balancing gender where data is available.";

        if ($programmeCount > 1) {
            $message .= " Groups were created separately for {$programmeCount} programme/year cohort(s).";
        }

        if ($withoutGender > 0 && $groupSize > 1) {
            $message .= " {$withoutGender} student(s) had no gender on file, so those placements used available members only.";
        }

        return redirect()
            ->route('coordinator.index')
            ->with('status', $message);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Student>
     */
    private function eligibleStudentsQuery(?int $deptId, mixed $progId, ?int $year)
    {
        return \App\Models\Student::query()
            ->with(['user.studentProfile.programme.department', 'programme.department'])
            ->where('enrollment_status', 'active')
            ->whereNotNull('programme_id')
            ->whereNotNull('year_of_study')
            ->whereHas('programme')
            ->whereHas('user', fn ($q) => $q
                ->whereIn('role', User::STUDENT_ROLES)
                ->where('enrollment_status', 'active')
                ->where('account_status', 'active'))
            ->when($deptId !== null, function ($q) use ($deptId) {
                $q->where(function ($departmentQuery) use ($deptId) {
                    if (Schema::hasColumn('students', 'department_id')) {
                        $departmentQuery->where('department_id', $deptId);

                        if (Schema::hasColumn('programmes', 'department_id')) {
                            $departmentQuery->orWhereHas('programme', fn ($pq) => $pq->where('department_id', $deptId));
                        }

                        return;
                    }

                    if (Schema::hasColumn('programmes', 'department_id')) {
                        $departmentQuery->whereHas('programme', fn ($pq) => $pq->where('department_id', $deptId));
                    }
                });
            })
            ->when($progId, fn ($q) => $q->where('programme_id', $progId))
            ->when($year > 0, fn ($q) => $q->where('year_of_study', $year))
            ->whereDoesntHave('user.projectGroups')
            ->orderBy('full_name');
    }

    /**
     * @return Collection<int, \App\Models\Student>
     */
    private function eligibleStudentsForAssignment(?int $deptId, mixed $progId, ?int $year): Collection
    {
        return $this->eligibleStudentsQuery($deptId, $progId, $year)
            ->get()
            ->filter(fn (\App\Models\Student $student) => $this->studentEligibleForAssignment($student))
            ->values();
    }

    /**
     * @param  Collection<int, \App\Models\Student>  $students
     */
    private function paginateEligibleStudents(Collection $students, Request $request): LengthAwarePaginator
    {
        $perPage = PrmsTablePagination::perPage($request);
        $page = LengthAwarePaginator::resolveCurrentPage('students_page');

        return new LengthAwarePaginator(
            $students->forPage($page, $perPage)->values(),
            $students->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'students_page',
            ],
        );
    }

    private function studentEligibleForAssignment(\App\Models\Student $student): bool
    {
        $student->loadMissing(['user.studentProfile.programme.department', 'programme.department']);

        if ($student->user === null || $student->programme_id === null || $student->programme === null) {
            return false;
        }

        $student->user->setRelation('studentProfile', $student);

        return StudentResearchEligibility::isInResearchYear($student->user)
            && StudentResearchEligibility::availableTracks($student->user) !== [];
    }

    /**
     * @param  Collection<int, \App\Models\Student>  $students
     */
    private function defaultAutoGroupPrefix(Collection $students, mixed $progId): string
    {
        $year = (int) date('Y');

        if ($progId) {
            $programme = \App\Models\Program::query()->find($progId);
            if ($programme !== null) {
                return $programme->programme_code . '/' . $year;
            }
        }

        $firstProgramme = $students->first()?->programme;
        if ($firstProgramme !== null) {
            return $firstProgramme->programme_code . '/' . $year;
        }

        return 'PRMS/' . $year;
    }

    private function defaultIndividualGroupName(User $student): string
    {
        $reg = trim((string) ($student->regNo() ?? ''));

        return $reg !== ''
            ? 'Individual — ' . $student->name . ' (' . $reg . ')'
            : 'Individual — ' . $student->name;
    }

    /**
     * @param  list<int>  $userIds
     */
    private function assertStudentsNotAlreadyGrouped(array $userIds): void
    {
        $conflict = User::query()
            ->whereIn('id', $userIds)
            ->whereHas('projectGroups')
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'student_ids' => ['One or more selected students are already in a project group.'],
                'student_id' => ['This student is already in a project group.'],
            ]);
        }
    }

    /**
     * @param  list<int>  $userIds
     */
    private function assertStudentsEligibleForAssignment(array $userIds): void
    {
        $students = \App\Models\Student::query()
            ->with(['user.studentProfile.programme.department', 'programme.department'])
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $invalid = collect($userIds)->contains(function ($userId) use ($students): bool {
            $student = $students->get($userId);

            return ! $student || ! $this->studentEligibleForAssignment($student);
        });

        if ($invalid) {
            throw ValidationException::withMessages([
                'student_ids' => ['Only active final-year students with a programme can be assigned.'],
                'student_id' => ['Only an active final-year student with a programme can be assigned.'],
            ]);
        }
    }

    /**
     * @param  list<int>  $userIds
     */
    private function assertStudentsSameProgrammeAndYear(array $userIds): void
    {
        $students = \App\Models\Student::query()
            ->whereIn('user_id', $userIds)
            ->get(['programme_id', 'year_of_study']);

        $programmeIds = $students->pluck('programme_id')->unique()->values();
        $studyYears = $students->pluck('year_of_study')->unique()->values();

        if ($programmeIds->count() !== 1) {
            throw ValidationException::withMessages([
                'student_ids' => ['All students in a group must be from the same programme.'],
            ]);
        }

        if ($studyYears->count() !== 1) {
            throw ValidationException::withMessages([
                'student_ids' => ['All students in a group must be from the same year of study.'],
            ]);
        }
    }

    public function assignSupervisor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_group_id' => [
                'required',
                'integer',
                Rule::exists('project_groups', 'id')->where(
                    fn ($q) => $q->where('coordinator_id', $request->user()->id)
                ),
            ],
            'supervisor_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'supervisor')],
        ], [
            'project_group_id.required' => 'Please select a target group.',
            'project_group_id.exists' => 'That group is not part of your coordinator workspace.',
            'supervisor_id.required' => 'Please select a supervisor.',
        ]);

        SupervisorAssignment::updateOrCreate(
            ['project_group_id' => $validated['project_group_id']],
            [
                'supervisor_id' => $validated['supervisor_id'],
                'student_id' => null,
            ]
        );

        $group = ProjectGroup::query()->with('members')->findOrFail($validated['project_group_id']);
        $supervisor = User::query()->findOrFail($validated['supervisor_id']);
        PrmsEventNotifier::notifySupervisorAssigned($group, $supervisor, $request->user());

        Audit::log(
            $request,
            'coordinator.supervisor_assigned',
            'ProjectGroup',
            (string) $validated['project_group_id'],
            null,
            ['supervisor_id' => $validated['supervisor_id']]
        );

        return redirect()->route('coordinator.index')->with('status', 'Supervisor assigned successfully.');
    }

    public function autoAssignSupervisors(Request $request): RedirectResponse
    {
        $coordinatorId = $request->user()->id;
        
        // 1. Get groups without supervisors
        $unassignedGroups = ProjectGroup::where('coordinator_id', $coordinatorId)
            ->whereDoesntHave('supervisorAssignment')
            ->get();

        if ($unassignedGroups->isEmpty()) {
            return back()->with('warning', 'No unassigned groups found.');
        }

        // 2. Get available supervisors
        $supervisors = User::where('role', 'supervisor')
            ->withCount('supervisorAssignments')
            ->get()
            ->sortBy('supervisor_assignments_count');

        if ($supervisors->isEmpty()) {
            return back()->with('error', 'No supervisors available in the system.');
        }

        $assignedCount = 0;
        foreach ($unassignedGroups as $group) {
            // Pick the supervisor with the lowest workload
            $targetSupervisor = $supervisors->first();
            
            SupervisorAssignment::create([
                'project_group_id' => $group->id,
                'supervisor_id' => $targetSupervisor->id,
            ]);

            PrmsEventNotifier::notifySupervisorAssigned($group->load('members'), $targetSupervisor, $request->user());

            // Increment workload locally so we don't dump everyone on one person in the same loop
            $targetSupervisor->supervisor_assignments_count++;
            $supervisors = $supervisors->sortBy('supervisor_assignments_count');
            
            $assignedCount++;
        }

        return redirect()->route('coordinator.index')->with('status', "Successfully auto-assigned $assignedCount groups to supervisors based on workload balance.");
    }
    public function manageDeadlines(Request $request): View
    {
        $stages = [
            'proposal_chapter_1',
            'proposal_chapter_2',
            'proposal_chapter_3',
            'research_chapter_1',
            'research_chapter_2',
            'research_chapter_3',
            'research_chapter_4',
            'research_chapter_5',
            'project_source_code'
        ];

        $deadlines = \App\Models\StageDeadline::with('projectGroup')
            ->latest()
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        $statsRows = \App\Models\StageDeadline::query()->get(['start_time', 'end_time']);
        $now = now();
        $deadlineStats = [
            'total' => $statsRows->count(),
            'active' => $statsRows->filter(fn ($d) => $d->end_time && $now->between(
                $d->start_time ?? $now->copy()->subYear(),
                $d->end_time,
            ))->count(),
            'upcoming' => $statsRows->filter(fn ($d) => $d->start_time && $now->isBefore($d->start_time))->count(),
            'closed' => $statsRows->filter(fn ($d) => $d->end_time && $now->isAfter($d->end_time))->count(),
        ];

        return view('coordinator.deadlines', [
            'stages' => $stages,
            'deadlines' => $deadlines,
            'deadlineStats' => $deadlineStats,
            'groups' => ProjectGroup::where('coordinator_id', $request->user()->id)->get(),
            'now' => $now,
        ]);
    }

    public function storeDeadline(StoreStageDeadlineRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $deadline = \App\Models\StageDeadline::create($validated);

        Audit::log(
            $request,
            'coordinator.deadline_created',
            'StageDeadline',
            (string) $deadline->id,
            null,
            $validated,
        );

        PrmsEventNotifier::notifyStageDeadline($deadline);

        return redirect()->route('coordinator.deadlines')->with('status', 'Deadline set successfully.');
    }

    public function updateDeadline(StoreStageDeadlineRequest $request, \App\Models\StageDeadline $deadline): RedirectResponse
    {
        $validated = $request->validated();

        $old = $deadline->only(array_keys($validated));
        $deadline->fill($validated)->save();

        Audit::log(
            $request,
            'coordinator.deadline_updated',
            'StageDeadline',
            (string) $deadline->id,
            $old,
            $validated,
        );

        PrmsEventNotifier::notifyStageDeadline($deadline, updated: true);

        return redirect()
            ->route('coordinator.deadlines')
            ->with('status', 'Deadline updated successfully.');
    }

    public function destroyDeadline(Request $request, \App\Models\StageDeadline $deadline): RedirectResponse
    {
        $snapshot = [
            'stage_name'       => $deadline->stage_name,
            'academic_year'    => $deadline->academic_year,
            'project_group_id' => $deadline->project_group_id,
            'start_time'       => optional($deadline->start_time)->toDateTimeString(),
            'end_time'         => optional($deadline->end_time)->toDateTimeString(),
        ];

        $deadline->delete();

        Audit::log(
            $request,
            'coordinator.deadline_deleted',
            'StageDeadline',
            (string) $deadline->id,
            $snapshot,
            null,
        );

        return redirect()
            ->route('coordinator.deadlines')
            ->with('status', 'Deadline removed successfully.');
    }

    public function submissions(Request $request): View|RedirectResponse
    {
        $defaults = [
            'type' => '',
            'stage' => '',
            'status' => 'all',
            'q' => '',
        ];

        $resolved = PrmsListFilters::resolve(
            $request,
            'coordinator.submissions',
            $defaults,
            'coordinator.submissions',
            [],
            fn (array $filters) => $this->sanitizeCoordinatorSubmissionFilters($filters)
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $filters = $resolved['filters'];

        $submissions = $this->applyCoordinatorSubmissionFilters(
            ProjectSubmission::query()
            ->with(['projectGroup.members', 'student', 'feedback.supervisor'])
            ->where('submitted_to_coordinator', true)
                ->whereIn('stage', StudentStageProgress::completeDocumentStageNames()),
            $filters
        )
            ->latest()
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        return view('coordinator.submissions', [
            'submissions' => $submissions,
            'filters' => $filters,
            'stages' => $this->coordinatorFinalStageOptions($filters['type']),
            'filterResetUrl' => PrmsListFilters::resetUrl('coordinator.submissions'),
            'consentSubmissions' => $this->consentMapForFinalSubmissions($submissions),
        ]);
    }

    public static function approveConsentSubmission(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        PresentationConsentForm::authorizeCoordinatorForSubmission($request->user(), $submission);

        if ($submission->supervisor_consent_signed_at === null || ! $submission->submitted_to_coordinator) {
            return back()->withErrors([
                'error' => 'Supervisor consent must be signed and forwarded before coordinator approval.',
            ]);
        }

        if ($submission->coordinator_approved_at !== null) {
            return back()->with('status', 'Consent letter was already finalized.');
        }

        return redirect()->route('coordinator.submissions.consent.sign', $submission);
    }

    public function consentSign(Request $request, ProjectSubmission $submission): View|RedirectResponse
    {
        PresentationConsentForm::authorizeCoordinatorForSubmission($request->user(), $submission);

        if ($submission->supervisor_consent_signed_at === null || ! $submission->submitted_to_coordinator) {
            return redirect()
                ->route('coordinator.submissions')
                ->withErrors(['error' => 'Supervisor consent must be signed and forwarded before you can sign.']);
        }

        $context = PresentationConsentForm::resolveFromSubmission($submission, $request->user());

        return view('coordinator.consent-sign', array_merge($context, [
            'submission' => $submission,
            'alreadySigned' => $submission->coordinator_approved_at !== null,
        ]));
    }

    public function consentSignStore(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $coordinator = $request->user();
        PresentationConsentForm::authorizeCoordinatorForSubmission($coordinator, $submission);

        if ($submission->supervisor_consent_signed_at === null || ! $submission->submitted_to_coordinator) {
            return back()->withErrors([
                'error' => 'Supervisor consent must be signed and forwarded before coordinator approval.',
            ]);
        }

        if ($submission->coordinator_approved_at !== null) {
            return redirect()
                ->route('coordinator.submissions.consent.sign', $submission)
                ->with('status', 'Consent was already finalized.');
        }

        $validated = $request->validate([
            'consent_reviewed' => ['accepted'],
            'signature' => ['required', 'string'],
        ], [
            'consent_reviewed.accepted' => 'Confirm you have reviewed the supervisor confirmation form before signing.',
            'signature.required' => 'Please draw your signature before submitting.',
        ]);

        $signaturePath = PresentationConsentForm::storeSignatureImage($validated['signature']);
        $signatureDataUri = PresentationConsentForm::signatureDataUriFromBase64($validated['signature']);
        $approvedAt = now();

        $context = PresentationConsentForm::resolveFromSubmission($submission, $coordinator);
        $context['coordinatorSignatureDataUri'] = $signatureDataUri;
        $context['coordinatorApprovedAt'] = $approvedAt;
        $context['coordinator'] = $coordinator;

        try {
            $pdfPath = PresentationConsentForm::saveCoordinatorConsentPdfToDisk($context, $submission);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('coordinator.submissions.consent.sign', $submission)
                ->withInput()
                ->withErrors(['pdf' => $e->getMessage()]);
        }

        $submission->update([
            'coordinator_approved_at' => $approvedAt,
            'coordinator_approved_by' => $coordinator->id,
            'coordinator_signature_path' => $signaturePath,
            'coordinator_consent_pdf_path' => $pdfPath,
        ]);

        $published = RepositoryPublication::publishScopeOnConsentCoordinatorApproval($submission);

        Audit::log(
            $request,
            'coordinator.consent_approved',
            'ProjectSubmission',
            (string) $submission->id,
            null,
            ['pdf_path' => $pdfPath, 'published_count' => $published]
        );

        PrmsEventNotifier::notifyCoordinatorConsentApproved($submission, $coordinator, $published);

        $message = 'Supervisor confirmation form signed and finalized successfully.';
        if ($published > 0) {
            $message .= " {$published} related complete document(s) published to the repository.";
        }

        return redirect()
            ->route('coordinator.submissions')
            ->with('status', $message);
    }

    public function consentReview(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $coordinator = $request->user();
        PresentationConsentForm::authorizeCoordinatorForSubmission($coordinator, $submission);

        if ($submission->coordinator_approved_at !== null) {
            return back()->withErrors(['error' => 'Consent was already finalized and cannot be returned.']);
        }

        if ($submission->supervisor_consent_signed_at === null || ! $submission->submitted_to_coordinator) {
            return back()->withErrors(['error' => 'Supervisor consent must be signed before coordinator review.']);
        }

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:3000'],
            'decision' => ['required', Rule::in(['rejected', 'needs_revision'])],
        ], [
            'comments.required' => 'Please explain why you are rejecting or returning this consent request.',
        ]);

        SubmissionFeedback::create([
            'project_submission_id' => $submission->id,
            'supervisor_id' => $coordinator->id,
            'comments' => trim($validated['comments']),
            'decision' => $validated['decision'],
        ]);

        PresentationConsentForm::clearCoordinatorConsentSignature($submission);
        PresentationConsentForm::clearSupervisorConsentSignature($submission);

        $submission->update(['status' => $validated['decision']]);

        Audit::log(
            $request,
            'coordinator.consent_returned',
            'ProjectSubmission',
            (string) $submission->id,
            null,
            ['decision' => $validated['decision']]
        );

        PrmsEventNotifier::notifyConsentReturnedByCoordinator($submission, $coordinator, $validated['decision']);

        $label = $validated['decision'] === 'rejected' ? 'rejected' : 'returned for revision';

        return redirect()
            ->route('coordinator.submissions')
            ->with('status', 'Consent request '.$label.' successfully. The student and supervisor have been notified.');
    }

    public function consentPdf(Request $request, ProjectSubmission $submission): Response
    {
        PresentationConsentForm::authorizeCoordinatorForSubmission($request->user(), $submission);

        $storedPdf = PresentationConsentForm::consentPdfPath($submission);
        if ($storedPdf !== null) {
            return response()->file(
                \Illuminate\Support\Facades\Storage::disk('public')->path($storedPdf),
                ['Content-Type' => 'application/pdf']
            );
        }

        $context = PresentationConsentForm::resolveFromSubmission($submission, $request->user());

        return PresentationConsentForm::renderPdf($context, $context['supervisorSignatureDataUri'] ?? null);
    }

    public function approveSubmission(Request $request, \App\Models\ProjectSubmission $submission): RedirectResponse
    {
        if (! \App\Support\StudentStageProgress::isCompleteDocumentStage((string) $submission->stage)) {
            return back()->withErrors([
                'error' => 'Only complete proposal, research report, or project documents can be finalized here.',
            ]);
        }

        $submission->update(['coordinator_approved_at' => now()]);

        $publishedViaConsent = 0;
        if (trim((string) $submission->stage) === 'Complete Project Document') {
            $publishedViaConsent = $this->coordinatorApproveRelatedConsent($submission);
        }

        RepositoryPublication::tryPublishOnCoordinatorFinalize($submission);
        $submission->refresh();

        PrmsEventNotifier::notifyCoordinatorSubmissionFinalized($submission, $publishedViaConsent);

        $message = 'Submission finalized and recorded successfully.';
        if ($submission->repository_published_at !== null) {
            $message .= ' It is now visible in the repository.';
        } elseif (RepositoryPublication::requiresConsentForStage((string) $submission->stage)) {
            $message .= ' It will appear in the repository once your supervisor has signed and forwarded the Final Presentation Consent Letter.';
        }

        if ($publishedViaConsent > 0) {
            $message .= " {$publishedViaConsent} related complete document(s) were also published to the repository.";
        }

        return back()->with('status', $message);
    }

    private function coordinatorApproveRelatedConsent(\App\Models\ProjectSubmission $submission): int
    {
        // Consent must be coordinator-signed via the dedicated sign page.
            return 0;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function sanitizeCoordinatorSubmissionFilters(array $filters): array
    {
        $allowedStatus = ['all', 'pending', 'approved'];
        $allowedStages = array_merge([''], StudentStageProgress::completeDocumentStageNames());
        $stage = (string) ($filters['stage'] ?? '');

        return [
            'type' => in_array($filters['type'] ?? '', ['', 'proposal', 'research', 'project'], true) ? $filters['type'] : '',
            'stage' => in_array($stage, $allowedStages, true) ? $stage : '',
            'status' => in_array($filters['status'] ?? 'all', $allowedStatus, true) ? $filters['status'] : 'all',
            'q' => trim((string) ($filters['q'] ?? '')),
        ];
    }

    /**
     * @param  array{type: string, stage: string, status: string, q: string}  $filters
     */
    private function applyCoordinatorSubmissionFilters(Builder $query, array $filters): Builder
    {
        if ($filters['status'] === 'pending') {
            $query->whereNull('coordinator_approved_at');
        } elseif ($filters['status'] === 'approved') {
            $query->whereNotNull('coordinator_approved_at');
        }

        if ($filters['type'] !== '') {
            StudentStageProgress::scopeWorkType($query, $filters['type']);
        }

        if ($filters['stage'] !== '') {
            $query->where('stage', $filters['stage']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('stage', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%")
                    ->orWhereHas('projectGroup', fn ($group) => $group->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    private function coordinatorFinalStageOptions(string $type): array
    {
        $stages = StudentStageProgress::completeDocumentStageNames();

        if ($type === '') {
            return $stages;
        }

        return array_values(array_filter(
            $stages,
            fn (string $name) => StudentStageProgress::workTypeFromStage($name) === $type
        ));
    }

    /**
     * @param  Collection<int, ProjectSubmission>|LengthAwarePaginator<int, ProjectSubmission>  $submissions
     * @return array<int, ProjectSubmission> keyed by parent final submission id
     */
    private function consentMapForFinalSubmissions(Collection|LengthAwarePaginator $submissions): array
    {
        $map = [];

        $items = $submissions instanceof LengthAwarePaginator
            ? $submissions->getCollection()
            : $submissions;

        foreach ($items as $submission) {
            $student = $submission->student;
            if ($student === null) {
                continue;
            }

            $consent = RepositoryPublication::latestConsentSubmission($student, $submission->projectGroup);
            if ($consent === null
                || $consent->supervisor_consent_signed_at === null
                || ! $consent->submitted_to_coordinator) {
                continue;
            }

            $map[$submission->id] = $consent;
        }

        return $map;
    }
}
