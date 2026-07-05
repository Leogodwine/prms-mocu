<?php

namespace App\Support;

use App\Models\ProjectSubmission;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\PublicPortalPublication;

class SubmissionFileAccess
{
    public static function authorize(?User $user, ProjectSubmission $submission): void
    {
        if (! $user && PublicPortalPublication::isPublicShowcaseSubmission($submission)) {
            return;
        }

        if (! $user) {
            abort(403, 'Authentication required.');
        }

        if (in_array($user->role, ['coordinator', 'hod', 'admin'], true)) {
            return;
        }

        $inSubmissionGroup = $submission->project_group_id
            && $user->projectGroups()->where('project_groups.id', $submission->project_group_id)->exists();

        $isStudent = $submission->student_id === $user->id || $inSubmissionGroup;

        if ($isStudent) {
            return;
        }

        if ($user->role === 'supervisor') {
            $isAssignedToGroup = $submission->project_group_id
                && SupervisorAssignment::query()
                    ->where('project_group_id', $submission->project_group_id)
                    ->where('supervisor_id', $user->id)
                    ->exists();

            $isAssignedToStudent = $submission->student_id
                && SupervisorAssignment::query()
                    ->where('student_id', $submission->student_id)
                    ->where('supervisor_id', $user->id)
                    ->exists();

            if ($isAssignedToGroup || $isAssignedToStudent) {
                return;
            }
        }

        abort(403, 'You are not allowed to access this file.');
    }

    public static function isStudentOwner(User $user, ProjectSubmission $submission): bool
    {
        if (in_array($user->role, ['project_student', 'research_student', 'normal_student', 'student'], true)) {
            $inSubmissionGroup = $submission->project_group_id
                && $user->projectGroups()->where('project_groups.id', $submission->project_group_id)->exists();

            return $submission->student_id === $user->id || $inSubmissionGroup;
        }

        return false;
    }

    public static function isAssignedSupervisor(User $user, ProjectSubmission $submission): bool
    {
        if ($user->role !== 'supervisor') {
            return false;
        }

        $isAssignedToGroup = $submission->project_group_id
            && SupervisorAssignment::query()
                ->where('project_group_id', $submission->project_group_id)
                ->where('supervisor_id', $user->id)
                ->exists();

        $isAssignedToStudent = $submission->student_id
            && SupervisorAssignment::query()
                ->where('student_id', $submission->student_id)
                ->where('supervisor_id', $user->id)
                ->exists();

        return $isAssignedToGroup || $isAssignedToStudent;
    }

    public static function canStudentEdit(User $user, ProjectSubmission $submission): bool
    {
        if (! self::isStudentOwner($user, $submission)) {
            return false;
        }

        // Students may edit until the stage is sent to the coordinator (including approved drafts).
        return ! $submission->submitted_to_coordinator;
    }

    /**
     * Whether a student may delete their own submission (withdraw draft / pending work).
     */
    public static function canStudentRemove(User $user, ProjectSubmission $submission): ?string
    {
        if (! self::isStudentOwner($user, $submission)) {
            return 'You are not allowed to remove this submission.';
        }

        if ($submission->submitted_to_coordinator) {
            return 'This submission was sent to the coordinator and cannot be removed.';
        }

        if (($submission->status ?? '') === 'approved') {
            return 'Approved submissions cannot be removed.';
        }

        return null;
    }

    /**
     * Student workspace URL for replacing a non-Word upload or opening the upload form.
     */
    public static function studentReplaceUrl(ProjectSubmission $submission): string
    {
        $stageId = \App\Models\ProjectStage::query()
            ->where('stage_name', $submission->stage)
            ->value('id');

        $track = StudentStageProgress::workTypeFromStage($submission->stage);
        $params = array_filter([
            'type' => in_array($track, StudentStageProgress::workTypeOptions(), true) ? $track : null,
            'stage_id' => $stageId,
        ]);

        return route('student.index', $params).'#prms-submit-stage-card';
    }

    /**
     * ONLYOFFICE editor capabilities per role.
     * editorMode must be "edit" for typing, comments, and review UI (ONLYOFFICE API requirement).
     *
     * @return array{editorMode: string, canEdit: bool, canReview: bool, canComment: bool, hint: string}
     */
    public static function resolveEditorCapabilities(User $user, ProjectSubmission $submission): array
    {
        if (self::canStudentEdit($user, $submission)) {
            return [
                'editorMode' => 'edit',
                'canEdit' => true,
                'canReview' => false,
                'canComment' => true,
                'hint' => ($submission->status ?? '') === 'draft'
                    ? 'Draft — use Save or Save & return below. Your work is kept as a draft until you submit it to your supervisor from the workspace.'
                    : 'You can type and edit freely. Use Save or Save & return below, or the Save button in the Word toolbar.',
            ];
        }

        if (self::isStudentOwner($user, $submission) && $submission->submitted_to_coordinator) {
            return [
                'editorMode' => 'view',
                'canEdit' => false,
                'canReview' => false,
                'canComment' => false,
                'hint' => 'This document was submitted to the coordinator and is read-only.',
            ];
        }

        if (self::isAssignedSupervisor($user, $submission)) {
            return [
                'editorMode' => 'edit',
                'canEdit' => true,
                'canReview' => true,
                'canComment' => true,
                'hint' => 'Use the Review tab for track changes and the Comment tool for feedback. You can also edit the text directly.',
            ];
        }

        if (in_array($user->role, ['coordinator', 'hod', 'admin'], true)) {
            return [
                'editorMode' => 'edit',
                'canEdit' => true,
                'canReview' => true,
                'canComment' => true,
                'hint' => 'Oversight mode: you can edit, review, and comment on this submission.',
            ];
        }

        return [
            'editorMode' => 'view',
            'canEdit' => false,
            'canReview' => false,
            'canComment' => false,
            'hint' => 'View-only access.',
        ];
    }

    /**
     * Icon metadata for submission file type (PDF, Word, archive, etc.).
     *
     * @return array{icon: string, class: string, label: string}
     */
    public static function documentIconMeta(?string $mimeType, ?string $filename): array
    {
        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        $mime = strtolower((string) $mimeType);

        if (str_contains($mime, 'pdf') || $ext === 'pdf') {
            return ['icon' => 'far fa-file-pdf', 'class' => 'text-danger', 'label' => 'PDF document'];
        }

        if (in_array($ext, ['doc', 'docx'], true) || str_contains($mime, 'word') || str_contains($mime, 'document')) {
            return ['icon' => 'far fa-file-word', 'class' => 'text-primary', 'label' => 'Word document'];
        }

        if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz', 'tgz'], true)
            || str_contains($mime, 'zip') || str_contains($mime, 'archive')) {
            return ['icon' => 'fas fa-file-archive', 'class' => 'text-warning', 'label' => 'Archive'];
        }

        return ['icon' => 'far fa-file-alt', 'class' => 'text-muted', 'label' => 'Document'];
    }
}
