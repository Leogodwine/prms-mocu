<?php

namespace App\Http\Controllers;

use App\Models\ProjectStage;
use App\Models\ProjectSubmission;
use App\Services\OnlyOfficeService;
use App\Support\Audit;
use App\Support\StudentResearchEligibility;
use App\Support\StudentStageProgress;
use App\Support\SubmissionFileAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionEditorController extends Controller
{
    public function __construct(private OnlyOfficeService $onlyOffice)
    {
    }

    public function edit(Request $request, ProjectSubmission $submission): View|RedirectResponse
    {
        SubmissionFileAccess::authorize($request->user(), $submission);

        if (! $submission->isWordDocument()) {
            return redirect()->back()->with('error', 'This submission is not a Word document.');
        }

        if (! $this->onlyOffice->isConfigured()) {
            return redirect()->back()->with('error', 'Word editor is not available. ONLYOFFICE Document Server must be configured.');
        }

        if (! $this->onlyOffice->isDocumentServerReachable()) {
            return redirect()->back()->with('error',
                'ONLYOFFICE Document Server is not reachable at '.$this->onlyOffice->documentServerBaseUrl()
                .'. Run: docker compose -f docker-compose.onlyoffice.yml up -d'
            );
        }

        if (! $submission->file_path || ! Storage::disk('public')->exists($submission->file_path)) {
            abort(404, 'Document file not found.');
        }

        $editorPayload = $this->onlyOffice->buildSubmissionEditorConfig($submission, $request->user());
        $capabilities = $editorPayload['capabilities'];
        $editorLabel = $this->editorLabelForUser($request->user(), $submission, $capabilities);

        return view('submissions.editor', [
            'submission' => $submission,
            'config' => $editorPayload['config'],
            'capabilities' => $capabilities,
            'apiScriptUrl' => $this->onlyOffice->apiScriptUrl(),
            'documentServerBase' => $this->onlyOffice->documentServerBaseUrl(),
            'editorLabel' => $editorLabel,
            'backUrl' => $this->backUrlForUser($request->user()),
            'isDraft' => ($submission->status ?? '') === 'draft',
        ]);
    }

    public function createBlank(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->isStudentUser(), 403);

        $validated = $request->validate([
            'stage_id' => ['required', 'exists:project_stages,id'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        if ($blockReason = StudentResearchEligibility::researchYearBlockReason($user)) {
            return redirect()->back()->withInput()->withErrors(['stage_id' => $blockReason]);
        }

        $stage = ProjectStage::findOrFail($validated['stage_id']);
        $track = StudentStageProgress::workTypeFromStage($stage->stage_name);
        if (in_array($track, StudentStageProgress::workTypeOptions(), true)
            && ! StudentResearchEligibility::hasTrack($user, $track)) {
            return redirect()->back()->withInput()->withErrors(['stage_id' => 'This stage is not available for your programme.']);
        }

        $projectGroup = $user->projectGroups()->first();
        $latestByStage = StudentStageProgress::latestSubmissionByStage($user, $projectGroup);
        if ($uploadBlockReason = StudentStageProgress::canUploadStage(
            $stage->stage_name,
            $user,
            $projectGroup,
            $latestByStage
        )) {
            return redirect()->back()->withInput()->withErrors(['stage_id' => $uploadBlockReason]);
        }

        try {
            $fileMeta = $this->onlyOffice->createBlankDocx($validated['title']);
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }
        $relativePath = $fileMeta['file_path'];

        $nextVersionQuery = ProjectSubmission::query()->where('stage', $stage->stage_name);
        if ($projectGroup) {
            $nextVersionQuery->where('project_group_id', $projectGroup->id);
        } else {
            $nextVersionQuery->where('student_id', $user->id);
        }

        $nextVersion = (int) $nextVersionQuery->max('version') + 1;

        $submission = ProjectSubmission::create([
            'project_group_id' => $projectGroup?->id,
            'student_id' => $user->id,
            'stage' => $stage->stage_name,
            'title' => $validated['title'],
            'version' => $nextVersion,
            'file_path' => $relativePath,
            'original_filename' => $fileMeta['original_filename'],
            'mime_type' => $fileMeta['mime_type'],
            'file_size' => $fileMeta['file_size'],
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        Audit::log($request, 'student.submission_blank_word_created', 'ProjectSubmission', (string) $submission->id, null, [
            'stage' => $submission->stage,
            'version' => $submission->version,
        ]);

        return redirect()
            ->route('student.submissions.editor', $submission)
            ->with('success', 'Blank document created as a draft. Write your content, save, then submit to your supervisor when ready.');
    }

    public function serve(Request $request, ProjectSubmission $submission): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired document link.');
        }

        if (! $submission->file_path || ! Storage::disk('public')->exists($submission->file_path)) {
            abort(404, 'Document file not found.');
        }

        $filename = $submission->original_filename ?: basename($submission->file_path);
        $mime = $submission->mime_type ?: (Storage::disk('public')->mimeType($submission->file_path) ?: 'application/octet-stream');

        return Storage::disk('public')->response($submission->file_path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
        ]);
    }

    public function callback(Request $request, ProjectSubmission $submission): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['error' => 1]);
        }

        $payload = $this->onlyOffice->decodeCallbackRequest($request->getContent());

        try {
            $result = $this->onlyOffice->handleSubmissionCallback($submission, $payload);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 1]);
        }

        return response()->json($result);
    }

    private function editorLabelForUser($user, ProjectSubmission $submission, array $capabilities): string
    {
        if ($capabilities['canEdit'] && $capabilities['canReview']) {
            return 'Edit & review';
        }

        if ($capabilities['canEdit']) {
            return 'Edit document';
        }

        if ($capabilities['canReview'] || $capabilities['canComment']) {
            return 'Review & comment';
        }

        return 'View document';
    }

    private function backUrlForUser($user): string
    {
        if ($user->role === 'supervisor') {
            return route('supervisor.index');
        }

        return route('student.index');
    }
}
