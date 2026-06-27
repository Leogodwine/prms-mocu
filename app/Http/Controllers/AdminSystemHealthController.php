<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\StudentSisSyncLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminSystemHealthController extends Controller
{
    public function index(): View
    {
        $queueFailed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;
        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->latest('failed_at')->limit(10)->get()
            : collect();

        $latestSisSync = StudentSisSyncLog::query()->latest('sync_timestamp')->first();
        $latestAudit = AuditLog::query()->latest()->first();
        $latestLogin = LoginHistory::query()->latest('login_time')->first();
        $heartbeatPath = storage_path('app/health/queue-worker-heartbeat.txt');
        $queueHeartbeat = File::exists($heartbeatPath) ? trim((string) File::get($heartbeatPath)) : null;

        return view('admin.system-health', [
            'queueFailed' => $queueFailed,
            'failedJobs' => $failedJobs,
            'latestSisSync' => $latestSisSync,
            'latestAudit' => $latestAudit,
            'latestLogin' => $latestLogin,
            'queueHeartbeat' => $queueHeartbeat,
            'recentAuditCount' => AuditLog::query()->where('created_at', '>=', now()->subDay())->count(),
            'recentLoginFailures' => LoginHistory::query()
                ->where('success', false)
                ->where('login_time', '>=', now()->subDay())
                ->count(),
        ]);
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

    public function heartbeat(): RedirectResponse
    {
        $dir = storage_path('app/health');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put("{$dir}/queue-worker-heartbeat.txt", now()->toDateTimeString());

        return back()->with('info', 'Queue heartbeat updated.');
    }
}

