<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\ProjectSubmission;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds context for the final project presentation consent letter.
 */
final class PresentationConsentForm
{
    public static function consentStageName(): string
    {
        return RepositoryPublication::consentStageName();
    }

    public static function resolveContext(Request $request): array
    {
        $viewer = $request->user();
        $projectGroup = null;
        $student = $viewer;

        if ($viewer->role === 'supervisor') {
            $groupId = (int) $request->query('group', 0);
            $studentId = (int) $request->query('student', 0);
            $submissionId = (int) $request->query('submission', 0);

            if ($submissionId > 0) {
                $submission = ProjectSubmission::query()->findOrFail($submissionId);
                self::authorizeSupervisorForSubmission($viewer, $submission);

                return self::resolveFromSubmission($submission, $viewer);
            }

            if ($groupId > 0) {
                $projectGroup = ProjectGroup::query()
                    ->whereHas('supervisorAssignment', fn ($q) => $q->where('supervisor_id', $viewer->id))
                    ->with(['members.studentProfile.programme', 'supervisorAssignment.supervisor.staffProfile'])
                    ->findOrFail($groupId);
                $student = $projectGroup->members->first() ?? $viewer;
            } elseif ($studentId > 0) {
                $assigned = User::query()
                    ->whereHas('studentAssignment', fn ($q) => $q->where('supervisor_id', $viewer->id))
                    ->with(['studentProfile.programme'])
                    ->findOrFail($studentId);
                $student = $assigned;
            } else {
                abort(422, 'Select a student or group to open the consent form.');
            }
        } elseif (in_array($viewer->role, ['coordinator', 'hod', 'admin'], true)) {
            $groupId = (int) $request->query('group', 0);
            $studentId = (int) $request->query('student', 0);

            if ($groupId > 0) {
                $projectGroup = ProjectGroup::query()
                    ->with(['members.studentProfile.programme', 'supervisorAssignment.supervisor.staffProfile'])
                    ->findOrFail($groupId);
                $student = $projectGroup->members->first() ?? $viewer;
            } elseif ($studentId > 0) {
                $student = User::query()->with(['studentProfile.programme'])->findOrFail($studentId);
            }
        } else {
            $projectGroup = $viewer->projectGroups()->with([
                'members.studentProfile.programme',
                'supervisorAssignment.supervisor.staffProfile',
            ])->first();
        }

        if ($projectGroup === null && $student !== null) {
            $projectGroup = $student->projectGroups()
                ->with(['members.studentProfile.programme', 'supervisorAssignment.supervisor.staffProfile'])
                ->first();
        }

        return self::buildContext(
            $student,
            $projectGroup,
            trim((string) $request->query('presentation_date', '')),
            $viewer
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolveFromSubmission(ProjectSubmission $submission, ?User $viewer = null): array
    {
        $submission->loadMissing(['student.studentProfile.programme', 'projectGroup.members.studentProfile.programme', 'projectGroup.supervisorAssignment.supervisor']);

        $student = $submission->student;
        $projectGroup = $submission->projectGroup;

        if ($projectGroup === null && $student !== null) {
            $projectGroup = $student->projectGroups()
                ->with(['members.studentProfile.programme', 'supervisorAssignment.supervisor'])
                ->first();
        }

        $context = self::buildContext(
            $student,
            $projectGroup,
            self::formatPresentationDate($submission->presentation_date),
            $viewer ?? $submission->student
        );
        $context['submission'] = $submission;
        $context['projectTitle'] = filled($submission->consent_project_title)
            ? trim((string) $submission->consent_project_title)
            : $context['projectTitle'];
        $context['groupNumber'] = filled($submission->consent_group_number)
            ? trim((string) $submission->consent_group_number)
            : $context['groupNumber'];
        $context['presentationDateRaw'] = self::formatPresentationDate($submission->presentation_date);
        $context['presentationDate'] = self::formatPresentationDateLabel($context['presentationDateRaw']);

        if ($submission->supervisor_signature_path && Storage::disk('public')->exists($submission->supervisor_signature_path)) {
            $context['supervisorSignatureDataUri'] = self::imageToDataUri(
                Storage::disk('public')->path($submission->supervisor_signature_path)
            );
        }

        if ($submission->supervisor_consent_signed_at) {
            $context['signedAt'] = $submission->supervisor_consent_signed_at;
        }

        if ($submission->coordinator_approved_at) {
            $context['coordinatorApprovedAt'] = $submission->coordinator_approved_at;
        }

        if ($submission->coordinator_signature_path && Storage::disk('public')->exists($submission->coordinator_signature_path)) {
            $context['coordinatorSignatureDataUri'] = self::imageToDataUri(
                Storage::disk('public')->path($submission->coordinator_signature_path)
            );
        }

        if ($submission->coordinator_approved_by) {
            $context['coordinator'] = User::query()->find($submission->coordinator_approved_by);
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildContext(?User $student, ?ProjectGroup $projectGroup, string $presentationDate, ?User $viewer): array
    {
        $supervisor = null;
        if ($projectGroup?->supervisorAssignment?->supervisor) {
            $supervisor = $projectGroup->supervisorAssignment->supervisor;
        } elseif ($student) {
            $supervisor = SupervisorAssignment::query()
                ->with('supervisor.staffProfile')
                ->where('student_id', $student->id)
                ->first()?->supervisor;
        }

        if ($viewer?->role === 'supervisor') {
            $supervisor = $viewer;
        }

        $members = $projectGroup && $projectGroup->members->count() > 1
            ? $projectGroup->members->sortBy('name')->values()
            : collect([$student])->filter();

        $profile = $student?->studentProfile;
        $projectTitle = self::resolveProjectTitle($student, $projectGroup);

        return [
            'viewer' => $viewer,
            'student' => $student,
            'members' => $members,
            'projectGroup' => $projectGroup,
            'supervisor' => $supervisor,
            'projectTitle' => $projectTitle !== '' ? $projectTitle : '________________________________________',
            'registrationNumber' => $profile?->registration_number ?? $student?->regNo() ?? '—',
            'programme' => $profile?->programme?->programme_code ?? $student?->programme ?? '—',
            'programmeConsentLabel' => self::consentProgrammeLabel($student, $projectGroup),
            'groupNumber' => self::consentGroupNumber($student, $projectGroup),
            'department' => $student?->department ?? data_get($profile?->sis_data, 'department') ?? '—',
            'academicYear' => $projectGroup?->academic_year ?? now()->format('Y'),
            'presentationDate' => self::formatPresentationDateLabel($presentationDate),
            'presentationDateRaw' => $presentationDate,
            'generatedAt' => now(),
            'supervisorSignatureDataUri' => null,
            'signedAt' => null,
            'coordinatorApprovedAt' => null,
            'coordinatorSignatureDataUri' => null,
            'coordinator' => null,
        ];
    }

    public static function consentProgrammeLabel(?User $student, ?ProjectGroup $projectGroup): string
    {
        $profile = $student?->studentProfile;
        if ($profile === null && $projectGroup !== null) {
            $profile = $projectGroup->members->first()?->studentProfile;
        }

        $code = strtoupper(trim((string) ($profile?->programme?->programme_code ?? '')));
        $year = (int) ($profile?->year_of_study ?? $student?->year_of_study ?? 0);

        if ($code !== '' && $year >= 1 && $year <= 4) {
            $level = ['I', 'II', 'III', 'IV'][$year - 1] ?? (string) $year;

            return "{$code} {$level}";
        }

        if ($code !== '') {
            return $code;
        }

        return 'PROJECT';
    }

    public static function consentGroupNumber(?User $student, ?ProjectGroup $projectGroup): string
    {
        if ($projectGroup !== null && filled($projectGroup->name)) {
            return (string) $projectGroup->name;
        }

        $reg = $student?->studentProfile?->registration_number ?? $student?->regNo();

        return filled($reg) ? (string) $reg : '';
    }

    public static function authorizeSupervisorForSubmission(User $supervisor, ProjectSubmission $submission): void
    {
        if ($supervisor->role !== 'supervisor') {
            abort(403);
        }

        if (! StudentStageProgress::isConsentLetterStage((string) $submission->stage)) {
            abort(422, 'This submission is not a presentation consent letter.');
        }

        $assigned = false;
        if ($submission->project_group_id) {
            $assigned = $submission->projectGroup()
                ->whereHas('supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisor->id))
                ->exists();
        } elseif ($submission->student_id) {
            $assigned = $submission->student()
                ->whereHas('studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisor->id))
                ->exists();
        }

        if (! $assigned) {
            abort(403, 'You are not assigned to this consent submission.');
        }
    }

    public static function authorizeCoordinatorForSubmission(User $user, ProjectSubmission $submission): void
    {
        if (! in_array($user->role, ['coordinator', 'hod', 'admin'], true)) {
            abort(403);
        }

        if (! StudentStageProgress::isConsentLetterStage((string) $submission->stage)) {
            abort(422, 'This submission is not a presentation consent letter.');
        }

        if ($user->role !== 'coordinator') {
            return;
        }

        $inScope = false;
        if ($submission->project_group_id) {
            $inScope = ProjectGroup::query()
                ->where('id', $submission->project_group_id)
                ->where('coordinator_id', $user->id)
                ->exists();
        } elseif ($submission->student_id) {
            $inScope = $submission->student()
                ->whereHas('projectGroups', fn ($q) => $q->where('coordinator_id', $user->id))
                ->exists();
        }

        if (! $inScope) {
            abort(403, 'This consent submission is outside your coordinator workspace.');
        }
    }

    public static function storeSignatureImage(string $base64Payload): string
    {
        if (! preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $base64Payload, $matches)) {
            throw new \InvalidArgumentException('Invalid signature image.');
        }

        $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Payload), true);
        if ($binary === false || strlen($binary) < 100) {
            throw new \InvalidArgumentException('Signature image is empty or invalid.');
        }

        $extension = strtolower($matches[1]) === 'jpeg' || strtolower($matches[1]) === 'jpg' ? 'jpg' : 'png';
        $path = 'submissions/consent-signatures/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function downloadPdf(array $context, ?string $signatureDataUri = null): Response
    {
        self::ensurePdfSupported();
        if ($signatureDataUri !== null) {
            $context['supervisorSignatureDataUri'] = $signatureDataUri;
        }

        $html = view('documents.presentation-consent-pdf', array_merge($context, [
            'pdfMode' => true,
        ]))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->download(self::downloadFilename($context));
    }

    /**
     * DomPDF requires ext-gd to embed PNG/JPEG images (logos, signatures).
     */
    public static function ensurePdfSupported(): void
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException(
                'PDF generation requires the PHP GD extension. Enable extension=gd in php.ini (e.g. C:\\xampp\\php\\php.ini) and restart Apache or `php artisan serve`.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function renderPdf(array $context, ?string $signatureDataUri = null): Response
    {
        self::ensurePdfSupported();
        if ($signatureDataUri !== null) {
            $context['supervisorSignatureDataUri'] = $signatureDataUri;
        }

        $html = view('documents.presentation-consent-pdf', array_merge($context, [
            'pdfMode' => true,
        ]))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->stream('final-presentation-consent.pdf');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public static function savePdfToDisk(array $context, ProjectSubmission $submission): string
    {
        self::ensurePdfSupported();
        $html = view('documents.presentation-consent-pdf', array_merge($context, [
            'pdfMode' => true,
        ]))->render();

        $relative = 'submissions/consent-pdfs/consent-'.$submission->id.'-'.now()->format('YmdHis').'.pdf';
        $binary = Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
        Storage::disk('public')->put($relative, $binary);

        return $relative;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function saveCoordinatorConsentPdfToDisk(array $context, ProjectSubmission $submission): string
    {
        self::ensurePdfSupported();
        $html = view('documents.presentation-consent-pdf', array_merge($context, [
            'pdfMode' => true,
        ]))->render();

        $relative = 'submissions/consent-pdfs/coordinator-consent-'.$submission->id.'-'.now()->format('YmdHis').'.pdf';
        $binary = Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
        Storage::disk('public')->put($relative, $binary);

        return $relative;
    }

    public static function consentPdfPath(ProjectSubmission $submission): ?string
    {
        if ($submission->coordinator_consent_pdf_path && Storage::disk('public')->exists($submission->coordinator_consent_pdf_path)) {
            return $submission->coordinator_consent_pdf_path;
        }

        if ($submission->supervisor_consent_pdf_path && Storage::disk('public')->exists($submission->supervisor_consent_pdf_path)) {
            return $submission->supervisor_consent_pdf_path;
        }

        return null;
    }

    public static function signatureDataUriFromBase64(string $base64Payload): string
    {
        if (! preg_match('#^data:image/#i', $base64Payload)) {
            throw new \InvalidArgumentException('Invalid signature image.');
        }

        return $base64Payload;
    }

    public static function publicImageDataUri(string $relativePublicPath): ?string
    {
        $absolute = public_path($relativePublicPath);
        if (! is_file($absolute)) {
            return null;
        }

        return self::imageToDataUri($absolute);
    }

    private static function imageToDataUri(string $absolutePath): string
    {
        $mime = mime_content_type($absolutePath) ?: 'image/png';
        $data = base64_encode((string) file_get_contents($absolutePath));

        return 'data:'.$mime.';base64,'.$data;
    }

    private static function resolveProjectTitle(?User $student, ?ProjectGroup $projectGroup): string
    {
        if ($student === null && $projectGroup === null) {
            return '';
        }

        $query = ProjectSubmission::query()
            ->whereIn('stage', ['Complete Project Document', \App\Support\StudentStageProgress::completeSystemStageName(), 'Source Code Submission'])
            ->orderByDesc('id');

        if ($projectGroup) {
            $query->where('project_group_id', $projectGroup->id);
        } elseif ($student) {
            $query->where('student_id', $student->id);
        }

        $submission = $query->first();

        return trim((string) ($submission?->title ?? ''));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function downloadFilename(array $context): string
    {
        /** @var ProjectGroup|null $projectGroup */
        $projectGroup = $context['projectGroup'] ?? null;
        /** @var User|null $student */
        $student = $context['student'] ?? null;

        $label = $projectGroup?->name
            ?? $student?->name
            ?? 'consent-form';

        $slug = Str::slug($label, '-');

        return 'final-presentation-consent-'.($slug !== '' ? $slug : 'form').'.pdf';
    }

    public static function formatPresentationDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return trim((string) $value);
    }

    public static function formatPresentationDateLabel(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '________________________';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    public static function clearSupervisorConsentSignature(ProjectSubmission $submission): void
    {
        if ($submission->supervisor_signature_path) {
            Storage::disk('public')->delete($submission->supervisor_signature_path);
        }

        if ($submission->supervisor_consent_pdf_path) {
            Storage::disk('public')->delete($submission->supervisor_consent_pdf_path);
        }

        $submission->forceFill([
            'supervisor_signature_path' => null,
            'supervisor_consent_pdf_path' => null,
            'supervisor_consent_signed_at' => null,
            'supervisor_consent_signed_by' => null,
            'submitted_to_coordinator' => false,
        ])->save();
    }

    public static function clearCoordinatorConsentSignature(ProjectSubmission $submission): void
    {
        if ($submission->coordinator_signature_path) {
            Storage::disk('public')->delete($submission->coordinator_signature_path);
        }

        if ($submission->coordinator_consent_pdf_path) {
            Storage::disk('public')->delete($submission->coordinator_consent_pdf_path);
        }

        $submission->forceFill([
            'coordinator_signature_path' => null,
            'coordinator_consent_pdf_path' => null,
            'coordinator_approved_at' => null,
            'coordinator_approved_by' => null,
        ])->save();
    }

    public static function authorizeStudentForSubmission(User $student, ProjectSubmission $submission): void
    {
        if (! $student->isStudentUser()) {
            abort(403);
        }

        if (! StudentStageProgress::isConsentLetterStage((string) $submission->stage)) {
            abort(422, 'This submission is not a presentation consent letter.');
        }

        $projectGroup = $student->projectGroups()->first();
        $owns = $submission->student_id === $student->id
            || ($projectGroup && $submission->project_group_id === $projectGroup->id);

        if (! $owns) {
            abort(403, 'You do not have access to this consent submission.');
        }
    }
}
