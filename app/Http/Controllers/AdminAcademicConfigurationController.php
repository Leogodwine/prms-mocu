<?php

namespace App\Http\Controllers;

use App\Enums\AcademicLevel;
use App\Enums\FinalYearRuleType;
use App\Enums\ProgramOutputType;
use App\Enums\WorkflowType;
use App\Models\AcademicLevelSetting;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use App\Support\Audit;
use App\Support\FinalYearWorkflowEngine;
use App\Support\StudentWorkflowAssigner;
use App\Support\WorkflowSettingsCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAcademicConfigurationController extends Controller
{
    public function index(): View
    {
        return view('admin.academic-configuration.index', $this->sharedViewData());
    }

    public function departments(): View
    {
        $data = $this->sharedViewData();
        $data['departments'] = Department::query()->withCount('programmes')->orderBy('department_name')->get();

        return view('admin.academic-configuration.departments', $data);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $this->normalizeDepartmentPayload($this->validateDepartment($request), $request);
        Department::query()->create($validated);

        Audit::log($request, 'admin.department_created', 'Department');

        return back()->with('status', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $this->normalizeDepartmentPayload($this->validateDepartment($request, $department->id), $request);
        $department->update($validated);

        Audit::log($request, 'admin.department_updated', 'Department', (string) $department->id);

        return back()->with('status', 'Department updated.');
    }

    public function destroyDepartment(Request $request, Department $department): RedirectResponse
    {
        if ($department->programmes()->exists()) {
            return back()->withErrors(['error' => 'Remove or reassign programmes before deleting this department.']);
        }

        $department->delete();
        Audit::log($request, 'admin.department_deleted', 'Department', (string) $department->id);

        return back()->with('status', 'Department deleted.');
    }

    public function programmes(): View
    {
        $data = $this->sharedViewData();
        $data['programmes'] = Program::query()->with('department')->orderBy('programme_code')->get();

        return view('admin.academic-configuration.programmes', $data);
    }

    public function storeProgramme(Request $request): RedirectResponse
    {
        $validated = $this->validateProgramme($request);
        Program::query()->create($this->normalizeProgrammePayload($validated, $request));

        Audit::log($request, 'admin.programme_created', 'Program');

        return back()->with('status', 'Programme created.');
    }

    public function updateProgramme(Request $request, Program $program): RedirectResponse
    {
        $validated = $this->validateProgramme($request, $program->id);
        $program->update($this->normalizeProgrammePayload($validated, $request));

        Audit::log($request, 'admin.programme_updated', 'Program', (string) $program->id);

        return back()->with('status', 'Programme updated.');
    }

    public function destroyProgramme(Request $request, Program $program): RedirectResponse
    {
        $program->delete();
        Audit::log($request, 'admin.programme_deleted', 'Program', (string) $program->id);

        return back()->with('status', 'Programme deleted.');
    }

    public function levels(): View
    {
        $data = $this->sharedViewData();
        $data['levelSettings'] = AcademicLevelSetting::query()->orderBy('academic_level')->get();

        return view('admin.academic-configuration.levels', $data);
    }

    public function updateLevel(Request $request, AcademicLevelSetting $academicLevelSetting): RedirectResponse
    {
        $validated = $request->validate([
            'final_year_default' => ['required', 'integer', 'min:1', 'max:8'],
            'final_stage_definition' => ['nullable', 'string', 'max:255'],
            'workflow_complexity' => ['required', 'in:simplified,standard,extended'],
            'default_output_type' => ['required', Rule::in(array_column(ProgramOutputType::cases(), 'value'))],
            'supports_project' => ['nullable', 'boolean'],
            'supports_research' => ['nullable', 'boolean'],
        ]);

        $academicLevelSetting->update([
            'final_year_default' => $validated['final_year_default'],
            'final_stage_definition' => $validated['final_stage_definition'],
            'workflow_complexity' => $validated['workflow_complexity'],
            'output_rules' => [
                'default_output_type' => $validated['default_output_type'],
                'supports_project' => $request->boolean('supports_project'),
                'supports_research' => $request->boolean('supports_research'),
            ],
        ]);

        Audit::log($request, 'admin.academic_level_updated', 'AcademicLevelSetting', (string) $academicLevelSetting->id);

        return back()->with('status', $academicLevelSetting->levelEnum()->label().' rules updated.');
    }

    public function preview(): View
    {
        $data = $this->sharedViewData();
        $data['students'] = User::query()
            ->whereIn('role', ['project_student', 'research_student', 'normal_student', 'student'])
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email', 'login_id', 'role', 'year_of_study', 'programme']);

        return view('admin.academic-configuration.preview', $data);
    }

    public function checkEligibility(Request $request): View
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        StudentWorkflowAssigner::syncForUser($user);
        $user->refresh();

        $data = $this->sharedViewData();
        $data['students'] = User::query()
            ->whereIn('role', ['project_student', 'research_student', 'normal_student', 'student'])
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email', 'login_id', 'role', 'year_of_study', 'programme']);
        $data['selectedUserId'] = $user->id;
        $data['evaluation'] = FinalYearWorkflowEngine::evaluateEligibility($user);

        return view('admin.academic-configuration.preview', $data);
    }

    public function reevaluate(Request $request): RedirectResponse
    {
        $count = StudentWorkflowAssigner::reevaluateAll();
        Audit::log($request, 'admin.student_workflows_reevaluated', 'User', null, null, ['count' => $count]);

        return back()->with('status', "Re-evaluated {$count} student account(s). Changes apply immediately.");
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedViewData(): array
    {
        return [
            'departmentsList' => Schema::hasTable('departments')
                ? Department::query()->orderBy('department_name')->get()
                : collect(),
            'academicLevels' => AcademicLevel::cases(),
            'outputTypes' => ProgramOutputType::cases(),
            'finalYearRuleTypes' => FinalYearRuleType::cases(),
            'workflowTypes' => WorkflowType::cases(),
            'workflowDefaults' => WorkflowSettingsCatalog::settings(),
            'stats' => [
                'departments' => Schema::hasTable('departments') ? Department::query()->count() : 0,
                'programmes' => Schema::hasTable('programmes') ? Program::query()->count() : 0,
                'students' => User::query()->whereIn('role', ['project_student', 'research_student', 'normal_student', 'student'])->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDepartment(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('departments', 'department_code');
        if ($ignoreId !== null) {
            $codeRule->ignore($ignoreId);
        }

        return $request->validate([
            'department_code' => ['required', 'string', 'max:20', $codeRule],
            'department_name' => ['required', 'string', 'max:100'],
            'head_of_department' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'default_programme_type' => ['nullable', 'string', 'max:40'],
            'supports_project' => ['nullable', 'boolean'],
            'supports_research' => ['nullable', 'boolean'],
            'final_year_rule_type' => ['required', Rule::in(array_column(FinalYearRuleType::cases(), 'value'))],
            'fixed_final_year' => ['nullable', 'integer', 'min:1', 'max:8', 'required_if:final_year_rule_type,FIXED_YEAR'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProgramme(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('programmes', 'programme_code');
        if ($ignoreId !== null) {
            $codeRule->ignore($ignoreId);
        }

        return $request->validate([
            'programme_code' => ['required', 'string', 'max:20', $codeRule],
            'programme_name' => ['required', 'string', 'max:100'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'academic_level' => ['required', Rule::in(array_column(AcademicLevel::cases(), 'value'))],
            'duration_years' => ['required', 'integer', 'min:1', 'max:8'],
            'final_year' => ['required', 'integer', 'min:1', 'max:8'],
            'allowed_project_years' => ['nullable', 'string'],
            'output_type' => ['required', Rule::in(array_column(ProgramOutputType::cases(), 'value'))],
            'workflow_type' => ['required', Rule::in(array_column(WorkflowType::cases(), 'value'))],
            'is_project_eligible' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeProgrammePayload(array $validated, Request $request): array
    {
        $outputType = ProgramOutputType::tryFromMixed($validated['output_type']);
        $years = $this->parseYearList($validated['allowed_project_years'] ?? null);

        return [
            'programme_code' => strtoupper(trim($validated['programme_code'])),
            'programme_name' => trim($validated['programme_name']),
            'department_id' => $validated['department_id'],
            'academic_level' => $validated['academic_level'],
            'duration_years' => $validated['duration_years'],
            'final_year' => $validated['final_year'],
            'project_year' => $validated['final_year'],
            'allowed_project_years' => $years !== [] ? $years : null,
            'output_type' => $validated['output_type'],
            'workflow_type' => $validated['workflow_type'],
            'is_project_eligible' => $request->boolean('is_project_eligible')
                || $outputType === ProgramOutputType::ProjectOnly
                || $outputType === ProgramOutputType::BothAllowed,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeDepartmentPayload(array $validated, Request $request): array
    {
        $validated['supports_project'] = $request->boolean('supports_project');
        $validated['supports_research'] = $request->boolean('supports_research');

        if (($validated['final_year_rule_type'] ?? '') !== FinalYearRuleType::FixedYear->value) {
            $validated['fixed_final_year'] = null;
        }

        return $validated;
    }

    /**
     * @return list<int>
     */
    private function parseYearList(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($part) => (int) trim($part),
            preg_split('/[\s,;]+/', $raw) ?: []
        ), static fn ($y) => $y >= 1 && $y <= 8)));
    }
}
