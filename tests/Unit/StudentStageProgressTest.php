<?php

namespace Tests\Unit;

use App\Models\ProjectSubmission;
use App\Support\StudentStageProgress;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentStageProgressTest extends TestCase
{
    #[Test]
    public function work_type_from_stage_classifies_tracks(): void
    {
        $this->assertSame('proposal', StudentStageProgress::workTypeFromStage('Proposal Chapter 1'));
        $this->assertSame('research', StudentStageProgress::workTypeFromStage('Research Chapter 5'));
        $this->assertSame('project', StudentStageProgress::workTypeFromStage('Source Code Submission'));
    }

    #[Test]
    public function filter_submissions_for_track_keeps_only_matching_stages(): void
    {
        $submissions = new Collection([
            $this->makeSubmission('Proposal Chapter 1'),
            $this->makeSubmission('Research Chapter 2'),
            $this->makeSubmission('Source Code Submission'),
        ]);

        $proposalOnly = StudentStageProgress::filterSubmissionsForTrack($submissions, 'proposal');

        $this->assertCount(1, $proposalOnly);
        $this->assertSame('Proposal Chapter 1', $proposalOnly->first()->stage);
    }

    private function makeSubmission(string $stage): ProjectSubmission
    {
        $submission = new ProjectSubmission;
        $submission->stage = $stage;

        return $submission;
    }
}
