<?php

namespace App\Jobs;

use App\Models\ProjectSubmission;
use App\Services\Showcase\SubmissionShowcaseAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeSubmissionShowcaseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly int $submissionId,
    ) {}

    public function handle(SubmissionShowcaseAnalyzer $analyzer): void
    {
        $submission = ProjectSubmission::query()->find($this->submissionId);

        if (! $submission) {
            return;
        }

        try {
            $analyzer->analyze($submission);
        } catch (\Throwable $e) {
            Log::error('AnalyzeSubmissionShowcaseJob failed', [
                'submission_id' => $this->submissionId,
                'error' => $e->getMessage(),
            ]);

            $analyzer->markFailed($submission);
        }
    }
}
