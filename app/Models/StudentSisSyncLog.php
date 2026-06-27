<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSisSyncLog extends Model
{
    protected $fillable = [
        'sync_timestamp',
        'records_processed',
        'records_added',
        'records_updated',
        'records_deactivated',
        'sync_status',
        'error_message',
        'initiated_by',
    ];

    protected $casts = [
        'sync_timestamp' => 'datetime',
    ];
}

