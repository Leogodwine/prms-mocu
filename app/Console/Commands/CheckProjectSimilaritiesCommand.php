<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeProjectSimilarityJob;
use App\Models\ResearchProject;
use App\Services\Similarity\ProjectSimilarityAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckProjectSimilaritiesCommand extends Command
{
    protected $signature = 'projects:check-similarities
                            {--project= : Analyze a single research_projects.id}
                            {--sync : Run inline instead of queueing}
                            {--force : Re-analyze even if recently checked}';

    protected $description = 'Compare projects/research for similarity using Ollama (Mistral 7B)';

    public function handle(ProjectSimilarityAnalyzer $analyzer): int
    {
        if (! Schema::hasTable('project_similarities')) {
            $this->error('Run migrations first: php artisan migrate');

            return self::FAILURE;
        }

        $projectId = $this->option('project');

        $query = ResearchProject::query()->orderBy('id');

        if ($projectId) {
            $query->where('id', $projectId);
        } elseif (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('similarity_checked_at')
                    ->orWhere('similarity_checked_at', '<', now()->subDay());
            });
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('No projects pending similarity analysis.');

            return self::SUCCESS;
        }

        $this->info('Analyzing '.$projects->count().' project(s)…');

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        foreach ($projects as $project) {
            if ($this->option('sync')) {
                $count = $analyzer->analyze($project);
                $this->newLine();
                $this->line("  #{$project->id} {$project->project_code}: {$count} similar match(es)");
            } else {
                AnalyzeProjectSimilarityJob::dispatch($project->id);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->option('sync')) {
            $this->info('Similarity analysis finished.');
        } else {
            $this->info('Jobs queued. Ensure a worker is running: php artisan queue:work');
        }

        return self::SUCCESS;
    }
}
