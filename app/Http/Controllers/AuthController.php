<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use App\Support\Audit;
use App\Support\StudentWorkflowAssigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** @return list<string> */
    private function staffRoles(): array
    {
        return ['admin', 'hod', 'coordinator', 'supervisor'];
    }

    private function looksLikeEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function staffAccountUsesNonEmailIdentifier(string $idValue): bool
    {
        if ($this->looksLikeEmail($idValue)) {
            return false;
        }

        $roles = $this->staffRoles();

        return User::query()
            ->whereIn('role', $roles)
            ->where(function ($q) use ($idValue) {
                $q->where('login_id', $idValue);
                if (Schema::hasColumn('users', 'staff_id')) {
                    $q->orWhere('staff_id', $idValue);
                }
            })
            ->exists();
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isLoginLockedOut($request)) {
            return back()
                ->withErrors([
                    'login_id' => 'Too many failed sign-in attempts. Please wait 15 minutes and try again.',
                ])
                ->onlyInput('login_id');
        }

        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $idValue = trim($credentials['login_id']);
        $password = $credentials['password'];

        // Staff (admin, HoD, coordinator, supervisor) must sign in with university email only — not Staff ID / staff_id.
        if ($this->looksLikeEmail($idValue)) {
            $attempted = Auth::attempt(['email' => $idValue, 'password' => $password], $remember);
        } else {
            if ($this->staffAccountUsesNonEmailIdentifier($idValue)) {
                LoginHistory::create([
                    'user_id' => null,
                    'login_time' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'success' => false,
                    'failure_reason' => 'invalid_credentials',
                    'session_id' => $request->session()->getId(),
                ]);

                return back()
                    ->withErrors([
                        'login_id' => 'Staff accounts must sign in using your university email address, not your Staff ID or staff number.',
                    ])
                    ->onlyInput('login_id');
            }

            $attempted = Auth::attempt(['login_id' => $idValue, 'password' => $password], $remember);

            if (! $attempted && Schema::hasColumn('users', 'registration_number')) {
                $attempted = Auth::attempt(['registration_number' => $idValue, 'password' => $password], $remember);
            }
        }

        if (! $attempted) {
            LoginHistory::create([
                'user_id' => null,
                'login_time' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => false,
                'failure_reason' => 'invalid_credentials',
                'session_id' => $request->session()->getId(),
            ]);

            $message = $this->looksLikeEmail($idValue)
                ? 'Invalid email or password.'
                : 'Invalid registration number or password.';

            return back()
                ->withErrors(['login_id' => $message])
                ->onlyInput('login_id');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->account_status !== 'active') {
            LoginHistory::create([
                'user_id' => $user->id,
                'login_time' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => false,
                'failure_reason' => 'account_inactive',
                'session_id' => $request->session()->getId(),
            ]);
            Auth::logout();
            return back()
                ->withErrors(['login_id' => 'Account inactive. Contact admin.'])
                ->onlyInput('login_id');
        }

        if (in_array($user->role, ['project_student', 'research_student', 'normal_student'], true) && $user->enrollment_status !== 'active') {
            LoginHistory::create([
                'user_id' => $user->id,
                'login_time' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => false,
                'failure_reason' => 'enrollment_not_active',
                'session_id' => $request->session()->getId(),
            ]);
            Auth::logout();
            return back()
                ->withErrors(['login_id' => 'Enrollment inactive. Access denied.'])
                ->onlyInput('login_id');
        }

        LoginHistory::create([
            'user_id' => $user->id,
            'login_time' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => true,
            'session_id' => $request->session()->getId(),
        ]);

        Audit::log($request, 'auth.login', 'User', (string) $user->id);

        if ($user->isStudentUser()) {
            StudentWorkflowAssigner::syncForUser($user);
            $user->refresh();
        }

        if ($user->must_change_password) {
            return redirect()->route('password.force.edit');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        if ($userId) {
            LoginHistory::query()
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->whereNull('logout_time')
                ->latest('login_time')
                ->limit(1)
                ->update(['logout_time' => now()]);

            Audit::log($request, 'auth.logout', 'User', (string) $userId);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function isLoginLockedOut(Request $request): bool
    {
        $recentFailures = LoginHistory::query()
            ->where('success', false)
            ->where('ip_address', $request->ip())
            ->where('login_time', '>=', now()->subMinutes(15))
            ->count();

        return $recentFailures >= 5;
    }
}
