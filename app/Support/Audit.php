<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class Audit
{
    /**
     * Create an audit log entry.
     *
     * @param array<string, mixed>|null $old
     * @param array<string, mixed>|null $new
     */
    public static function log(
        Request $request,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $old = null,
        ?array $new = null,
    ): void {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}

