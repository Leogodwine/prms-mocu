@if (auth()->check())
    <li class="nav-item topbar-icon d-flex align-items-center me-1 me-md-2">
        <button type="button"
                class="btn btn-light border rounded-pill px-2 px-md-3 py-1 d-inline-flex align-items-center gap-2 prms-quick-nav-trigger"
                id="prmsQuickNavOpen"
                aria-label="Open quick find"
                aria-keyshortcuts="Control+K Meta+K"
                aria-controls="prmsQuickNavModal"
                style="font-size: 0.82rem;">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span class="d-none d-md-inline">Quick find</span>
            <kbd class="d-none d-xl-inline-flex align-items-center gap-1 px-2 py-0 rounded border bg-white text-muted prms-quick-nav-kbd"
                 style="font-size: 0.68rem;"></kbd>
        </button>
    </li>
@endif
