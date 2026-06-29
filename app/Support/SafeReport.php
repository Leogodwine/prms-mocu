<?php

namespace App\Support;

/**
 * Report exceptions without failing the request when storage/logs is not writable.
 */
final class SafeReport
{
    public static function call(\Throwable $e): void
    {
        try {
            report($e);
        } catch (\Throwable) {
            // Logging unavailable (e.g. storage/logs permission denied).
        }
    }
}
