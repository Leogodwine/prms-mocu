<?php

namespace App\Support;

/**
 * Normalizes gender values from SIS, CSV imports, or manual entry.
 */
final class StudentGenderNormalizer
{
    /**
     * @return 'male'|'female'|null
     */
    public static function normalize(mixed $value): ?string
    {
        $gender = strtolower(trim((string) ($value ?? '')));

        if ($gender === '') {
            return null;
        }

        if (in_array($gender, ['male', 'm', 'man', 'boy'], true)) {
            return 'male';
        }

        if (in_array($gender, ['female', 'f', 'woman', 'girl'], true)) {
            return 'female';
        }

        return null;
    }
}
