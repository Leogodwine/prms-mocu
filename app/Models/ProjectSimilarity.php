<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSimilarity extends Model
{
    protected $fillable = [
        'project_id',
        'similar_project_id',
        'similarity_score',
        'text_similarity_score',
        'risk_level',
        'summary',
        'overlap_areas',
        'analysis_model',
        'analyzed_at',
    ];

    protected $casts = [
        'similarity_score' => 'decimal:2',
        'text_similarity_score' => 'decimal:2',
        'overlap_areas' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'project_id');
    }

    public function similarProject(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'similar_project_id');
    }

    public static function riskLevelForScore(float $score): string
    {
        $levels = config('ollama.similarity.risk_levels', ['low' => 39, 'medium' => 64, 'high' => 100]);

        if ($score <= ($levels['low'] ?? 39)) {
            return 'low';
        }

        if ($score <= ($levels['medium'] ?? 64)) {
            return 'medium';
        }

        return 'high';
    }
}
