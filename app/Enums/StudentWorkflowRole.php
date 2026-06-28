<?php

namespace App\Enums;

enum StudentWorkflowRole: string
{
    case NoAccess = 'NO_ACCESS';
    case ViewerOnly = 'VIEWER_ONLY';
    case FinalYearStudent = 'FINAL_YEAR_STUDENT';
    case ResearchCandidate = 'RESEARCH_CANDIDATE';
    case ProjectCandidate = 'PROJECT_CANDIDATE';

    public function label(): string
    {
        return match ($this) {
            self::NoAccess => 'No access',
            self::ViewerOnly => 'Viewer only',
            self::FinalYearStudent => 'Final-year student',
            self::ResearchCandidate => 'Research candidate',
            self::ProjectCandidate => 'Project candidate',
        };
    }

    public function canEnterWorkflow(): bool
    {
        return in_array($this, [
            self::FinalYearStudent,
            self::ResearchCandidate,
            self::ProjectCandidate,
        ], true);
    }
}
