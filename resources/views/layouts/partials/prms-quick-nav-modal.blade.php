@if (auth()->check())
    @php
        $quickNavItems = \App\Support\PrmsNavigationIndex::quickNavForUser(auth()->user());
        $quickNavSearchUrls = [
            'archive' => route('archive.index', ['apply_q' => '__QUERY__']),
            'public' => route('public.research.index', ['apply_search' => '__QUERY__']),
            'users' => route('admin.users.index', ['apply_q' => '__QUERY__']),
        ];
    @endphp

    <div class="modal fade prms-quick-nav-modal" id="prmsQuickNavModal" tabindex="-1"
         aria-labelledby="prmsQuickNavTitle" aria-hidden="true"
         data-bs-backdrop="true" data-bs-keyboard="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div class="w-100 pe-2">
                        <h2 class="modal-title h5 fw-semibold mb-1" id="prmsQuickNavTitle">
                            <i class="fas fa-search text-primary me-2" aria-hidden="true"></i>
                            Quick find
                        </h2>
                        <p class="small text-muted mb-3">Jump to any page, workspace chapter, or search the library.</p>
                        <label class="visually-hidden" for="prmsQuickNavInput">Search destinations</label>
                        <div class="input-group input-group-lg prms-quick-nav-input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search text-primary" aria-hidden="true"></i>
                            </span>
                            <input type="text"
                                   id="prmsQuickNavInput"
                                   class="form-control"
                                   placeholder="e.g. deadlines, chapter 1, consent, reports…"
                                   autocomplete="off"
                                   spellcheck="false">
                            <span class="input-group-text text-muted small d-none d-sm-inline">Esc</span>
                        </div>
                        <p id="prmsQuickNavHint" class="small text-muted mb-0 mt-2"></p>
                    </div>
                    <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="prmsQuickNavResults" class="list-group list-group-flush" role="listbox"></div>
                    <div id="prmsQuickNavEmpty" class="text-center text-muted py-5 small d-none">
                        <i class="fas fa-search mb-2 d-block opacity-25" style="font-size: 2rem;" aria-hidden="true"></i>
                        No matching destinations. Try another keyword.
                    </div>
                </div>
                <div class="modal-footer border-top small text-muted justify-content-between flex-wrap gap-2">
                    <span><kbd>↑</kbd> <kbd>↓</kbd> select · <kbd>Enter</kbd> open · <kbd>Esc</kbd> close</span>
                    <span id="prmsQuickNavCount">{{ count($quickNavItems) }} destinations available</span>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="prmsQuickNavData">@json($quickNavItems)</script>
    <script type="application/json" id="prmsQuickNavSearchUrls">@json($quickNavSearchUrls)</script>
    <script>
        (function () {
            var kbd = document.querySelector('.prms-quick-nav-kbd');
            if (!kbd) return;
            var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            kbd.textContent = isMac ? '⌘ K' : 'Ctrl K';
        })();
    </script>
@endif
