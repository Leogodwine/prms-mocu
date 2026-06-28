<?php

use App\Support\PrmsBackupCatalog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('projects:check-similarities')->dailyAt('02:30');

Schedule::call(function (): void {
    if (! PrmsBackupCatalog::autoBackupEnabled()) {
        return;
    }

    $settings = PrmsBackupCatalog::settings();

    Artisan::call('prms:backup', [
        '--keep' => $settings['retention'],
        '--trigger' => 'scheduled',
        '--user-name' => 'Scheduler',
    ]);
})->dailyAt(PrmsBackupCatalog::scheduledTime())
    ->when(fn () => PrmsBackupCatalog::autoBackupEnabled() && PrmsBackupCatalog::settings()['schedule'] === 'daily')
    ->name('prms-backup-daily');

Schedule::call(function (): void {
    if (! PrmsBackupCatalog::autoBackupEnabled()) {
        return;
    }

    $settings = PrmsBackupCatalog::settings();

    Artisan::call('prms:backup', [
        '--keep' => $settings['retention'],
        '--trigger' => 'scheduled',
        '--user-name' => 'Scheduler',
    ]);
})->weeklyOn(0, PrmsBackupCatalog::scheduledTime())
    ->when(fn () => PrmsBackupCatalog::autoBackupEnabled() && PrmsBackupCatalog::settings()['schedule'] === 'weekly')
    ->name('prms-backup-weekly');
