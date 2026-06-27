<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeSubmissionShowcaseJob;
use App\Models\ProjectSubmission;
use App\Support\SubmissionFileAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubmissionShowcaseController extends Controller
{
    public function show(Request $request, ProjectSubmission $submission): View
    {
        SubmissionFileAccess::authorize($request->user(), $submission);

        if (! $submission->isProjectShowcase()) {
            abort(404, 'This submission does not have a system showcase.');
        }

        $this->dispatchAnalysisIfNeeded($submission);

        $submission->load(['student', 'projectGroup.members']);

        $stageLabel = Str::title(str_replace('_', ' ', (string) $submission->stage));
        $statusMeta = $this->statusMeta($submission);
        $showcaseAuthor = $submission->student?->name
            ?? $submission->projectGroup?->members->first()?->name
            ?? 'MoCU Scholar';

        return view('submissions.showcase', [
            'submission' => $submission,
            'stageLabel' => $stageLabel,
            'statusLabel' => $statusMeta['label'],
            'statusBadge' => $statusMeta['badge'],
            'showcaseAuthor' => $showcaseAuthor,
            'githubHandle' => $this->githubHandle($showcaseAuthor),
            'repoSlug' => $this->repoSlug((string) $submission->title),
            'backUrl' => $this->resolveBackUrl($request),
            'openDemo' => $request->query('open') === 'demo',
        ]);
    }

    public function meta(ProjectSubmission $submission): JsonResponse
    {
        SubmissionFileAccess::authorize(request()->user(), $submission);

        $this->dispatchAnalysisIfNeeded($submission);

        return response()->json([
            'status' => $submission->showcase_analysis_status ?? 'pending',
            'overview' => $submission->showcase_doc_summary,
            'significance' => $submission->showcase_doc_significance,
            'readme_body' => $submission->showcase_readme_body,
            'tree' => $submission->showcase_archive_tree ?? [],
        ]);
    }

    private function dispatchAnalysisIfNeeded(ProjectSubmission $submission): void
    {
        if ($submission->isProjectShowcase()
            && ! in_array($submission->showcase_analysis_status, ['pending', 'completed'], true)) {
            AnalyzeSubmissionShowcaseJob::dispatch($submission->id);
        }
    }

    /**
     * @return array{label: string, badge: string}
     */
    private function statusMeta(ProjectSubmission $submission): array
    {
        return match ($submission->status) {
            'approved' => ['label' => 'Approved', 'badge' => 'bg-success'],
            'rejected' => ['label' => 'Rejected', 'badge' => 'bg-danger'],
            'needs_revision' => ['label' => 'Returned for revision', 'badge' => 'bg-warning text-dark'],
            'pending' => ['label' => 'Awaiting review', 'badge' => 'bg-warning text-dark'],
            'submitted' => ['label' => 'Submitted', 'badge' => 'bg-warning text-dark'],
            'under_review' => ['label' => 'Under review', 'badge' => 'bg-warning text-dark'],
            default => [
                'label' => Str::title(str_replace('_', ' ', (string) $submission->status)),
                'badge' => 'bg-secondary',
            ],
        };
    }

    private function githubHandle(string $name): string
    {
        $handle = strtolower(preg_replace('/[^a-z0-9]+/', '', $name) ?? '');

        return $handle !== '' ? Str::limit($handle, 24, '') : 'mocu-scholar';
    }

    private function repoSlug(string $title): string
    {
        $slug = Str::slug($title);

        return $slug !== '' ? Str::limit($slug, 48, '') : 'project-repository';
    }

    private function resolveBackUrl(Request $request): string
    {
        $return = $request->query('return');
        if (is_string($return) && $return !== '' && str_starts_with($return, url('/'))) {
            return $return;
        }

        $user = $request->user();

        return match ($user?->role) {
            'supervisor' => route('supervisor.index'),
            'coordinator' => route('coordinator.index'),
            'hod' => route('hod.index'),
            default => route('student.index'),
        };
    }
}
