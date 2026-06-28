<?php

namespace App\Enums;

enum WorkflowType: string
{
    case StandardResearch = 'STANDARD_RESEARCH_WORKFLOW';

    public function label(): string
    {
        return match ($this) {
            self::StandardResearch => 'Standard research workflow (proposal → execution → evaluation)',
        };
    }

    public static function tryFromMixed(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            return self::StandardResearch;
        }

        $normalized = strtoupper(str_replace([' ', '-'], '_', trim($value)));

        return match ($normalized) {
            'STANDARD', 'STANDARD_RESEARCH', 'STANDARD_RESEARCH_WORKFLOW' => self::StandardResearch,
            default => self::StandardResearch,
        };
    }

    /**
     * @return list<string>
     */
    public function stages(): array
    {
        return match ($this) {
            self::StandardResearch => [
                'Proposal submission',
                'Supervisor assignment',
                'Proposal approval',
                'Execution phase (research or project)',
                'Final submission',
                'Evaluation',
            ],
        };
    }
}
