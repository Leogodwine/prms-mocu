<?php

namespace App\Models;

use App\Enums\AcademicLevel;
use Illuminate\Database\Eloquent\Model;

class AcademicLevelSetting extends Model
{
    protected $fillable = [
        'academic_level',
        'final_year_default',
        'final_stage_definition',
        'workflow_complexity',
        'output_rules',
    ];

    protected $casts = [
        'final_year_default' => 'integer',
        'output_rules' => 'array',
    ];

    public function levelEnum(): AcademicLevel
    {
        return AcademicLevel::tryFromMixed($this->academic_level);
    }

    public function defaultOutputType(): string
    {
        return (string) ($this->output_rules['default_output_type'] ?? 'RESEARCH_ONLY');
    }

    public function supportsProject(): bool
    {
        return (bool) ($this->output_rules['supports_project'] ?? true);
    }

    public function supportsResearch(): bool
    {
        return (bool) ($this->output_rules['supports_research'] ?? true);
    }
}
