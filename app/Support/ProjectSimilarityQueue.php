<?php

namespace App\Support;

use App\Jobs\AnalyzeProjectSimilarityJob;
use App\Models\ResearchProject;
use Illuminate\Support\Facades\Schema;

class ProjectSimilarityQueue
{
    public static function dispatchFor(ResearchProject $project): void
    {
        if (! Schema::hasTable('project_similarities') || ! config('ollama.enabled', false)) {
            return;
        }

        AnalyzeProjectSimilarityJob::dispatch($project->id);
    }
}
