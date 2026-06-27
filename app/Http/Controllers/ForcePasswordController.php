<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (!$request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Password updated successfully.');
    }
}
