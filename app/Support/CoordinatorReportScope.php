<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Project groups included in coordinator-style analytics (reports, exports,
 * materials list) — same rules as the original ReportController scope.
 *
 * @return Collection<int, int>
 */
final class CoordinatorReportScope
{
    public static function projectGroupIdsForUser(User $user): Collection
    {
        return match ($user->role) {
            'coordinator' => ProjectGroup::query()
                ->where('coordinator_id', $user->id)
                ->pluck('id'),
            'admin' => ProjectGroup::query()->pluck('id'),
            'hod' => self::hodScopedProjectGroupIds($user),
            default => collect(),
        };
    }

    /**
     * @return Collection<int, int>
     */
    private static function hodScopedProjectGroupIds(User $user): Collection
    {
        $deptId = $user->staffProfile?->department_id;
        $q = ProjectGroup::query();
        if ($deptId) {
            $q->whereHas(
                'members.studentProfile.programme',
                fn ($pq) => $pq->where('department_id', $deptId)
            );
        }

        return $q->pluck('id');
    }
}
