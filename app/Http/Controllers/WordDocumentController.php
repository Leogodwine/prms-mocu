<?php

namespace App\Http\Controllers;

use App\Models\WordDocument;
use App\Services\OnlyOfficeService;
use App\Support\Audit;
use App\Support\PrmsTablePagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WordDocumentController extends Controller
{
    public function __construct(private OnlyOfficeService $onlyOffice)
    {
    }

    public function index(Request $request): View
    {
        $documents = WordDocument::query()
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        return view('word-documents.index', [
            'documents' => $documents,
            'onlyOfficeConfigured' => $this->onlyOffice->isConfigured(),
            'documentServerUrl' => config('onlyoffice.document_server_url'),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $fileMeta = $this->onlyOffice->createBlankDocx($validated['title']);

        $document = WordDocument::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            ...$fileMeta,
        ]);

        Audit::log($request, 'word_document.created', 'WordDocument', (string) $document->id, null, [
            'title' => $document->title,
            'action' => 'create_blank',
        ]);

        return redirect()
            ->route('word-documents.edit', $document)
            ->with('success', 'Blank Word document created. You can start editing now.');
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('onlyoffice.max_upload_kb', 20480);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:doc,docx', 'max:'.$maxKb],
        ]);

        try {
            $fileMeta = $this->onlyOffice->storeUploadedDocx($validated['title'], $validated['file']);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        $document = WordDocument::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            ...$fileMeta,
        ]);

        Audit::log($request, 'word_document.created', 'WordDocument', (string) $document->id, null, [
            'title' => $document->title,
            'action' => 'upload',
        ]);

        return redirect()
            ->route('word-documents.edit', $document)
            ->with('success', 'Document uploaded successfully.');
    }

    public function edit(Request $request, WordDocument $wordDocument): View|RedirectResponse
    {
        $this->authorizeDocument($request, $wordDocument);

        if (! $this->onlyOffice->isConfigured()) {
            return redirect()
                ->route('word-documents.index')
                ->with('error', 'ONLYOFFICE Document Server is not configured. Set ONLYOFFICE_DOCUMENT_SERVER_URL in your .env file.');
        }

        $config = $this->onlyOffice->buildEditorConfig($wordDocument, $request->user(), 'edit');

        return view('word-documents.editor', [
            'document' => $wordDocument,
            'config' => $config,
            'apiScriptUrl' => $this->onlyOffice->apiScriptUrl(),
        ]);
    }

    public function serve(Request $request, WordDocument $wordDocument): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired document link.');
        }

        if (! Storage::disk('public')->exists($wordDocument->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->response(
            $wordDocument->file_path,
            $wordDocument->original_filename,
            [
                'Content-Type' => $wordDocument->mime_type,
                'Content-Disposition' => 'inline; filename="'.$wordDocument->original_filename.'"',
            ]
        );
    }

    public function download(Request $request, WordDocument $wordDocument): StreamedResponse
    {
        $this->authorizeDocument($request, $wordDocument);

        if (! Storage::disk('public')->exists($wordDocument->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->download(
            $wordDocument->file_path,
            $wordDocument->original_filename
        );
    }

    public function callback(Request $request, WordDocument $wordDocument): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['error' => 1]);
        }

        $payload = $this->onlyOffice->decodeCallbackRequest($request->getContent());

        try {
            $result = $this->onlyOffice->handleCallback($wordDocument, $payload);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 1]);
        }

        return response()->json($result);
    }

    public function destroy(Request $request, WordDocument $wordDocument): RedirectResponse
    {
        $this->authorizeDocument($request, $wordDocument);

        $wordDocument->deleteFile();
        $wordDocument->delete();

        Audit::log($request, 'word_document.deleted', 'WordDocument', (string) $wordDocument->id, [
            'title' => $wordDocument->title,
        ], null);

        return redirect()
            ->route('word-documents.index')
            ->with('success', 'Document deleted.');
    }

    private function authorizeDocument(Request $request, WordDocument $wordDocument): void
    {
        if ((int) $wordDocument->user_id !== (int) $request->user()->id) {
            abort(403, 'You do not have permission to access this document.');
        }
    }
}
