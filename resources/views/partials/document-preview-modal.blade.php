<div class="modal fade" id="prmsPreviewModal" tabindex="-1" aria-labelledby="prmsPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content" style="height: 85vh;">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="prmsPreviewModalLabel">
                    <i class="far fa-eye text-primary me-2" aria-hidden="true"></i>
                    <span id="prmsPreviewFileName">Document preview</span>
                </h5>
                <div class="d-flex gap-2 ms-auto me-2">
                    <a id="prmsPreviewDownload" href="#" class="btn btn-light btn-sm border">
                        <i class="fas fa-download me-1" aria-hidden="true"></i> Download
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light" style="overflow: hidden;">
                <iframe id="prmsPreviewFrame" title="Document preview" src="about:blank"
                        style="width: 100%; height: 100%; border: 0;"></iframe>

                <div id="prmsPreviewImageWrap" class="d-none h-100 d-flex align-items-center justify-content-center p-3" style="overflow: auto;">
                    <img id="prmsPreviewImage" alt="Document preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>

                <pre id="prmsPreviewText" class="d-none h-100 m-0 p-3 bg-white" style="overflow: auto; white-space: pre-wrap; word-break: break-word; font-family: var(--bs-font-monospace, monospace); font-size: 0.85rem; color: var(--prms-text);"></pre>

                <div id="prmsPreviewFallback" class="d-none h-100 d-flex flex-column align-items-center justify-content-center text-center p-4">
                    <i id="prmsPreviewFallbackIcon" class="far fa-file-alt text-primary mb-3" aria-hidden="true" style="font-size: 3rem;"></i>
                    <h4 class="h6 fw-bold text-strong" id="prmsPreviewFallbackTitle">Inline preview not available</h4>
                    <p class="text-muted mb-3" style="max-width: 420px;" id="prmsPreviewFallbackBody">
                        Your browser can natively preview PDFs, images, and plain-text files.
                        For Word documents, archives (ZIP/RAR/7Z), and other binary formats,
                        download the file to open it in a desktop application.
                    </p>
                    <a id="prmsPreviewFallbackDownload" href="#" class="btn btn-primary">
                        <i class="fas fa-download me-1" aria-hidden="true"></i> Download document
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('prmsPreviewModal');
        if (!modal) return;

        const frame      = document.getElementById('prmsPreviewFrame');
        const imageWrap  = document.getElementById('prmsPreviewImageWrap');
        const imageEl    = document.getElementById('prmsPreviewImage');
        const textEl     = document.getElementById('prmsPreviewText');
        const fallback   = document.getElementById('prmsPreviewFallback');
        const fallbackIcon  = document.getElementById('prmsPreviewFallbackIcon');
        const fallbackTitle = document.getElementById('prmsPreviewFallbackTitle');
        const fallbackBody  = document.getElementById('prmsPreviewFallbackBody');
        const fallbackBtn = document.getElementById('prmsPreviewFallbackDownload');
        const dlBtn      = document.getElementById('prmsPreviewDownload');
        const nameEl     = document.getElementById('prmsPreviewFileName');

        const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        const textExts  = ['txt', 'md', 'csv', 'json', 'xml', 'log'];

        function hideAll() {
            frame.classList.add('d-none');
            imageWrap.classList.add('d-none');
            textEl.classList.add('d-none');
            fallback.classList.add('d-none');
            frame.setAttribute('src', 'about:blank');
            imageEl.removeAttribute('src');
            textEl.textContent = '';
        }

        function showFallback(reason, ext) {
            hideAll();
            fallback.classList.remove('d-none');
            fallbackTitle.textContent = reason || 'Inline preview not available';
            if (ext) {
                fallbackBody.textContent = 'This file type (' + ext.toUpperCase() + ') cannot be previewed inline. Download the file to open it in a desktop application.';
            }
        }

        modal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;

            hideAll();

            const previewUrl  = trigger.getAttribute('data-preview-url');
            const downloadUrl = trigger.getAttribute('data-download-url');
            const fileName    = trigger.getAttribute('data-file-name') || 'Document preview';
            const mimeType    = (trigger.getAttribute('data-mime-type') || '').toLowerCase();
            const ext         = (trigger.getAttribute('data-extension') || '').toLowerCase();
            const isPdf       = trigger.getAttribute('data-is-pdf') === '1'
                || mimeType.includes('pdf') || ext === 'pdf';

            nameEl.textContent = fileName;
            if (dlBtn && downloadUrl) dlBtn.setAttribute('href', downloadUrl);
            if (fallbackBtn && downloadUrl) fallbackBtn.setAttribute('href', downloadUrl);

            if (!previewUrl) { showFallback('Preview unavailable', ext); return; }

            if (isPdf) {
                frame.classList.remove('d-none');
                frame.setAttribute('src', previewUrl);
                return;
            }

            if (imageExts.includes(ext) || mimeType.startsWith('image/')) {
                imageWrap.classList.remove('d-none');
                imageEl.setAttribute('src', previewUrl);
                return;
            }

            if (textExts.includes(ext) || mimeType.startsWith('text/')) {
                fetch(previewUrl, { credentials: 'same-origin' })
                    .then(r => r.ok ? r.text() : Promise.reject())
                    .then(t => { textEl.textContent = t; textEl.classList.remove('d-none'); })
                    .catch(() => showFallback('Could not load text preview', ext));
                return;
            }

            showFallback('Inline preview not available', ext);
        });

        modal.addEventListener('hidden.bs.modal', function () {
            hideAll();
        });
    })();
</script>
@endpush
