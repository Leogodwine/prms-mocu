<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\ProjectStage;
use App\Models\ProjectSubmission;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Computes proposal / project / research stage progress for the student dashboard
 * using the same stage naming as {@see \App\Http\Controllers\StudentController}.
 */
final class StudentStageProgress
{
    /**
     * @return list<string>
     */
    public static function completeDocumentStageNames(): array
    {
        return [
            'Complete Proposal Document',
            'Complete Research Document',
            'Complete Project Document',
        ];
    }

    /**
     * @return list<string>
     */
    public static function presentationStageNames(): array
    {
        return [
            'Progress Presentation 1',
            'Progress Presentation 2',
            'Progress Presentation 3',
            'Final Presentation',
            RepositoryPublication::consentStageName(),
        ];
    }

    public static function completeSystemStageName(): string
    {
        return 'Complete System';
    }

    public static function isCompleteSystemStage(string $stageName): bool
    {
        $name = strtolower(trim($stageName));

        return $name === strtolower(self::completeSystemStageName())
            || str_contains($name, 'source code');
    }

    public static function isFinalPresentationStage(string $stageName): bool
    {
        return trim($stageName) === 'Final Presentation';
    }

    /**
     * @return array<string, string>
     */
    public static function systemInterfaceOptions(): array
    {
        return [
            'home_page' => 'Home page',
            'login' => 'Login page',
            'registration' => 'Registration page',
            'dashboard' => 'Dashboard',
            'buyer_dashboard' => 'Buyer dashboard',
            'seller_dashboard' => 'Seller dashboard',
            'admin_panel' => 'Admin panel',
            'reports' => 'Reports / analytics',
            'settings' => 'Settings page',
            'profile' => 'User profile',
            'checkout' => 'Checkout / payment',
            'other' => 'Other interface',
        ];
    }

    public static function resolveInterfaceLabel(string $key, ?string $customLabel = null): string
    {
        if ($key === 'other') {
            $custom = trim((string) $customLabel);

            return $custom !== '' ? $custom : 'Other interface';
        }

        return self::systemInterfaceOptions()[$key] ?? $key;
    }

    public static function isCompleteDocumentStage(string $stageName): bool
    {
        return in_array(trim($stageName), self::completeDocumentStageNames(), true);
    }

    public static function isPresentationStage(string $stageName): bool
    {
        return in_array(trim($stageName), self::presentationStageNames(), true);
    }

    public static function isConsentLetterStage(string $stageName): bool
    {
        return trim($stageName) === RepositoryPublication::consentStageName();
    }

    /**
     * Last chapter stage per track — must be supervisor-approved before complete document upload.
     */
    public static function finalChapterStageForTrack(string $track): ?string
    {
        return match (strtolower($track)) {
            'proposal' => 'Proposal Chapter 3',
            'research' => 'Research Chapter 5',
            'project' => self::completeSystemStageName(),
            default => null,
        };
    }

    public static function completeDocumentStageForTrack(string $track): ?string
    {
        return match (strtolower($track)) {
            'proposal' => 'Complete Proposal Document',
            'research' => 'Complete Research Document',
            'project' => 'Complete Project Document',
            default => null,
        };
    }

