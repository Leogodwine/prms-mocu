<?php

namespace App\Support;

use App\Models\Program;
use App\Models\User;

/**
 * Programme-specific rules for when students may conduct proposal,
 * research report, and project work.
 */
final class StudentResearchEligibility
{
    /**
     * @return list<string>
     */
    public static function availableTracks(User $user): array
    {
        $tracks = ['proposal', 'research'];

        if (self::includesProjectTrack(self::resolveProgramme($user))) {
            $tracks[] = 'project';
        }

        return $tracks;
    }

    public static function hasTrack(User $user, string $track): bool
    {
        return in_array(strtolower(trim($track)), self::availableTracks($user), true);
    }

    public static function resolveProgramme(User $user): ?Program
    {
        $profile = $user->relationLoaded('studentProfile')
            ? $user->studentProfile
            : $user->studentProfile()->with('programme.department')->first();

        if ($profile?->programme instanceof Program) {
            return $profile->programme;
        }

        $programmeName = trim((string) ($user->programme ?? ''));
        if ($programmeName === '') {
            return null;
        }

        return Program::query()
            ->with('department')
            ->where(function ($query) use ($programmeName) {
                $query->whereRaw('LOWER(programme_code) = ?', [strtolower($programmeName)])
                    ->orWhereRaw('LOWER(programme_name) = ?', [strtolower($programmeName)])
                    ->orWhere('programme_name', 'like', '%'.$programmeName.'%');
            })
            ->first();
    }

    public static function yearOfStudy(User $user): ?int
    {
        $profile = $user->relationLoaded('studentProfile')
            ? $user->studentProfile
            : $user->studentProfile;

        if ($profile?->year_of_study !== null) {
            return (int) $profile->year_of_study;
        }

        if ($user->year_of_study !== null) {
            return (int) $user->year_of_study;
        }

        return null;
    }

    public static function researchYear(?Program $programme): int
    {
        if ($programme !== null && $programme->project_year !== null) {
            return (int) $programme->project_year;
        }

        return self::defaultResearchYearForCode((string) ($programme?->programme_code ?? ''));
    }

    public static function includesProjectTrack(?Program $programme): bool
    {
        if ($programme === null) {
            return false;
        }

        if ($programme->is_project_eligible) {
            return true;
        }

        $code = strtoupper(trim((string) $programme->programme_code));

        return $code === 'DBICT';
    }

    public static function isInResearchYear(User $user): bool
    {
        $year = self::yearOfStudy($user);
        if ($year === null) {
            return false;
        }

        return $year === self::researchYear(self::resolveProgramme($user));
    }

    public static function researchYearBlockReason(User $user): ?string
    {
        if (self::isInResearchYear($user)) {
            return null;
        }

        $programme = self::resolveProgramme($user);
        $expectedYear = self::researchYear($programme);
        $currentYear = self::yearOfStudy($user);
        $programmeLabel = $programme?->programme_name ?? 'your programme';

        if ($currentYear === null) {
            return 'Your year of study is not set. Contact the coordinator to update your profile before starting research work.';
        }

        return "Research proposal, report, and project work for {$programmeLabel} starts in year {$expectedYear}. You are currently in year {$currentYear}.";
    }

    /**
     * @return array{
     *     programme: ?Program,
     *     department: ?\App\Models\Department,
     *     year_of_study: ?int,
     *     research_year: int,
     *     in_research_year: bool,
     *     includes_project_track: bool,
     *     research_year_block: ?string
     * }
     */
    public static function academicContext(User $user): array
    {
        $programme = self::resolveProgramme($user);

        return [
            'programme' => $programme,
            'department' => $programme?->department,
            'year_of_study' => self::yearOfStudy($user),
            'research_year' => self::researchYear($programme),
            'in_research_year' => self::isInResearchYear($user),
            'includes_project_track' => self::includesProjectTrack($programme),
            'research_year_block' => self::researchYearBlockReason($user),
        ];
    }

    private static function defaultResearchYearForCode(string $code): int
    {
        $code = strtoupper(trim($code));

        return match ($code) {
            'DBICT' => 2,
            'BBICT' => 3,
            'BDS', 'BDSC', 'BDATA', 'DATASCI' => 4,
            default => 3,
        };
    }
}
