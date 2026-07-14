<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Support\Facades\Schema;

/**
 * MoCU account identifier formats:
 * - Staff ID: MoCU/DEPT-CODE/NUMBER/YY (e.g. MoCU/ACC/231/20)
 * - Registration number: MoCU/PROGRAMME-CODE/NUMBER/YY (e.g. MoCU/BBICT/231/20)
 *
 * The DEPT-CODE and PROGRAMME-CODE segments must match rows in `departments`
 * and `programmes` respectively.
 */
final class PrmsAccountIdentifierFormat
{
    public const STAFF_EXAMPLE = 'MoCU/ACC/231/20';

    public const STUDENT_EXAMPLE = 'MoCU/BBICT/231/20';

    public const STAFF_HELP = 'Format: MoCU/DEPT-CODE/NUMBER/YY — exact casing required: MoCU/ prefix, uppercase code, two-digit year (e.g. '.self::STAFF_EXAMPLE.')';

    public const STUDENT_HELP = 'Format: MoCU/PROGRAMME-CODE/NUMBER/YY — exact casing required: MoCU/ prefix, uppercase code, two-digit year (e.g. '.self::STUDENT_EXAMPLE.')';

    public static function requiredStudentRegistrationMessage(): string
    {
        return 'Registration number is required.';
    }

    public static function requiredStaffIdMessage(): string
    {
        return 'Staff ID is required.';
    }

    /** @var string Standard four-part identifier (case-sensitive MoCU prefix and uppercase code). */
    private const STANDARD_PATTERN = '/^MoCU\/([A-Z0-9]+(?:-[A-Z0-9]+)*)\/(\d+)\/(\d{2})$/';

    /** @var string Legacy seeded admin identifier (three segments, case-sensitive). */
    private const ADMIN_LEGACY_PATTERN = '/^MoCU\/ADMIN\/\d+$/';

    /**
     * @return array{prefix: string, code: string, number: string, year: string, normalized: string}|null
     */
    public static function parse(?string $value): ?array
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '' || ! preg_match(self::STANDARD_PATTERN, $trimmed, $matches)) {
            return null;
        }

        $code = $matches[1];

        return [
            'prefix' => 'MoCU',
            'code' => $code,
            'number' => $matches[2],
            'year' => $matches[3],
            'normalized' => sprintf('MoCU/%s/%s/%s', $code, $matches[2], $matches[3]),
        ];
    }

    public static function normalize(?string $value): ?string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        $parsed = self::parse($trimmed);

        return $parsed !== null ? $parsed['normalized'] : $trimmed;
    }

    public static function hasExactCasing(?string $value): bool
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return false;
        }

        if (preg_match(self::ADMIN_LEGACY_PATTERN, $trimmed)) {
            return true;
        }

        return self::parse($trimmed) !== null;
    }

    public static function parsedDepartmentCode(?string $staffId): ?string
    {
        return self::parse($staffId)['code'] ?? null;
    }

    public static function parsedProgrammeCode(?string $registrationNumber): ?string
    {
        return self::parse($registrationNumber)['code'] ?? null;
    }

    public static function findDepartmentByCode(?string $code): ?Department
    {
        $trimmed = trim((string) $code);
        if ($trimmed === '' || ! Schema::hasTable('departments')) {
            return null;
        }

        return Department::query()
            ->where('department_code', $trimmed)
            ->first();
    }

    public static function findProgrammeByCode(?string $code): ?Program
    {
        $trimmed = trim((string) $code);
        if ($trimmed === '' || ! Schema::hasTable('programmes')) {
            return null;
        }

        return Program::query()
            ->where('programme_code', $trimmed)
            ->first();
    }

    public static function departmentCodeIsRegistered(?string $code): bool
    {
        return self::findDepartmentByCode($code) !== null;
    }

    public static function programmeCodeIsRegistered(?string $code): bool
    {
        return self::findProgrammeByCode($code) !== null;
    }

    public static function hasValidStaffIdFormat(?string $value): bool
    {
        return self::parse($value) !== null;
    }

    public static function hasValidRegistrationNumberFormat(?string $value): bool
    {
        return self::parse($value) !== null;
    }

    public static function isValidStaffId(?string $value): bool
    {
        $parsed = self::parse($value);

        return $parsed !== null && self::departmentCodeIsRegistered($parsed['code']);
    }

    public static function isValidRegistrationNumber(?string $value): bool
    {
        $parsed = self::parse($value);

        return $parsed !== null && self::programmeCodeIsRegistered($parsed['code']);
    }

    public static function isValidAdminIdentifier(?string $value): bool
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return false;
        }

        if ((bool) preg_match(self::ADMIN_LEGACY_PATTERN, $trimmed)) {
            return true;
        }

        $parsed = self::parse($trimmed);

        return $parsed !== null && $parsed['code'] === 'ADMIN';
    }

    public static function resolveDepartmentCode(?string $label): ?string
    {
        if ($label === null || trim($label) === '') {
            return null;
        }

        $trimmed = trim($label);

        $department = self::findDepartmentByCode($trimmed);
        if ($department !== null) {
            return (string) $department->department_code;
        }

        if (! Schema::hasTable('departments')) {
            return null;
        }

        $department = Department::query()
            ->whereRaw('LOWER(department_name) = ?', [mb_strtolower($trimmed)])
            ->first();

        return $department !== null ? (string) $department->department_code : null;
    }

    public static function resolveProgrammeCode(?string $label): ?string
    {
        if ($label === null || trim($label) === '') {
            return null;
        }

        $trimmed = trim($label);

        $programme = self::findProgrammeByCode($trimmed);
        if ($programme !== null) {
            return (string) $programme->programme_code;
        }

        if (! Schema::hasTable('programmes')) {
            return null;
        }

        $programme = Program::query()
            ->whereRaw('LOWER(programme_name) = ?', [mb_strtolower($trimmed)])
            ->first();

        return $programme !== null ? (string) $programme->programme_code : null;
    }

    public static function staffIdMatchesDepartment(?string $staffId, ?string $departmentLabel): bool
    {
        $parsed = self::parse($staffId);
        if ($parsed === null || ! self::departmentCodeIsRegistered($parsed['code'])) {
            return false;
        }

        if ($departmentLabel === null || trim($departmentLabel) === '') {
            return true;
        }

        $expectedCode = self::resolveDepartmentCode($departmentLabel);

        return $expectedCode !== null && $parsed['code'] === $expectedCode;
    }

    public static function registrationMatchesProgramme(?string $registrationNumber, ?string $programmeLabel): bool
    {
        $parsed = self::parse($registrationNumber);
        if ($parsed === null || ! self::programmeCodeIsRegistered($parsed['code'])) {
            return false;
        }

        if ($programmeLabel === null || trim($programmeLabel) === '') {
            return true;
        }

        $expectedCode = self::resolveProgrammeCode($programmeLabel);

        return $expectedCode !== null && $parsed['code'] === $expectedCode;
    }

    public static function caseSensitivityHint(): string
    {
        return 'Use exact casing: MoCU/ prefix (capital M, o, C, U), uppercase department or programme code, and a two-digit year suffix.';
    }
}
