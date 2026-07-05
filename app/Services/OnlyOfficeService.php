<?php

namespace App\Services;

use App\Models\ProjectSubmission;
use App\Models\User;
use App\Models\WordDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class OnlyOfficeService
{
    private const BLANK_TEMPLATE_RELATIVE = 'onlyoffice-templates/blank.docx';

    private const BUNDLED_BLANK_TEMPLATE = 'onlyoffice/blank.docx';
    public function isConfigured(): bool
    {
        return (bool) config('onlyoffice.document_server_url');
    }

    public function documentServerBaseUrl(): string
    {
        $base = rtrim((string) config('onlyoffice.document_server_url'), '/');

        if (! app()->runningInConsole() && request()->hasHeader('Host')) {
            $appHost = request()->getHost();
            $configuredHost = parse_url($base, PHP_URL_HOST) ?: 'localhost';
            $port = parse_url($base, PHP_URL_PORT) ?: 8080;

            if (in_array($configuredHost, ['localhost', '127.0.0.1'], true)
                && ! in_array($appHost, ['localhost', '127.0.0.1'], true)) {
                $base = request()->getScheme().'://'.$appHost.':'.$port;
            }
        }

        return $base;
    }

    public function apiScriptUrl(): string
    {
        return $this->documentServerBaseUrl().'/web-apps/apps/api/documents/api.js';
    }

    public function isDocumentServerReachable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->apiScriptUrl());

            return $response->successful() && strlen($response->body()) > 1000;
        } catch (\Throwable) {
            return false;
        }
    }

    public function storageBaseUrl(): string
    {
        return rtrim((string) config('onlyoffice.storage_url'), '/');
    }

    public function buildSubmissionEditorConfig(ProjectSubmission $submission, User $user): array
    {
        $this->repairSubmissionDocumentIfNeeded($submission);

        $submission->refreshOnlyOfficeKey();

        $fileUrl = $this->signedSubmissionDownloadUrl($submission);
        $callbackUrl = $this->submissionCallbackUrl($submission);
        $extension = strtolower(pathinfo($submission->original_filename, PATHINFO_EXTENSION) ?: 'docx');
        $capabilities = \App\Support\SubmissionFileAccess::resolveEditorCapabilities($user, $submission);

        $customization = [
            'autosave' => $capabilities['canEdit'],
            'forcesave' => $capabilities['canEdit'],
            'comments' => true,
            'compactHeader' => false,
            'toolbarNoTabs' => false,
            'hideRightMenu' => false,
            'toolbar' => true,
        ];

        if ($capabilities['canReview']) {
            $customization['review'] = [
                'reviewDisplay' => 'markup',
                'trackChanges' => true,
                'showReviewChanges' => true,
            ];
        } else {
            $customization['review'] = [
                'hideReviewDisplay' => true,
                'trackChanges' => false,
                'showReviewChanges' => false,
            ];
        }

        $jwtPayload = [
            'documentType' => 'word',
            'document' => [
                'fileType' => $extension,
                'key' => $submission->onlyoffice_key,
                'title' => $submission->title,
                'url' => $fileUrl,
                'permissions' => [
                    'edit' => $capabilities['canEdit'],
                    'download' => true,
                    'print' => true,
                    'copy' => true,
                    'review' => $capabilities['canReview'],
                    'comment' => $capabilities['canComment'],
                    'fillForms' => $capabilities['canEdit'],
                    'modifyFilter' => $capabilities['canEdit'],
                    'modifyContentControl' => $capabilities['canEdit'],
                ],
            ],
            'editorConfig' => [
                'mode' => $capabilities['editorMode'],
                'lang' => 'en',
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => $customization,
                'coEditing' => [
                    'mode' => 'fast',
                    'change' => $capabilities['canEdit'],
                ],
            ],
        ];

        return [
            'config' => $this->wrapConfigForEditor($jwtPayload),
            'capabilities' => $capabilities,
        ];
    }

    public function signedSubmissionDownloadUrl(ProjectSubmission $submission): string
    {
        return $this->signedRouteOnStorage('student.submissions.onlyoffice-serve', [
            'submission' => $submission->id,
        ]);
    }

    public function submissionCallbackUrl(ProjectSubmission $submission): string
    {
        return $this->signedRouteOnStorage('student.submissions.onlyoffice-callback', [
            'submission' => $submission->id,
        ]);
    }

    private function signedRouteOnStorage(string $name, array $parameters): string
    {
        $storageBase = rtrim($this->storageBaseUrl(), '/');

        URL::forceRootUrl($storageBase);

        try {
            return URL::signedRoute($name, $parameters);
        } finally {
            URL::forceRootUrl(config('app.url'));
        }
    }

    public function handleSubmissionCallback(ProjectSubmission $submission, array $payload): array
    {
        if ($this->jwtEnabled() && isset($payload['token'])) {
            $payload = $this->decodeJwt($payload['token']);
        }

        $status = (int) ($payload['status'] ?? 0);

        if (in_array($status, [2, 6], true) && ! empty($payload['url'])) {
            $this->saveSubmissionFromUrl($submission, (string) $payload['url']);
        }

        return ['error' => 0];
    }

    public function buildEditorConfig(WordDocument $document, User $user, string $mode = 'edit'): array
    {
        $document->refreshOnlyOfficeKey();

        $fileUrl = $this->signedDownloadUrl($document);
        $callbackUrl = $this->callbackUrl($document);

        $extension = strtolower(pathinfo($document->original_filename, PATHINFO_EXTENSION) ?: 'docx');

        $jwtPayload = [
            'documentType' => 'word',
            'document' => [
                'fileType' => $extension,
                'key' => $document->onlyoffice_key,
                'title' => $document->original_filename,
                'url' => $fileUrl,
                'permissions' => [
                    'edit' => $mode === 'edit',
                    'download' => true,
                    'print' => true,
                    'copy' => true,
                    'review' => true,
                    'comment' => true,
                ],
            ],
            'editorConfig' => [
                'mode' => $mode,
                'lang' => 'en',
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => [
                    'autosave' => true,
                    'forcesave' => true,
                    'comments' => true,
                    'compactHeader' => false,
                    'toolbarNoTabs' => false,
                ],
            ],
        ];

        return $this->wrapConfigForEditor($jwtPayload);
    }

    public function signedDownloadUrl(WordDocument $document): string
    {
        return $this->signedRouteOnStorage('word-documents.serve', [
            'wordDocument' => $document->id,
        ]);
    }

    public function callbackUrl(WordDocument $document): string
    {
        return $this->signedRouteOnStorage('word-documents.callback', [
            'wordDocument' => $document->id,
        ]);
    }

    public function createBlankDocx(string $title): array
    {
        $safeTitle = $this->sanitizeFilename($title);
        $filename = $safeTitle.'.docx';
        $relativePath = config('onlyoffice.storage_path').'/'.uniqid('doc_', true).'_'.$this->storageSafeFilename($filename);

        $this->ensureBlankTemplate();
        Storage::disk('public')->makeDirectory(dirname($relativePath));
        Storage::disk('public')->copy('onlyoffice-templates/blank.docx', $relativePath);

        $absolutePath = Storage::disk('public')->path($relativePath);
        $size = filesize($absolutePath) ?: 0;

        return [
            'file_path' => $relativePath,
            'original_filename' => $filename,
            'file_size' => $size,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    public function storeUploadedDocx(string $title, $uploadedFile): array
    {
        $safeTitle = $this->sanitizeFilename($title);
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'docx');

        if (! in_array($extension, ['doc', 'docx'], true)) {
            throw new \InvalidArgumentException('Only Word documents (.doc, .docx) are supported.');
        }

        $filename = $safeTitle.'.'.$extension;
        $relativePath = config('onlyoffice.storage_path').'/'.uniqid('doc_', true).'_'.$this->storageSafeFilename($filename);

        Storage::disk('public')->putFileAs(
            dirname($relativePath),
            $uploadedFile,
            basename($relativePath)
        );

        $mimeType = $extension === 'docx'
            ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            : 'application/msword';

        return [
            'file_path' => $relativePath,
            'original_filename' => $filename,
            'file_size' => Storage::disk('public')->size($relativePath),
            'mime_type' => $mimeType,
        ];
    }

    public function handleCallback(WordDocument $document, array $payload): array
    {
        if ($this->jwtEnabled() && isset($payload['token'])) {
            $payload = $this->decodeJwt($payload['token']);
        }

        $status = (int) ($payload['status'] ?? 0);

        // 2 = ready for saving, 6 = force save
        if (in_array($status, [2, 6], true) && ! empty($payload['url'])) {
            $this->saveDocumentFromUrl($document, (string) $payload['url']);
        }

        return ['error' => 0];
    }

    public function decodeCallbackRequest(string $rawBody): array
    {
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return [];
        }

        if ($this->jwtEnabled() && isset($payload['token'])) {
            return $this->decodeJwt($payload['token']);
        }

        return $payload;
    }

    private function saveSubmissionFromUrl(ProjectSubmission $submission, string $url): void
    {
        $response = Http::timeout(60)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to download saved document from ONLYOFFICE.');
        }

        Storage::disk('public')->put($submission->file_path, $response->body());

        $submission->update([
            'file_size' => Storage::disk('public')->size($submission->file_path),
            'onlyoffice_key' => null,
        ]);
    }

    private function saveDocumentFromUrl(WordDocument $document, string $url): void
    {
        $response = Http::timeout(60)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to download saved document from ONLYOFFICE.');
        }

        Storage::disk('public')->put($document->file_path, $response->body());

        $document->update([
            'file_size' => Storage::disk('public')->size($document->file_path),
            'onlyoffice_key' => null,
        ]);
    }

    private function ensureBlankTemplate(): string
    {
        $relative = self::BLANK_TEMPLATE_RELATIVE;
        $absolute = Storage::disk('public')->path($relative);

        Storage::disk('public')->makeDirectory('onlyoffice-templates');

        if (Storage::disk('public')->exists($relative) && $this->isValidEditableDocx($absolute)) {
            return $absolute;
        }

        if ($this->installBlankTemplate($absolute)) {
            return $absolute;
        }

        throw new \RuntimeException(
            'Blank Word template is unavailable. Enable the PHP zip extension in php.ini (extension=zip) and restart the web server.'
        );
    }

    private function installBlankTemplate(string $absolutePath): bool
    {
        if ($this->copyBundledBlankTemplate($absolutePath)) {
            return true;
        }

        if (! $this->canGenerateDocx()) {
            return false;
        }

        $this->generateBlankDocx($absolutePath);

        return is_file($absolutePath);
    }

    private function copyBundledBlankTemplate(string $absolutePath): bool
    {
        $bundled = resource_path(self::BUNDLED_BLANK_TEMPLATE);

        if (! is_file($bundled)) {
            return false;
        }

        $directory = dirname($absolutePath);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return false;
        }

        return copy($bundled, $absolutePath);
    }

    private function canGenerateDocx(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    public function repairSubmissionDocumentIfNeeded(ProjectSubmission $submission): void
    {
        if (! $submission->file_path || ! Storage::disk('public')->exists($submission->file_path)) {
            return;
        }

        $path = Storage::disk('public')->path($submission->file_path);

        if ($this->isValidEditableDocx($path)) {
            return;
        }

        $this->ensureBlankTemplate();
        Storage::disk('public')->copy('onlyoffice-templates/blank.docx', $submission->file_path);
        $submission->update([
            'file_size' => Storage::disk('public')->size($submission->file_path),
            'onlyoffice_key' => null,
        ]);
    }

    private function isValidEditableDocx(string $path): bool
    {
        if (! is_file($path) || filesize($path) < 512) {
            return false;
        }

        if (! $this->canGenerateDocx()) {
            return true;
        }

        $zip = new \ZipArchive;

        if ($zip->open($path) !== true) {
            return false;
        }

        $hasSettings = $zip->locateName('word/settings.xml') !== false;
        $hasFontTable = $zip->locateName('word/fontTable.xml') !== false;
        $hasNormalStyle = false;

        $stylesIndex = $zip->locateName('word/styles.xml');
        if ($stylesIndex !== false) {
            $styles = $zip->getFromIndex($stylesIndex);
            $hasNormalStyle = is_string($styles) && str_contains($styles, 'w:styleId="Normal"');
        }

        $zip->close();

        return $hasSettings && $hasFontTable && $hasNormalStyle;
    }

    private function generateBlankDocx(string $path): void
    {
        if (! $this->canGenerateDocx()) {
            throw new \RuntimeException('PHP zip extension is required to generate blank Word documents.');
        }

        $zip = new \ZipArchive;

        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create blank Word document.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
  <Override PartName="/word/fontTable.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.fontTable+xml"/>
  <Override PartName="/word/webSettings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.webSettings+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>');

        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/webSettings" Target="webSettings.xml"/>
  <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/fontTable" Target="fontTable.xml"/>
</Relationships>');

        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p w14:paraId="00000001" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml">
      <w:pPr><w:pStyle w:val="Normal"/></w:pPr>
      <w:r><w:t>Start typing here...</w:t></w:r>
    </w:p>
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>');

        $zip->addFromString('word/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault>
    <w:pPrDefault><w:pPr><w:spacing w:after="160" w:line="259" w:lineRule="auto"/></w:pPr></w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
  </w:style>
</w:styles>');

        $zip->addFromString('word/settings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:zoom w:percent="100"/>
  <w:defaultTabStop w:val="720"/>
  <w:characterSpacingControl w:val="doNotCompress"/>
</w:settings>');

        $zip->addFromString('word/webSettings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:webSettings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>');

        $zip->addFromString('word/fontTable.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:fonts xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:font w:name="Calibri"><w:panose1 w:val="020F0502020204030204"/><w:charset w:val="00"/><w:family w:val="swiss"/><w:pitch w:val="variable"/></w:font>
</w:fonts>');

        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Untitled Document</dc:title>
  <dc:creator>MoCU-PRMS</dc:creator>
  <cp:lastModifiedBy>MoCU-PRMS</cp:lastModifiedBy>
</cp:coreProperties>');

        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
  <Application>MoCU-PRMS</Application>
</Properties>');

        $zip->close();
    }

    private function sanitizeFilename(string $title): string
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s\-_()]/u', '', $title) ?? 'Untitled';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? 'Untitled');

        return $clean !== '' ? $clean : 'Untitled Document';
    }

    private function storageSafeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $safe = preg_replace('/[^\p{L}\p{N}\-_]/u', '_', $name) ?? 'document';
        $safe = trim(preg_replace('/_+/', '_', $safe) ?? 'document', '_');

        return ($safe !== '' ? $safe : 'document').($ext ? '.'.$ext : '');
    }

    private function wrapConfigForEditor(array $jwtPayload): array
    {
        $clientShell = [
            'width' => '100%',
            'height' => '100%',
            'type' => 'desktop',
        ];

        if (! $this->jwtEnabled()) {
            return array_merge($jwtPayload, $clientShell);
        }

        // ONLYOFFICE: document params go in JWT; width/height/type stay outside in the browser.
        return array_merge([
            'token' => $this->encodeJwt($jwtPayload),
        ], $clientShell);
    }

    private function jwtEnabled(): bool
    {
        return (bool) config('onlyoffice.jwt_enabled') && (string) config('onlyoffice.jwt_secret') !== '';
    }

    private function encodeJwt(array $payload): string
    {
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], $jsonFlags));
        $body = $this->base64UrlEncode(json_encode($payload, $jsonFlags));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', $header.'.'.$body, (string) config('onlyoffice.jwt_secret'), true)
        );

        return $header.'.'.$body.'.'.$signature;
    }

    private function decodeJwt(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return [];
        }

        [$header, $body, $signature] = $parts;

        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', $header.'.'.$body, (string) config('onlyoffice.jwt_secret'), true)
        );

        if (! hash_equals($expected, $signature)) {
            return [];
        }

        $decoded = json_decode($this->base64UrlDecode($body), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
