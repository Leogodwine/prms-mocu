<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\ProjectSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Controls when coordinator-finalized work becomes visible in the repository.
 * Complete project documents require a supervisor-signed consent letter that
 * the coordinator has also finalized.
 */
final class RepositoryPublication
{
    public static function consentStageName(): string
    {
        return 'Final Presentation Consent Letter';
    }

    public static function requiresConsentForStage(string $stageName): bool
    {
        return trim($stageName) === 'Complete Project Document';
    }

    public static function latestConsentSubmission(User $user, ?ProjectGroup $projectGroup): ?ProjectSubmission
    {
        return ProjectSubmission::query()
            ->where('stage', self::consentStageName())
            ->where(function (Builder $query) use ($user, $projectGroup) {
                $query->where('student_id', $user->id);
                if ($projectGroup) {
                    $query->orWhere('project_group_id', $projectGroup->id);
                }
            })
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Supervisor has signed the consent letter and forwarded it to the coordinator.
     */
    public static function hasSupervisorSignedConsent(User $user, ?ProjectGroup $projectGroup): bool
    {
        $consent = self::latestConsentSubmission($user, $projectGroup);

        return $consent !== null
            && strtolower((string) $consent->status) === 'approved'
            && $consent->supervisor_consent_signed_at !== null
            && $consent->submitted_to_coordinator;
    }

    /**
     * Consent letter is fully approved: supervisor signed and coordinator finalized.
     */
    public static function hasApprovedConsent(User $user, ?ProjectGroup $projectGroup): bool
    {
        $consent = self::latestConsentSubmission($user, $projectGroup);

        return $consent !== null
            && strtolower((string) $consent->status) === 'approved'
            && $consent->coordinator_approved_at !== null
            && $consent->submitted_to_coordinator;
    }

    public static function isRepositoryVisible(ProjectSubmission $submission): bool
    {
        if (! StudentStageProgress::isCompleteDocumentStage((string) $submission->stage)) {
            return false;
        }

        if ($submission->repository_published_at !== null) {
            return true;
        }

        $student = $submission->student ?? new User;

        if (self::requiresConsentForStage((string) $submission->stage)) {
            return strtolower((string) $submission->status) === 'approved'
                && self::hasApprovedConsent($student, $submission->projectGroup);
        }

        return $submission->coordinator_approved_at !== null;
    }

    /**
     * Publish complete documents for a group/individual once the coordinator
     * approves the consent letter.
     */
    public static function publishScopeOnConsentCoordinatorApproval(ProjectSubmission $consentSubmission): int
    {
        if (! StudentStageProgress::isConsentLetterStage((string) $consentSubmission->stage)) {
            return 0;
        }

        $student = $consentSubmission->student;
        if ($student === null) {
            return 0;
        }

        $projectGroup = $consentSubmission->projectGroup;
        $published = 0;

        $projectDocuments = self::scopeCompleteDocuments($student, $projectGroup)
            ->where('stage', 'Complete Project Document')
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['approved'])
            ->get();

        foreach ($projectDocuments as $submission) {
            if (self::markPublished($submission, $consentSubmission)) {
                $published++;
            }
        }

        $otherDocuments = self::scopeCompleteDocuments($student, $projectGroup)
            ->whereNotNull('coordinator_approved_at')
            ->whereIn('stage', [
                'Complete Proposal Document',
                'Complete Research Document',
            ])
            ->get();

        foreach ($otherDocuments as $submission) {
            if (self::markPublished($submission, $consentSubmission)) {
                $published++;
            }
        }

        return $published;
    }

    public static function tryPublishOnCoordinatorFinalize(ProjectSubmission $submission): void
    {
        if (! StudentStageProgress::isCompleteDocumentStage((string) $submission->stage)) {
            return;
        }

        $student = $submission->student;
        if ($student === null) {
            return;
        }

        if (self::requiresConsentForStage((string) $submission->stage)
            && ! self::hasApprovedConsent($student, $submission->projectGroup)) {
            return;
        }

        if (self::markPublished($submission)) {
            return;
        }
    }

    private static function scopeCompleteDocuments(User $student, ?ProjectGroup $projectGroup): Builder
    {
        return ProjectSubmission::query()
            ->whereNull('repository_published_at')
            ->whereIn('stage', StudentStageProgress::completeDocumentStageNames())
            ->where(function (Builder $query) use ($student, $projectGroup) {
                $query->where('student_id', $student->id);
                if ($projectGroup) {
                    $query->orWhere('project_group_id', $projectGroup->id);
                }
            });
    }

    private static function markPublished(
        ProjectSubmission $submission,
        ?ProjectSubmission $consentSubmission = null,
    ): bool {
        if ($submission->repository_published_at !== null) {
            PublicPortalPublication::syncSubmission($submission);

            return false;
        }

        $supervisorId = $consentSubmission?->supervisor_consent_signed_by;

        $submission->update([
            'repository_published_at' => now(),
            'coordinator_approved_at' => $submission->coordinator_approved_at ?? now(),
            'supervisor_consent_signed_at' => $submission->supervisor_consent_signed_at
                ?? $consentSubmission?->supervisor_consent_signed_at
                ?? now(),
            'supervisor_consent_signed_by' => $submission->supervisor_consent_signed_by
                ?? $supervisorId,
        ]);

        PublicPortalPublication::syncSubmission($submission->fresh());

        return true;
    }
}
