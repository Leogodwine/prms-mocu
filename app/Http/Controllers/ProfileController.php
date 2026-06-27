<?php

namespace App\Http\Controllers;

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
 * Provides three views per signed-in user:
 *   - show()   : read-only profile summary (default landing page).
 *   - edit()   : update form for personal details, contact info, and
 *                optional password change (FRD §Account & security).
 *   - update() : persist changes with validation and audit logging.
 *
 * Students may not change department, programme, or year of study here;
 * those fields are updated by administrators or the HOD.
 *
 * The form is intentionally schema-defensive: each input only writes
 * to a column when it exists on the `users` table, so the page stays
 * functional across partially-migrated environments.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number'  => ['nullable', 'string', 'max:32'],
            'department'    => ['nullable', 'string', 'max:120'],
            'programme'     => ['nullable', 'string', 'max:120'],

            // Optional password change. current_password is enforced
            // when a new password is provided.
            'current_password' => ['nullable', 'string', 'required_with:password'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        $validated = $request->validate($rules);

        if ($user->isStudentUser()) {
            unset($validated['department'], $validated['programme']);
        }

        if (!empty($validated['password'])) {
            if (! Hash::check((string) $validated['current_password'], (string) $user->password)) {
                return back()
                    ->withInput($request->except(['current_password', 'password', 'password_confirmation']))
                    ->withErrors(['current_password' => 'Your current password does not match.']);
            }

            $user->password = Hash::make((string) $validated['password']);
        }

        // Plain profile fields — only write columns that actually exist.
        $columnMap = [
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
        ];

        if (! $user->isStudentUser()) {
            $columnMap['department'] = $validated['department'] ?? null;
            $columnMap['programme'] = $validated['programme'] ?? null;
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
                'password_changed' => !empty($validated['password']),
            ]
        );

        return redirect()
            ->route('profile.show')
            ->with('status', 'Profile updated successfully.');
    }
}
