<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $submission->title }} | {{ $editorLabel }} | {{ config('app.name', 'MoCU-PRMS') }}</title>
    <script src="{{ $apiScriptUrl }}"></script>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; height: 100%; overflow: hidden;
            font-family: system-ui, sans-serif;
            display: flex; flex-direction: column;
        }
        .prms-skip-link {
            position: absolute; left: -9999px; top: auto; width: 1px; height: 1px; overflow: hidden;
        }
        .prms-skip-link:focus {
            position: fixed; left: 1rem; top: 1rem; width: auto; height: auto;
            padding: 0.5rem 1rem; background: #fff; color: #1a2035; z-index: 20;
            border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .editor-toolbar {
            flex: 0 0 auto;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            padding: 0.5rem 1rem; background: #1a2035; color: #fff;
        }
        .editor-toolbar a, .editor-toolbar button {
            color: #fff; text-decoration: none; font-size: 0.875rem;
        }
        .editor-toolbar a:focus-visible, .editor-toolbar button:focus-visible {
            outline: 2px solid #fff; outline-offset: 2px; border-radius: 4px;
        }
        .editor-toolbar-actions {
            display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
        }
        .editor-toolbar .btn-prms {
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.08);
            color: #fff;
            border-radius: 999px;
            padding: 0.35rem 0.85rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
        }
        .editor-toolbar .btn-prms-primary {
            background: #2563eb;
            border-color: #2563eb;
        }
        .editor-toolbar .btn-prms:disabled {
            opacity: 0.6;
            cursor: wait;
        }
        .editor-hint,
        .editor-deploy-warnings,
        .editor-ready-hint {
            display: none !important;
        }
        #onlyoffice-editor {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            position: relative;
        }
        .editor-fallback {
            padding: 2rem; color: #721c24; background: #f8d7da; margin: 1rem; border-radius: 8px;
        }
        .editor-status {
            flex: 0 0 auto;
            padding: 0.35rem 1rem;
            font-size: 0.8rem;
            background: #eef2ff;
            color: #1e3a8a;
            border-bottom: 1px solid #c7d2fe;
            display: none;
        }
        .editor-status.is-visible {
            display: block;
        }
        .editor-status.is-error {
            background: #fef2f2;
            color: #991b1b;
            border-bottom-color: #fecaca;
        }
        .editor-stuck-help {
            display: none;
            margin: 1rem;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .editor-stuck-help.visible { display: block; }
        .editor-stuck-help ul { margin: 0.5rem 0 0; padding-left: 1.25rem; }
        @media (prefers-reduced-motion: reduce) {
            .editor-stuck-help { transition: none; }
        }
    </style>
</head>
<body>
    <a href="#onlyoffice-editor" class="prms-skip-link">Skip to document editor</a>

    <header class="editor-toolbar" role="banner" aria-label="Document editor toolbar">
        <div>
            <a href="{{ $backUrl }}" aria-label="Back to workspace">← Back</a>
            <span style="opacity:0.8;font-size:0.8rem;margin-left:0.5rem;" aria-hidden="true">
                {{ \App\Support\StudentStageProgress::shortStageLabel($submission->stage) }} · v{{ $submission->version }} · {{ $editorLabel }}
            </span>
            <div style="font-weight:600;font-size:0.9rem;" id="editor-doc-title">{{ $submission->title }}</div>
        </div>
        <div class="editor-toolbar-actions">
            @if (! empty($capabilities['canEdit']))
                <button type="button" class="btn-prms" id="prms-editor-save" aria-label="Save document to server">
                    Save
                </button>
                <button type="button" class="btn-prms btn-prms-primary" id="prms-editor-save-return" aria-label="Save document and return to workspace">
                    Save &amp; return
                </button>
            @endif
            <a href="{{ route('student.submissions.download', $submission) }}"
               aria-label="Download current document version">Download</a>
        </div>
    </header>

    <div id="editor-status" class="editor-status" role="status" aria-live="polite"></div>

    <main id="onlyoffice-editor" role="main" aria-labelledby="editor-doc-title" tabindex="-1"></main>
    <div id="editor-stuck-help" class="editor-stuck-help" role="alert">
        <strong>Document did not open.</strong>
        <p class="mb-0">The ONLYOFFICE shell loaded, but the Document Server could not fetch your file from Laravel. Typical fixes:</p>
        <ul>
            <li>Set <code>ONLYOFFICE_STORAGE_URL</code> to a URL reachable <em>from inside the Document Server container</em> (not <code>127.0.0.1</code> when Laravel runs on the host).</li>
            <li>On Docker Desktop + XAMPP: <code>ONLYOFFICE_STORAGE_URL=http://host.docker.internal</code></li>
            <li>On a Linux server: use the server LAN IP or public domain, and open port <strong>8080</strong> for ONLYOFFICE.</li>
            <li>Match JWT: set <code>ONLYOFFICE_JWT_ENABLED=false</code> in <code>.env</code> if Docker uses <code>JWT_ENABLED=false</code>.</li>
            <li>Run <code>php artisan storage:link</code> on the server so uploaded files exist under <code>public/storage</code>.</li>
            <li>Test from the container: <code>docker exec prms-onlyoffice curl -I "{{ rtrim($documentServerBase ?? '', '/') }}"</code> and curl your Laravel app at <code>{{ rtrim(config('onlyoffice.storage_url'), '/') }}</code>.</li>
        </ul>
    </div>
    </div>

    <script>
        (function () {
            var placeholder = document.getElementById('onlyoffice-editor');
            var statusEl = document.getElementById('editor-status');
            var saveBtn = document.getElementById('prms-editor-save');
            var saveReturnBtn = document.getElementById('prms-editor-save-return');
            var backUrl = @json($backUrl);
            var config = @json($config);
            var docEditor = null;
            var savePending = false;
            var returnAfterSave = false;
            var documentOpened = false;
            var openTimeout = null;

            function setStatus(message, isError) {
                if (! statusEl) {
                    return;
                }
                if (! message) {
                    statusEl.textContent = '';
                    statusEl.classList.remove('is-visible', 'is-error');
                    return;
                }
                statusEl.textContent = message;
                statusEl.classList.add('is-visible');
                statusEl.classList.toggle('is-error', !! isError);
            }

            function setSaving(isSaving) {
                savePending = isSaving;
                [saveBtn, saveReturnBtn].forEach(function (btn) {
                    if (btn) {
                        btn.disabled = isSaving;
                    }
                });
            }

            function forceSave() {
                if (! docEditor) {
                    setStatus('Editor is still loading. Please wait a moment.');
                    return false;
                }

                setSaving(true);
                setStatus('Saving document…');

                if (typeof docEditor.processSave === 'function') {
                    docEditor.processSave();
                    return true;
                }

                if (typeof docEditor.serviceCommand === 'function') {
                    docEditor.serviceCommand('forcesave', '');
                    return true;
                }

                setSaving(false);
                setStatus('Use the Save icon in the Word toolbar (top-left), then return to your workspace.');
                return false;
            }

            function finishSaveFlow() {
                setSaving(false);
                if (returnAfterSave) {
                    returnAfterSave = false;
                    window.location.href = backUrl;
                    return;
                }
                setStatus('');
            }

            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    returnAfterSave = false;
                    forceSave();
                    window.setTimeout(function () {
                        if (savePending) {
                            finishSaveFlow();
                        }
                    }, 2500);
                });
            }

            if (saveReturnBtn) {
                saveReturnBtn.addEventListener('click', function () {
                    returnAfterSave = true;
                    if (! forceSave()) {
                        returnAfterSave = false;
                        return;
                    }
                    window.setTimeout(function () {
                        if (savePending) {
                            finishSaveFlow();
                        }
                    }, 2500);
                });
            }

            config.width = '100%';
            config.height = '100%';
            config.type = 'desktop';

            config.events = {
                onAppReady: function () {
                    if (openTimeout) {
                        clearTimeout(openTimeout);
                    }
                    openTimeout = window.setTimeout(function () {
                        if (! documentOpened) {
                            setStatus('Document failed to open — check ONLYOFFICE configuration.', true);
                            var stuck = document.getElementById('editor-stuck-help');
                            if (stuck) {
                                stuck.classList.add('visible');
                            }
                        }
                    }, 15000);
                },
                onDocumentReady: function () {
                    documentOpened = true;
                    if (openTimeout) {
                        clearTimeout(openTimeout);
                        openTimeout = null;
                    }
                    var stuck = document.getElementById('editor-stuck-help');
                    if (stuck) {
                        stuck.classList.remove('visible');
                    }
                    setStatus('');
                    placeholder.focus();
                },
                onDocumentStateChange: function () {},
                onRequestSaveAs: function () {},
                onMetaChange: function () {},
                onError: function (e) {
                    setSaving(false);
                    var msg = (e && e.data) ? (e.data.errorCode + ': ' + (e.data.errorDescription || '')) : 'Unknown error';
                    setStatus('Editor error: ' + msg, true);
                    placeholder.innerHTML = '<div class="editor-fallback" role="alert"><strong>Editor error</strong><p>' + msg + '</p><p><a href="' + backUrl + '">Return to workspace</a></p></div>';
                },
                onWarning: function () {},
                onRequestClose: function () {
                    if (returnAfterSave) {
                        window.location.href = backUrl;
                    }
                }
            };

            if (typeof DocsAPI === 'undefined') {
                setStatus('ONLYOFFICE failed to load.', true);
                placeholder.innerHTML =
                    '<div class="editor-fallback" role="alert"><strong>ONLYOFFICE failed to load</strong><p>Check Docker: <code>docker ps</code> and open <a href="{{ $apiScriptUrl }}">{{ $apiScriptUrl }}</a></p><p><a href="' + backUrl + '">Return to workspace</a></p></div>';
                return;
            }

            docEditor = new DocsAPI.DocEditor('onlyoffice-editor', config);
        })();
    </script>
</body>
</html>