    /**
     * Whether a student/group may upload to this stage (stage-gate rules).
     */
    public static function canUploadStage(
        string $stageName,
        User $user,
        ?ProjectGroup $projectGroup,
        Collection $latestByStage,
    ): ?string {
        $stageName = trim($stageName);

        if ($proposalBlock = FinalYearWorkflowEngine::executionTrackBlockReason($user, $stageName, $latestByStage)) {
            return $proposalBlock;
        }

        if (self::isCompleteDocumentStage($stageName)) {
            if (trim($stageName) === 'Complete Project Document') {
                if (($latestByStage->get('Final Presentation')?->status ?? '') !== 'approved') {
                    return 'Complete and receive approval for Final Presentation before uploading the Complete Project Document.';
                }

                return null;
            }

            $track = self::workTypeFromCompleteDocumentStage($stageName);
            $finalChapter = self::finalChapterStageForTrack($track);
            if ($finalChapter === null) {
                return 'This stage is not available for your programme.';
            }

            $finalApproved = ($latestByStage->get($finalChapter)?->status ?? '') === 'approved';
            if (! $finalApproved) {
                return 'Your supervisor must approve '.$finalChapter.' before you can upload the '.self::completeStageShortLabel($track).'.';
            }

            return null;
        }

        if ($stageName === 'Progress Presentation 1') {
            if (($latestByStage->get(self::completeSystemStageName())?->status ?? '') !== 'approved') {
                return 'Your supervisor must approve '.self::completeSystemStageName().' before you can upload '.self::shortStageLabel($stageName).'.';
            }

            return null;
        }

        if (in_array($stageName, ['Progress Presentation 2', 'Progress Presentation 3'], true)) {
            $previous = match ($stageName) {
                'Progress Presentation 2' => 'Progress Presentation 1',
                'Progress Presentation 3' => 'Progress Presentation 2',
                default => null,
            };

            if (($latestByStage->get($previous)?->status ?? '') !== 'approved') {
                return 'Complete and receive approval for '.self::shortStageLabel($previous).' first.';
            }

            return null;
        }

        if (self::isConsentLetterStage($stageName)) {
            if (($latestByStage->get('Progress Presentation 3')?->status ?? '') !== 'approved') {
                return 'Complete and receive approval for '.self::shortStageLabel('Progress Presentation 3').' before uploading the consent letter.';
            }

            return null;
        }

        if (self::isFinalPresentationStage($stageName)) {
            $consentStage = RepositoryPublication::consentStageName();
            if (($latestByStage->get($consentStage)?->status ?? '') !== 'approved') {
                return 'Complete and receive approval for the Final Presentation Consent Letter before uploading the Final Presentation.';
            }

            return null;
        }

        if (self::isCompleteSystemStage($stageName) || preg_match('/chapter\s*\d+/i', $stageName)) {
            $previousStageName = self::previousGlobalStageName($stageName);
            if ($previousStageName !== null) {
                $previousApproved = ($latestByStage->get($previousStageName)?->status ?? '') === 'approved';
                if (! $previousApproved) {
                    return 'Your supervisor must approve '
                        .self::shortStageLabel($previousStageName)
                        .' before you can submit '
                        .self::shortStageLabel($stageName).'.';
                }
            }
        }

        return null;
    }

    /**
     * @return Collection<int, string|null> stage id => block reason (null when upload is allowed)
     */
    public static function uploadBlockReasonsForStages(
        Collection $stages,
        User $user,
        ?ProjectGroup $projectGroup,
        Collection $latestByStage,
    ): Collection {
        return $stages->mapWithKeys(fn (ProjectStage $stage) => [
            $stage->id => self::canUploadStage($stage->stage_name, $user, $projectGroup, $latestByStage),
        ]);
    }

    /**
     * Previous workflow stage by global {@see ProjectStage::$stage_order}.
     */
    public static function previousGlobalStageName(string $stageName): ?string
    {
        $currentOrder = ProjectStage::query()
            ->where('stage_name', trim($stageName))
            ->value('stage_order');

        if ($currentOrder === null || (int) $currentOrder <= 1) {
            return null;
        }

        return ProjectStage::query()
            ->where('stage_order', (int) $currentOrder - 1)
            ->value('stage_name');
    }

    public static function workTypeFromCompleteDocumentStage(string $stageName): string
    {
        return match (trim($stageName)) {
            'Complete Proposal Document' => 'proposal',
            'Complete Research Document' => 'research',
            'Complete Project Document' => 'project',
            default => 'other',
        };
    }

    /**
     * Only complete documents may be submitted to the coordinator for finalization.
     * Requires supervisor approval on the document AND on the consent letter.
     */
    public static function isCoordinatorEligibleStage(string $stageName): bool
    {
        return self::isCompleteDocumentStage(trim($stageName));
    }

