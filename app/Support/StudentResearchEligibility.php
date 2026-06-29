<?php

namespace App\Support;

use App\Enums\AcademicLevel;
use App\Enums\ProgramOutputType;
use App\Models\Program;
use App\Models\User;

/**
 * Backward-compatible facade over {@see FinalYearWorkflowEngine}.
 */
final class StudentResearchEligibility
{
    /**
     * @return list<string>
     */
    public static function availableTracks(User $user): array
    {
        return FinalYearWorkflowEngine::availableTracks($user);
    }

    public static function hasTrack(User $user, string $track): bool
    {
        return FinalYearWorkflowEngine::hasTrack($user, $track);
    }

    public static function resolveProgramme(User $user): ?Program
    {
        return FinalYearWorkflowEngine::resolveProgramme($user);
    }

    public static function yearOfStudy(User $user): ?int
    {
        return FinalYearWorkflowEngine::yearOfStudy($user);
    }

    public static function researchYear(?Program $programme): int
    {
        if ($programme === null) {
            return (int) config('prms.workflow.default_final_year.bachelor', 3);
        }

        if ($programme->final_year !== null) {
            return (int) $programme->final_year;
        }

        if ($programme->project_year !== null) {
            return (int) $programme->project_year;
        }

        if ($programme->duration_years !== null) {
            return (int) $programme->duration_years;
        }

        $level = AcademicLevel::tryFromMixed($programme->academic_level ?? 'bachelor')->value;

        return (int) config("prms.workflow.default_final_year.{$level}", 3);
    }

    public static function includesProjectTrack(?Program $programme): bool
    {
        if ($programme === null) {
            return false;
        }

        $outputType = ProgramOutputType::tryFromMixed($programme->output_type ?? null);

        if ($outputType === ProgramOutputType::None) {
            return false;
        }

        if ($outputType === ProgramOutputType::ProjectOnly || $outputType === ProgramOutputType::BothAllowed) {
            return true;
        }

        return (bool) $programme->is_project_eligible;
    }

    public static function isInResearchYear(User $user): bool
    {
        return FinalYearWorkflowEngine::isFinalYearEligible($user);
    }

    public static function researchYearBlockReason(User $user): ?string
    {
        return FinalYearWorkflowEngine::workflowBlockReason($user);
    }

    /**
     * @return array<string, mixed>
     */
    public static function academicContext(User $user): array
    {
        return FinalYearWorkflowEngine::academicContext($user);
    }
}
