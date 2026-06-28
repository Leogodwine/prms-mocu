<?php

namespace App\Enums;

enum ProgramOutputType: string
{
    case ResearchOnly = 'RESEARCH_ONLY';
    case ProjectOnly = 'PROJECT_ONLY';
    case BothAllowed = 'BOTH_ALLOWED';

    public function label(): string
    {
        return match ($this) {
            self::ResearchOnly => 'Research / thesis only',
            self::ProjectOnly => 'Practical project only',
            self::BothAllowed => 'Research or project (programme choice)',
        };
    }

    public static function tryFromMixed(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            return self::ResearchOnly;
        }

        $normalized = strtoupper(str_replace([' ', '-'], '_', trim($value)));

        return match ($normalized) {
            'PROJECT_ONLY', 'PROJECT' => self::ProjectOnly,
            'BOTH_ALLOWED', 'BOTH', 'HYBRID' => self::BothAllowed,
            default => self::ResearchOnly,
        };
    }
}
