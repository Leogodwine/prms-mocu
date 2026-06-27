<?php

namespace App\Jobs;

use App\Models\ResearchProject;
use App\Services\Similarity\ProjectSimilarityAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeProjectSimilarityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly int $projectId,
    ) {}

    public function handle(ProjectSimilarityAnalyzer $analyzer): void
    {
        $project = ResearchProject::query()->find($this->projectId);

        if (! $project) {
            return;
        }

        try {
            $analyzer->analyze($project);
        } catch (\Throwable $e) {
            Log::error('AnalyzeProjectSimilarityJob failed', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            $analyzer->markFailed($project);
        }
    }
}
