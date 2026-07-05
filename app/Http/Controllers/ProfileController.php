<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * User profile workspace.
 *
 * Administrators may only update phone number and password on their own profile;
 * name, email, department, programme, and gender are managed by another administrator.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->loadMissing('studentProfile');

        return view('profile.show', [
            'user' => $user,
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing('studentProfile');

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdminUser()) {
            return $this->updateAdminSelfProfile($request, $user);
        }

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'department' => ['nullable', 'string', 'max:120'],
            'programme' => ['nullable', 'string', 'max:120'],
            'current_password' => ['nullable', 'string', 'required_with:password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        $validated = $request->validate($rules);

        if ($user->isStudentUser()) {
            unset($validated['department'], $validated['programme']);
        }

        return $this->persistProfileChanges($request, $user, $validated, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            ...(! $user->isStudentUser() ? [
                'department' => $validated['department'] ?? null,
                'programme' => $validated['programme'] ?? null,
            ] : []),
        ]);
    }

    private function updateAdminSelfProfile(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:32'],
            'current_password' => ['nullable', 'string', 'required_with:password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        foreach (['name', 'email', 'department', 'programme', 'gender'] as $lockedField) {
            if ($request->filled($lockedField)) {
                return back()->withErrors([
                    $lockedField => 'Administrators cannot change '.str_replace('_', ' ', $lockedField).' on their own account. Ask another administrator.',
                ]);
            }
        }

        return $this->persistProfileChanges($request, $user, $validated, [
            'phone_number' => $validated['phone_number'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $columnMap
     */
    private function persistProfileChanges(Request $request, User $user, array $validated, array $columnMap): RedirectResponse
    {
        if (! empty($validated['password'])) {
            if (! Hash::check((string) $validated['current_password'], (string) $user->password)) {
                return back()
                    ->withInput($request->except(['current_password', 'password', 'password_confirmation']))
                    ->withErrors(['current_password' => 'Your current password does not match.']);
            }

            $user->password = Hash::make((string) $validated['password']);
            $user->must_change_password = false;
        }

        foreach ($columnMap as $column => $value) {
            if (Schema::hasColumn('users', $column)) {
                $user->{$column} = $value;
            }
        }

        $user->save();

        Audit::log(
            $request,
            'user.profile_updated',
            'User',
            (string) $user->id,
            null,
            [
                'fields_changed' => array_keys($columnMap),
                'password_changed' => ! empty($validated['password']),
            ]
        );

        return redirect()
            ->route('profile.show')
            ->with('status', 'Profile updated successfully.');
    }
}