    public static function canSubmitToCoordinator(ProjectSubmission $submission, User $user, ?ProjectGroup $projectGroup): ?string
    {
        if (! self::isCompleteDocumentStage((string) $submission->stage)) {
            return 'Only complete proposal, research, or project documents can be sent to the coordinator.';
        }

        if ($submission->status !== 'approved') {
            $track = self::workTypeFromCompleteDocumentStage((string) $submission->stage);

            return 'Your supervisor must approve this '.self::completeStageShortLabel($track).' first.';
        }

        if (trim((string) $submission->stage) === 'Complete Project Document'
            && ! RepositoryPublication::hasSupervisorSignedConsent($user, $projectGroup)) {
            return 'Your supervisor must sign the Final Presentation Consent Letter and forward it to the coordinator before you can submit the complete project document.';
        }

        return null;
    }

    /**
     * Stages belonging to a workspace track (proposal, project, research).
     *
     * @return Collection<int, ProjectStage>
     */
    public static function stagesForTrack(Collection $allStagesOrdered, string $track): Collection
    {
        $t = strtolower($track);

        return $allStagesOrdered
            ->filter(function (ProjectStage $s) use ($t) {
                $n = strtolower($s->stage_name);

                return match ($t) {
                    'proposal' => str_contains($n, 'proposal') && ! str_contains($n, 'presentation'),
                    'research' => str_contains($n, 'research'),
                    'project' => str_contains($n, 'complete system')
                        || str_contains($n, 'source')
                        || str_contains($n, 'presentation')
                        || str_contains($n, 'consent')
                        || $s->stage_name === 'Complete Project Document',
                    'complete' => str_contains($n, 'complete'),
                    default => str_contains($n, $t),
                };
            })
            ->values();
    }

    /**
     * Latest submission per stage name (highest version, then highest id).
     *
     * @return Collection<string, ProjectSubmission>
     */
    public static function latestSubmissionByStage(User $user, ?ProjectGroup $projectGroup): Collection
    {
        $rows = ProjectSubmission::query()
            ->where(function ($query) use ($user, $projectGroup) {
                $query->where('student_id', $user->id);
                if ($projectGroup) {
                    $query->orWhere('project_group_id', $projectGroup->id);
                }
            })
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get();

        return $rows
            ->groupBy(fn (ProjectSubmission $s) => (string) $s->stage)
            ->map(fn (Collection $group) => $group->first());
    }

    /**
     * @return array{approved: int, in_progress: int, total: int, percent: int}
     */
    public static function summarizeTrack(Collection $trackStages, Collection $latestByStage): array
    {
        $approved = 0;
        $inProgress = 0;

        foreach ($trackStages as $stage) {
            $row = $latestByStage->get($stage->stage_name);
            if (!$row) {
                continue;
            }
            $st = strtolower((string) ($row->status ?? ''));
            if ($st === 'approved') {
                $approved++;
            } elseif (self::isInProgressStatus($st)) {
                $inProgress++;
            }
        }

        $total = $trackStages->count();
        $engaged = $approved + $inProgress;
        $percent = $total > 0 ? (int) round(min(100, ($engaged / $total) * 100)) : 0;

        return [
            'approved' => $approved,
            'in_progress' => $inProgress,
            'total' => $total,
            'percent' => $percent,
        ];
    }

    private static function isInProgressStatus(string $status): bool
    {
        return in_array($status, ['draft', 'pending', 'submitted', 'under_review', 'needs_revision'], true);
    }

    /**
     * Classify a stage name into proposal, research, or project track.
     */
    public static function workTypeFromStage(?string $stageName): string
    {
        $n = strtolower(trim((string) $stageName));

        if ($n === '') {
            return 'other';
        }

        if (self::isCompleteDocumentStage((string) $stageName)) {
            return self::workTypeFromCompleteDocumentStage((string) $stageName);
        }

        if (self::isPresentationStage((string) $stageName)) {
            return 'project';
        }

        if (str_contains($n, 'proposal')) {
            return 'proposal';
        }

        if (str_contains($n, 'research')) {
            return 'research';
        }

        if ($n === 'final report') {
            return 'research';
        }

        if (str_contains($n, 'source') || str_contains($n, 'complete system')) {
            return 'project';
        }

        return 'other';
    }

