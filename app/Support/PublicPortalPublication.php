<?php

namespace App\Support;

use App\Models\Document;
use App\Models\ProjectSubmission;
use App\Models\ResearchProject;
use App\Models\SupervisorAssignment;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mirrors coordinator-published PRMS submissions into the public
 * research_projects / documents tables used by the public repository.
 */
final class PublicPortalPublication
{
    public static function syncSubmission(ProjectSubmission $submission): ?ResearchProject
    {
        if ($submission->repository_published_at === null) {
            return null;
        }

        if (! StudentStageProgress::isCompleteDocumentStage((string) $submission->stage)) {
            return null;
        }

        if (! $submission->file_path) {
            return null;
        }

        if (! Schema::hasTable('research_projects') || ! Schema::hasTable('documents')) {
            return null;
        }

        $submission->loadMissing([
            'student.studentProfile.programme',
            'projectGroup.supervisorAssignment',
        ]);

        $student = $submission->student;
        if ($student === null) {
            return null;
        }

        $workType = StudentStageProgress::workTypeFromStage((string) $submission->stage);
        if (! in_array($workType, ['proposal', 'research', 'project'], true)) {
            return null;
        }

        $project = self::resolveResearchProject($submission, $student->id, $workType);
        $project->update(self::researchProjectPayload($submission, $student->id, $workType));

        self::syncDocument($project, $submission, $workType);

        return $project->fresh();
    }

    /**
     * Re-run consent-triggered publication and public-portal sync for records
     * that were coordinator-approved before the bridge existed.
     */
    public static function backfillFromSubmissions(): int
    {
        $synced = 0;

        ProjectSubmission::query()
            ->where('stage', RepositoryPublication::consentStageName())
            ->whereNotNull('coordinator_approved_at')
            ->orderBy('id')
            ->each(function (ProjectSubmission $consent) use (&$synced): void {
                $synced += RepositoryPublication::publishScopeOnConsentCoordinatorApproval($consent);
            });

        ProjectSubmission::query()
            ->whereNotNull('repository_published_at')
            ->whereIn('stage', StudentStageProgress::completeDocumentStageNames())
            ->orderBy('id')
            ->each(function (ProjectSubmission $submission): void {
                self::syncSubmission($submission);
            });

        return $synced;
    }

    public static function resolveShowcaseSubmission(ResearchProject $project): ?ProjectSubmission
    {
        $document = $project->relationLoaded('documents')
            ? $project->documents
                ->where('is_public', true)
                ->where('is_current_version', true)
                ->sortByDesc('upload_date')
                ->first()
            : Document::query()
                ->where('project_id', $project->id)
                ->where('is_public', true)
                ->where('is_current_version', true)
                ->latest('upload_date')
                ->first();

        $showcaseId = $document?->metadata_json['showcase_submission_id'] ?? null;
        if ($showcaseId) {
            $linked = ProjectSubmission::query()->find($showcaseId);
            if ($linked !== null) {
                return $linked;
            }
        }

        return self::sourceCodeSubmissionForProject($project);
    }

    public static function sourceCodeSubmissionForProject(ResearchProject $project): ?ProjectSubmission
    {
        return ProjectSubmission::query()
            ->where('stage', StudentStageProgress::completeSystemStageName())
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['approved'])
            ->where(function ($query) use ($project) {
                $query->where('student_id', $project->student_id);
                if ($project->project_group_id) {
                    $query->orWhere('project_group_id', $project->project_group_id);
                }
            })
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();
    }

    public static function isPublicShowcaseSubmission(ProjectSubmission $submission): bool
    {
        if ($submission->repository_published_at !== null
            && StudentStageProgress::isCompleteDocumentStage((string) $submission->stage)) {
            return true;
        }

        if (! StudentStageProgress::isCompleteSystemStage((string) $submission->stage)) {
            return false;
        }

        return Document::query()
            ->where('is_public', true)
            ->where('is_current_version', true)
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(metadata_json, '$.showcase_submission_id')) = ?",
                [(string) $submission->id]
            )
            ->exists();
    }

    private static function resolveResearchProject(
        ProjectSubmission $submission,
        int $studentId,
        string $workType,
    ): ResearchProject {
        $linked = Document::query()
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(metadata_json, '$.project_submission_id')) = ?",
                [(string) $submission->id]
            )
            ->first();

        if ($linked?->project) {
            return $linked->project;
        }

        $existing = ResearchProject::query()
            ->where('student_id', $studentId)
            ->when(
                $submission->project_group_id,
                fn ($query) => $query->where('project_group_id', $submission->project_group_id),
                fn ($query) => $query->whereNull('project_group_id')
            )
            ->where('project_type', $workType)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return ResearchProject::create([
            'student_id' => $studentId,
            'project_group_id' => $submission->project_group_id,
            'project_type' => $workType,
            'project_code' => 'PRMS-SUB-'.$submission->id,
            'title' => self::resolveTitle($submission),
            'abstract' => self::resolveAbstract($submission),
            'status' => 'completed',
            'current_stage' => 'Published',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function researchProjectPayload(
        ProjectSubmission $submission,
        int $studentId,
        string $workType,
    ): array {
        $supervisorId = $submission->projectGroup?->supervisorAssignment?->supervisor_id
            ?? SupervisorAssignment::query()->where('student_id', $studentId)->value('supervisor_id');

        $payload = [
            'title' => self::resolveTitle($submission),
            'abstract' => self::resolveAbstract($submission),
            'supervisor_id' => $supervisorId,
            'status' => 'completed',
            'current_stage' => 'Published',
            'is_public' => true,
            'published_at' => $submission->repository_published_at,
        ];

        if (Schema::hasColumn('research_projects', 'project_group_id')) {
            $payload['project_group_id'] = $submission->project_group_id;
        }

        if (Schema::hasColumn('research_projects', 'project_type')) {
            $payload['project_type'] = $workType;
        }

        return $payload;
    }

    private static function syncDocument(
        ResearchProject $project,
        ProjectSubmission $submission,
        string $workType,
    ): void {
        $showcase = self::sourceCodeSubmissionForProject($project);

        Document::query()
            ->where('project_id', $project->id)
            ->where('document_type', $workType)
            ->update(['is_current_version' => false]);

        Document::updateOrCreate(
            [
                'project_id' => $project->id,
                'file_path' => $submission->file_path,
            ],
            [
                'document_type' => $workType,
                'file_name' => $submission->original_filename ?: basename((string) $submission->file_path),
                'file_size' => (int) ($submission->file_size ?? 0),
                'mime_type' => $submission->mime_type,
                'version_number' => (int) ($submission->version ?? 1),
                'is_public' => true,
                'is_current_version' => true,
                'uploaded_by' => $submission->student_id,
                'upload_date' => $submission->repository_published_at,
                'description' => $submission->description,
                'preview_file_path' => $showcase?->screenshot_path,
                'metadata_json' => [
                    'project_submission_id' => $submission->id,
                    'showcase_submission_id' => $showcase?->id,
                    'stage' => $submission->stage,
                    'source' => 'prms_project_submissions',
                ],
            ]
        );
    }

    private static function resolveTitle(ProjectSubmission $submission): string
    {
        $title = trim((string) $submission->title);

        return $title !== '' ? $title : 'PRMS publication #'.$submission->id;
    }

    private static function resolveAbstract(ProjectSubmission $submission): ?string
    {
        $abstract = trim((string) ($submission->description ?? ''));

        return $abstract !== '' ? Str::limit($abstract, 5000) : null;
    }
}
