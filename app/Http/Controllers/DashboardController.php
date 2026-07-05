<?php

namespace App\Http\Controllers;

use App\Models\ProjectStage;
use App\Models\SupervisorAssignment;
use App\Support\FinalYearWorkflowEngine;
use App\Support\PrmsUserCapabilities;
use App\Support\StudentResearchEligibility;
use App\Support\StudentStageProgress;
use App\Support\PrmsAcademicCalendar;
use App\Support\SupervisorAssignmentScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $projectGroup = $user->projectGroups()->with('members')->first();
        $supervisorAssignment = null;

        if ($projectGroup) {
            $supervisorAssignment = $projectGroup->supervisorAssignment()->with('supervisor')->first();
        } elseif (in_array($user->role, ['project_student', 'research_student'], true)) {
            $supervisorAssignment = SupervisorAssignment::query()
                ->with('supervisor')
                ->where('student_id', $user->id)
                ->first();
        }

        $allStages = ProjectStage::query()->orderBy('stage_order')->get();
        $latestByStage = StudentStageProgress::latestSubmissionByStage($user, $projectGroup);

        $proposalSummary = StudentStageProgress::summarizeTrack(
            StudentStageProgress::stagesForTrack($allStages, 'proposal'),
            $latestByStage
        );
        $researchSummary = StudentStageProgress::summarizeTrack(
            StudentStageProgress::stagesForTrack($allStages, 'research'),
            $latestByStage
        );
        $projectSummary = StudentStageProgress::summarizeTrack(
            StudentStageProgress::stagesForTrack($allStages, 'project'),
            $latestByStage
        );

        $supervisorAssignments = $user->role === 'supervisor'
            ? SupervisorAssignmentScope::forSupervisor($user->id)
            : null;

        return view('dashboard', [
            'user' => $user,
            'projectGroup' => $projectGroup,
            'supervisorAssignment' => $supervisorAssignment,
            'supervisorAssignments' => $supervisorAssignments,
            'availableTracks' => StudentResearchEligibility::availableTracks($user),
            'canCreateProjects' => PrmsUserCapabilities::canEnterStudentWorkflow($user),
            'workflowBlockReason' => FinalYearWorkflowEngine::workflowBlockReason($user),
            'studentAcademic' => StudentResearchEligibility::academicContext($user),
            'proposalProgress' => $proposalSummary['percent'],
            'researchProgress' => $researchSummary['percent'],
            'projectProgress' => $projectSummary['percent'],
            'proposalApproved' => $proposalSummary['approved'],
            'researchApproved' => $researchSummary['approved'],
            'projectApproved' => $projectSummary['approved'],
            'proposalInProgress' => $proposalSummary['in_progress'],
            'researchInProgress' => $researchSummary['in_progress'],
            'projectInProgress' => $projectSummary['in_progress'],
            'proposalTotal' => $proposalSummary['total'],
            'researchTotal' => $researchSummary['total'],
            'projectTotal' => $projectSummary['total'],
            'academicCalendar' => in_array($user->role, ['project_student', 'research_student', 'normal_student'], true)
                ? PrmsAcademicCalendar::forUser($user, $projectGroup)
                : null,
        ]);
    }
}
