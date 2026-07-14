<?php

namespace App\Http\Controllers;

use App\Models\EvaluationRubric;
use App\Models\ProjectGroup;
use App\Models\ProjectStage;
use App\Models\ProjectSubmission;
use App\Models\User;
use App\Models\StudentEvaluation;
use App\Models\SubmissionFeedback;
use App\Models\SupervisionLog;
use App\Notifications\SubmissionReviewedNotification;
use App\Services\OnlyOfficeService;
use App\Support\Audit;
use App\Support\EvaluationScoring;
use App\Support\PrmsEventNotifier;
use App\Support\PrmsListFilters;
use App\Support\PrmsTablePagination;
use App\Support\PresentationConsentForm;
use App\Support\RepositoryPublication;
use App\Support\StudentStageProgress;
use App\Support\SupervisorAssignmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $supervisorId = $request->user()->id;

        $defaults = [
            'queue' => 'all',
            'type' => '',
            'stage' => '',
            'q' => '',
        ];

        if ($request->filled('apply_queue')) {
            $queue = (string) $request->query('apply_queue');
            session()->flash(PrmsListFilters::sessionKey('supervisor.index'), array_merge(
                $defaults,
                ['queue' => in_array($queue, ['all', 'pending', 'reviewed', 'approved', 'rejected'], true) ? $queue : 'all']
            ));

            return redirect()->route('supervisor.index');
        }

        $resolved = PrmsListFilters::resolve(
            $request,
            'supervisor.index',
            $defaults,
            'supervisor.index',
            [],
            fn (array $filters) => $this->sanitizeSupervisorFilters($filters)
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $filters = $resolved['filters'];

        $submissions = $this->applySupervisorSubmissionFilters(
            ProjectSubmission::query()
                ->with(['projectGroup.members', 'student', 'feedback.supervisor'])
                ->whereRaw('LOWER(COALESCE(status, "")) != ?', ['draft'])
                ->where(function ($query) use ($supervisorId) {
                    $query->whereHas('projectGroup.supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                        ->orWhereHas('student.studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId));
                }),
            $filters
        )
            ->latest()
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        return view('supervisor.index', [
            'submissions' => $submissions,
            'queueFilter' => $filters['queue'],
            'filters' => $filters,
            'stages' => $this->supervisorStageOptions($filters['type']),
            'filterResetUrl' => PrmsListFilters::resetUrl('supervisor.index'),
            'onlyOfficeConfigured' => app(OnlyOfficeService::class)->isConfigured(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function sanitizeSupervisorFilters(array $filters): array
    {
        $allowedQueues = ['all', 'pending', 'reviewed', 'approved', 'rejected'];

        return [
            'queue' => in_array($filters['queue'] ?? 'all', $allowedQueues, true) ? $filters['queue'] : 'all',
            'type' => in_array($filters['type'] ?? '', ['', 'proposal', 'research', 'project'], true) ? $filters['type'] : '',
            'stage' => (string) ($filters['stage'] ?? ''),
            'q' => trim((string) ($filters['q'] ?? '')),
        ];
    }

    /**
     * @param  array{queue: string, type: string, stage: string, q: string}  $filters
     */
    private function applySupervisorSubmissionFilters(Builder $query, array $filters): Builder
    {
        $queue = $filters['queue'];

        $query
            ->when($queue === 'pending', function ($q) {
                $q->whereRaw('LOWER(COALESCE(status, "")) IN (?,?,?,?)', [
                    'pending', 'submitted', 'under_review', 'needs_revision',
                ]);
            })
            ->when($queue === 'reviewed', function ($q) {
                $q->whereRaw('LOWER(COALESCE(status, "")) IN (?,?)', ['approved', 'rejected']);
            })
            ->when($queue === 'approved', function ($q) {
                $q->whereRaw('LOWER(COALESCE(status, "")) = ?', ['approved']);
            })
            ->when($queue === 'rejected', function ($q) {
                $q->whereRaw('LOWER(COALESCE(status, "")) = ?', ['rejected']);
            });

            /** if ($filters['type'] !== '') {
            StudentStageProgress::scopeWorkType($query, $filters['type']);
        }*/
            if ($filters['type'] !== null && $filters['type'] !== '') {
    $query->workType($filters['type']);
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
    private function supervisorStageOptions(string $workType): array
    {
        $stages = ProjectStage::query()->orderBy('stage_order')->get();

        if ($workType !== '') {
            $stages = StudentStageProgress::stagesForTrack($stages, $workType);
        }

        return $stages->pluck('stage_name')->all();
    }

    public function logs(Request $request): View
    {
        $supervisorId = $request->user()->id;

        $logs = SupervisionLog::query()
            ->with(['projectGroup', 'student'])
            ->where(function ($query) use ($supervisorId) {
                $query->whereHas('projectGroup.supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                    ->orWhereHas('student.studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId));
            })
            ->orderByDesc('meeting_starts_at')
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        return view('supervisor.logs', [
            'logs' => $logs,
            ...$this->supervisorLogFormData($supervisorId),
        ]);
    }

    public function createLog(Request $request): View
    {
        $data = $this->supervisorLogFormData($request->user()->id);

        return view('supervisor.logs-create', $data);
    }

    public function storeLog(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'apply_to_all' => 'nullable|boolean',
            'targets' => 'nullable|array',
            'targets.*' => 'string',
            'meeting_date' => 'required|date',
            'meeting_start_time' => 'required|date_format:H:i',
            'meeting_end_time' => 'required|date_format:H:i|after:meeting_start_time',
            'summary' => 'required|string',
            'progress_score' => 'required|integer|min:0|max:100',
            'next_steps' => 'nullable|string',
        ]);

        $supervisorId = $request->user()->id;
        $allowed = $this->supervisorLogTargets($supervisorId);
        $targets = $this->resolveSupervisionTargets(
            $request->boolean('apply_to_all'),
            $validated['targets'] ?? [],
            $allowed
        );

        if ($targets->isEmpty()) {
            return redirect()
                ->route('supervisor.logs.create')
                ->withInput()
                ->withErrors(['targets' => 'Select at least one student or group, or choose Apply to all.']);
        }

        $startsAt = Carbon::parse($validated['meeting_date'].' '.$validated['meeting_start_time']);
        $endsAt = Carbon::parse($validated['meeting_date'].' '.$validated['meeting_end_time']);

        $payload = [
            'supervisor_id' => $supervisorId,
            'meeting_starts_at' => $startsAt,
            'meeting_ends_at' => $endsAt,
            'summary' => $validated['summary'],
            'progress_score' => $validated['progress_score'],
            'next_steps' => $validated['next_steps'] ?? null,
        ];

        foreach ($targets as $target) {
            SupervisionLog::create(array_merge($payload, $target));
        }

        $count = $targets->count();
        $message = $count === 1
            ? 'Supervision meeting saved successfully.'
            : "{$count} supervision meetings saved successfully.";

        return redirect()
            ->route('supervisor.logs')
            ->with('status', $message);
    }

    /**
     * @return array{groups: Collection, students: Collection}
     */
    private function supervisorLogFormData(int $supervisorId): array
    {
        $groups = ProjectGroup::whereHas('supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
            ->with(['members', 'supervisionLogs' => fn ($q) => $q->orderByDesc('meeting_starts_at')])
            ->get();

        $students = User::whereHas('studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
            ->with(['supervisionLogs' => fn ($q) => $q->orderByDesc('meeting_starts_at')])
            ->get();

        return compact('groups', 'students');
    }

    /**
     * @return array{groups: Collection<int, int>, students: Collection<int, int>}
     */
    private function supervisorLogTargets(int $supervisorId): array
    {
        return [
            'groups' => ProjectGroup::query()
                ->whereHas('supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                ->pluck('id'),
            'students' => User::query()
                ->whereHas('studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                ->pluck('id'),
        ];
    }

    /**
     * @param  list<string>  $selected
     * @param  array{groups: Collection<int, int>, students: Collection<int, int>}  $allowed
     * @return Collection<int, array{project_group_id: ?int, student_id: ?int}>
     */
    private function resolveSupervisionTargets(bool $applyToAll, array $selected, array $allowed): Collection
    {
        if ($applyToAll) {
            return collect()
                ->merge($allowed['groups']->map(fn ($id) => [
                    'project_group_id' => (int) $id,
                    'student_id' => null,
                ]))
                ->merge($allowed['students']->map(fn ($id) => [
                    'project_group_id' => null,
                    'student_id' => (int) $id,
                ]))
                ->values();
        }

        return collect($selected)
            ->map(function (string $target) use ($allowed) {
                if (str_starts_with($target, 'group:')) {
                    $id = (int) substr($target, 6);

                    if (! $allowed['groups']->contains($id)) {
                        return null;
                    }

                    return ['project_group_id' => $id, 'student_id' => null];
                }

                if (str_starts_with($target, 'student:')) {
                    $id = (int) substr($target, 9);

                    if (! $allowed['students']->contains($id)) {
                        return null;
                    }

                    return ['project_group_id' => null, 'student_id' => $id];
                }

                return null;
            })
            ->filter()
            ->unique(fn (array $row) => ($row['project_group_id'] ?? 'g').':'.($row['student_id'] ?? 's'))
            ->values();
    }

    public function workload(Request $request): View
    {
        $assignments = SupervisorAssignmentScope::forSupervisor($request->user()->id);
        $groups = $assignments['groups'];

        $groupFilter = (string) $request->query('group', 'all');
        $selectedGroup = null;

        if ($groupFilter !== 'all' && $groupFilter !== '') {
            $groupId = (int) $groupFilter;
            $selectedGroup = $groups->firstWhere('id', $groupId);

            if ($selectedGroup === null) {
                abort(404);
            }
        }

        $visibleGroups = $selectedGroup !== null ? collect([$selectedGroup]) : $groups;

        $individualStudentsAll = $assignments['individuals'];
        $individualStudents = PrmsTablePagination::paginateCollection(
            collect($individualStudentsAll),
            $request,
            'individuals_page',
        );

        return view('supervisor.workload', [
            'groups' => $groups,
            'visibleGroups' => $visibleGroups,
            'groupFilter' => $groupFilter === '' ? 'all' : $groupFilter,
            'individualStudents' => $individualStudents,
            'individualStudentsAll' => $individualStudentsAll,
            'assignmentSummary' => $assignments,
        ]);
    }

    public function review(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $supervisorId = $request->user()->id;
        $assignedToSupervisor = false;

        if ($submission->project_group_id) {
            $assignedToSupervisor = $submission->projectGroup()
                ->whereHas('supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                ->exists();
        } elseif ($submission->student_id) {
            $assignedToSupervisor = $submission->student()
                ->whereHas('studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                ->exists();
        }

        if (!$assignedToSupervisor) {
            abort(403, 'You are not assigned to this submission.');
        }

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:3000'],
            'decision' => ['required', Rule::in(['approved', 'rejected', 'needs_revision'])],
        ]);

        $isConsent = StudentStageProgress::isConsentLetterStage((string) $submission->stage);
        if ($isConsent && in_array($validated['decision'], ['rejected', 'needs_revision'], true)
            && ! filled(trim((string) ($validated['comments'] ?? '')))) {
            return back()
                ->withInput()
                ->withErrors(['comments' => 'Please explain why you are rejecting or returning this consent request.']);
        }

        if ($validated['decision'] === 'approved'
            && StudentStageProgress::isConsentLetterStage((string) $submission->stage)) {
            return redirect()
                ->route('supervisor.presentation-consent.sign', $submission)
                ->with('status', 'Complete the consent form with your signature to forward this submission to the coordinator.');
        }

        SubmissionFeedback::create([
            'project_submission_id' => $submission->id,
            'supervisor_id' => $request->user()->id,
            'comments' => filled($validated['comments'] ?? null) ? trim($validated['comments']) : null,
            'decision' => $validated['decision'],
        ]);

        $submission->status = $validated['decision'];
        $submission->save();

        if ($isConsent && in_array($validated['decision'], ['rejected', 'needs_revision'], true)) {
            PresentationConsentForm::clearSupervisorConsentSignature($submission);
            PresentationConsentForm::clearCoordinatorConsentSignature($submission);
        }

        Audit::log(
            $request,
            'supervisor.submission_reviewed',
            'ProjectSubmission',
            (string) $submission->id,
            null,
            [
                'decision' => $validated['decision'],
                'stage' => $submission->stage,
                'project_group_id' => $submission->project_group_id,
                'student_id' => $submission->student_id,
            ]
        );

        if ($submission->project_group_id) {
            $group = $submission->projectGroup()->with('members')->first();
            if ($group) {
                foreach ($group->members as $member) {
                    PrmsEventNotifier::safeNotify($member, new SubmissionReviewedNotification($submission, $validated['decision']));
                }
            }
        } elseif ($submission->student_id) {
            $student = $submission->student;
            if ($student) {
                PrmsEventNotifier::safeNotify($student, new SubmissionReviewedNotification($submission, $validated['decision']));
            }
        }

        $redirectTo = (string) $request->input('redirect_to', '');
        if ($redirectTo !== '' && str_starts_with($redirectTo, url('/'))) {
            return redirect()->to($redirectTo)->with('status', 'Review submitted successfully.');
        }

        return redirect()->route('supervisor.index')->with('status', 'Review submitted successfully.');
    }

    /**
     * FR-04: Show the grading-scheme evaluation form for a specific
     * submission. Supervisors can score each criterion against an
     * active scheme and persist a StudentEvaluation record.
     */
    public function evaluate(Request $request, ProjectSubmission $submission): View
    {
        $this->authorizeSubmissionForSupervisor($request, $submission);

        $submission->load(['student', 'projectGroup.members']);
        $rubrics = EvaluationRubric::forSupervisorGrading();
        $systemRubric = EvaluationRubric::systemDefault();
        $presentationGroupGrading = $this->usesPresentationGroupGrading($submission);

        $existingEvaluations = StudentEvaluation::query()
            ->where('project_submission_id', $submission->id)
            ->where('evaluator_id', $request->user()->id)
            ->get();

        $existingGroup = $existingEvaluations->firstWhere('evaluation_scope', 'group');
        $existingMembers = $existingEvaluations
            ->where('evaluation_scope', 'individual')
            ->keyBy(fn (StudentEvaluation $evaluation) => (int) $evaluation->student_id);
        $existing = $presentationGroupGrading
            ? null
            : ($existingEvaluations->firstWhere('evaluation_scope', 'submission') ?? $existingEvaluations->first());

        return view('supervisor.evaluate', [
            'submission' => $submission,
            'rubrics' => $rubrics,
            'systemRubric' => $systemRubric,
            'presentationGroupGrading' => $presentationGroupGrading,
            'groupMembers' => $presentationGroupGrading
                ? $submission->projectGroup?->members ?? collect()
                : collect(),
            'existing' => $existing,
            'existingGroup' => $existingGroup,
            'existingMembers' => $existingMembers,
        ]);
    }

    public function storeEvaluation(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $this->authorizeSubmissionForSupervisor($request, $submission);

        $submission->load(['projectGroup.members']);
        $presentationGroupGrading = $this->usesPresentationGroupGrading($submission);

        $allowedRubricIds = EvaluationRubric::forSupervisorGrading()->pluck('id')->all();

        $baseRules = [
            'evaluation_rubric_id' => ['required', 'integer', Rule::in($allowedRubricIds)],
            'status' => ['required', Rule::in(['draft', 'finalized'])],
        ];

        if ($presentationGroupGrading) {
            $validated = $request->validate(array_merge($baseRules, $this->nestedScoreRules('group'), $this->memberScoreRules($submission)));
            $saved = $this->persistPresentationGroupEvaluations($request, $submission, $validated);
        } else {
            $validated = $request->validate(array_merge($baseRules, $this->nestedScoreRules('scores', false)));
            $saved = [$this->persistSingleEvaluation($request, $submission, $validated, 'submission')];
        }

        if ($validated['status'] === 'finalized') {
            foreach ($saved as $evaluation) {
                $this->notifyFinalizedEvaluation($submission, $evaluation);
            }
        }

        $message = $validated['status'] === 'finalized'
            ? 'Evaluation(s) finalized successfully.'
            : 'Evaluation draft saved.';

        return redirect()
            ->route('supervisor.index')
            ->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function nestedScoreRules(string $prefix, bool $nested = true): array
    {
        $scoresKey = $nested ? "{$prefix}.scores" : $prefix;

        return [
            $scoresKey => ['required', 'array', 'min:1'],
            "{$scoresKey}.*.criterion" => ['required', 'string', 'max:120'],
            "{$scoresKey}.*.score" => ['required', 'numeric', 'min:0', 'max:100'],
            "{$scoresKey}.*.weight" => ['required', 'numeric', 'min:0', 'max:100'],
            "{$scoresKey}.*.comments" => ['nullable', 'string', 'max:1000'],
            ($nested ? "{$prefix}.general_comments" : 'general_comments') => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberScoreRules(ProjectSubmission $submission): array
    {
        $rules = [];
        $members = $submission->projectGroup?->members ?? collect();

        foreach ($members as $member) {
            $prefix = "members.{$member->id}";
            $rules = array_merge($rules, $this->nestedScoreRules($prefix));
        }

        $rules['members'] = ['required', 'array'];

        if ($members->isEmpty()) {
            throw ValidationException::withMessages([
                'members' => 'This presentation submission has no group members to grade individually.',
            ]);
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<StudentEvaluation>
     */
    private function persistPresentationGroupEvaluations(Request $request, ProjectSubmission $submission, array $validated): array
    {
        $saved = [];
        $saved[] = $this->persistScopedEvaluation(
            $request,
            $submission,
            $validated,
            'group',
            null,
            $validated['group'] ?? []
        );

        foreach ($submission->projectGroup?->members ?? [] as $member) {
            $memberPayload = $validated['members'][$member->id] ?? null;
            if ($memberPayload === null) {
                continue;
            }

            $saved[] = $this->persistScopedEvaluation(
                $request,
                $submission,
                $validated,
                'individual',
                (int) $member->id,
                $memberPayload
            );
        }

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistSingleEvaluation(Request $request, ProjectSubmission $submission, array $validated, string $scope): StudentEvaluation
    {
        $payload = [
            'scores' => $validated['scores'],
            'general_comments' => $validated['general_comments'] ?? null,
        ];

        return $this->persistScopedEvaluation(
            $request,
            $submission,
            $validated,
            $scope,
            $submission->student_id ? (int) $submission->student_id : null,
            $payload
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $scopePayload
     */
    private function persistScopedEvaluation(
        Request $request,
        ProjectSubmission $submission,
        array $validated,
        string $scope,
        ?int $studentId,
        array $scopePayload
    ): StudentEvaluation {
        $computed = EvaluationScoring::fromRows($scopePayload['scores'] ?? []);

        $lookup = [
            'project_submission_id' => $submission->id,
            'evaluator_id' => $request->user()->id,
            'evaluation_scope' => $scope,
        ];

        if ($scope === 'individual') {
            $lookup['student_id'] = $studentId;
        } elseif ($scope === 'group') {
            $lookup['student_id'] = null;
        } else {
            $lookup['student_id'] = $studentId;
        }

        $evaluation = StudentEvaluation::query()->updateOrCreate(
            $lookup,
            [
                'evaluation_rubric_id' => $validated['evaluation_rubric_id'],
                'student_id' => $scope === 'group' ? null : $studentId,
                'project_group_id' => $submission->project_group_id,
                'scores' => $computed['scores'],
                'total_score' => $computed['total_score'],
                'general_comments' => $scopePayload['general_comments'] ?? null,
                'status' => $validated['status'],
            ]
        );

        Audit::log(
            $request,
            'supervisor.evaluation_saved',
            'StudentEvaluation',
            (string) $evaluation->id,
            null,
            [
                'submission_id' => $submission->id,
                'evaluation_scope' => $scope,
                'student_id' => $studentId,
                'total_score' => $evaluation->total_score,
                'status' => $evaluation->status,
            ]
        );

        return $evaluation;
    }

    private function notifyFinalizedEvaluation(ProjectSubmission $submission, StudentEvaluation $evaluation): void
    {
        if ($evaluation->evaluation_scope === 'group') {
            PrmsEventNotifier::notifyEvaluationFinalized(
                $submission,
                (int) $evaluation->total_score,
                'Group presentation grade'
            );

            return;
        }

        if ($evaluation->evaluation_scope === 'individual' && $evaluation->student_id) {
            $student = User::query()->find($evaluation->student_id);
            if ($student) {
                PrmsEventNotifier::notifyStudentEvaluationScore(
                    $student,
                    $submission,
                    (int) $evaluation->total_score,
                    'Individual presentation grade'
                );
            }

            return;
        }

        PrmsEventNotifier::notifyEvaluationFinalized($submission, (int) $evaluation->total_score);
    }

    private function usesPresentationGroupGrading(ProjectSubmission $submission): bool
    {
        if ($submission->project_group_id === null) {
            return false;
        }

        return StudentStageProgress::isPresentationStage((string) $submission->stage);
    }

    /**
     * Shared authorization: a supervisor can only act on a submission
     * that belongs to a group or student they are assigned to.
     */
    private function authorizeSubmissionForSupervisor(Request $request, ProjectSubmission $submission): void
    {
        $supervisorId = $request->user()->id;

        $assigned = false;

        if ($submission->project_group_id) {
            $assigned = $submission->projectGroup()
                ->whereHas('supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                ->exists();
        } elseif ($submission->student_id) {
            $assigned = $submission->student()
                ->whereHas('studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                ->exists();
        }

        if (!$assigned) {
            abort(403, 'You are not assigned to this submission.');
        }
    }
}
