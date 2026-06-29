<?php

namespace App\Enums;

enum AcademicLevel: string
{
    case Certificate = 'certificate';
    case Diploma = 'diploma';
    case Bachelor = 'bachelor';
    case Masters = 'masters';
    case Phd = 'phd';

    public function label(): string
    {
        return match ($this) {
            self::Certificate => 'Certificate',
            self::Diploma => 'Diploma',
            self::Bachelor => 'Bachelor',
            self::Masters => 'Masters',
            self::Phd => 'PhD',
        };
    }

    public static function tryFromMixed(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            return self::Bachelor;
        }

        $normalized = strtolower(str_replace([' ', '-'], '_', trim($value)));

        return match ($normalized) {
            'certificate', 'cert' => self::Certificate,
            'diploma' => self::Diploma,
            'masters', 'master', 'msc', 'ma', 'mba' => self::Masters,
            'phd', 'doctorate', 'doctoral' => self::Phd,
            default => self::Bachelor,
        };
    }
}
