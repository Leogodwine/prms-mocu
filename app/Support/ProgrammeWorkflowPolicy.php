<?php

namespace App\Support;

use App\Enums\AcademicLevel;
use App\Enums\ProgramOutputType;
use App\Models\Program;

/**
 * MoCU rules: certificate programmes never conduct research or project work;
 * only the DBICT diploma conducts a project — other diplomas do not.
 */
final class ProgrammeWorkflowPolicy
{
    public static function projectDiplomaCode(): string
    {
        return strtoupper((string) config('prms.workflow.project_diploma_programme_code', 'DBICT'));
    }

    public static function isCertificateLevel(?string $academicLevel): bool
    {
        return AcademicLevel::tryFromMixed($academicLevel ?? '') === AcademicLevel::Certificate;
    }

    public static function isDiplomaLevel(?string $academicLevel): bool
    {
        return AcademicLevel::tryFromMixed($academicLevel ?? '') === AcademicLevel::Diploma;
    }

    public static function isCertificateProgramme(?Program $programme): bool
    {
        return $programme !== null && self::isCertificateLevel($programme->academic_level);
    }

    public static function isProjectDiplomaProgramme(?Program $programme): bool
    {
        if ($programme === null || ! self::isDiplomaLevel($programme->academic_level)) {
            return false;
        }

        return strtoupper((string) $programme->programme_code) === self::projectDiplomaCode();
    }

    public static function excludesResearchAndProject(?Program $programme): bool
    {
        if ($programme === null) {
            return false;
        }

        if (self::isCertificateProgramme($programme)) {
            return true;
        }

        return self::isDiplomaLevel($programme->academic_level)
            && ! self::isProjectDiplomaProgramme($programme);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function applyToProgrammePayload(array $payload, ?string $programmeCode = null): array
    {
        $code = strtoupper((string) ($programmeCode ?? $payload['programme_code'] ?? ''));
        $level = AcademicLevel::tryFromMixed($payload['academic_level'] ?? '');

        if ($level === AcademicLevel::Certificate) {
            return array_merge($payload, [
                'output_type' => ProgramOutputType::None->value,
                'is_project_eligible' => false,
                'allowed_project_years' => null,
            ]);
        }

        if ($level === AcademicLevel::Diploma && $code !== self::projectDiplomaCode()) {
            return array_merge($payload, [
                'output_type' => ProgramOutputType::None->value,
                'is_project_eligible' => false,
                'allowed_project_years' => null,
            ]);
        }

        if ($level === AcademicLevel::Diploma && $code === self::projectDiplomaCode()) {
            return array_merge($payload, [
                'output_type' => ProgramOutputType::ProjectOnly->value,
                'is_project_eligible' => true,
            ]);
        }

        return $payload;
    }

    public static function normalizeDepartmentRuleOutputType(string $academicLevel, string $outputType): string
    {
        if (self::isCertificateLevel($academicLevel) || self::isDiplomaLevel($academicLevel)) {
            return ProgramOutputType::None->value;
        }

        return $outputType;
    }
}
