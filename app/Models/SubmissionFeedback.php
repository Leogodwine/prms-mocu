<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFeedback extends Model
{
    protected $table = 'submission_feedback';

    protected $fillable = [
        'project_submission_id',
        'supervisor_id',
        'comments',
        'decision',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ProjectSubmission::class, 'project_submission_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
