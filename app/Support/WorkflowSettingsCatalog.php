<?php

namespace App\Support;

use App\Enums\AcademicLevel;
use App\Enums\WorkflowType;
use App\Models\SystemConfiguration;

/**
 * Global final-year workflow defaults stored in system_configurations.
 */
final class WorkflowSettingsCatalog
{
    public const KEY_DEFAULT_ACADEMIC_LEVEL = 'workflow.default_academic_level';

    public const KEY_DEFAULT_WORKFLOW_TYPE = 'workflow.default_workflow_type';

    public const KEY_FINAL_YEAR_DIPLOMA = 'workflow.final_year.diploma';

    public const KEY_FINAL_YEAR_BACHELOR = 'workflow.final_year.bachelor';

    public const KEY_FINAL_YEAR_MASTERS = 'workflow.final_year.masters';

    public const KEY_FINAL_YEAR_PHD = 'workflow.final_year.phd';

    /**
     * @return array{
     *     default_academic_level: string,
     *     default_workflow_type: string,
     *     final_year: array{diploma: int, bachelor: int, masters: int, phd: int}
     * }
     */
    public static function settings(): array
    {
        return [
            'default_academic_level' => self::defaultAcademicLevel(),
            'default_workflow_type' => self::defaultWorkflowType(),
            'final_year' => [
                'diploma' => self::defaultFinalYearForLevel(AcademicLevel::Diploma),
                'bachelor' => self::defaultFinalYearForLevel(AcademicLevel::Bachelor),
                'masters' => self::defaultFinalYearForLevel(AcademicLevel::Masters),
                'phd' => self::defaultFinalYearForLevel(AcademicLevel::Phd),
            ],
        ];
    }

    public static function defaultAcademicLevel(): string
    {
        return self::configValue(
            self::KEY_DEFAULT_ACADEMIC_LEVEL,
            (string) config('prms.workflow.default_academic_level', 'bachelor')
        );
    }

    public static function defaultWorkflowType(): string
    {
        return WorkflowType::tryFromMixed(
            self::configValue(self::KEY_DEFAULT_WORKFLOW_TYPE, 'STANDARD_RESEARCH_WORKFLOW')
        )->value;
    }

    public static function defaultFinalYearForLevel(AcademicLevel $level): int
    {
        $key = match ($level) {
            AcademicLevel::Certificate => self::KEY_FINAL_YEAR_DIPLOMA,
            AcademicLevel::Diploma => self::KEY_FINAL_YEAR_DIPLOMA,
            AcademicLevel::Bachelor => self::KEY_FINAL_YEAR_BACHELOR,
            AcademicLevel::Masters => self::KEY_FINAL_YEAR_MASTERS,
            AcademicLevel::Phd => self::KEY_FINAL_YEAR_PHD,
        };

        $fallback = (int) config("prms.workflow.default_final_year.{$level->value}", match ($level) {
            AcademicLevel::Certificate => 1,
            AcademicLevel::Diploma => 2,
            AcademicLevel::Bachelor => 3,
            AcademicLevel::Masters => 2,
            AcademicLevel::Phd => 3,
        });

        return max(1, min(8, (int) self::configValue($key, (string) $fallback)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function saveSettings(array $data): void
    {
        $level = AcademicLevel::tryFromMixed($data['default_academic_level'] ?? 'bachelor')->value;
        self::upsert(self::KEY_DEFAULT_ACADEMIC_LEVEL, $level, 'Default academic level when programme does not specify one');

        $workflowType = WorkflowType::tryFromMixed($data['default_workflow_type'] ?? '')->value;
        self::upsert(self::KEY_DEFAULT_WORKFLOW_TYPE, $workflowType, 'Default workflow template for programmes');

        foreach (['diploma', 'bachelor', 'masters', 'phd'] as $levelKey) {
            $value = max(1, min(8, (int) ($data['final_year'][$levelKey] ?? 3)));
            $configKey = match ($levelKey) {
                'diploma' => self::KEY_FINAL_YEAR_DIPLOMA,
                'bachelor' => self::KEY_FINAL_YEAR_BACHELOR,
                'masters' => self::KEY_FINAL_YEAR_MASTERS,
                'phd' => self::KEY_FINAL_YEAR_PHD,
            };
            self::upsert($configKey, (string) $value, "Default final year for {$levelKey} programmes");
        }
    }

    private static function configValue(string $key, string $default): string
    {
        $row = SystemConfiguration::query()->where('config_key', $key)->first();

        if ($row === null || $row->config_value === null || $row->config_value === '') {
            return $default;
        }

        return (string) $row->config_value;
    }

    private static function upsert(string $key, string $value, string $description): void
    {
        SystemConfiguration::query()->updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => $value,
                'config_type' => 'string',
                'category' => 'workflow',
                'description' => $description,
            ]
        );
    }
}
