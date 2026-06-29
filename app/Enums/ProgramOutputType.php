<?php

namespace App\Enums;

enum ProgramOutputType: string
{
    case None = 'NONE';
    case ResearchOnly = 'RESEARCH_ONLY';
    case ProjectOnly = 'PROJECT_ONLY';
    case BothAllowed = 'BOTH_ALLOWED';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No research or project',
            self::ResearchOnly => 'Research / thesis only',
            self::ProjectOnly => 'Practical project only',
            self::BothAllowed => 'Research or project (programme choice)',
        };
    }

    public function allowsWorkflow(): bool
    {
        return $this !== self::None;
    }

    public static function tryFromMixed(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            return self::ResearchOnly;
        }

        $normalized = strtoupper(str_replace([' ', '-'], '_', trim($value)));

        return match ($normalized) {
            'NONE', 'NOT_APPLICABLE', 'NA', 'NO_OUTPUT' => self::None,
            'PROJECT_ONLY', 'PROJECT' => self::ProjectOnly,
            'BOTH_ALLOWED', 'BOTH', 'HYBRID' => self::BothAllowed,
            default => self::ResearchOnly,
        };
    }
}
