<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\ResearchProject;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Notifications\ProjectNotification;
use App\Support\PrmsEventNotifier;
use App\Support\Audit;
use App\Support\ProjectSimilarityQueue;
use App\Support\PrmsUserCapabilities;
use App\Support\PrmsTablePagination;
use App\Support\StudentResearchEligibility;
use App\Support\StudentWorkflowAssigner;
use App\Services\Similarity\ProjectSimilarityAnalyzer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * FR-02: Project, research report, and proposal management workspace.
 *
 * Lets students create proposal, dissertation, thesis, or project
 * report records, attach an optional document, and persist version
 * history. The store flow generates a tracking number, files an
 * audit history entry, and (when "Submitted") creates document and
 * submission records that flow into the supervisor review queue.
 */
class ProjectController extends Controller
{
    private const CREATOR_ROLES = User::STUDENT_ROLES;

    public function index(Request $request): View
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'coordinator', 'hod'], true)) {
            abort(403, 'This workspace is for students and supervisors only.');
        }

        $projects = ResearchProject::query()
            ->where(function ($q) use ($user) {
                $q->where('student_id', $user->id);

                if (Schema::hasColumn('research_projects', 'supervisor_id')) {
                    $q->orWhere('supervisor_id', $user->id);
                }
                if (Schema::hasColumn('research_projects', 'co_supervisor_id')) {
                    $q->orWhere('co_supervisor_id', $user->id);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        return view('projects.index', array_merge(
            [
                'title' => 'Projects',
                'projects' => $projects,
                'canCreateProjects' => PrmsUserCapabilities::canEnterStudentWorkflow($user),
            ],
            $this->createProjectFormData()
        ));
    }

    /**
     * Full project / proposal idea record (problem statement, title, student)
     * for the owner, assigned supervisors, and oversight roles.
     */
    public function show(Request $request, ResearchProject $researchProject): View
    {
        $this->authorizeResearchProjectAccess($request->user(), $researchProject);

        $researchProject->load(['student', 'supervisor', 'contributors']);

        $canViewSimilarity = in_array($request->user()->role, ['admin', 'coordinator'], true);
        $similarProjects = $canViewSimilarity && Schema::hasTable('project_similarities')
            ? app(ProjectSimilarityAnalyzer::class)->similarProjectsFor($researchProject)
            : collect();

        $user = $request->user();
        $projectGroup = $user->projectGroups()->with('members')->first();
        $existingContributorIds = $researchProject->contributors->pluck('id')->all();
        $eligibleContributors = collect();

        if (PrmsUserCapabilities::canManageProjectContributors($user, $researchProject) && $projectGroup) {
            $eligibleContributors = $projectGroup->members
                ->reject(fn ($member) => (int) $member->id === (int) $user->id
                    || in_array((int) $member->id, $existingContributorIds, true));
        }

        return view('projects.show', [
            'project' => $researchProject,
            'similarProjects' => $similarProjects,
            'showSimilarityPanel' => $canViewSimilarity,
            'canRerunSimilarity' => $request->user()->role === 'admin',
            'ollamaReachable' => $canViewSimilarity ? app(\App\Services\Ollama\OllamaClient::class)->isReachable() : true,
            'canManageContributors' => PrmsUserCapabilities::canManageProjectContributors($user, $researchProject),
            'eligibleContributors' => $eligibleContributors,
        ]);
    }

    /**
     * Legacy URL: open the create form in a modal on the projects index.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('projects.index', ['modal' => 'create']);
    }

    /**
     * @return array<string, mixed>
     */
    private function createProjectFormData(): array
    {
        $faculties = Schema::hasTable('faculties')
            ? Faculty::orderBy('faculty_name')->get()
            : collect();
        $departments = Schema::hasTable('departments')
            ? Department::orderBy('department_name')->get()
            : collect();
        $programs = Schema::hasTable('programmes')
            ? Program::orderBy('programme_name')->get()
            : collect();
        $academicYears = Schema::hasTable('academic_years')
            ? AcademicYear::orderByDesc('year_name')->get()
            : collect();
        $semesters = Schema::hasTable('semesters')
            ? Semester::orderByDesc('semester_number')->get()
            : collect();

        $currentYear = $academicYears->firstWhere('is_current', true);
        $currentSemester = $semesters->firstWhere('is_current', true);

        $supervisors = collect();
        if (Schema::hasTable('roles')) {
            $supervisorRoleId = Role::query()->where('role_name', 'Supervisor')->value('id');
            if ($supervisorRoleId && Schema::hasColumn('users', 'role_id')) {
                $supervisors = User::query()
                    ->where('role_id', $supervisorRoleId)
                    ->orderBy('name')
                    ->get();
            }
        }
        if ($supervisors->isEmpty()) {
            $supervisors = User::query()
                ->where('role', 'supervisor')
                ->orderBy('name')
                ->get();
        }

        $studentAcademic = null;
        $user = Auth::user();
        if ($user && $user->isStudentUser()) {
            $studentAcademic = StudentResearchEligibility::academicContext($user);
        }

        return [
            'faculties' => $faculties,
            'departments' => $departments,
            'programs' => $programs,
            'academicYears' => $academicYears,
            'semesters' => $semesters,
            'currentYear' => $currentYear,
            'currentSemester' => $currentSemester,
            'supervisors' => $supervisors,
            'studentAcademic' => $studentAcademic,
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, self::CREATOR_ROLES, true)) {
            abort(403, 'You are not allowed to create a project.');
        }

        $submissionMode = $request->input('submission_mode', 'Draft');

        if ($blockReason = StudentResearchEligibility::researchYearBlockReason($user)) {
            return back()->withInput()->withErrors(['error' => $blockReason]);
        }

        $academic = StudentResearchEligibility::academicContext($user);

        // HTML select empty option posts ""; normalize to null for validation.
        $request->merge([
            'supervisor_id' => $request->input('supervisor_id') ?: null,
            'co_supervisor_id' => $request->input('co_supervisor_id') ?: null,
            'department_id' => $academic['department']?->id,
            'program_id' => $academic['programme']?->id,
        ]);

        $baseRules = [
            'project_type' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'research_area' => ['nullable', 'string', 'max:200'],
            'submission_deadline' => ['nullable', 'date'],
            'collaboration_enabled' => ['nullable', 'boolean'],
            'preview_enabled' => ['nullable', 'boolean'],
        ];

        $baseRules['faculty_id'] = Schema::hasTable('faculties')
            ? ['nullable', 'integer', 'exists:faculties,id']
            : ['nullable', 'integer'];
        $baseRules['department_id'] = Schema::hasTable('departments')
            ? ['nullable', 'integer', 'exists:departments,id']
            : ['nullable', 'integer'];
        $baseRules['program_id'] = Schema::hasTable('programmes')
            ? ['nullable', 'integer', 'exists:programmes,id']
            : ['nullable', 'integer'];
        $baseRules['academic_year_id'] = Schema::hasTable('academic_years')
            ? ['nullable', 'integer', 'exists:academic_years,id']
            : ['nullable', 'integer'];
        $baseRules['semester_id'] = Schema::hasTable('semesters')
            ? ['nullable', 'integer', 'exists:semesters,id']
            : ['nullable', 'integer'];

        $baseRules['supervisor_id'] = ['nullable', 'integer', 'exists:users,id'];
        $baseRules['co_supervisor_id'] = ['nullable', 'integer', 'exists:users,id'];

        $submissionRules = [];
        if ($submissionMode === 'Submitted') {
            $submissionRules['proposal_document'] = ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx'];
        }

        if ($submissionMode !== 'Draft' && $submissionMode !== 'Submitted') {
            $submissionMode = 'Draft';
        }

        $validated = $request->validate(array_merge($baseRules, $submissionRules));

        $projectCode = 'PRJ-'.now()->format('Y').'-'.(string) random_int(1000, 9999);

        $payload = [
            'student_id' => Auth::id(),
            'title' => $validated['title'],
            'abstract' => $validated['abstract'] ?? null,
            'status' => 'ongoing',
        ];

        $optional = [
            'project_code' => $projectCode,
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'co_supervisor_id' => $validated['co_supervisor_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'faculty_id' => $validated['faculty_id'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'semester_id' => $validated['semester_id'] ?? null,
            'project_type' => $validated['project_type'],
            'keywords' => $validated['keywords'] ?? null,
            'research_area' => $validated['research_area'] ?? null,
            'current_stage' => $submissionMode,
            'submission_deadline' => $validated['submission_deadline'] ?? null,
            'preview_enabled' => $validated['preview_enabled'] ?? true,
            'collaboration_enabled' => $validated['collaboration_enabled'] ?? true,
        ];

        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('research_projects', $column)) {
                $payload[$column] = $value;
            }
        }

        $project = ResearchProject::create($payload);

        StudentWorkflowAssigner::assignOutputTrackFromProjectType($user, $validated['project_type'] ?? null);

        ProjectSimilarityQueue::dispatchFor($project);

        if (Schema::hasTable('project_history')) {
            $project->project_history()->create([
                'action' => $submissionMode === 'Submitted' ? 'Submitted' : 'DraftSaved',
                'previous_stage' => null,
                'new_stage' => $submissionMode,
                'action_by' => Auth::id(),
                'action_reason' => null,
                'action_notes' => null,
                'ip_address' => $request->ip(),
            ]);
        }

        if ($submissionMode === 'Submitted' && $request->hasFile('proposal_document')) {
            $file = $request->file('proposal_document');

            $storedRelativePath = $file->storeAs(
                'proposal_documents/'.$projectCode,
                $file->getClientOriginalName(),
                'public'
            );

            $fileHash = hash_file('sha256', $file->getRealPath());

            $this->insertIfSchemaMatches('documents', [
                'project_id' => $project->id,
                'document_type' => 'Proposal',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedRelativePath,
                'preview_file_path' => null,
                'searchable_text' => null,
                'file_size' => (int) $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_hash' => $fileHash,
                'version_number' => 1,
                'annotation_enabled' => true,
                'collaboration_enabled' => true,
                'is_current_version' => true,
                'uploaded_by' => Auth::id(),
                'upload_date' => now(),
                'description' => null,
                'metadata_json' => null,
                'ai_summary' => null,
                'ai_keywords' => null,
                'is_public' => false,
                'embargo_until' => null,
                'download_count' => 0,
            ]);

            $proposalStageId = Schema::hasTable('project_stages')
                ? DB::table('project_stages')->orderBy('stage_order')->value('id')
                : null;

            $this->insertIfSchemaMatches('submissions', [
                'research_project_id' => $project->id,
                'project_id' => $project->id,
                'stage_id' => $proposalStageId,
                'submission_type' => 'Proposal',
                'submission_stage' => 1,
                'version' => 1,
                'version_number' => 1,
                'file_path' => $storedRelativePath,
                'document_path' => $storedRelativePath,
                'file_name' => $file->getClientOriginalName(),
                'document_name' => $file->getClientOriginalName(),
                'preview_path' => null,
                'file_size' => (int) $file->getSize(),
                'document_size' => (int) $file->getSize(),
                'file_type' => $file->getMimeType(),
                'plagiarism_score' => null,
                'review_status' => 'Pending',
                'total_comments' => 0,
                'status' => 'submitted',
                'submitted_at' => now(),
                'submission_date' => now(),
                'submitted_by' => Auth::id(),
                'ip_address' => $request->ip(),
                'is_current' => true,
            ]);

            $this->insertIfSchemaMatches('project_versions', [
                'project_id' => $project->id,
                'version_number' => 1,
                'version_note' => 'Initial submitted version',
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
                'total_comments' => 0,
                'total_annotations' => 0,
                'is_current' => true,
            ], requiredColumns: ['project_id', 'version_number']);
        }

        return redirect()->route('projects.index')->with('status', 'Project '.$submissionMode.' saved with tracking number '.$projectCode.'.');
    }

    /**
     * Dashboard quick-start: student submits a working proposal name, full
     * title, and problem statement so the assigned supervisor can review
     * the research / project focus before chapter-level document uploads.
     */
    public function storeProblemProposal(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isStudentUser()) {
            abort(403, 'Only students can register a new proposal or project idea.');
        }

        $validated = $request->validate([
            'work_kind' => ['required', Rule::in(['proposal', 'project'])],
            'proposal_name' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:500'],
            'problem_statement' => ['required', 'string', 'min:40', 'max:8000'],
        ]);

        if ($blockReason = StudentResearchEligibility::researchYearBlockReason($user)) {
            return back()->withInput()->withErrors(['work_kind' => $blockReason]);
        }

        if ($validated['work_kind'] === 'project'
            && ! StudentResearchEligibility::hasTrack($user, 'project')) {
            return back()->withInput()->withErrors([
                'work_kind' => 'Computer-based project work is only available for diploma programmes.',
            ]);
        }

        $projectCode = 'PRJ-'.now()->format('Y').'-'.(string) random_int(1000, 9999);

        $supervisorId = $this->resolveAssignedSupervisorId($user);

        $projectTypeLabel = $validated['work_kind'] === 'project'
            ? 'Computer-based project'
            : 'Research proposal';

        $payload = [
            'student_id' => $user->id,
            'title' => $validated['title'],
            'abstract' => $validated['problem_statement'],
            'status' => 'ongoing',
        ];

        $optional = [
            'project_code' => $projectCode,
            'supervisor_id' => $supervisorId,
            'project_type' => $projectTypeLabel,
            'keywords' => $validated['proposal_name'],
            'current_stage' => 'ProblemProposalSubmitted',
        ];

        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('research_projects', $column)) {
                $payload[$column] = $value;
            }
        }

        $projectGroup = $user->projectGroups()->first();
        if ($projectGroup && Schema::hasColumn('research_projects', 'project_group_id')) {
            $payload['project_group_id'] = $projectGroup->id;
        }

        $project = ResearchProject::create($payload);

        ProjectSimilarityQueue::dispatchFor($project);

        Audit::log(
            $request,
            'research_project.problem_proposal_submitted',
            'ResearchProject',
            (string) $project->id,
            null,
            [
                'project_code' => $projectCode,
                'work_kind' => $validated['work_kind'],
                'proposal_name' => $validated['proposal_name'],
            ]
        );

        $workspace = $validated['work_kind'] === 'project' ? 'project' : 'proposal';
        $studentWorkspaceUrl = route('student.index', ['type' => $workspace]);

        $supervisor = $supervisorId ? User::find($supervisorId) : null;

        if ($supervisor) {
            PrmsEventNotifier::notify(
                $supervisor,
                'New proposal / project idea — '.$validated['proposal_name'],
                $user->name.' registered '.$projectCode.' with a proposed problem and title for your review: '.$validated['title'],
                route('projects.show', $project),
                'View full details'
            );
        }

        $studentBody = $supervisor
            ? 'Your '.$projectTypeLabel.' idea was saved as '.$projectCode.'. '.$supervisor->name.' has been notified to review your problem statement and working title.'
            : 'Your '.$projectTypeLabel.' idea was saved as '.$projectCode.'. You do not have an assigned supervisor yet — please contact your coordinator.';

        PrmsEventNotifier::notify(
            $user,
            'Idea registered — '.$projectCode,
            $studentBody,
            $studentWorkspaceUrl,
            'Open workspace'
        );

        $msg = 'Your idea was registered as '.$projectCode.'. ';
        $msg .= $supervisor
            ? 'Your supervisor has been notified to review your problem and title.'
            : 'You do not have an assigned supervisor yet — please contact your coordinator.';

        return redirect()
            ->route('student.index', ['type' => $workspace])
            ->with('status', $msg);
    }

    /**
     * Supervisor linked to this student directly, or via their project group.
     */
    private function resolveAssignedSupervisorId(User $user): ?int
    {
        $direct = SupervisorAssignment::query()
            ->where('student_id', $user->id)
            ->value('supervisor_id');

        if ($direct) {
            return (int) $direct;
        }

        $groupIds = DB::table('project_group_members')
            ->where('student_id', $user->id)
            ->pluck('project_group_id');
        if ($groupIds->isEmpty()) {
            return null;
        }

        $fromGroup = SupervisorAssignment::query()
            ->whereIn('project_group_id', $groupIds)
            ->whereNotNull('supervisor_id')
            ->value('supervisor_id');

        return $fromGroup ? (int) $fromGroup : null;
    }

    private function authorizeResearchProjectAccess(User $user, ResearchProject $project): void
    {
        if (in_array($user->role, ['admin', 'coordinator', 'hod'], true)) {
            return;
        }

        if ((int) $project->student_id === (int) $user->id) {
            return;
        }

        if (Schema::hasTable('research_project_contributors')
            && PrmsUserCapabilities::isProjectContributor($user, $project)) {
            return;
        }

        if (Schema::hasColumn('research_projects', 'supervisor_id')
            && $project->supervisor_id
            && (int) $project->supervisor_id === (int) $user->id) {
            return;
        }

        if (Schema::hasColumn('research_projects', 'co_supervisor_id')
            && $project->co_supervisor_id
            && (int) $project->co_supervisor_id === (int) $user->id) {
            return;
        }

        if ($user->role === 'supervisor') {
            $studentId = $project->student_id;
            if ($studentId) {
                $assignedStudent = SupervisorAssignment::query()
                    ->where('supervisor_id', $user->id)
                    ->where('student_id', $studentId)
                    ->exists();
                if ($assignedStudent) {
                    return;
                }

                $groupIds = DB::table('project_group_members')
                    ->where('student_id', $studentId)
                    ->pluck('project_group_id');
                if ($groupIds->isNotEmpty()) {
                    $assignedGroup = SupervisorAssignment::query()
                        ->where('supervisor_id', $user->id)
                        ->whereIn('project_group_id', $groupIds)
                        ->exists();
                    if ($assignedGroup) {
                        return;
                    }
                }
            }
        }

        throw new AccessDeniedHttpException('You are not allowed to view this project.');
    }

    /**
     * Insert a row only if the target table exists and its schema can
     * accept the payload. The payload is filtered down to columns that
     * actually exist on the table; any columns listed in $requiredColumns
     * must be present on the table or the insert is skipped silently.
     *
     * Defensive insertion keeps FR-02 store flow working across
     * partially-migrated environments where ancillary tracking tables
     * (e.g. submissions, project_versions) may carry alternate schemas.
     */
    private function insertIfSchemaMatches(string $table, array $payload, array $requiredColumns = []): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        $filtered = [];
        foreach ($payload as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        if ($filtered === []) {
            return;
        }

        $now = now();
        if (Schema::hasColumn($table, 'created_at') && ! array_key_exists('created_at', $filtered)) {
            $filtered['created_at'] = $now;
        }
        if (Schema::hasColumn($table, 'updated_at') && ! array_key_exists('updated_at', $filtered)) {
            $filtered['updated_at'] = $now;
        }

        DB::table($table)->insert($filtered);
    }
}
