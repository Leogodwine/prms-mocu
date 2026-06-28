<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

final class PrmsPlatformMonitor
{
    public static function isMaintenanceMode(): bool
    {
        return app()->isDownForMaintenance();
    }

    public static function maintenanceMessage(): ?string
    {
        $path = storage_path('framework/down');

        if (! File::exists($path)) {
            return null;
        }

        $payload = json_decode((string) File::get($path), true);

        return is_array($payload) ? ($payload['message'] ?? null) : null;
    }

    /**
     * @return array{label: string, ok: bool, detail: string}
     */
    public static function databaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            $size = self::databaseSizeMb();

            return [
                'label' => 'Database',
                'ok' => true,
                'detail' => $size !== null ? number_format($size, 1).' MB' : 'Connected',
            ];
        } catch (\Throwable) {
            return [
                'label' => 'Database',
                'ok' => false,
                'detail' => 'Unavailable',
            ];
        }
    }

    public static function databaseSizeMb(): ?float
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $database = config("database.connections.{$connection}.database");

        if ($driver !== 'mysql' || ! is_string($database) || $database === '') {
            return null;
        }

        try {
            $row = DB::selectOne(
                'SELECT SUM(data_length + index_length) AS bytes
                 FROM information_schema.TABLES
                 WHERE table_schema = ?',
                [$database]
            );

            $bytes = (int) ($row->bytes ?? 0);

            return $bytes > 0 ? round($bytes / 1024 / 1024, 2) : 0.0;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{current_mb: float, peak_mb: float, limit: string, percent: ?float}
     */
    public static function memoryUsage(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limitBytes = self::parseIniBytes((string) ini_get('memory_limit'));
        $percent = ($limitBytes > 0)
            ? round(min(100, ($peak / $limitBytes) * 100), 1)
            : null;

        return [
            'current_mb' => round($current / 1024 / 1024, 1),
            'peak_mb' => round($peak / 1024 / 1024, 1),
            'limit' => ini_get('memory_limit') ?: '—',
            'percent' => $percent,
        ];
    }

    /**
     * @return array{free_gb: float, total_gb: ?float, used_percent: ?float, path: string}
     */
    public static function diskUsage(): array
    {
        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false) {
            return [
                'free_gb' => 0,
                'total_gb' => null,
                'used_percent' => null,
                'path' => $path,
            ];
        }

        $freeGb = round($free / 1024 / 1024 / 1024, 2);
        $totalGb = $total ? round($total / 1024 / 1024 / 1024, 2) : null;
        $usedPercent = ($total && $total > 0)
            ? round((($total - $free) / $total) * 100, 1)
            : null;

        return [
            'free_gb' => $freeGb,
            'total_gb' => $totalGb,
            'used_percent' => $usedPercent,
            'path' => $path,
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function environmentRows(): array
    {
        $logPath = storage_path('logs/laravel.log');
        $logSizeKb = File::exists($logPath) ? round(File::size($logPath) / 1024, 1) : 0;

        return [
            ['label' => 'Application', 'value' => (string) config('app.name')],
            ['label' => 'Environment', 'value' => (string) config('app.env')],
            ['label' => 'Laravel', 'value' => app()->version()],
            ['label' => 'PHP', 'value' => PHP_VERSION],
            ['label' => 'Debug mode', 'value' => config('app.debug') ? 'Enabled' : 'Disabled'],
            ['label' => 'Cache driver', 'value' => (string) config('cache.default')],
            ['label' => 'Queue driver', 'value' => (string) config('queue.default')],
            ['label' => 'Session driver', 'value' => (string) config('session.driver')],
            ['label' => 'Log file size', 'value' => number_format($logSizeKb, 1).' KB'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function recentLogLines(int $lines = 150): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            return ['No log file yet.'];
        }

        $content = File::get($logPath);
        $allLines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $slice = array_slice($allLines, -1 * max(1, $lines));

        return array_values(array_filter($slice, fn ($line) => $line !== '' && $line !== null));
    }

    /**
     * @return list<array{path: string, ok: bool}>
     */
    public static function storagePermissionChecks(): array
    {
        $paths = [
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            storage_path('app'),
            storage_path('backups'),
        ];

        return collect($paths)->map(function (string $path) {
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $testFile = $path.DIRECTORY_SEPARATOR.'.prms_write_test_'.uniqid('', true);
            $ok = @file_put_contents($testFile, 'ok') !== false;
            if ($ok) {
                @unlink($testFile);
            }

            return ['path' => $path, 'ok' => $ok];
        })->all();
    }

    public static function queueFailedCount(): ?int
    {
        return Schema::hasTable('failed_jobs')
            ? (int) DB::table('failed_jobs')->count()
            : null;
    }

    private static function parseIniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
