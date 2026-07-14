<?php

namespace App\Support;

/**
 * Phone normalization and SMS body formatting for PRMS.
 */
final class PrmsSms
{
    public const MAX_LENGTH = 480;

    /** Example bare international number (no + prefix). */
    public const E164_EXAMPLE = '255738234345';

    /** Example E.164 number with + prefix. */
    public const E164_EXAMPLE_PLUS = '+255738234345';

    /**
     * Guidance shown when a phone number fails validation.
     */
    public static function invalidPhoneMessage(): string
    {
        return 'SMS is sent only to valid E.164 numbers (e.g. '.self::E164_EXAMPLE.' or '.self::E164_EXAMPLE_PLUS.'). '
            .'Use digits only; the + country-code prefix is recommended.';
    }

    public static function requiredPhoneMessage(): string
    {
        return 'Phone number is required.';
    }

    /**
     * Validate a required phone field and normalize it to E.164 (+255...) when valid.
     */
    public static function validatePhoneField(mixed $validator, mixed $request, string $field = 'phone_number'): void
    {
        $phone = $request->input($field);

        if ($phone === null || trim((string) $phone) === '') {
            $validator->errors()->add($field, self::requiredPhoneMessage());

            return;
        }

        $normalized = self::normalizePhone($phone);

        if ($normalized === null) {
            $validator->errors()->add($field, self::invalidPhoneMessage());

            return;
        }

        $request->merge([$field => $normalized]);
    }

    /**
     * Validate an optional phone field and normalize it to E.164 (+255...) when valid.
     */
    public static function validateOptionalPhoneField(mixed $validator, mixed $request, string $field = 'phone_number'): void
    {
        $phone = $request->input($field);

        if ($phone === null || trim((string) $phone) === '') {
            return;
        }

        $normalized = self::normalizePhone($phone);

        if ($normalized === null) {
            $validator->errors()->add($field, self::invalidPhoneMessage());

            return;
        }

        $request->merge([$field => $normalized]);
    }

    /**
     * Normalize a Tanzanian mobile number to E.164 (+255...).
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '255'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '255')) {
            return null;
        }

        if (strlen($digits) < 12 || strlen($digits) > 13) {
            return null;
        }

        return '+'.$digits;
    }

    public static function formatBody(string $title, string $message, ?string $footer = null): string
    {
        $text = trim($title).': '.trim($message);

        if ($footer !== null && trim($footer) !== '') {
            $text .= ' '.trim($footer);
        }

        return self::truncate($text);
    }

    public static function truncate(string $text): string
    {
        return mb_substr(trim($text), 0, self::MAX_LENGTH);
    }
}
