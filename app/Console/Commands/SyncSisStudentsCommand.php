<?php

namespace App\Console\Commands;

use App\Models\StudentSisSyncLog;
use App\Support\StudentSisRecordSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SyncSisStudentsCommand extends Command
{
    protected $signature = 'sis:sync-students {--source=storage/app/sis/students.json : Path to SIS JSON file}';

    protected $description = 'Synchronize student records from SIS stub source';

    public function handle(): int
    {
        $source = base_path($this->option('source'));
        $processed = 0;
        $added = 0;
        $updated = 0;
        $deactivated = 0;
        $status = 'success';
        $error = null;

        try {
            if (! File::exists($source)) {
                throw new \RuntimeException("SIS source file not found: {$source}");
            }

            $raw = File::get($source);
            $records = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            DB::transaction(function () use ($records, &$processed, &$added, &$updated, &$deactivated): void {
                foreach ($records as $row) {
                    $processed++;

                    $result = StudentSisRecordSync::syncRow($row);

                    if ($result['action'] === 'added') {
                        $added++;
                    } else {
                        $updated++;
                    }

                    if ($result['deactivated']) {
                        $deactivated++;
                    }
                }
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
            $this->error($error);
        } finally {
            StudentSisSyncLog::create([
                'sync_timestamp' => now(),
                'records_processed' => $processed,
                'records_added' => $added,
                'records_updated' => $updated,
                'records_deactivated' => $deactivated,
                'sync_status' => $status,
                'error_message' => $error,
                'initiated_by' => 'artisan',
            ]);
        }

        if ($status === 'success') {
            $this->info("SIS sync complete. processed={$processed}, added={$added}, updated={$updated}, deactivated={$deactivated}");

            return self::SUCCESS;
        }

        return self::FAILURE;
    }
}
