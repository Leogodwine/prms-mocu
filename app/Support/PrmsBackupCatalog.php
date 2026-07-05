<?php

namespace App\Support;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PrmsBackupCatalog
{
    public const KEY_AUTO_ENABLED = 'backup.auto_enabled';

    public const KEY_SCHEDULE = 'backup.schedule';

    public const KEY_RETENTION = 'backup.retention_count';

    public const KEY_TIME = 'backup.scheduled_time';

    public static function backupRoot(): string
    {
        return storage_path('backups');
    }

    public static function autoBackupEnabled(): bool
    {
        return self::configValue(self::KEY_AUTO_ENABLED, '0') === '1';
    }

    public static function scheduleLabel(): string
    {
        return match (self::configValue(self::KEY_SCHEDULE, 'weekly')) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            default => 'Weekly',
        };
    }

    public static function retentionCount(): int
    {
        return max(1, (int) self::configValue(self::KEY_RETENTION, '14'));
    }

    public static function scheduledTime(): string
    {
        return self::configValue(self::KEY_TIME, '03:00');
    }

    /**
     * @return array{auto_enabled: bool, schedule: string, retention: int, time: string}
     */
    public static function settings(): array
    {
        return [
            'auto_enabled' => self::autoBackupEnabled(),
            'schedule' => self::configValue(self::KEY_SCHEDULE, 'weekly'),
            'retention' => self::retentionCount(),
            'time' => self::scheduledTime(),
        ];
    }

    public static function saveSettings(array $data): void
    {
        self::upsert(self::KEY_AUTO_ENABLED, ! empty($data['auto_enabled']) ? '1' : '0');
        self::upsert(self::KEY_SCHEDULE, in_array($data['schedule'] ?? '', ['daily', 'weekly'], true) ? $data['schedule'] : 'weekly');
        self::upsert(self::KEY_RETENTION, (string) max(1, min(90, (int) ($data['retention'] ?? 14))));
        self::upsert(self::KEY_TIME, (string) ($data['time'] ?? '03:00'));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function listBackups(): Collection
    {
        $root = self::backupRoot();

        if (! File::isDirectory($root)) {
            return collect();
        }

        return collect(File::directories($root))
            ->sortDesc()
            ->map(function (string $dir) {
                $manifestPath = $dir.DIRECTORY_SEPARATOR.'manifest.json';
                $manifest = File::exists($manifestPath)
                    ? json_decode((string) File::get($manifestPath), true)
                    : [];

                $folder = basename($dir);
                $dbFile = $dir.DIRECTORY_SEPARATOR.'database.sql';
                $sizeBytes = self::directorySize($dir);

                return [
                    'id' => $folder,
                    'filename' => $folder,
                    'size_bytes' => $sizeBytes,
                    'size_label' => self::formatBytes($sizeBytes),
                    'status' => (string) ($manifest['status'] ?? 'unknown'),
                    'type' => (string) ($manifest['trigger'] ?? 'manual'),
                    'created_by' => (string) ($manifest['created_by_name'] ?? 'System'),
                    'completed_at' => (string) ($manifest['created_at'] ?? $folder),
                    'has_database' => File::exists($dbFile) && File::size($dbFile) > 0,
                    'path' => $dir,
                ];
            })
            ->values();
    }

    public static function createBackup(?User $actor = null, string $trigger = 'manual'): array
    {
        $keep = self::retentionCount();
        $exitCode = Artisan::call('prms:backup', [
            '--keep' => $keep,
            '--trigger' => $trigger,
            '--user-name' => $actor?->name ?? 'System',
        ]);
        $latest = self::listBackups()->first();

        if ($latest !== null && $actor !== null) {
            self::annotateLatestManifest($latest['path'], $actor, $trigger);
            $latest = self::listBackups()->first();
        } elseif ($latest !== null) {
            $latest = self::listBackups()->first();
        }

        return [
            'success' => $exitCode === 0 && ($latest['status'] ?? '') === 'ok',
            'output' => trim(Artisan::output()),
            'backup' => $latest,
        ];
    }

    public static function deleteBackup(string $backupId): bool
    {
        $path = self::resolveBackupPath($backupId);

        if ($path === null) {
            return false;
        }

        File::deleteDirectory($path);

        return ! File::isDirectory($path);
    }

    public static function downloadDatabase(string $backupId): ?BinaryFileResponse
    {
        $path = self::resolveBackupPath($backupId);

        if ($path === null) {
            return null;
        }

        $dbFile = $path.DIRECTORY_SEPARATOR.'database.sql';

        if (! File::exists($dbFile)) {
            return null;
        }

        return response()->download($dbFile, 'prms-backup-'.$backupId.'.sql');
    }

    public static function restoreDatabase(string $backupId): array
    {
        $path = self::resolveBackupPath($backupId);

        if ($path === null) {
            return ['success' => false, 'message' => 'Backup not found.'];
        }

        $dbFile = $path.DIRECTORY_SEPARATOR.'database.sql';

        if (! File::exists($dbFile) || File::size($dbFile) === 0) {
            return ['success' => false, 'message' => 'This backup has no database dump.'];
        }

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? '') !== 'mysql') {
            return ['success' => false, 'message' => 'Database restore is supported for MySQL only.'];
        }

        $mysql = self::resolveMysqlBinary('mysql');

        if ($mysql === null) {
            return ['success' => false, 'message' => 'mysql client not found. Set MYSQL_PATH in .env.'];
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $result = Process::timeout(900)->input(File::get($dbFile))->run([
            $mysql,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--password='.$password,
            $database,
        ]);

        if (! $result->successful()) {
            return [
                'success' => false,
                'message' => 'Restore failed: '.trim($result->errorOutput()),
            ];
        }

        return ['success' => true, 'message' => 'Database restored from backup '.$backupId.'.'];
    }

    private static function annotateLatestManifest(string $dir, User $actor, string $trigger): void
    {
        $manifestPath = $dir.DIRECTORY_SEPARATOR.'manifest.json';

        if (! File::exists($manifestPath)) {
            return;
        }

        $manifest = json_decode((string) File::get($manifestPath), true) ?: [];
        $manifest['created_by_id'] = $actor->id;
        $manifest['created_by_name'] = $actor->name;
        $manifest['trigger'] = $trigger;

        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private static function resolveBackupPath(string $backupId): ?string
    {
        if ($backupId === '' || str_contains($backupId, '..') || str_contains($backupId, DIRECTORY_SEPARATOR)) {
            return null;
        }

        $path = self::backupRoot().DIRECTORY_SEPARATOR.$backupId;

        return File::isDirectory($path) ? $path : null;
    }

    private static function directorySize(string $dir): int
    {
        $size = 0;

        foreach (File::allFiles($dir) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    private static function configValue(string $key, string $default): string
    {
        if (! Schema::hasTable('system_configurations')) {
            return $default;
        }

        $row = SystemConfiguration::query()->where('config_key', $key)->first();

        return filled($row?->config_value) ? (string) $row->config_value : $default;
    }

    private static function upsert(string $key, string $value): void
    {
        SystemConfiguration::query()->updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => $value,
                'config_type' => 'string',
                'category' => 'backup',
                'description' => 'Backup and recovery setting',
            ]
        );
    }

    private static function resolveMysqlBinary(string $binary): ?string
    {
        $candidates = array_filter([
            env('MYSQL_PATH'),
            env('MYSQLDUMP_PATH') ? str_replace('mysqldump', 'mysql', (string) env('MYSQLDUMP_PATH')) : null,
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'mysql',
        ]);

        foreach ($candidates as $candidate) {
            $result = Process::run('"'.$candidate.'" --version');

            if ($result->successful()) {
                return $candidate;
            }
        }

        return null;
    }
}
