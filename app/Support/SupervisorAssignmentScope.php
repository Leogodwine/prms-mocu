<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves a supervisor's assigned groups and individual students.
 */
final class SupervisorAssignmentScope
{
    /**
     * @return array{
     *     groups: Collection<int, ProjectGroup>,
     *     individuals: Collection<int, User>,
     *     group_count: int,
     *     individual_count: int,
     *     total_count: int
     * }
     */
    public static function forSupervisor(int $supervisorId): array
    {
        $groups = ProjectGroup::query()
            ->whereHas('supervisorAssignment', fn ($query) => $query->where('supervisor_id', $supervisorId))
            ->with(['members.studentProfile.programme'])
            ->withCount('members')
            ->orderBy('name')
            ->get();

        $directIndividuals = User::query()
            ->whereHas('studentAssignment', fn ($query) => $query->where('supervisor_id', $supervisorId))
            ->with(['studentProfile.programme'])
            ->orderBy('name')
            ->get();

        $groupAssignments = $groups
            ->filter(fn (ProjectGroup $group) => $group->members_count > 1)
            ->values();

        $individualUsers = $directIndividuals->keyBy('id');

        foreach ($groups as $group) {
            if ($group->members_count > 1) {
                continue;
            }

            $member = $group->members->first();
            if ($member !== null) {
                $individualUsers->put($member->id, $member);
            }
        }

        $individuals = $individualUsers->values();

        return [
            'groups' => $groupAssignments,
            'individuals' => $individuals,
            'group_count' => $groupAssignments->count(),
            'individual_count' => $individuals->count(),
            'total_count' => $groupAssignments->count() + $individuals->count(),
        ];
    }

    /**
     * @param  array{group_count: int, individual_count: int, total_count: int}  $summary
     */
    public static function summaryLabel(array $summary): string
    {
        $parts = [];

        if ($summary['group_count'] > 0) {
            $parts[] = $summary['group_count'].' '.($summary['group_count'] === 1 ? 'group' : 'groups');
        }

        if ($summary['individual_count'] > 0) {
            $parts[] = $summary['individual_count'].' '.($summary['individual_count'] === 1 ? 'individual' : 'individuals');
        }

        if ($parts === []) {
            return 'No assignments yet';
        }

        return implode(' · ', $parts);
    }
}
