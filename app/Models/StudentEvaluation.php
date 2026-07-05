<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_rubric_id',
        'evaluator_id',
        'student_id',
        'project_group_id',
        'project_submission_id',
        'scores',
        'total_score',
        'general_comments',
        'status',
        'evaluation_scope',
    ];

    protected $casts = [
        'scores' => 'array',
    ];

    public function rubric()
    {
        return $this->belongsTo(EvaluationRubric::class, 'evaluation_rubric_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function projectGroup()
    {
        return $this->belongsTo(ProjectGroup::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function submission()
    {
        return $this->belongsTo(ProjectSubmission::class, 'project_submission_id');
    }
}
