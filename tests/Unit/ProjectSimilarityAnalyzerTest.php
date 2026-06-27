<?php

namespace Tests\Unit;

use App\Models\ResearchProject;
use App\Services\Ollama\OllamaClient;
use App\Services\Similarity\ProjectSimilarityAnalyzer;
use Mockery;
use Tests\TestCase;

class ProjectSimilarityAnalyzerTest extends TestCase
{
    public function test_build_comparison_text_includes_title_and_abstract(): void
    {
        $analyzer = new ProjectSimilarityAnalyzer(Mockery::mock(OllamaClient::class));

        $project = new ResearchProject([
            'title' => 'Mobile banking adoption',
            'keywords' => 'fintech',
            'abstract' => 'This study examines user trust.',
            'project_type' => 'Thesis',
        ]);

        $text = $analyzer->buildComparisonText($project);

        $this->assertStringContainsString('Mobile banking adoption', $text);
        $this->assertStringContainsString('This study examines user trust.', $text);
        $this->assertStringContainsString('fintech', $text);
    }
}
