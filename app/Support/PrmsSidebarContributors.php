<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Group member list for the student sidebar (contributors panel).
 */
final class PrmsSidebarContributors
{
    /**
     * @return array{group_name: string, group_id: int, members: Collection<int, User>}|null
     */
    public static function panelForUser(?User $user): ?array
    {
        if ($user === null || ! $user->isStudentUser()) {
            return null;
        }

        $group = $user->projectGroups()
            ->with(['members' => fn ($query) => $query->orderBy('name')])
            ->first();

        if ($group === null) {
            return null;
        }

        $members = $group->members;
        if ($members->count() < 2) {
            return null;
        }

        $groupName = trim((string) $group->name);

        return [
            'group_name' => $groupName !== '' ? $groupName : 'Group #'.$group->id,
            'group_id' => (int) $group->id,
            'members' => $members,
        ];
    }
}
