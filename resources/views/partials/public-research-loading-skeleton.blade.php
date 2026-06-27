<div id="prms-research-loading" class="prms-research-loading d-none" aria-live="polite" aria-busy="true" aria-label="Loading search results">
    <p class="visually-hidden">Loading publications…</p>
    @for ($i = 0; $i < 3; $i++)
        <div class="prms-skeleton-card" aria-hidden="true">
            <div class="prms-skeleton-line prms-skeleton-line--title"></div>
            <div class="prms-skeleton-line prms-skeleton-line--meta"></div>
            <div class="prms-skeleton-line"></div>
            <div class="prms-skeleton-line prms-skeleton-line--short"></div>
        </div>
    @endfor
</div>
