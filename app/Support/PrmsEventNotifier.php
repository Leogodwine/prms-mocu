<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\ProjectSubmission;
use App\Models\StageDeadline;
use App\Models\User;
use App\Notifications\ProjectNotification;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Collection;

/**
 * Delivers in-app, email, and SMS notifications for PRMS workflow events.
 */
final class PrmsEventNotifier
{
    public static function notify(User $user, string $title, string $message, ?string $actionUrl = null, ?string $actionText = null): void
    {
        try {
            $user->notify(new ProjectNotification($title, $message, $actionUrl, $actionText));
        } catch (\Throwable $e) {
            SafeReport::call($e);
        }
    }

    public static function safeNotify(User $user, \Illuminate\Notifications\Notification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            SafeReport::call($e);
        }
    }

    public static function notifyWorkflow(User $user, string $title, string $message, ?string $actionUrl = null, ?string $actionText = null, string $toastType = 'info'): void
    {
        try {
            $user->notify(new WorkflowNotification($title, $message, $actionUrl, $actionText, $toastType));
        } catch (\Throwable $e) {
            SafeReport::call($e);
        }
    }

    /**
     * @param  iterable<User>  $users
     */
    public static function notifyWorkflowMany(iterable $users, string $title, string $message, ?string $actionUrl = null, ?string $actionText = null, string $toastType = 'info'): void
    {
        foreach ($users as $user) {
            self::notifyWorkflow($user, $title, $message, $actionUrl, $actionText, $toastType);
        }
    }

    /**
     * @param  iterable<User>  $users
     */
    public static function notifyMany(iterable $users, string $title, string $message, ?string $actionUrl = null, ?string $actionText = null): void
    {
        foreach ($users as $user) {
            self::notify($user, $title, $message, $actionUrl, $actionText);
        }
    }

    public static function notifyRole(string $role, string $title, string $message, ?string $actionUrl = null, ?string $actionText = null): void
    {
        User::query()
            ->where('role', $role)
            ->where('account_status', 'active')
            ->orderBy('id')
            ->each(fn (User $user) => self::notify($user, $title, $message, $actionUrl, $actionText));
    }

    public static function notifyCoordinators(string $title, string $message, ?string $actionUrl = null, ?string $actionText = null): void
    {
        self::notifyRole('coordinator', $title, $message, $actionUrl, $actionText);
    }

    public static function notifyAdmins(string $title, string $message, ?string $actionUrl = null, ?string $actionText = null): void
    {
        self::notifyRole('admin', $title, $message, $actionUrl, $actionText);
    }

    public static function notifyGroupMembers(ProjectGroup $group, string $title, string $message, ?string $actionUrl = null, ?string $actionText = null): void
    {
        $group->loadMissing('members');
        self::notifyMany($group->members, $title, $message, $actionUrl, $actionText);
    }

    /**
     * @return Collection<int, User>
     */
    public static function submissionStudents(ProjectSubmission $submission): Collection
    {
        $submission->loadMissing(['student', 'projectGroup.members']);

        if ($submission->project_group_id && $submission->projectGroup) {
            return $submission->projectGroup->members;
        }

        if ($submission->student) {
            return collect([$submission->student]);
        }

        return collect();
    }

    public static function submissionSupervisor(ProjectSubmission $submission): ?User
    {
        $submission->loadMissing([
            'projectGroup.supervisorAssignment.supervisor',
            'student.studentAssignment.supervisor',
        ]);

        if ($submission->projectGroup?->supervisorAssignment?->supervisor) {
            return $submission->projectGroup->supervisorAssignment->supervisor;
        }

        return $submission->student?->studentAssignment?->supervisor;
    }

    public static function notifyGroupCreated(ProjectGroup $group, ?User $coordinator = null): void
    {
        $group->loadMissing(['members', 'supervisorAssignment.supervisor']);
        $coordinator = self::resolveCoordinator($group, $coordinator);
        $supervisor = $group->supervisorAssignment?->supervisor;
        $memberCount = $group->members->count();
        $isIndividual = $memberCount === 1;
        $recordLabel = $isIndividual ? 'individual supervision record' : 'project group';

        foreach ($group->members as $student) {
            $peerNames = $group->members
                ->where('id', '!=', $student->id)
                ->pluck('name')
                ->join(', ');

            $peerNote = $isIndividual
                ? ''
                : ' Group members: '.$peerNames.'.';

            $supervisorNote = $supervisor !== null
                ? ' '.$supervisor->name.' is your supervisor.'
                : ' Your coordinator will assign a supervisor when ready.';

            self::notifyWorkflow(
                $student,
                ucfirst($recordLabel).' created — '.$group->name,
                'You have been placed in '.$recordLabel.' '.$group->name.'.'.$peerNote.$supervisorNote,
                route('student.index'),
                'Open student workspace',
                'info'
            );
        }

        if ($coordinator !== null) {
            $studentNames = $group->members->pluck('name')->join(', ');
            $supervisorNote = $supervisor !== null
                ? ' Supervisor: '.$supervisor->name.'.'
                : ' Assign a supervisor when ready.';

            self::notifyWorkflow(
                $coordinator,
                ucfirst($recordLabel).' formed — '.$group->name,
                'You formed '.$recordLabel.' '.$group->name.' with '.$memberCount.' member(s): '.$studentNames.'.'.$supervisorNote,
                route('coordinator.index'),
                'Open coordinator hub',
                'success'
            );
        }

        if ($supervisor !== null) {
            self::notifySupervisorAssigned($group, $supervisor, $coordinator, notifyStudents: false);
        }
    }

    public static function notifySupervisorAssigned(
        ProjectGroup $group,
        User $supervisor,
        ?User $coordinator = null,
        bool $notifyStudents = true,
    ): void {
        $group->loadMissing('members');
        $coordinator = self::resolveCoordinator($group, $coordinator);
        $memberCount = $group->members->count();
        $isIndividual = $memberCount === 1;
        $recordLabel = $isIndividual ? 'individual student' : 'group';
        $studentNames = $group->members->pluck('name')->join(', ');

        self::notifyWorkflow(
            $supervisor,
            'New supervisor assignment — '.$group->name,
            'You have been assigned as supervisor for '.$recordLabel.' '.$group->name.' ('.$studentNames.').',
            route('supervisor.workload'),
            'View assigned students',
            'info'
        );

        if ($notifyStudents) {
            self::notifyWorkflowMany(
                $group->members,
                'Supervisor assigned — '.$group->name,
                $supervisor->name.' is now your supervisor for '.$group->name.'.',
                route('student.index'),
                'Open student workspace',
                'success'
            );
        }

        if ($coordinator !== null) {
            self::notifyWorkflow(
                $coordinator,
                'Supervisor assigned — '.$group->name,
                $supervisor->name.' is now supervisor for '.$recordLabel.' '.$group->name.' ('.$studentNames.').',
                route('coordinator.index'),
                'Open coordinator hub',
                'success'
            );
        }
    }

    private static function resolveCoordinator(ProjectGroup $group, ?User $coordinator): ?User
    {
        if ($coordinator !== null) {
            return $coordinator;
        }

        if ($group->coordinator_id) {
            return User::query()->find($group->coordinator_id);
        }

        return null;
    }

    public static function notifySubmittedToCoordinator(ProjectSubmission $submission, User $student): void
    {
        self::notifyCoordinators(
            'Submission ready for coordinator review',
            $student->name.' submitted "'.$submission->title.'" ('.$submission->stage.') for final coordinator review.',
            route('coordinator.submissions'),
            'Review submissions'
        );
    }

    public static function notifyConsentForwardedToCoordinator(ProjectSubmission $submission, User $supervisor): void
    {
        self::notifyCoordinators(
            'Consent signed — '.$submission->title,
            $supervisor->name.' signed the presentation consent for "'.$submission->title.'" ('.$submission->stage.'). It is ready for coordinator review.',
            route('coordinator.submissions'),
            'Review submissions'
        );

        self::notifyWorkflowMany(
            self::submissionStudents($submission),
            'Supervisor signed your consent request',
            $supervisor->name.' signed your presentation consent for "'.$submission->title.'". You can view the signed PDF in your student workspace. It has also been forwarded to the coordinator for final approval.',
            route('student.index'),
            'Open student workspace',
            'success'
        );
    }

    public static function notifyConsentReturnedByCoordinator(
        ProjectSubmission $submission,
        User $coordinator,
        string $decision
    ): void {
        $action = $decision === 'rejected' ? 'rejected' : 'returned for revision';
        $message = $coordinator->name.' '.$action.' the presentation consent for "'.$submission->title.'". Open your student workspace to review the feedback and resubmit if needed.';

        self::notifyWorkflowMany(
            self::submissionStudents($submission),
            'Consent '.$action,
            $message,
            route('student.index'),
            'Open student workspace',
            $decision === 'rejected' ? 'danger' : 'warning'
        );

        $supervisor = self::submissionSupervisor($submission);
        if ($supervisor) {
            self::notify(
                $supervisor,
                'Consent '.$action,
                $message,
                route('supervisor.index'),
                'Open supervisor workspace'
            );
        }
    }

    public static function notifyCoordinatorConsentApproved(ProjectSubmission $submission, User $coordinator, int $publishedCount = 0): void
    {
        $publishedNote = $publishedCount > 0
            ? ' '.$publishedCount.' related complete document(s) were published to the repository.'
            : '';

        self::notifyWorkflowMany(
            self::submissionStudents($submission),
            'Presentation consent finalized',
            $coordinator->name.' finalized the presentation consent for "'.$submission->title.'".'.$publishedNote,
            route('student.index'),
            'Open student workspace',
            'success'
        );

        $supervisor = self::submissionSupervisor($submission);
        if ($supervisor) {
            self::notify(
                $supervisor,
                'Presentation consent finalized',
                $coordinator->name.' finalized the presentation consent for "'.$submission->title.'".'.$publishedNote,
                route('supervisor.index'),
                'Open supervisor workspace'
            );
        }
    }

    public static function notifyCoordinatorSubmissionFinalized(ProjectSubmission $submission, int $publishedViaConsent = 0): void
    {
        $repositoryNote = $submission->repository_published_at !== null
            ? ' It is now visible in the repository.'
            : '';

        $consentNote = $publishedViaConsent > 0
            ? ' '.$publishedViaConsent.' related complete document(s) were also published.'
            : '';

        $message = '"'.$submission->title.'" ('.$submission->stage.') was finalized by the coordinator.'.$repositoryNote.$consentNote;

        self::notifyWorkflowMany(
            self::submissionStudents($submission),
            'Submission finalized',
            $message,
            route('student.index'),
            'Open student workspace',
            'success'
        );

        $supervisor = self::submissionSupervisor($submission);
        if ($supervisor) {
            self::notify(
                $supervisor,
                'Submission finalized',
                $message,
                route('supervisor.index'),
                'Open supervisor workspace'
            );
        }
    }

    public static function notifyEvaluationFinalized(ProjectSubmission $submission, int $totalScore, ?string $gradeLabel = null): void
    {
        $prefix = $gradeLabel ? $gradeLabel.': ' : '';

        self::notifyWorkflowMany(
            self::submissionStudents($submission),
            'Evaluation finalized',
            $prefix.'Your submission "'.$submission->title.'" was evaluated with a score of '.$totalScore.'/100.',
            route('student.index'),
            'Open student workspace',
            'success'
        );
    }

    public static function notifyStudentEvaluationScore(User $student, ProjectSubmission $submission, int $totalScore, string $gradeLabel): void
    {
        self::notifyWorkflow(
            $student,
            'Evaluation finalized',
            $gradeLabel.': Your submission "'.$submission->title.'" was scored '.$totalScore.'/100.',
            route('student.index'),
            'Open student workspace',
            'success'
        );
    }

    public static function notifyStageDeadline(StageDeadline $deadline, bool $updated = false): void
    {
        $stageLabel = ucwords(str_replace('_', ' ', (string) $deadline->stage_name));
        $endLabel = $deadline->end_time?->format('j M Y H:i') ?? 'the due date';
        $verb = $updated ? 'updated' : 'set';
        $title = 'Stage deadline '.$verb.' — '.$stageLabel;

        $deadline->loadMissing(['projectGroup.members', 'projectGroup.supervisorAssignment.supervisor']);

        if ($deadline->project_group_id && $deadline->projectGroup) {
            $group = $deadline->projectGroup;
            $message = 'The deadline for '.$stageLabel.' in '.$group->name.' was '.$verb.'. Submit before '.$endLabel.'.';

            self::notifyWorkflowMany($group->members, $title, $message, route('student.index'), 'Open student workspace', 'info');

            $supervisor = $group->supervisorAssignment?->supervisor;
            if ($supervisor) {
                self::notifyWorkflow($supervisor, $title, $message, route('supervisor.index'), 'Open supervisor workspace', 'info');
            }

            return;
        }

        self::notifyCoordinators(
            $title,
            'A deadline for '.$stageLabel.' was '.$verb.' for all groups. Due by '.$endLabel.'.',
            route('coordinator.deadlines'),
            'View deadlines'
        );

        User::query()
            ->whereIn('role', ['project_student', 'research_student'])
            ->where('account_status', 'active')
            ->orderBy('id')
            ->each(fn (User $student) => self::notifyWorkflow(
                $student,
                $title,
                'The deadline for '.$stageLabel.' was '.$verb.'. Submit before '.$endLabel.'.',
                route('student.index'),
                'Open student workspace',
                'info'
            ));
    }

    public static function notifyAccountUpdated(User $user, ?User $actor = null): void
    {
        $actorNote = $actor ? ' by '.$actor->name : '';

        self::notify(
            $user,
            'Your account was updated',
            'An administrator'.$actorNote.' updated your account details. Review your profile if anything looks incorrect.',
            route('profile.edit'),
            'My profile'
        );
    }

    public static function notifyAccountDeleted(array $snapshot, User $actor): void
    {
        $name = (string) ($snapshot['name'] ?? 'A user');
        $email = (string) ($snapshot['email'] ?? '');

        self::notifyAdmins(
            'User account deleted',
            $actor->name.' removed the account for '.$name.($email !== '' ? ' ('.$email.')' : '').'.',
            route('admin.users.index'),
            'User management'
        );
    }

    /**
     * @param  list<string>  $deletedNames
     */
    public static function notifyBulkAccountsDeleted(array $deletedNames, User $actor): void
    {
        $count = count($deletedNames);
        if ($count === 0) {
            return;
        }

        if ($count === 1) {
            self::notifyAdmins(
                'User account deleted',
                $actor->name.' removed 1 account ('.$deletedNames[0].') via bulk delete.',
                route('admin.users.index'),
                'User management'
            );

            return;
        }

        $preview = implode(', ', array_slice($deletedNames, 0, 5));
        if ($count > 5) {
            $preview .= ', and '.($count - 5).' more';
        }

        self::notifyAdmins(
            'Bulk user deletion',
            $actor->name.' removed '.$count.' accounts via bulk delete: '.$preview.'.',
            route('admin.users.index'),
            'User management'
        );
    }

    public static function notifyStudentAcademicUpdated(User $student, ?User $actor = null): void
    {
        $actorNote = $actor ? ' by '.$actor->name : '';

        self::notify(
            $student,
            'Academic record updated',
            'Your department, programme, or year of study was updated'.$actorNote.'.',
            route('profile.edit'),
            'My profile'
        );
    }
}
