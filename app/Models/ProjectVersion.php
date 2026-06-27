<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectVersion extends Model
{
    use HasFactory;

    protected $table = 'project_versions';

    protected $fillable = [
        'project_id',
        'version_number',
        'version_note',
        'submitted_at',
        'submitted_by',
        'total_comments',
        'total_annotations',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }
}

