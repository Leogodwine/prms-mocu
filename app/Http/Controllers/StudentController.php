<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionRequest;
use App\Jobs\AnalyzeSubmissionShowcaseJob;
use App\Models\ProjectSubmission;
use App\Models\ProjectSubmissionScreenshot;
use App\Models\SupervisorAssignment;
use App\Notifications\NewSubmissionNotification;
use App\Support\Audit;
use App\Support\PrmsEventNotifier;
use App\Support\SubmissionFileAccess;
use App\Services\OnlyOfficeService;
use App\Support\StudentStageProgress;
use App\Support\StudentResearchEligibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    private function getOrderedStages()
    {
        return \App\Models\ProjectStage::orderBy('stage_order')->get();
    }

    private function getStageRequirements($stage): array
    {
        // For now, we return empty or default requirements if not stored in DB
        // In a future step, we can add a 'requirements' column to project_stages
        return [];
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $isStudent = $user->isStudentUser();
        abort_unless($isStudent || $user->role === 'coordinator', 403, 'Unauthorized.');
        $type = $request->query('type', 'overview');
        if (strtolower((string) $type) === 'presentation') {
            return redirect()->route('student.index', array_filter([
                'type' => 'project',
                'stage_id' => $request->query('stage_id'),
            ]));
        }
        $isOverview = in_array($type, ['overview', '', null], true);
        $currentStageId = $request->query('stage_id');
        $availableTracks = StudentStageProgress::tracksForUser($user);
        $studentAcademic = StudentResearchEligibility::academicContext($user);

        if (! $isOverview && ! in_array(strtolower((string) $type), $availableTracks, true)) {
            abort(403, 'This workspace is not available for your programme.');
        }

        $projectGroup = $user->projectGroups()->first();
        $supervisorAssignment = null;

        if ($projectGroup) {
            $supervisorAssignment = $projectGroup->supervisorAssignment()->with('supervisor')->first();
        } elseif ($isStudent) {
            $supervisorAssignment = SupervisorAssignment::query()
                ->with('supervisor')
                ->where('student_id', $user->id)
                ->first();
        }

        $allStages = $this->getOrderedStages();
        $latestByStage = StudentStageProgress::latestSubmissionByStage($user, $projectGroup);

        $proposalStages = StudentStageProgress::stagesForNavTrack($allStages, 'proposal');
        $projectStages = StudentStageProgress::stagesForNavTrack($allStages, 'project');
        $researchStages = StudentStageProgress::stagesForNavTrack($allStages, 'research');

        $filteredStages = $isOverview
            ? collect()
            : StudentStageProgress::stagesForNavTrack($allStages, strtolower((string) $type));

        $submissionsQuery = ProjectSubmission::query()
            ->with(['feedback.supervisor', 'interfaceScreenshots'])
            ->where(function ($query) use ($user, $projectGroup) {
                $query->where('student_id', $user->id);

                if ($projectGroup) {
                    $query->orWhere('project_group_id', $projectGroup->id);
                }
            });

        if (! $isOverview && in_array(strtolower((string) $type), StudentStageProgress::workTypeOptions(), true)) {
            StudentStageProgress::scopeWorkType($submissionsQuery, $type);
        }

        $currentStage = $currentStageId
            ? $filteredStages->firstWhere('id', (int) $currentStageId)
            : null;

        if ($currentStage) {
            $submissionsQuery->where('stage', $currentStage->stage_name);
        }

        $submissions = $submissionsQuery->latest()->get();

        return view('student.index', [
            'user' => $user,
            'projectGroup' => $projectGroup,
            'supervisorAssignment' => $supervisorAssignment,
            'submissions' => $submissions,
            'stages' => $filteredStages,
            'currentStageId' => $currentStageId,
            'currentStage' => $currentStage,
            'workspaceType' => $isOverview ? 'overview' : $type,
            'isOverview' => $isOverview,
            'latestByStage' => $latestByStage,
            'proposalStages' => $proposalStages,
            'projectStages' => $projectStages,
            'researchStages' => $researchStages,
            'onlyOfficeConfigured' => app(OnlyOfficeService::class)->isConfigured(),
            'availableTracks' => $availableTracks,
            'studentAcademic' => $studentAcademic,
            'stageUploadBlocks' => StudentStageProgress::uploadBlockReasonsForStages(
                $filteredStages,
                $user,
                $projectGroup,
                $latestByStage
            ),
        ]);
    }

    public function storeSubmission(StoreSubmissionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($blockReason = StudentResearchEligibility::researchYearBlockReason($user)) {
            return back()->withErrors(['stage_id' => $blockReason]);
        }

        $stage = \App\Models\ProjectStage::findOrFail((int) $validated['stage_id']);
        $track = StudentStageProgress::workTypeFromStage($stage->stage_name);
        if (in_array($track, StudentStageProgress::workTypeOptions(), true)
            && ! StudentResearchEligibility::hasTrack($user, $track)) {
            return back()->withErrors(['stage_id' => 'This stage is not available for your programme.']);
        }

        $isCompleteSystem = StudentStageProgress::isCompleteSystemStage($stage->stage_name);
        $isPresentation = StudentStageProgress::isPresentationStage($stage->stage_name);
        $isConsentLetter = StudentStageProgress::isConsentLetterStage($stage->stage_name);

        $projectGroup = $user->projectGroups()->first();

        $latestByStage = StudentStageProgress::latestSubmissionByStage($user, $projectGroup);
        $uploadBlockReason = StudentStageProgress::canUploadStage(
            $stage->stage_name,
            $user,
            $projectGroup,
            $latestByStage
        );
        if ($uploadBlockReason !== null) {
            return back()->withErrors(['stage_id' => $uploadBlockReason]);
        }

        // 2. Check Deadlines
        $deadline = \App\Models\StageDeadline::where('stage_name', $stage->stage_name)
            ->where(function($query) use ($projectGroup) {
                $query->whereNull('project_group_id');
                if ($projectGroup) {
                    $query->orWhere('project_group_id', $projectGroup->id);
                }
            })
            ->first();

        if ($deadline && now()->isAfter($deadline->end_time)) {
            return back()->withErrors(['stage_id' => "Deadline passed (" . $deadline->end_time->format('M d, H:i') . ")."]);
        }

        $file = $request->file('document');
        $path = null;
        $originalFilename = null;
        $mimeType = null;
        $fileSize = null;

        if ($file) {
            $path = $file->store('submissions', 'public');
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
        } elseif (! $isConsentLetter) {
            return back()->withErrors(['document' => 'Please attach the required document or archive.']);
        }

        // Interface screenshots for Complete System (one or more).
        $screenshotPath = null;
        $screenshotName = null;
        $screenshotMime = null;
        $storedScreenshots = [];

        if ($isCompleteSystem) {
            foreach ($request->input('interface_screenshots', []) as $index => $row) {
                $image = $request->file("interface_screenshots.{$index}.image");
                if (! $image) {
                    continue;
                }

                $interfaceKey = (string) ($row['interface'] ?? 'home_page');
                $interfaceLabel = StudentStageProgress::resolveInterfaceLabel(
                    $interfaceKey,
                    $row['custom_label'] ?? null
                );

                $storedScreenshots[] = [
                    'interface_name' => $interfaceLabel,
                    'file_path' => $image->store('submissions/screenshots', 'public'),
                    'original_filename' => $image->getClientOriginalName(),
                    'mime_type' => $image->getClientMimeType(),
                    'sort_order' => (int) $index,
                ];
            }

            if ($storedScreenshots === []) {
                return back()->withErrors([
                    'interface_screenshots' => 'Upload a home page interface screenshot.',
                ]);
            }

            $primary = $storedScreenshots[0];
            $screenshotPath = $primary['file_path'];
            $screenshotName = $primary['original_filename'];
            $screenshotMime = $primary['mime_type'];
        }

        // Optional documentation file (PDF/DOC/DOCX/MD/TXT) — kept separate
        // from the source archive so reviewers can read the user manual or
        // technical docs without unzipping anything.
        $docPath = null;
        $docName = null;
        $docMime = null;

        if ($request->hasFile('documentation')) {
            $doc = $request->file('documentation');
            $docPath = $doc->store('submissions/documentation', 'public');
            $docName = $doc->getClientOriginalName();
            $docMime = $doc->getClientMimeType();
        }

        $nextVersionQuery = ProjectSubmission::query()->where('stage', $stage->stage_name);
        if ($projectGroup) {
            $nextVersionQuery->where('project_group_id', $projectGroup->id);
        } else {
            $nextVersionQuery->where('student_id', $user->id);
        }

        $latestVersion = (int) $nextVersionQuery->max('version');
        $nextVersion = $latestVersion + 1;

        $consentTitle = $isConsentLetter
            ? ('Final presentation consent — '.($validated['presentation_date'] ?? now()->toDateString()))
            : $validated['title'];

        $submission = ProjectSubmission::create([
            'project_group_id'                => $projectGroup?->id,
            'student_id'                      => $user->id,
            'stage'                           => $stage->stage_name,
            'title'                           => $consentTitle,
            'description'                     => $validated['description'] ?? null,
            'demo_url'                        => $validated['demo_url']  ?? null,
            'video_url'                       => $validated['video_url'] ?? null,
            'version'                         => $nextVersion,
            'file_path'                       => $path,
            'original_filename'               => $originalFilename,
            'mime_type'                       => $mimeType,
            'file_size'                       => $fileSize,
            'presentation_date'               => $isConsentLetter ? ($validated['presentation_date'] ?? null) : null,
            'screenshot_path'                 => $screenshotPath,
            'screenshot_original_filename'    => $screenshotName,
            'screenshot_mime_type'            => $screenshotMime,
            'documentation_path'              => $docPath,
            'documentation_original_filename' => $docName,
            'documentation_mime_type'         => $docMime,
            'status'                          => 'pending',
            'submitted_at'                    => now(),
        ]);

        foreach ($storedScreenshots as $shot) {
            $submission->interfaceScreenshots()->create($shot);
        }

        Audit::log(
            $request,
            'student.submission_uploaded',
            'ProjectSubmission',
            (string) $submission->id,
            null,
            [
                'stage' => $submission->stage,
                'version' => $submission->version,
                'project_group_id' => $submission->project_group_id,
                'student_id' => $submission->student_id,
            ]
        );

        $supervisor = null;

        if ($projectGroup) {
            $supervisor = optional($projectGroup->supervisorAssignment)->supervisor;
        } else {
            $assignment = SupervisorAssignment::query()->with('supervisor')->where('student_id', $user->id)->first();
            $supervisor = $assignment?->supervisor;
        }

        if ($supervisor) {
            PrmsEventNotifier::safeNotify($supervisor, new NewSubmissionNotification($submission));
        }

        if ($submission->isProjectShowcase()) {
            AnalyzeSubmissionShowcaseJob::dispatch($submission->id);
        }

        $onlyOffice = app(OnlyOfficeService::class);
        if ($onlyOffice->isConfigured() && $submission->isWordDocument()) {
            return redirect()
                ->route('student.submissions.editor', $submission)
                ->with('status', 'Submission uploaded. You can continue editing in the Word editor.');
        }

        return redirect()->back()->with(
            'status',
            $isConsentLetter
                ? 'Consent request sent to your supervisor with the proposed presentation date.'
                : 'Submission uploaded successfully.'
        );
    }

    public function submitToCoordinator(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $user = $request->user();
        
        // Ensure only student can submit their work
        $projectGroup = $user->projectGroups()->first();
        $isOwner = $submission->student_id === $user->id || ($projectGroup && $submission->project_group_id === $projectGroup->id);
        abort_if(!$isOwner, 403);

        // Only complete documents may be sent to the coordinator, after consent is signed.
        $blockReason = StudentStageProgress::canSubmitToCoordinator($submission, $user, $projectGroup);
        if ($blockReason !== null) {
            return back()->withErrors(['error' => $blockReason]);
        }

        if (! StudentStageProgress::isCoordinatorEligibleStage((string) $submission->stage)) {
            return back()->withErrors(['error' => 'This stage is reviewed by your supervisor only and cannot be submitted directly to the coordinator.']);
        }

        // Ensure it is approved by supervisor first
        if ($submission->status !== 'approved') {
            return back()->withErrors(['error' => 'Your supervisor must approve this stage before submitting to the coordinator.']);
        }

        $submission->update(['submitted_to_coordinator' => true]);

        PrmsEventNotifier::notifySubmittedToCoordinator($submission, $user);

        return back()->with('status', 'Stage successfully submitted to the coordinator for final review.');
    }

    public function submitToSupervisor(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $user = $request->user();
        SubmissionFileAccess::authorize($user, $submission);

        $projectGroup = $user->projectGroups()->first();
        $isOwner = $submission->student_id === $user->id
            || ($projectGroup && $submission->project_group_id === $projectGroup->id);

        abort_if(! $isOwner, 403);

        if (($submission->status ?? '') !== 'draft') {
            return back()->withErrors(['error' => 'This submission has already been sent to your supervisor.']);
        }

        $latestByStage = StudentStageProgress::latestSubmissionByStage($user, $projectGroup);
        if ($blockReason = StudentStageProgress::canUploadStage(
            (string) $submission->stage,
            $user,
            $projectGroup,
            $latestByStage
        )) {
            return back()->withErrors(['error' => $blockReason]);
        }

        if (! $submission->file_path || ! Storage::disk('public')->exists($submission->file_path)) {
            return back()->withErrors(['error' => 'Document file not found. Open the editor, save your work, and try again.']);
        }

        $submission->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        Audit::log(
            $request,
            'student.submission_submitted_to_supervisor',
            'ProjectSubmission',
            (string) $submission->id,
            ['status' => 'draft'],
            ['status' => 'pending']
        );

        $supervisor = null;

        if ($projectGroup) {
            $supervisor = optional($projectGroup->supervisorAssignment)->supervisor;
        } else {
            $assignment = SupervisorAssignment::query()->with('supervisor')->where('student_id', $user->id)->first();
            $supervisor = $assignment?->supervisor;
        }

        if ($supervisor) {
            PrmsEventNotifier::safeNotify($supervisor, new NewSubmissionNotification($submission->fresh()));
        }

        return back()->with('status', 'Submission sent to your supervisor for review.');
    }

    public function destroySubmission(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $user = $request->user();
        SubmissionFileAccess::authorize($user, $submission);

        if ($blockReason = SubmissionFileAccess::canStudentRemove($user, $submission)) {
            return back()->withErrors(['error' => $blockReason]);
        }

        $this->deleteSubmissionFiles($submission);

        Audit::log(
            $request,
            'student.submission_removed',
            'ProjectSubmission',
            (string) $submission->id,
            ['stage' => $submission->stage, 'status' => $submission->status],
            null
        );

        $submission->delete();

        return back()->with('status', 'Submission removed.');
    }

    public function download(ProjectSubmission $submission): StreamedResponse
    {
        $this->authorizeFileAccess($submission);

        if (!$submission->file_path || !Storage::disk('public')->exists($submission->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $submission->file_path,
            $submission->original_filename ?: basename($submission->file_path)
        );
    }

    /**
     * Stream a submission file inline so the browser can render it
     * (PDFs preview natively; doc/docx/zip will fall back to download).
     * Reuses the same authorization rules as download() so only the
     * student owner, members of their group, the assigned supervisor,
     * or a coordinator/admin can read the file.
     */
    public function preview(ProjectSubmission $submission): StreamedResponse
    {
        $this->authorizeFileAccess($submission);

        if (!$submission->file_path || !Storage::disk('public')->exists($submission->file_path)) {
            abort(404, 'File not found.');
        }

        $filename = $submission->original_filename ?: basename($submission->file_path);
        $mime = $submission->mime_type ?: (Storage::disk('public')->mimeType($submission->file_path) ?: 'application/octet-stream');

        return Storage::disk('public')->response($submission->file_path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function interfaceScreenshot(ProjectSubmissionScreenshot $screenshot): StreamedResponse
    {
        $screenshot->loadMissing('submission');
        $this->authorizeFileAccess($screenshot->submission);

        if (! $screenshot->file_path || ! Storage::disk('public')->exists($screenshot->file_path)) {
            abort(404, 'Screenshot not found.');
        }

        $filename = $screenshot->original_filename ?: basename($screenshot->file_path);
        $mime = $screenshot->mime_type
            ?: (Storage::disk('public')->mimeType($screenshot->file_path) ?: 'image/png');

        return Storage::disk('public')->response($screenshot->file_path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Stream the primary home-page screenshot (legacy + first interface image).
     */
    public function screenshot(ProjectSubmission $submission): StreamedResponse
    {
        $this->authorizeFileAccess($submission);

        $submission->loadMissing('interfaceScreenshots');
        $shot = $submission->primaryInterfaceScreenshot();

        $path = $shot?->file_path ?: $submission->screenshot_path;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Screenshot not found.');
        }

        $filename = $shot?->original_filename
            ?: $submission->screenshot_original_filename
            ?: basename($path);
        $mime = $shot?->mime_type
            ?: $submission->screenshot_mime_type
            ?: (Storage::disk('public')->mimeType($path) ?: 'image/png');

        return Storage::disk('public')->response($path, $filename, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline; filename="'.addslashes($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Stream a project's documentation file (user manual, API docs, …)
     * inline so PDFs render in the showcase modal. Word docs/markdown
     * fall back to download.
     */
    public function documentation(ProjectSubmission $submission): StreamedResponse
    {
        $this->authorizeFileAccess($submission);

        if (!$submission->documentation_path || !Storage::disk('public')->exists($submission->documentation_path)) {
            abort(404, 'Documentation not found.');
        }

        $filename = $submission->documentation_original_filename ?: basename($submission->documentation_path);
        $mime     = $submission->documentation_mime_type
            ?: (Storage::disk('public')->mimeType($submission->documentation_path) ?: 'application/octet-stream');

        $disposition = str_contains((string) $mime, 'pdf') ? 'inline' : 'attachment';

        return Storage::disk('public')->response($submission->documentation_path, $filename, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => $disposition.'; filename="'.addslashes($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function isProjectSourceStage(?\App\Models\ProjectStage $stage): bool
    {
        return $stage !== null && StudentStageProgress::isCompleteSystemStage($stage->stage_name);
    }

    /**
     * Authorize a user to read a submission file. Allowed:
     *   • The student owner (or any member of their project group).
     *   • The supervisor assigned to that student/group.
     *   • Coordinators, HoDs, and admins (system-wide oversight).
     */
    private function authorizeFileAccess(ProjectSubmission $submission): void
    {
        SubmissionFileAccess::authorize(request()->user(), $submission);
    }

    private function deleteSubmissionFiles(ProjectSubmission $submission): void
    {
        $disk = Storage::disk('public');

        foreach (['file_path', 'screenshot_path', 'documentation_path'] as $column) {
            $path = $submission->{$column};
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $submission->loadMissing('interfaceScreenshots');

        foreach ($submission->interfaceScreenshots as $screenshot) {
            if ($screenshot->file_path && $disk->exists($screenshot->file_path)) {
                $disk->delete($screenshot->file_path);
            }
        }

        $submission->interfaceScreenshots()->delete();
    }
}
