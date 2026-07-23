<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;

/**
 * Applies one SIS student row to users + students tables.
 */
final class StudentSisRecordSync
{
    /**
     * @return array{action: 'added'|'updated', deactivated: bool}
     */
    public static function syncRow(array $row): array
    {
        $registrationNumber = trim((string) ($row['registration_number'] ?? ''));
        if ($registrationNumber === '') {
            throw new \InvalidArgumentException('SIS row is missing registration_number.');
        }

        $yearOfStudy = (int) ($row['year_of_study'] ?? 0);
        $role = 'student';
        $enrollment = strtolower(trim((string) ($row['enrollment_status'] ?? 'inactive')));
        $accountStatus = $enrollment === 'active' ? 'active' : 'inactive';

        $payload = [
            'name' => (string) ($row['full_name'] ?? ''),
            'email' => (string) ($row['university_email'] ?? ''),
            'login_id' => $registrationNumber,
            'registration_number' => $registrationNumber,
            'role' => $role,
            'department' => $row['department'] ?? null,
            'programme' => $row['programme'] ?? null,
            'year_of_study' => $yearOfStudy,
            'enrollment_status' => $enrollment,
            'account_status' => $accountStatus,
            'phone_number' => $row['phone_number'] ?? null,
        ];

        $user = User::query()->where('registration_number', $registrationNumber)->first();

        if ($user) {
            $user->fill($payload)->save();
            $action = 'updated';
        } else {
            $user = User::query()->create(array_merge($payload, [
                'password' => 'password',
                'must_change_password' => true,
                'notify_email_new_submission' => true,
                'notify_email_submission_reviewed' => true,
                'notify_email_workflow' => true,
                'notify_sms_workflow' => true,
            ]));
            $action = 'added';
        }

        self::syncStudentProfile($user, $row);

        return [
            'action' => $action,
            'deactivated' => $accountStatus !== 'active',
        ];
    }

    public static function syncStudentProfile(User $user, array $row): ?Student
    {
        if (! $user->isStudentUser()) {
            return null;
        }

        $registrationNumber = trim((string) ($row['registration_number'] ?? $user->registration_number ?? ''));
        $studyYear = (int) ($row['year_of_study'] ?? $user->year_of_study ?? 1);
        if ($studyYear < 1 || $studyYear > \App\Http\Requests\StoreAdminUserRequest::MAX_YEAR_OF_STUDY) {
            $studyYear = 1;
        }

        $enrollment = strtolower(trim((string) ($row['enrollment_status'] ?? 'active')));
        $studentEnrollment = in_array($enrollment, ['active', 'suspended', 'graduated', 'withdrawn'], true)
            ? $enrollment
            : 'active';

        $gender = StudentGenderNormalizer::normalize($row['gender'] ?? $row['sex'] ?? null);

        $student = Student::query()->firstOrNew(['user_id' => $user->id]);
        $student->fill([
            'registration_number' => $registrationNumber !== '' ? $registrationNumber : $user->login_id,
            'full_name' => (string) ($row['full_name'] ?? $user->name),
            'programme_id' => StudentProfileProvisioner::resolveProgrammeIdFromLabel(
                $row['programme'] ?? $user->programme
            ),
            'year_of_study' => $studyYear,
            'enrollment_status' => $studentEnrollment,
            'university_email' => $row['university_email'] ?? $user->email,
            'phone_number' => $row['phone_number'] ?? $user->phone_number,
        ]);

        if ($gender !== null) {
            $student->gender = $gender;
        }

        $student->sis_data = self::sisDataSnapshot($row);
        $student->sis_sync_date = now();
        $student->save();

        StudentWorkflowAssigner::syncForUser($user->fresh());

        return $student;
    }

    /**
     * @return array<string, mixed>
     */
    public static function sisDataSnapshot(array $row): array
    {
        $snapshot = [
            'registration_number' => $row['registration_number'] ?? null,
            'full_name' => $row['full_name'] ?? null,
            'programme' => $row['programme'] ?? null,
            'department' => $row['department'] ?? null,
            'year_of_study' => isset($row['year_of_study']) ? (int) $row['year_of_study'] : null,
            'university_email' => $row['university_email'] ?? null,
            'phone_number' => $row['phone_number'] ?? null,
            'enrollment_status' => $row['enrollment_status'] ?? null,
            'gender' => $row['gender'] ?? $row['sex'] ?? null,
        ];

        return array_filter(
            $snapshot,
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
