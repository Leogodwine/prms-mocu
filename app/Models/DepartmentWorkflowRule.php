<?php

namespace App\Models;

use App\Enums\AcademicLevel;
use App\Enums\ProgramOutputType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentWorkflowRule extends Model
{
    protected $fillable = [
        'department_id',
        'academic_level',
        'final_year',
        'output_type',
        'workflow_type',
        'is_active',
    ];

    protected $casts = [
        'final_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function academicLevelEnum(): AcademicLevel
    {
        return AcademicLevel::tryFromMixed($this->academic_level);
    }

    public function outputTypeEnum(): ?ProgramOutputType
    {
        return $this->output_type ? ProgramOutputType::tryFromMixed($this->output_type) : null;
    }
}
