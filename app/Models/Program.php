<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $table = 'programmes';

    protected $fillable = [
        'programme_code',
        'programme_name',
        'department_id',
        'duration_years',
        'academic_level',
        'final_year',
        'output_type',
        'workflow_type',
        'allowed_project_years',
        'is_project_eligible',
        'project_year',
    ];

    protected $casts = [
        'duration_years' => 'integer',
        'final_year' => 'integer',
        'project_year' => 'integer',
        'is_project_eligible' => 'boolean',
        'allowed_project_years' => 'array',
    ];

    /**
     * @return list<int>
     */
    public function allowedProjectYearsList(): array
    {
        if (is_array($this->allowed_project_years) && $this->allowed_project_years !== []) {
            return array_values(array_map('intval', $this->allowed_project_years));
        }

        if ($this->final_year !== null) {
            return [(int) $this->final_year];
        }

        return [];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}

