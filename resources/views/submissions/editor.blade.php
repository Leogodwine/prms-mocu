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
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.5rem 1rem; background: #1a2035; color: #fff;
        }
        .editor-toolbar a {
            color: #fff; text-decoration: none; font-size: 0.875rem;
        }
        .editor-toolbar a:focus-visible {
            outline: 2px solid #fff; outline-offset: 2px; border-radius: 4px;
        }
        .editor-hint {
            flex: 0 0 auto;
            background: #fff3cd; color: #664d03; padding: 0.4rem 1rem; font-size: 0.85rem;
            border-bottom: 1px solid #ffc107;
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
        }
        .editor-ready-hint {
            display: none;
            position: fixed; bottom: 12px; left: 50%; transform: translateX(-50%);
            background: rgba(26, 32, 53, 0.92); color: #fff; padding: 0.45rem 1rem;
            border-radius: 999px; font-size: 0.8rem; z-index: 5; pointer-events: none;
        }
        .editor-ready-hint.visible { display: block; }
        @media (prefers-reduced-motion: reduce) {
            .editor-ready-hint { transition: none; }
        }
    </style>
</head>
<body>
    <a href="#onlyoffice-editor" class="prms-skip-link">Skip to document editor</a>

    <header class="editor-toolbar" role="banner" aria-label="Document editor toolbar">
        <div>
            <a href="{{ $backUrl }}" aria-label="Back to workspace">← Back</a>
            <span style="opacity:0.8;font-size:0.8rem;margin-left:0.5rem;" aria-hidden="true">
                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $submission->stage)) }} · v{{ $submission->version }} · {{ $editorLabel }}
            </span>
            <div style="font-weight:600;font-size:0.9rem;" id="editor-doc-title">{{ $submission->title }}</div>
        </div>
        <a href="{{ route('student.submissions.download', $submission) }}"
           style="color:#fff;font-size:0.85rem;"
           aria-label="Download current document version">Download</a>
    </header>

    @if (! empty($capabilities['hint']))
        <div class="editor-hint" role="note">{{ $capabilities['hint'] }}</div>
    @endif

    <div id="editor-status" class="editor-status" role="status" aria-live="polite">
        Loading document editor…
    </div>

    <main id="onlyoffice-editor" role="main" aria-labelledby="editor-doc-title" tabindex="-1"></main>
    <div id="editor-ready-hint" class="editor-ready-hint" aria-hidden="true">
        Document loaded — click in the page and start typing
    </div>

    <script>
        (function () {
            var placeholder = document.getElementById('onlyoffice-editor');
            var readyHint = document.getElementById('editor-ready-hint');
            var statusEl = document.getElementById('editor-status');
            var config = @json($config);

            function setStatus(message) {
                if (statusEl) {
                    statusEl.textContent = message;
                }
            }

            config.width = '100%';
            config.height = '100%';
            config.type = 'desktop';

            config.events = {
                onAppReady: function () {
                    setStatus('Editor application ready. Opening document…');
                },
                onDocumentReady: function () {
                    setStatus('Document ready. You can edit now.');
                    if (readyHint) {
                        readyHint.classList.add('visible');
                        readyHint.setAttribute('aria-hidden', 'false');
                        setTimeout(function () {
                            readyHint.classList.remove('visible');
                            readyHint.setAttribute('aria-hidden', 'true');
                        }, 8000);
                    }
                    placeholder.focus();
                },
                onDocumentStateChange: function () {},
                onError: function (e) {
                    var msg = (e && e.data) ? (e.data.errorCode + ': ' + (e.data.errorDescription || '')) : 'Unknown error';
                    setStatus('Editor error: ' + msg);
                    placeholder.innerHTML = '<div class="editor-fallback" role="alert"><strong>Editor error</strong><p>' + msg + '</p><p><a href="{{ $backUrl }}">Return to workspace</a></p></div>';
                },
                onWarning: function () {}
            };

            if (typeof DocsAPI === 'undefined') {
                setStatus('ONLYOFFICE failed to load.');
                placeholder.innerHTML =
                    '<div class="editor-fallback" role="alert"><strong>ONLYOFFICE failed to load</strong><p>Check Docker: <code>docker ps</code> and open <a href="{{ $apiScriptUrl }}">{{ $apiScriptUrl }}</a></p><p><a href="{{ $backUrl }}">Return to workspace</a></p></div>';
                return;
            }

            new DocsAPI.DocEditor('onlyoffice-editor', config);
        })();
    </script>
</body>
</html>