    /**
     * @return array<int, string>
     */
    public static function workTypeOptions(): array
    {
        return ['proposal', 'research', 'project'];
    }

    public static function workTypeLabel(string $type): string
    {
        return match ($type) {
            'proposal' => 'Proposal',
            'research' => 'Research',
            'project' => 'Project',
            default => 'Other',
        };
    }

    /**
     * Workspace tracks shown on the student overview, in sidebar order.
     *
     * @return list<string>
     */
    public static function tracksForRole(string $role): array
    {
        $tracks = ['proposal', 'research'];

        if (in_array($role, ['project_student', 'normal_student', 'student'], true)) {
            $tracks[] = 'project';
        }

        return $tracks;
    }

    /**
     * Workspace tracks available to a student based on programme rules.
     *
     * @return list<string>
     */
    public static function tracksForUser(User $user): array
    {
        return StudentResearchEligibility::availableTracks($user);
    }

    public static function trackNavLabel(string $track): string
    {
        return match ($track) {
            'proposal' => 'Research proposal',
            'research' => 'Research report',
            'project' => 'Project workspace',
            default => self::workTypeLabel($track),
        };
    }

    /**
     * @param  Collection<int, ProjectSubmission>  $submissions
     * @return Collection<int, ProjectSubmission>
     */
    public static function filterSubmissionsForTrack(Collection $submissions, string $workType): Collection
    {
        $t = strtolower($workType);

        if (! in_array($t, self::workTypeOptions(), true)) {
            return $submissions->values();
        }

        return $submissions
            ->filter(fn (ProjectSubmission $s) => self::workTypeFromStage($s->stage) === $t)
            ->values();
    }

    /**
     * Apply work-type filter to a query on project_submissions.stage.
     */
    public static function scopeWorkType($query, string $workType): void
    {
        $t = strtolower($workType);

        if (! in_array($t, self::workTypeOptions(), true)) {
            return;
        }

        $query->where(function ($inner) use ($t) {
            match ($t) {
                'proposal' => $inner->whereRaw('LOWER(stage) LIKE ?', ['%proposal%']),
                'research' => $inner->where(function ($q) {
                    $q->whereRaw('LOWER(stage) LIKE ?', ['%research%'])
                        ->orWhere('stage', 'Final Report');
                }),
                'project' => $inner->where(function ($q) {
                    $q->whereRaw('LOWER(stage) LIKE ?', ['%source%'])
                        ->orWhereRaw('LOWER(stage) LIKE ?', ['%presentation%'])
                        ->orWhereRaw('LOWER(stage) LIKE ?', ['%consent%'])
                        ->orWhere('stage', 'Complete Project Document');
                }),
            };
        });
    }

    /**
     * Compact label for sidebar chapter links.
     */
    public static function shortStageLabel(string $stageName): string
    {
        $trimmed = trim($stageName);
        $lower = strtolower($trimmed);

        $known = [
            'Complete Proposal Document' => 'Complete Proposal',
            'Complete Research Document' => 'Complete Research',
            'Complete Project Document' => 'Complete Project',
            'Complete System' => 'Complete System',
            RepositoryPublication::consentStageName() => 'Consent Letter',
            'Final Presentation' => 'Final Presentation',
            'Progress Presentation 1' => 'Progress Presentation 1',
            'Progress Presentation 2' => 'Progress Presentation 2',
            'Progress Presentation 3' => 'Progress Presentation 3',
        ];

        if (isset($known[$trimmed])) {
            return $known[$trimmed];
        }

        if (str_contains($lower, 'consent')) {
            return 'Consent Letter';
        }

        if (str_contains($lower, 'presentation')) {
            if (self::isFinalPresentationStage($stageName)) {
                return 'Final Presentation';
            }

            if (preg_match('/presentation\s*(\d+)/i', $stageName, $matches)) {
                return 'Progress Presentation '.$matches[1];
            }

            return 'Presentation';
        }

        if (self::isCompleteSystemStage($stageName)) {
            return 'Complete System';
        }

        if (str_contains($lower, 'complete')) {
            return 'Complete document';
        }

        if (preg_match('/chapter\s*(\d+)/i', $stageName, $matches)) {
            return 'Chapter '.$matches[1];
        }

        if (str_contains($lower, 'source')) {
            return 'Source code';
        }

        return $stageName;
    }

