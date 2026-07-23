<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubmission;
use App\Models\SubmissionFeedback;
use App\Support\Audit;
use App\Support\PresentationConsentForm;
use App\Support\PrmsEventNotifier;
use App\Support\StudentStageProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PresentationConsentController extends Controller
{
    public function show(Request $request): View
    {
        return view('documents.presentation-consent-form', PresentationConsentForm::resolveContext($request));
    }

    public function download(Request $request): Response
    {
        $context = PresentationConsentForm::resolveContext($request);

        return PresentationConsentForm::downloadPdf($context);
    }

    public function supervisorSign(Request $request, ProjectSubmission $submission): View
    {
        PresentationConsentForm::authorizeSupervisorForSubmission($request->user(), $submission);

        $context = PresentationConsentForm::resolveFromSubmission($submission, $request->user());

        return view('supervisor.consent-sign', array_merge($context, [
            'submission' => $submission,
            'alreadySigned' => $submission->supervisor_consent_signed_at !== null
                && $submission->status === 'approved',
        ]));
    }

    public function supervisorSignStore(Request $request, ProjectSubmission $submission): RedirectResponse
    {
        $supervisor = $request->user();
        PresentationConsentForm::authorizeSupervisorForSubmission($supervisor, $submission);

        if ($submission->supervisor_consent_signed_at !== null && $submission->status === 'approved') {
            return redirect()
                ->route('supervisor.presentation-consent.sign', $submission)
                ->with('status', 'Consent has already been signed for this submission.');
        }

        $validated = $request->validate([
            'consent_agreed' => ['accepted'],
            'signature' => ['required', 'string'],
            'presentation_date' => ['required', 'date'],
            'consent_project_title' => ['required', 'string', 'max:500'],
            'consent_group_number' => ['required', 'string', 'max:120'],
            'comments' => ['nullable', 'string', 'max:3000'],
        ], [
            'consent_agreed.accepted' => 'You must agree to the consent declaration before signing.',
            'signature.required' => 'Please draw your signature before submitting.',
            'presentation_date.required' => 'Please confirm the proposed presentation date.',
            'consent_project_title.required' => 'Please enter the project title on the consent form.',
            'consent_group_number.required' => 'Please enter the group number on the consent form.',
        ]);

        $submission->update([
            'presentation_date' => $validated['presentation_date'],
            'consent_project_title' => trim($validated['consent_project_title']),
            'consent_group_number' => trim($validated['consent_group_number']),
        ]);
        $submission->refresh();

        $signaturePath = PresentationConsentForm::storeSignatureImage($validated['signature']);
        $signatureDataUri = PresentationConsentForm::signatureDataUriFromBase64($validated['signature']);

        $context = PresentationConsentForm::resolveFromSubmission($submission, $supervisor);
        $context['supervisorSignatureDataUri'] = $signatureDataUri;
        $context['signedAt'] = now();

        try {
            $pdfPath = PresentationConsentForm::savePdfToDisk($context, $submission);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('supervisor.presentation-consent.sign', $submission)
                ->withInput()
                ->withErrors(['pdf' => $e->getMessage()]);
        }

        SubmissionFeedback::create([
            'project_submission_id' => $submission->id,
            'supervisor_id' => $supervisor->id,
            'comments' => filled($validated['comments'] ?? null) ? trim($validated['comments']) : null,
            'decision' => 'approved',
        ]);

        $submission->update([
            'status' => 'approved',
            'supervisor_signature_path' => $signaturePath,
            'supervisor_consent_pdf_path' => $pdfPath,
            'supervisor_consent_signed_at' => now(),
            'supervisor_consent_signed_by' => $supervisor->id,
            'submitted_to_coordinator' => true,
        ]);

        Audit::log(
            $request,
            'supervisor.consent_signed',
            'ProjectSubmission',
            (string) $submission->id,
            null,
            ['pdf_path' => $pdfPath]
        );

        PrmsEventNotifier::notifyConsentForwardedToCoordinator($submission, $supervisor);

        return redirect()
            ->route('supervisor.index')
            ->with('status', 'Consent signed successfully. The student has been notified and the request was forwarded to the coordinator.');
    }

    public function previewPdf(Request $request, ProjectSubmission $submission): Response|RedirectResponse
    {
        PresentationConsentForm::authorizeSupervisorForSubmission($request->user(), $submission);

        $validated = $request->validate([
            'signature' => ['nullable', 'string'],
        ]);

        $context = PresentationConsentForm::resolveFromSubmission($submission, $request->user());
        $signatureDataUri = null;

        if (! empty($validated['signature'])) {
            $signatureDataUri = PresentationConsentForm::signatureDataUriFromBase64($validated['signature']);
            $context['signedAt'] = now();
        }

        try {
            return PresentationConsentForm::renderPdf($context, $signatureDataUri);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('supervisor.presentation-consent.sign', $submission)
                ->withErrors(['pdf' => $e->getMessage()]);
        }
    }

    public function pdf(Request $request, ProjectSubmission $submission): Response
    {
        $user = $request->user();

        if ($user->role === 'supervisor') {
            PresentationConsentForm::authorizeSupervisorForSubmission($user, $submission);
        } elseif ($user->isStudentUser()) {
            PresentationConsentForm::authorizeStudentForSubmission($user, $submission);
        } elseif (in_array($user->role, ['coordinator', 'hod', 'admin'], true)) {
            PresentationConsentForm::authorizeCoordinatorForSubmission($user, $submission);
        } else {
            abort(403);
        }

        $storedPdf = PresentationConsentForm::consentPdfPath($submission);
        if ($storedPdf !== null) {
            return response()->file(
                \Illuminate\Support\Facades\Storage::disk('public')->path($storedPdf),
                ['Content-Type' => 'application/pdf']
            );
        }

        $context = PresentationConsentForm::resolveFromSubmission($submission, $user);

        return PresentationConsentForm::renderPdf($context, $context['supervisorSignatureDataUri'] ?? null);
    }
}
