<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Support\PrmsTablePagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = PrmsTablePagination::perPage($request);

        $auditLogs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate($perPage, ['*'], 'audit_page')
            ->withQueryString();

        $loginHistory = LoginHistory::query()
            ->with('user:id,name,email')
            ->latest('login_time')
            ->paginate($perPage, ['*'], 'login_page')
            ->withQueryString();

        return view('admin.audit', [
            'auditLogs' => $auditLogs,
            'loginHistory' => $loginHistory,
        ]);
    }
}

