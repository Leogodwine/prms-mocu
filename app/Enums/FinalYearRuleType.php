<?php

namespace App\Enums;

enum FinalYearRuleType: string
{
    case FixedYear = 'FIXED_YEAR';
    case ProgrammeDefined = 'PROGRAMME_DEFINED';
    case LevelBased = 'LEVEL_BASED';

    public function label(): string
    {
        return match ($this) {
            self::FixedYear => 'Fixed year (department setting)',
            self::ProgrammeDefined => 'Defined by programme',
            self::LevelBased => 'Based on academic level rules',
        };
    }
}