    /**
     * Student-facing label for journey steppers (may differ from sidebar chapter links).
     */
    public static function journeyStageLabel(string $stageName): string
    {
        $trimmed = trim($stageName);

        $journeyOverrides = [
            'Progress Presentation 1' => 'Presentation 1',
            'Progress Presentation 2' => 'Presentation 2',
            'Progress Presentation 3' => 'Presentation 3',
        ];

        if (isset($journeyOverrides[$trimmed])) {
            return $journeyOverrides[$trimmed];
        }

        return self::shortStageLabel($stageName);
    }

    /**
     * Project workspace stages shown in sidebar navigation (display order).
     *
     * @return list<string>
     */
    public static function projectNavStageNames(): array
    {
        return [
            'Progress Presentation 1',
            'Progress Presentation 2',
            'Progress Presentation 3',
            RepositoryPublication::consentStageName(),
            'Final Presentation',
            'Complete Project Document',
        ];
    }

    public static function isProjectNavStage(string $stageName): bool
    {
        return in_array(trim($stageName), self::projectNavStageNames(), true);
    }

    /**
     * Student-facing sort order for workspace chapter links (display only).
     *
     * @return Collection<int, ProjectStage>
     */
    public static function stagesForNavTrack(Collection $allStagesOrdered, string $track): Collection
    {
        $stages = self::stagesForTrack($allStagesOrdered, $track);

        if (strtolower($track) === 'project') {
            $stages = $stages->filter(
                fn (ProjectStage $stage) => self::isProjectNavStage($stage->stage_name)
            );
        }

        return $stages
            ->sortBy(fn (ProjectStage $stage) => self::navStageSortOrder($stage->stage_name, $track))
            ->values();
    }

    public static function navStageSortOrder(string $stageName, string $track): int
    {
        $name = trim($stageName);
        $track = strtolower($track);

        if ($track === 'proposal') {
            if (preg_match('/chapter\s*(\d+)/i', $name, $matches)) {
                return (int) $matches[1];
            }

            if ($name === 'Complete Proposal Document') {
                return 100;
            }
        }

        if ($track === 'research') {
            if (preg_match('/chapter\s*(\d+)/i', $name, $matches)) {
                return (int) $matches[1];
            }

            if ($name === 'Complete Research Document') {
                return 100;
            }
        }

        if ($track === 'project') {
            if (self::isConsentLetterStage($name)) {
                return 40;
            }

            return match ($name) {
                'Progress Presentation 1' => 10,
                'Progress Presentation 2' => 20,
                'Progress Presentation 3' => 30,
                'Final Presentation' => 50,
                'Complete Project Document' => 60,
                default => 999,
            };
        }

        return 999;
    }

    public static function navTrackOverviewLabel(string $track): string
    {
        return match (strtolower($track)) {
            'proposal' => 'Proposal overview',
            'research' => 'Report overview',
            'project' => 'Project overview',
            default => 'Overview',
        };
    }

    public static function completeStageShortLabel(string $track): string
    {
        return match (strtolower($track)) {
            'proposal' => 'complete proposal',
            'research' => 'complete research report',
            'project' => 'complete project document',
            default => 'complete document',
        };
    }

