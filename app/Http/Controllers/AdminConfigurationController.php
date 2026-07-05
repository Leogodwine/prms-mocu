<?php

namespace App\Http\Controllers;

use App\Enums\AcademicLevel;
use App\Enums\ProgramOutputType;
use App\Enums\WorkflowType;
use App\Models\Department;
use App\Models\DepartmentWorkflowRule;
use App\Models\Program;
use App\Models\SystemConfiguration;
use App\Support\Audit;
use App\Support\PrmsTablePagination;
use App\Support\ProgrammeWorkflowPolicy;
use App\Support\StudentWorkflowAssigner;
use App\Support\WorkflowSettingsCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $configs = SystemConfiguration::query()->orderBy('category')->orderBy('config_key')->get();

        $departments = Schema::hasTable('departments')
            ? Department::query()->orderBy('department_name')->get()
            : collect();

        $programmes = Schema::hasTable('programmes')
            ? Program::query()->with('department')->orderBy('programme_code')
                ->paginate(PrmsTablePagination::perPage($request), ['*'], 'programmes_page')
                ->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, PrmsTablePagination::DEFAULT);

        $departmentRules = Schema::hasTable('department_workflow_rules')
            ? DepartmentWorkflowRule::query()->with('department')->orderBy('department_id')
                ->paginate(PrmsTablePagination::perPage($request), ['*'], 'rules_page')
                ->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, PrmsTablePagination::DEFAULT);

        return view('admin.configuration', [
            'configs' => $configs,
            'departments' => $departments,
            'programmes' => $programmes,
            'programmesTotal' => $programmes->total(),
            'departmentRules' => $departmentRules,
            'departmentRulesTotal' => $departmentRules->total(),
            'workflowDefaults' => WorkflowSettingsCatalog::settings(),
            'academicLevels' => AcademicLevel::cases(),
            'outputTypes' => ProgramOutputType::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'configs' => ['required', 'array'],
            'configs.academic_year' => ['required', 'string', 'max:40'],
            'configs.project_cycle' => ['required', 'string', 'max:80'],
            'configs.deadline_proposal' => ['required', 'date'],
            'configs.deadline_final' => ['required', 'date'],
            'configs.eligibility_min_year' => ['required', 'integer', 'min:1', 'max:8'],
            'configs.calendar_announcements' => ['nullable', 'string', 'max:10000'],
        ]);

        foreach ($validated['configs'] as $key => $value) {
            $existing = SystemConfiguration::query()->where('config_key', $key)->first();

            SystemConfiguration::query()->updateOrCreate(
                ['config_key' => $key],
                [
                    'config_value' => (string) $value,
                    'config_type' => 'string',
                    'category' => $existing?->category ?? match ($key) {
                        'academic_year', 'project_cycle' => 'lifecycle',
                        'deadline_proposal', 'deadline_final' => 'deadlines',
                        'calendar_announcements' => 'calendar',
                        'eligibility_min_year' => 'eligibility',
                        default => 'general',
                    },
                    'description' => $existing?->description,
                ]
            );
        }

        Audit::log($request, 'admin.configuration_updated', 'SystemConfiguration');

        return back()->with('status', 'General system settings saved.');
    }

    public function updateWorkflowDefaults(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_academic_level' => ['required', Rule::in(array_column(AcademicLevel::cases(), 'value'))],
            'default_workflow_type' => ['required', Rule::in(array_column(WorkflowType::cases(), 'value'))],
            'final_year' => ['required', 'array'],
            'final_year.diploma' => ['required', 'integer', 'min:1', 'max:8'],
            'final_year.bachelor' => ['required', 'integer', 'min:1', 'max:8'],
            'final_year.masters' => ['required', 'integer', 'min:1', 'max:8'],
            'final_year.phd' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        WorkflowSettingsCatalog::saveSettings($validated);

        Audit::log($request, 'admin.workflow_defaults_updated', 'SystemConfiguration');

        return back()->with('status', 'Workflow defaults saved.');
    }

    public function updateProgramme(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'academic_level' => ['required', Rule::in(array_column(AcademicLevel::cases(), 'value'))],
            'duration_years' => ['nullable', 'integer', 'min:1', 'max:8'],
            'final_year' => ['required', 'integer', 'min:1', 'max:8'],
            'output_type' => ['required', Rule::in(array_column(ProgramOutputType::cases(), 'value'))],
            'workflow_type' => ['required', Rule::in(array_column(WorkflowType::cases(), 'value'))],
            'is_project_eligible' => ['nullable', 'boolean'],
        ]);

        $outputType = ProgramOutputType::tryFromMixed($validated['output_type']);
        $isProjectEligible = $outputType !== ProgramOutputType::None && (
            $request->boolean('is_project_eligible')
            || $outputType === ProgramOutputType::ProjectOnly
            || $outputType === ProgramOutputType::BothAllowed
        );

        $payload = ProgrammeWorkflowPolicy::applyToProgrammePayload([
            'academic_level' => $validated['academic_level'],
            'duration_years' => $validated['duration_years'] ?? $validated['final_year'],
            'final_year' => $validated['final_year'],
            'project_year' => $validated['final_year'],
            'output_type' => $validated['output_type'],
            'workflow_type' => $validated['workflow_type'],
            'is_project_eligible' => $isProjectEligible,
        ], $program->programme_code);

        $program->update($payload);

        Audit::log($request, 'admin.programme_workflow_updated', 'Program', (string) $program->id, null, [
            'programme_code' => $program->programme_code,
            'final_year' => $program->final_year,
            'output_type' => $program->output_type,
        ]);

        return back()->with('status', "Programme {$program->programme_code} workflow rules updated.");
    }

    public function storeDepartmentRule(Request $request): RedirectResponse
    {
        $validated = $this->validateDepartmentRule($request);

        DepartmentWorkflowRule::query()->updateOrCreate(
            [
                'department_id' => $validated['department_id'],
                'academic_level' => $validated['academic_level'],
            ],
            [
                'final_year' => $validated['final_year'],
                'output_type' => ProgrammeWorkflowPolicy::normalizeDepartmentRuleOutputType(
                    $validated['academic_level'],
                    $validated['output_type'],
                ),
                'workflow_type' => $validated['workflow_type'],
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        Audit::log($request, 'admin.department_workflow_rule_saved', 'DepartmentWorkflowRule');

        return back()->with('status', 'Department workflow rule saved.');
    }

    public function updateDepartmentRule(Request $request, DepartmentWorkflowRule $departmentWorkflowRule): RedirectResponse
    {
        $validated = $this->validateDepartmentRule($request, $departmentWorkflowRule->id);

        $departmentWorkflowRule->update([
            'department_id' => $validated['department_id'],
            'academic_level' => $validated['academic_level'],
            'final_year' => $validated['final_year'],
            'output_type' => ProgrammeWorkflowPolicy::normalizeDepartmentRuleOutputType(
                $validated['academic_level'],
                $validated['output_type'],
            ),
            'workflow_type' => $validated['workflow_type'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        Audit::log($request, 'admin.department_workflow_rule_updated', 'DepartmentWorkflowRule', (string) $departmentWorkflowRule->id);

        return back()->with('status', 'Department workflow rule updated.');
    }

    public function destroyDepartmentRule(Request $request, DepartmentWorkflowRule $departmentWorkflowRule): RedirectResponse
    {
        $departmentWorkflowRule->delete();

        Audit::log($request, 'admin.department_workflow_rule_deleted', 'DepartmentWorkflowRule', (string) $departmentWorkflowRule->id);

        return back()->with('status', 'Department workflow rule removed.');
    }

    public function reevaluateWorkflows(Request $request): RedirectResponse
    {
        $count = StudentWorkflowAssigner::reevaluateAll();

        Audit::log($request, 'admin.student_workflows_reevaluated', 'User', null, null, ['count' => $count]);

        return back()->with('status', "Re-evaluated workflow roles for {$count} student account(s).");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDepartmentRule(Request $request, ?int $ignoreRuleId = null): array
    {
        $uniqueRule = Rule::unique('department_workflow_rules', 'academic_level')
            ->where('department_id', $request->input('department_id'));

        if ($ignoreRuleId !== null) {
            $uniqueRule->ignore($ignoreRuleId, 'id');
        }

        return $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'academic_level' => ['required', Rule::in(array_column(AcademicLevel::cases(), 'value')), $uniqueRule],
            'final_year' => ['required', 'integer', 'min:1', 'max:8'],
            'output_type' => ['required', Rule::in(array_column(ProgramOutputType::cases(), 'value'))],
            'workflow_type' => ['required', Rule::in(array_column(WorkflowType::cases(), 'value'))],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
