<?php

namespace App\Enums;

enum OutputTrack: string
{
    case Research = 'research';
    case Project = 'project';

    public function label(): string
    {
        return match ($this) {
            self::Research => 'Research / thesis track',
            self::Project => 'Practical project track',
        };
    }

    public static function tryFromMixed(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'research', 'thesis', 'dissertation' => self::Research,
            'project', 'system', 'product' => self::Project,
            default => null,
        };
    }
}