    /**
     * Google Material Symbols name for a workspace stage row (sidebar sub-panel).
     */
    public static function stageMaterialIcon(string $stageName, string $trackType = '', int $index = 0): string
    {
        $lower = strtolower($stageName);

        if (str_contains($lower, 'source')) {
            return 'code';
        }

        if (str_contains($lower, 'consent')) {
            return 'verified';
        }

        if (str_contains($lower, 'presentation')) {
            return 'present_to_all';
        }

        $iconsByTrack = [
            'proposal' => ['article', 'edit_note', 'fact_check', 'description'],
            'research' => ['menu_book', 'auto_stories', 'library_books', 'science', 'school', 'description'],
            'project' => ['terminal', 'integration_instructions', 'deployed_code', 'description'],
            'presentation' => ['present_to_all', 'co_present', 'groups', 'verified'],
        ];

        if (isset($iconsByTrack[$trackType])) {
            $icons = $iconsByTrack[$trackType];

            return $icons[$index % count($icons)];
        }

        return 'description';
    }

    /**
     * Material Symbols name for track section headers in the sub-panel.
     */
    public static function trackMaterialIcon(string $trackType): string
    {
        return match ($trackType) {
            'proposal' => 'description',
            'project' => 'terminal',
            'research' => 'menu_book',
            'presentation' => 'present_to_all',
            default => 'folder',
        };
    }

    /**
     * Sidebar status badge for a stage submission.
     *
     * @return array{class: string, icon: string, title: string}
     */
    public static function navStatusMeta(?ProjectSubmission $submission): array
    {
        if (! $submission) {
            return ['class' => 'secondary', 'icon' => 'far fa-circle', 'title' => 'Not started'];
        }

        return match (strtolower((string) $submission->status)) {
            'approved' => ['class' => 'success', 'icon' => 'fas fa-check', 'title' => 'Approved'],
            'pending' => ['class' => 'warning', 'icon' => 'far fa-clock', 'title' => 'Awaiting review'],
            'needs_revision' => ['class' => 'warning', 'icon' => 'fas fa-undo', 'title' => 'Returned'],
            'rejected' => ['class' => 'danger', 'icon' => 'fas fa-times', 'title' => 'Rejected'],
            default => ['class' => 'info', 'icon' => 'fas fa-spinner', 'title' => 'In progress'],
        };
    }

    /**
     * Group project-track submissions so consent letter versions sit at the
     * bottom of each student or group block in the UI.
     *
     * @param  Collection<int, ProjectSubmission>|LengthAwarePaginator<int, ProjectSubmission>  $submissions
     * @return Collection<int, array{key: string, representative: ProjectSubmission, main: Collection<int, ProjectSubmission>, consent: Collection<int, ProjectSubmission>}>
     */
    public static function groupSubmissionsForDisplay(Collection|LengthAwarePaginator $submissions): Collection
    {
        $items = $submissions instanceof LengthAwarePaginator
            ? $submissions->getCollection()
            : $submissions;

        return $items
            ->groupBy(fn (ProjectSubmission $submission) => $submission->project_group_id
                ? 'group-'.$submission->project_group_id
                : 'student-'.($submission->student_id ?? 0))
            ->map(function (Collection $items, string $key) {
                $consent = $items
                    ->filter(fn (ProjectSubmission $submission) => self::isConsentLetterStage((string) $submission->stage))
                    ->sortByDesc(fn (ProjectSubmission $submission) => [(int) $submission->version, (int) $submission->id])
                    ->values();

                $main = $items
                    ->reject(fn (ProjectSubmission $submission) => self::isConsentLetterStage((string) $submission->stage))
                    ->sortBy(function (ProjectSubmission $submission) {
                        $order = ProjectStage::query()
                            ->where('stage_name', $submission->stage)
                            ->value('stage_order');

                        return [(int) ($order ?? 999), -(int) $submission->version];
                    })
                    ->values();

                $representative = $main->first() ?? $consent->first();

                return [
                    'key' => $key,
                    'representative' => $representative,
                    'main' => $main,
                    'consent' => $consent,
                ];
            })
            ->filter(fn (array $group) => $group['representative'] instanceof ProjectSubmission
                && $group['main']->isNotEmpty())
            ->sortByDesc(fn (array $group) => $group['representative']->created_at?->timestamp ?? 0)
            ->values();
    }
}
