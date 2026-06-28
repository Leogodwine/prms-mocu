<?php

namespace App\Models;

use App\Enums\FinalYearRuleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $fillable = [
        'department_code',
        'department_name',
        'head_of_department',
        'contact_email',
        'default_programme_type',
        'supports_project',
        'supports_research',
        'final_year_rule_type',
        'fixed_final_year',
    ];

    protected $casts = [
        'supports_project' => 'boolean',
        'supports_research' => 'boolean',
        'fixed_final_year' => 'integer',
    ];

    public function finalYearRuleTypeEnum(): FinalYearRuleType
    {
        return FinalYearRuleType::tryFrom($this->final_year_rule_type ?? '')
            ?? FinalYearRuleType::ProgrammeDefined;
    }

    public function programmes(): HasMany
    {
        return $this->hasMany(Program::class, 'department_id');
    }

    public function workflowRules(): HasMany
    {
        return $this->hasMany(DepartmentWorkflowRule::class, 'department_id');
    }
}

