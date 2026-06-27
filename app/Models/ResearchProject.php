<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchProject extends Model
{
    use HasFactory;

    protected $table = 'research_projects';

    protected $fillable = [
        'project_code',
        'student_id',
        'supervisor_id',
        'co_supervisor_id',
        'department_id',
        'faculty_id',
        'program_id',
        'academic_year_id',
        'semester_id',
        'project_type',
        'title',
        'abstract',
        'status',
        'keywords',
        'research_area',
        'current_stage',
        'funding_source',
        'ethical_clearance_number',
        'submission_deadline',
        'plagiarism_score',
        'similarity_checked_at',
        'similarity_status',
        'preview_enabled',
        'collaboration_enabled',
        'final_grade',
        'final_grade_letter',
        'is_archived',
        'archived_date',
        'is_public',
        'published_at',
        'project_group_id',
    ];

    protected $casts = [
        'abstract' => 'string',
        'similarity_checked_at' => 'datetime',
        'preview_enabled' => 'boolean',
        'collaboration_enabled' => 'boolean',
        'is_archived' => 'boolean',
        'is_public' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function coSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_supervisor_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function projectGroup(): BelongsTo
    {
        return $this->belongsTo(ProjectGroup::class, 'project_group_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'project_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'project_id');
    }

    public function projectVersions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class, 'project_id');
    }

    public function project_history(): HasMany
    {
        return $this->hasMany(ProjectHistory::class, 'project_id');
    }

    public function project_versions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class, 'project_id');
    }

    public function similarities(): HasMany
    {
        return $this->hasMany(ProjectSimilarity::class, 'project_id');
    }

    public function flaggedAsSimilarTo(): HasMany
    {
        return $this->hasMany(ProjectSimilarity::class, 'similar_project_id');
    }
}

