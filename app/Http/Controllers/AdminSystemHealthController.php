<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\SmsDeliveryLog;
use App\Models\StudentSisSyncLog;
use App\Support\Audit;
use App\Support\PrmsPlatformMonitor;
use App\Support\PrmsSms;
use App\Support\PrmsSmsStatus;
use App\Support\PrmsTablePagination;
use App\Services\Sms\SmsSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSystemHealthController extends Controller
{
    public function index(Request $request): View
    {
        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->latest('failed_at')
                ->paginate(PrmsTablePagination::perPage($request), ['*'], 'failed_jobs_page')
                ->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, PrmsTablePagination::DEFAULT);

        $heartbeatPath = storage_path('app/health/queue-worker-heartbeat.txt');
        $queueHeartbeat = File::exists($heartbeatPath) ? trim((string) File::get($heartbeatPath)) : null;

        return view('admin.system-health', [
            'monitor' => [
                'online' => ! PrmsPlatformMonitor::isMaintenanceMode(),
                'maintenance' => PrmsPlatformMonitor::isMaintenanceMode(),
                'maintenance_message' => PrmsPlatformMonitor::maintenanceMessage(),
                'database' => PrmsPlatformMonitor::databaseStatus(),
                'memory' => PrmsPlatformMonitor::memoryUsage(),
                'disk' => PrmsPlatformMonitor::diskUsage(),
                'environment' => PrmsPlatformMonitor::environmentRows(),
                'log_lines' => PrmsPlatformMonitor::recentLogLines(150),
                'storage_checks' => PrmsPlatformMonitor::storagePermissionChecks(),
            ],
            'smsStatus' => PrmsSmsStatus::summary(),
            'recentSmsLogs' => Schema::hasTable('sms_delivery_logs')
                ? SmsDeliveryLog::query()->latest('created_at')->limit(10)->get()
                : collect(),
            'queueFailed' => PrmsPlatformMonitor::queueFailedCount(),
            'failedJobs' => $failedJobs,
            'latestSisSync' => StudentSisSyncLog::query()->latest('sync_timestamp')->first(),
            'latestAudit' => AuditLog::query()->latest()->first(),
            'latestLogin' => LoginHistory::query()->latest('login_time')->first(),
            'queueHeartbeat' => $queueHeartbeat,
            'recentAuditCount' => AuditLog::query()->where('created_at', '>=', now()->subDay())->count(),
            'recentLoginFailures' => LoginHistory::query()
                ->where('success', false)
                ->where('login_time', '>=', now()->subDay())
                ->count(),
        ]);
    }

    public function enableMaintenance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ]);

        Artisan::call('down', [
            '--retry' => 60,
            '--refresh' => 15,
            '--secret' => substr(md5((string) config('app.key')), 0, 16),
        ]);

        $path = storage_path('framework/down');
        if (File::exists($path) && filled($validated['maintenance_message'] ?? null)) {
            $payload = json_decode((string) File::get($path), true) ?: [];
            $payload['message'] = trim($validated['maintenance_message']);
            File::put($path, json_encode($payload, JSON_PRETTY_PRINT));
        }

        Audit::log($request, 'admin.maintenance_enabled', 'System', null);

        return back()->with('status', 'Maintenance mode enabled.');
    }

    public function disableMaintenance(Request $request): RedirectResponse
    {
        Artisan::call('up');

        Audit::log($request, 'admin.maintenance_disabled', 'System', null);

        return back()->with('status', 'Maintenance mode disabled. The application is online.');
    }

    public function runMaintenanceTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task' => ['required', 'in:optimize,clear,queue-restart'],
        ]);

        match ($validated['task']) {
            'optimize' => $this->runArtisanChain(['config:cache', 'route:cache', 'view:cache']),
            'clear' => $this->runArtisanChain(['cache:clear', 'config:clear', 'route:clear', 'view:clear']),
            'queue-restart' => Artisan::call('queue:restart'),
        };

        Audit::log($request, 'admin.maintenance_task', 'System', null, null, ['task' => $validated['task']]);

        return back()->with('status', 'Maintenance task completed: '.$validated['task'].'.');
    }

    public function retryFailedJob(int $id): RedirectResponse
    {
        if (Schema::hasTable('failed_jobs')) {
            Artisan::call('queue:retry', ['id' => [$id]]);
        }

        return back()->with('info', "Retry requested for failed job #{$id}.");
    }

    public function clearFailedJobs(): RedirectResponse
    {
        if (Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')->delete();
        }

        return back()->with('info', 'All failed jobs were cleared.');
    }

    public function sendTestSms(Request $request, SmsSender $sender): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = PrmsSms::normalizePhone($validated['phone']);

        if ($phone === null) {
            return back()->withErrors(['phone' => PrmsSms::invalidPhoneMessage()]);
        }

        $message = PrmsSms::formatBody(
            'PRMS test',
            'This is a test message from '.config('app.name').'.',
            now()->format('j M Y H:i')
        );

        if (! config('prms.sms.enabled', false)) {
            $sender->sendSync($phone, $message, $request->user()->id);

            return back()->with(
                'info',
                'SMS is disabled. The test was logged only (status: skipped). Set PRMS_SMS_ENABLED=true and run php artisan config:clear to send live SMS.'
            );
        }

        $sent = $sender->sendSync($phone, $message, $request->user()->id);

        Audit::log($request, 'admin.sms_test', 'System', null, null, ['phone' => $phone, 'sent' => $sent]);

        if (! $sent) {
            $detail = SmsDeliveryLog::query()
                ->where('phone', $phone)
                ->orderByDesc('id')
                ->value('provider_response');

            $message = 'Test SMS could not be delivered. Check gateway configuration and logs.';
            if (filled($detail)) {
                $message .= ' Provider response: '.Str::limit($detail, 200);
            }

            return back()->with('error', $message);
        }

        return back()->with('status', 'Test SMS sent to '.$phone.'.');
    }

    public function heartbeat(): RedirectResponse
    {
        $dir = storage_path('app/health');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put("{$dir}/queue-worker-heartbeat.txt", now()->toDateTimeString());

        return back()->with('info', 'Queue heartbeat updated.');
    }

    private function runArtisanChain(array $commands): void
    {
        foreach ($commands as $command) {
            Artisan::call($command);
        }
    }
}
