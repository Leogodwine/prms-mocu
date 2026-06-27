<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    use HasFactory;

    protected $table = 'submissions';

    protected $fillable = [
        'research_project_id',
        'project_id',
        'stage_id',
        'submission_type',
        'submission_stage',
        'version',
        'version_number',
        'file_path',
        'document_path',
        'file_name',
        'document_name',
        'preview_path',
        'file_size',
        'document_size',
        'file_type',
        'plagiarism_score',
        'review_status',
        'total_comments',
        'submitted_by',
        'submitted_at',
        'submission_date',
        'status',
        'review_due_date',
        'actual_review_date',
        'notes',
        'ip_address',
        'is_current',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'document_size' => 'integer',
        'submitted_at' => 'datetime',
        'submission_date' => 'datetime',
        'review_due_date' => 'date',
        'actual_review_date' => 'datetime',
        'version' => 'integer',
        'version_number' => 'integer',
        'submission_stage' => 'integer',
        'plagiarism_score' => 'decimal:3',
        'is_current' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'research_project_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}

