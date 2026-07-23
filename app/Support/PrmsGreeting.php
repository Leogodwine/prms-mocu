<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

final class PrmsGreeting
{
    public static function date(): string
    {
        return now()->format('l, F j Y');
    }

    public static function firstName(?User $user): string
    {
        if ($user === null) {
            return 'there';
        }

        return Str::title(Str::before($user->name, ' '));
    }

    public static function hello(?User $user): string
    {
        return 'Hello, '.self::firstName($user);
    }

    public static function subtitleForRole(?string $role): string
    {
        return match ($role) {
            'student',
            'project_student',
            'research_student',
            'normal_student' => 'Submit your proposal, project work, and research report through every supervised stage.',
            'supervisor' => 'Review proposals, supervise research and computer-based projects, and apply formal grading schemes.',
            'coordinator' => 'Form groups, assign supervisors, set deadlines, and approve final submissions.',
            'hod' => 'Oversee supervisors, students, projects, and research output across your department.',
            'admin' => 'Configure the system, manage users, and monitor health and audit activity.',
            default => 'Track submissions, review supervision activity, and access reporting tools from one place.',
        };
    }
}
