<?php

namespace App\Http\Controllers;

use App\Models\ResearchProject;
use App\Models\User;
use App\Support\Audit;
use App\Support\PrmsUserCapabilities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectContributorController extends Controller
{
    public function store(Request $request, ResearchProject $researchProject): RedirectResponse
    {
        $user = $request->user();

        if (! PrmsUserCapabilities::canManageProjectContributors($user, $researchProject)) {
            abort(403, 'You cannot manage contributors for this project.');
        }

        $group = $user->projectGroups()->with('members')->first();
        $eligibleIds = $group
            ? $group->members->pluck('id')->reject(fn ($id) => (int) $id === (int) $user->id)->all()
            : [];

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::in($eligibleIds),
            ],
        ]);

        if ($researchProject->contributors()->where('users.id', $validated['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'This student is already listed as a contributor.']);
        }

        $researchProject->contributors()->attach($validated['user_id'], [
            'contribution_role' => 'contributor',
            'added_by' => $user->id,
        ]);

        Audit::log(
            $request,
            'research_project.contributor_added',
            'ResearchProject',
            (string) $researchProject->id,
            null,
            ['contributor_user_id' => $validated['user_id']]
        );

        return back()->with('status', 'Contributor added successfully.');
    }

    public function destroy(Request $request, ResearchProject $researchProject, User $contributor): RedirectResponse
    {
        $user = $request->user();

        if (! PrmsUserCapabilities::canManageProjectContributors($user, $researchProject)) {
            abort(403, 'You cannot manage contributors for this project.');
        }

        if (! $researchProject->contributors()->where('users.id', $contributor->id)->exists()) {
            abort(404);
        }

        $researchProject->contributors()->detach($contributor->id);

        Audit::log(
            $request,
            'research_project.contributor_removed',
            'ResearchProject',
            (string) $researchProject->id,
            null,
            ['contributor_user_id' => $contributor->id]
        );

        return back()->with('status', 'Contributor removed.');
    }
}
