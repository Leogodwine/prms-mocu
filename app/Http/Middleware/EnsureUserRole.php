<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @var list<string>
     */
    private const STUDENT_ROLE_FAMILY = [
        'student',
        'project_student',
        'research_student',
        'normal_student',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $this->userHasAllowedRole($user, $roles)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $roles
     */
    private function userHasAllowedRole(User $user, array $roles): bool
    {
        if (in_array((string) $user->role, $roles, true)) {
            return true;
        }

        // Any student gate (canonical or legacy subtype) matches all student accounts.
        // Privileges inside those routes come from programme workflow eligibility.
        $gateAllowsStudent = count(array_intersect($roles, self::STUDENT_ROLE_FAMILY)) > 0;

        return $gateAllowsStudent && $user->isStudentUser();
    }
}
