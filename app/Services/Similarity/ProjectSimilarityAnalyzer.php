<?php

namespace App\Services\Similarity;

use App\Models\ProjectSimilarity;
use App\Models\ResearchProject;
use App\Models\User;
use App\Notifications\ProjectNotification;
use App\Services\Ollama\OllamaClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProjectSimilarityAnalyzer
{
    public function __construct(
        private readonly OllamaClient $ollama,
    ) {}

    /**
     * Find and persist similar projects for the given record.
     *
     * @return int Number of similarity rows stored
     */
    public function analyze(ResearchProject $project): int
    {
        if (! Schema::hasTable('project_similarities')) {
            return 0;
        }

        $this->markStatus($project, 'processing');
        $project->save();

        if (! $this->ollama->isEnabled()) {
            $this->markStatus($project, 'disabled');
            $project->save();

            return 0;
        }

        if (! $this->ollama->isReachable()) {
            $this->markStatus($project, 'unavailable');
            $project->save();

            return 0;
        }

        $comparisonText = $this->buildComparisonText($project);

        if (mb_strlen($comparisonText) < 20) {
            $this->markStatus($project, 'skipped');
            $project->save();

            return 0;
        }

        ProjectSimilarity::query()
            ->where('project_id', $project->id)
            ->delete();

        $candidates = $this->rankCandidates($project, $comparisonText);
        $stored = 0;
        $storeThreshold = (float) config('ollama.similarity.store_threshold', 35);
        $model = (string) config('ollama.chat_model', 'mistral');
        $maxScore = 0.0;

        foreach ($candidates as $candidate) {
            /** @var ResearchProject $other */
            $other = $candidate['project'];
            $otherText = $this->buildComparisonText($other);

            try {
                $analysis = $this->ollama->compareResearchTexts($comparisonText, $otherText);
            } catch (\Throwable $e) {
                Log::error('Similarity analysis failed', [
                    'project_id' => $project->id,
                    'similar_project_id' => $other->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($analysis === null) {
                continue;
            }

            $score = (float) $analysis['similarity_score'];
            $maxScore = max($maxScore, $score);

            if ($score < $storeThreshold) {
                continue;
            }

            ProjectSimilarity::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'similar_project_id' => $other->id,
                ],
                [
                    'similarity_score' => $score,
                    'text_similarity_score' => $candidate['text_score'],
                    'risk_level' => $analysis['risk_level'],
                    'summary' => $analysis['summary'],
                    'overlap_areas' => $analysis['overlap_areas'],
                    'analysis_model' => $model,
                    'analyzed_at' => now(),
                ]
            );

            $stored++;
        }

        if (Schema::hasColumn('research_projects', 'plagiarism_score')) {
            $project->plagiarism_score = $maxScore > 0 ? $maxScore : null;
        }

        $this->markStatus($project, 'completed', $stored);
        $project->save();

        $this->notifyIfHighRisk($project, $stored);

        return $stored;
    }

    /**
     * @return Collection<int, object{project: ResearchProject, meta: ProjectSimilarity}>
     */
    public function similarProjectsFor(ResearchProject $project, int $limit = 10): Collection
    {
        if (! Schema::hasTable('project_similarities')) {
            return collect();
        }

        $rows = ProjectSimilarity::query()
            ->with(['similarProject.student', 'project.student'])
            ->where(function ($q) use ($project) {
                $q->where('project_id', $project->id)
                    ->orWhere('similar_project_id', $project->id);
            })
            ->orderByDesc('similarity_score')
            ->limit($limit * 2)
            ->get();

        $seen = [];
        $results = collect();

        foreach ($rows as $row) {
            $otherId = (int) ($row->project_id === $project->id ? $row->similar_project_id : $row->project_id);

            if (isset($seen[$otherId])) {
                continue;
            }

            $seen[$otherId] = true;
            $other = $row->project_id === $project->id ? $row->similarProject : $row->project;

            if ($other) {
                $results->push((object) ['project' => $other, 'meta' => $row]);
            }

            if ($results->count() >= $limit) {
                break;
            }
        }

        return $results;
    }

    public function buildComparisonText(ResearchProject $project): string
    {
        $parts = array_filter([
            $project->title ? 'Title: '.$project->title : null,
            $project->keywords ? 'Keywords: '.$project->keywords : null,
            $project->research_area ? 'Research area: '.$project->research_area : null,
            $project->project_type ? 'Type: '.$project->project_type : null,
            $project->abstract ? 'Problem/abstract: '.$project->abstract : null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * @return list<array{project: ResearchProject, text_score: float}>
     */
    private function rankCandidates(ResearchProject $project, string $comparisonText): array
    {
        $prefilterMin = (float) config('ollama.similarity.text_prefilter_min', 12);
        $maxCandidates = (int) config('ollama.similarity.max_candidates', 8);

        $others = ResearchProject::query()
            ->where('id', '!=', $project->id)
            ->whereNotNull('title')
            ->orderByDesc('updated_at')
            ->limit(250)
            ->get();

        $ranked = [];

        foreach ($others as $other) {
            $otherText = $this->buildComparisonText($other);

            if ($otherText === '') {
                continue;
            }

            similar_text(
                mb_strtolower($comparisonText),
                mb_strtolower($otherText),
                $percent
            );

            if ($percent < $prefilterMin) {
                continue;
            }

            $ranked[] = [
                'project' => $other,
                'text_score' => round($percent, 2),
            ];
        }

        usort($ranked, fn ($a, $b) => $b['text_score'] <=> $a['text_score']);

        return array_slice($ranked, 0, $maxCandidates);
    }

    private function markStatus(ResearchProject $project, string $status, int $matchCount = 0): void
    {
        if (Schema::hasColumn('research_projects', 'similarity_status')) {
            $project->similarity_status = $status;
        }

        if (Schema::hasColumn('research_projects', 'similarity_checked_at')) {
            $project->similarity_checked_at = now();
        }

        if ($status === 'completed' && $matchCount === 0 && Schema::hasColumn('research_projects', 'similarity_status')) {
            $project->similarity_status = 'clear';
        }
    }

    private function notifyIfHighRisk(ResearchProject $project, int $stored): void
    {
        if ($stored === 0) {
            return;
        }

        $alertThreshold = (float) config('ollama.similarity.alert_threshold', 65);

        $top = ProjectSimilarity::query()
            ->where('project_id', $project->id)
            ->where('similarity_score', '>=', $alertThreshold)
            ->orderByDesc('similarity_score')
            ->with('similarProject')
            ->first();

        if (! $top) {
            return;
        }

        $code = $project->project_code ?? '#'.$project->id;
        $otherCode = $top->similarProject?->project_code ?? '#'.$top->similar_project_id;
        $title = 'Similarity alert — '.$code;
        $message = sprintf(
            'Background review flagged %s as %.0f%% similar to %s. Open the admin similarity report for details.',
            $code,
            $top->similarity_score,
            $otherCode
        );
        $url = route('admin.similarities.index');

        User::query()
            ->where('role', 'admin')
            ->get()
            ->each(function (User $admin) use ($title, $message, $url) {
                $admin->notify(new ProjectNotification($title, $message, $url, 'View report'));
            });
    }

    public function markFailed(ResearchProject $project): void
    {
        $this->markStatus($project, 'failed');
        $project->save();
    }
}
