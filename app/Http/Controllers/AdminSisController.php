<?php

namespace App\Http\Controllers;

use App\Models\StudentSisSyncLog;
use App\Support\PrmsTablePagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class AdminSisController extends Controller
{
    public function index(Request $request): View
    {
        $logs = StudentSisSyncLog::query()
            ->latest('sync_timestamp')
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        return view('admin.sis-sync', [
            'logs' => $logs,
        ]);
    }

    public function runSync(): RedirectResponse
    {
        Artisan::call('sis:sync-students');

        return back()->with([
            'status' => trim(Artisan::output()) ?: 'SIS student sync finished.',
            'status_preformatted' => true,
        ]);
    }

    public function runGenderBackfill(): RedirectResponse
    {
        Artisan::call('prms:backfill-student-gender');

        return back()->with([
            'status' => trim(Artisan::output()) ?: 'Student gender backfill finished.',
            'status_preformatted' => true,
        ]);
    }
}
