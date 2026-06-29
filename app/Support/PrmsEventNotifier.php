<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\ProjectSubmission;
use App\Models\StageDeadline;
use App\Models\User;
use App\Notifications\ProjectNotification;
use Illuminate\Support\Collection;

/**
 * Delivers in-app (and optional email) notifications for PRMS workflow events.
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

    public static function notifyGroupCreated(ProjectGroup $group): void
    {
        self::notifyGroupMembers(
            $group,
            'Project group created — '.$group->name,
            'You have been placed in project group '.$group->name.'. Your coordinator will assign a supervisor when ready.',
            route('student.index'),
            'Open student workspace'
        );
    }

    public static function notifySupervisorAssigned(ProjectGroup $group, User $supervisor): void
    {
        $group->loadMissing('members');

        self::notify(
            $supervisor,
            'New group assignment — '.$group->name,
            'You have been assigned as supervisor for '.$group->name.'.',
            route('supervisor.index'),
            'Open supervisor workspace'
        );

        self::notifyMany(
            $group->members,
            'Supervisor assigned — '.$group->name,
            $supervisor->name.' is now your supervisor for '.$group->name.'.',
            route('student.index'),
            'Open student workspace'
        );
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

        self::notifyMany(
            self::submissionStudents($submission),
            'Submission approved and forwarded',
            'Your supervisor approved "'.$submission->title.'" and forwarded it to the coordinator for final review.',
            route('student.index'),
            'Open student workspace'
        );
    }

    public static function notifyCoordinatorConsentApproved(ProjectSubmission $submission, User $coordinator, int $publishedCount = 0): void
    {
        $publishedNote = $publishedCount > 0
            ? ' '.$publishedCount.' related complete document(s) were published to the repository.'
            : '';

        self::notifyMany(
            self::submissionStudents($submission),
            'Presentation consent finalized',
            $coordinator->name.' finalized the presentation consent for "'.$submission->title.'".'.$publishedNote,
            route('student.index'),
            'Open student workspace'
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

        self::notifyMany(
            self::submissionStudents($submission),
            'Submission finalized',
            $message,
            route('student.index'),
            'Open student workspace'
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

    public static function notifyEvaluationFinalized(ProjectSubmission $submission, int $totalScore): void
    {
        self::notifyMany(
            self::submissionStudents($submission),
            'Evaluation finalized',
            'Your submission "'.$submission->title.'" was evaluated with a score of '.$totalScore.'/100.',
            route('student.index'),
            'Open student workspace'
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

            self::notifyGroupMembers($group, $title, $message, route('student.index'), 'Open student workspace');

            $supervisor = $group->supervisorAssignment?->supervisor;
            if ($supervisor) {
                self::notify($supervisor, $title, $message, route('supervisor.index'), 'Open supervisor workspace');
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
            ->each(fn (User $student) => self::notify(
                $student,
                $title,
                'The deadline for '.$stageLabel.' was '.$verb.'. Submit before '.$endLabel.'.',
                route('student.index'),
                'Open student workspace'
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
