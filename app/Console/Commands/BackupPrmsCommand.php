<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupPrmsCommand extends Command
{
    protected $signature = 'prms:backup
                            {--keep=14 : Number of backup folders to retain}';

    protected $description = 'Create a database and public-storage backup for disaster recovery';

    public function handle(): int
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupRoot = storage_path('backups');
        $targetDir = $backupRoot.DIRECTORY_SEPARATOR.$timestamp;

        if (! File::isDirectory($backupRoot)) {
            File::makeDirectory($backupRoot, 0755, true);
        }

        File::makeDirectory($targetDir, 0755, true);

        $manifest = [
            'app' => config('app.name'),
            'created_at' => now()->toIso8601String(),
            'database' => null,
            'storage' => null,
            'status' => 'partial',
        ];

        $dbPath = $this->backupDatabase($targetDir);
        if ($dbPath !== null) {
            $manifest['database'] = basename($dbPath);
            $this->info('Database backup: '.$dbPath);
        } else {
            $this->warn('Database backup skipped — configure mysqldump or use manual export.');
        }

        $storagePath = $this->backupPublicStorage($targetDir);
        if ($storagePath !== null) {
            $manifest['storage'] = basename($storagePath);
            $this->info('Storage backup: '.$storagePath);
        }

        $manifest['status'] = ($manifest['database'] || $manifest['storage']) ? 'ok' : 'failed';

        File::put(
            $targetDir.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->pruneOldBackups((int) $this->option('keep'));

        if ($manifest['status'] === 'failed') {
            $this->error('Backup completed with no artifacts. Check database driver and storage paths.');

            return self::FAILURE;
        }

        $this->info('PRMS backup saved to: '.$targetDir);

        return self::SUCCESS;
    }

    private function backupDatabase(string $targetDir): ?string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? '') !== 'mysql') {
            return null;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if ($database === '') {
            return null;
        }

        $outputFile = $targetDir.DIRECTORY_SEPARATOR.'database.sql';
        File::put($outputFile, '');

        $dumpBinary = $this->resolveMysqldumpBinary();

        if ($dumpBinary === null) {
            return null;
        }

        $result = Process::timeout(600)->run([
            $dumpBinary,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--password='.$password,
            '--single-transaction',
            '--routines',
            '--triggers',
            $database,
        ], function ($type, $buffer) use ($outputFile) {
            File::append($outputFile, $buffer);
        });

        return $result->successful() && File::exists($outputFile) && File::size($outputFile) > 0
            ? $outputFile
            : null;
    }

    private function resolveMysqldumpBinary(): ?string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ]);

        foreach ($candidates as $candidate) {
            $result = Process::run('"'.$candidate.'" --version');

            if ($result->successful()) {
                return $candidate;
            }
        }

        return null;
    }

    private function backupPublicStorage(string $targetDir): ?string
    {
        $source = Storage::disk('public')->path('');

        if (! File::isDirectory($source)) {
            return null;
        }

        $destination = $targetDir.DIRECTORY_SEPARATOR.'storage-public';

        File::copyDirectory($source, $destination);

        return File::isDirectory($destination) ? $destination : null;
    }

    private function pruneOldBackups(int $keep): void
    {
        $backupRoot = storage_path('backups');
        $folders = collect(File::directories($backupRoot))
            ->sortDesc()
            ->values();

        $folders->slice($keep)->each(function (string $folder): void {
            File::deleteDirectory($folder);
            $this->line('Pruned old backup: '.$folder);
        });
    }
}
