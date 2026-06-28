<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use App\Support\PrmsBackupCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminBackupController extends Controller
{
    public function index(): View
    {
        return view('admin.backups', [
            'settings' => PrmsBackupCatalog::settings(),
            'backups' => PrmsBackupCatalog::listBackups(),
            'schedulerNote' => 'Server cron must run `php artisan schedule:run` every minute for automatic backups.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = PrmsBackupCatalog::createBackup($request->user(), 'manual');

        Audit::log($request, 'admin.backup_created', 'Backup', $result['backup']['id'] ?? null);

        if (! $result['success']) {
            return back()->withErrors([
                'backup' => $result['output'] ?: 'Backup failed. Check mysqldump path and storage permissions.',
            ]);
        }

        return back()->with('status', 'Backup created successfully.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'auto_enabled' => ['nullable', 'boolean'],
            'schedule' => ['required', 'in:daily,weekly'],
            'retention' => ['required', 'integer', 'min:1', 'max:90'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        PrmsBackupCatalog::saveSettings([
            'auto_enabled' => $request->boolean('auto_enabled'),
            'schedule' => $validated['schedule'],
            'retention' => $validated['retention'],
            'time' => $validated['time'],
        ]);

        Audit::log($request, 'admin.backup_settings_updated', 'SystemConfiguration');

        return back()->with('status', 'Backup settings saved.');
    }

    public function destroy(Request $request, string $backup): RedirectResponse
    {
        if (! PrmsBackupCatalog::deleteBackup($backup)) {
            return back()->withErrors(['backup' => 'Backup not found or could not be deleted.']);
        }

        Audit::log($request, 'admin.backup_deleted', 'Backup', $backup);

        return back()->with('status', 'Backup deleted.');
    }

    public function download(string $backup): BinaryFileResponse|RedirectResponse
    {
        $response = PrmsBackupCatalog::downloadDatabase($backup);

        if ($response === null) {
            return back()->withErrors(['backup' => 'Database file not available for this backup.']);
        }

        return $response;
    }

    public function restore(Request $request, string $backup): RedirectResponse
    {
        $request->validate([
            'restore_confirm' => ['accepted'],
        ], [
            'restore_confirm.accepted' => 'Confirm that you understand restore will overwrite current database data.',
        ]);

        $result = PrmsBackupCatalog::restoreDatabase($backup);

        Audit::log(
            $request,
            'admin.backup_restored',
            'Backup',
            $backup,
            null,
            ['success' => $result['success']]
        );

        if (! $result['success']) {
            return back()->withErrors(['backup' => $result['message']]);
        }

        return back()->with('status', $result['message']);
    }
}
