<?php

namespace App\Support;

use App\Models\ResearchProject;
use App\Models\User;

/**
 * UI and route capability checks derived from workflow eligibility and ownership.
 */
final class PrmsUserCapabilities
{
    public static function canEnterStudentWorkflow(User $user): bool
    {
        return FinalYearWorkflowEngine::mustStartWithProposal($user);
    }

    public static function canAccessWorkspaceTrack(User $user, string $track): bool
    {
        return StudentResearchEligibility::hasTrack($user, $track);
    }

    public static function canManageProjectContributors(User $user, ResearchProject $project): bool
    {
        if ((int) $project->student_id !== (int) $user->id) {
            return false;
        }

        if (! $user->isStudentUser() || ! self::canAccessWorkspaceTrack($user, 'project')) {
            return false;
        }

        return $project->isComputerBasedProject();
    }

    public static function isProjectContributor(User $user, ResearchProject $project): bool
    {
        if (! $project->relationLoaded('contributors')) {
            return $project->contributors()->where('users.id', $user->id)->exists();
        }

        return $project->contributors->contains('id', $user->id);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function isNavItemVisible(User $user, array $item): bool
    {
        if (! $user->isStudentUser()) {
            return true;
        }

        if (($item['nav_capability'] ?? null) === 'create_project') {
            return self::canEnterStudentWorkflow($user);
        }

        $track = $item['workspace_track'] ?? null;
        if (is_string($track) && $track !== '') {
            return self::canAccessWorkspaceTrack($user, $track);
        }

        return true;
    }
}
