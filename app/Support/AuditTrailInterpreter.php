<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Str;

class AuditTrailInterpreter
{
    /** @var array<string, string> */
    private const ACTION_LABELS = [
        'auth.login' => 'Signed in',
        'auth.logout' => 'Signed out',
        'admin.user_created' => 'User account created',
        'admin.user_updated' => 'User account updated',
        'admin.user_deleted' => 'User account deleted',
        'admin.configuration_updated' => 'System configuration updated',
        'admin.workflow_defaults_updated' => 'Workflow defaults updated',
        'admin.programme_workflow_updated' => 'Programme workflow updated',
        'admin.department_workflow_rule_saved' => 'Department workflow rule saved',
        'admin.department_workflow_rule_updated' => 'Department workflow rule updated',
        'admin.department_workflow_rule_deleted' => 'Department workflow rule deleted',
        'admin.student_workflows_reevaluated' => 'Student workflows re-evaluated',
        'admin.department_created' => 'Department created',
        'admin.department_updated' => 'Department updated',
        'admin.department_deleted' => 'Department deleted',
        'admin.programme_created' => 'Programme created',
        'admin.programme_updated' => 'Programme updated',
        'admin.programme_deleted' => 'Programme deleted',
        'admin.academic_level_updated' => 'Academic level updated',
        'admin.maintenance_enabled' => 'Maintenance mode enabled',
        'admin.maintenance_disabled' => 'Maintenance mode disabled',
        'admin.maintenance_task' => 'Maintenance task run',
        'admin.backup_created' => 'Backup created',
        'admin.backup_deleted' => 'Backup deleted',
        'admin.backup_settings_updated' => 'Backup settings updated',
        'hod.student_academic_updated' => 'Student academic record updated',
        'student.submission_uploaded' => 'Submission uploaded',
        'student.submission_blank_word_created' => 'Blank Word document created',
        'research_project.problem_proposal_submitted' => 'Problem proposal submitted',
        'research_project.contributor_added' => 'Project contributor added',
        'research_project.contributor_removed' => 'Project contributor removed',
        'archive.export' => 'Archive exported',
        'word_document.created' => 'Word document created',
        'word_document.deleted' => 'Word document deleted',
    ];

    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'account_status' => 'Status',
        'stage' => 'Stage',
        'version' => 'Version',
        'count' => 'Count',
        'task' => 'Task',
        'contributor_user_id' => 'Contributor',
        'project_code' => 'Project code',
        'work_kind' => 'Work type',
        'project_group_id' => 'Group',
        'title' => 'Title',
        'registration_number' => 'Reg. no.',
        'department_id' => 'Department',
        'programme_id' => 'Programme',
    ];

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? self::humanizeAction($action);
    }

    public static function actionTone(string $action): string
    {
        $normalized = strtolower($action);

        if (str_contains($normalized, 'delete') || str_contains($normalized, 'removed') || str_contains($normalized, 'denied')) {
            return 'danger';
        }

        if (str_contains($normalized, 'create') || str_contains($normalized, 'upload') || str_contains($normalized, 'added') || str_contains($normalized, 'submitted')) {
            return 'success';
        }

        if (str_contains($normalized, 'update') || str_contains($normalized, 'enabled') || str_contains($normalized, 'disabled') || str_contains($normalized, 'export') || str_contains($normalized, 'login')) {
            return 'info';
        }

        if (str_contains($normalized, 'logout')) {
            return 'secondary';
        }

        return 'primary';
    }

    public static function entityLabel(?string $entityType, ?string $entityId): ?string
    {
        if ($entityType === null || $entityType === '') {
            return null;
        }

        $label = match ($entityType) {
            'User' => 'User',
            'ResearchProject' => 'Research project',
            'ProjectSubmission' => 'Submission',
            'ProjectGroup' => 'Project group',
            'SystemConfiguration' => 'Configuration',
            'Department' => 'Department',
            'Program' => 'Programme',
            'DepartmentWorkflowRule' => 'Workflow rule',
            'AcademicLevelSetting' => 'Academic level',
            'WordDocument' => 'Word document',
            'Archive' => 'Archive',
            'Backup' => 'Backup',
            'System' => 'System',
            default => Str::headline($entityType),
        };

        if ($entityId !== null && $entityId !== '') {
            return "{$label} #{$entityId}";
        }

        return $label;
    }

    public static function actorLabel(AuditLog $log): string
    {
        if ($log->user) {
            return $log->user->name;
        }

        if ($log->user_id) {
            return 'User #'.$log->user_id;
        }

        return 'System';
    }

    public static function actorMeta(AuditLog $log): ?string
    {
        return $log->user?->email;
    }

    public static function summarize(AuditLog $log): ?string
    {
        $parts = [];

        if (is_array($log->new_values) && $log->new_values !== []) {
            $parts = array_merge($parts, self::summarizePayload($log->new_values));
        }

        if ($parts === [] && is_array($log->old_values) && $log->old_values !== []) {
            $parts = array_merge($parts, self::summarizePayload($log->old_values, deleted: true));
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private static function summarizePayload(array $payload, bool $deleted = false): array
    {
        $priority = [
            'name', 'email', 'role', 'account_status', 'title', 'stage', 'version',
            'project_code', 'work_kind', 'task', 'count', 'contributor_user_id',
            'registration_number', 'department_id', 'programme_id', 'project_group_id',
        ];

        $parts = [];

        foreach ($priority as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if ($value === null || $value === '') {
                continue;
            }

            $label = self::FIELD_LABELS[$key] ?? Str::headline(str_replace('_', ' ', $key));
            $formatted = self::formatValue($value);

            $parts[] = $deleted
                ? "{$label}: {$formatted} (removed)"
                : "{$label}: {$formatted}";
        }

        if ($parts !== []) {
            return $parts;
        }

        foreach ($payload as $key => $value) {
            if (is_array($value) || $value === null || $value === '') {
                continue;
            }

            $label = self::FIELD_LABELS[$key] ?? Str::headline(str_replace('_', ' ', (string) $key));
            $parts[] = "{$label}: ".self::formatValue($value);

            if (count($parts) >= 4) {
                break;
            }
        }

        return $parts;
    }

    private static function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }

    private static function humanizeAction(string $action): string
    {
        $segments = explode('.', $action);
        $verb = array_pop($segments) ?: $action;

        return Str::title(str_replace('_', ' ', $verb));
    }
}
