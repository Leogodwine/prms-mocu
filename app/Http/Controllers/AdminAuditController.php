<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function index(Request $request): View
    {
        $auditLogs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(25, ['*'], 'audit_page');

        $loginHistory = LoginHistory::query()
            ->with('user:id,name,email')
            ->latest('login_time')
            ->paginate(25, ['*'], 'login_page');

        return view('admin.audit', [
            'auditLogs' => $auditLogs,
            'loginHistory' => $loginHistory,
        ]);
    }
}

