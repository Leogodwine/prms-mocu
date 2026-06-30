<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeProjectSimilarityJob;
use App\Models\ProjectSimilarity;
use App\Models\ResearchProject;
use App\Services\Ollama\OllamaClient;
use App\Services\Similarity\ProjectSimilarityAnalyzer;
use App\Support\PrmsListFilters;
use App\Support\PrmsTablePagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProjectSimilarityController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $this->ensureAdmin($request);

        $defaults = [
            'min_score' => (string) config('ollama.similarity.store_threshold', 35),
        ];

        $resolved = PrmsListFilters::resolve(
            $request,
            'admin.similarities',
            $defaults,
            'admin.similarities.index',
            [],
            fn (array $filters) => [
                'min_score' => (string) max(0, min(100, (int) ($filters['min_score'] ?? $defaults['min_score']))),
            ]
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $minScore = (float) $resolved['filters']['min_score'];

        $pairs = ProjectSimilarity::query()
            ->with(['project.student', 'similarProject.student'])
            ->where('similarity_score', '>=', $minScore)
            ->orderByDesc('similarity_score')
            ->orderByDesc('analyzed_at')
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        $ollama = app(OllamaClient::class);

        return view('similarities.index', [
            'pairs' => $pairs,
            'minScore' => $minScore,
            'filters' => $resolved['filters'],
            'filterResetUrl' => PrmsListFilters::resetUrl('admin.similarities.index'),
            'ollamaReachable' => $ollama->isReachable(),
            'ollamaEnabled' => $ollama->isEnabled(),
        ]);
    }

    public function rerun(Request $request, ResearchProject $researchProject): RedirectResponse
    {
        $this->ensureAdmin($request);

        if (! Schema::hasTable('project_similarities')) {
            return back()->with('error', 'Similarity tracking is not available. Run database migrations.');
        }

        $ollama = app(OllamaClient::class);

        if (! $ollama->isEnabled()) {
            return back()->with('error', $this->ollamaUnavailableMessage());
        }

        if (! $ollama->isReachable()) {
            return back()->with('error', $this->ollamaUnavailableMessage());
        }

        try {
            if ($request->boolean('sync') || $request->input('sync')) {
                $count = app(ProjectSimilarityAnalyzer::class)->analyze($researchProject);

                return back()->with('status', "Similarity check complete — {$count} similar project(s) recorded.");
            }

            AnalyzeProjectSimilarityJob::dispatch($researchProject->id);

            return back()->with('status', 'Similarity check queued. Refresh shortly to see results.');
        } catch (\Throwable) {
            return back()->with('error', $this->ollamaUnavailableMessage());
        }
    }

    private function ensureAdmin(Request $request): void
    {
        if ($request->user()?->role !== 'admin') {
            abort(403);
        }
    }

    private function ollamaUnavailableMessage(): string
    {
        return 'The similarity service is not available. Ensure Ollama is running (ollama serve), Mistral is installed (ollama pull mistral), and the queue worker is active (php artisan queue:work).';
    }
}
