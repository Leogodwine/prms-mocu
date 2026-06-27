<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSubmissionScreenshot extends Model
{
    protected $fillable = [
        'project_submission_id',
        'interface_name',
        'file_path',
        'original_filename',
        'mime_type',
        'sort_order',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ProjectSubmission::class, 'project_submission_id');
    }
}
