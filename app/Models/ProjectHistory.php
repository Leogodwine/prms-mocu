<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHistory extends Model
{
    use HasFactory;

    protected $table = 'project_history';

    protected $fillable = [
        'project_id',
        'action',
        'previous_stage',
        'new_stage',
        'action_by',
        'action_reason',
        'action_notes',
        'ip_address',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }
}

