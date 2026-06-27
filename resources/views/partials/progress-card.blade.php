{{--
    Reusable progress card.

    Required:
        $label    – string, eg. "Proposal"
        $percent  – int 0..100 (typically includes approved + in-progress stages)
        $approved – int — stages whose latest submission is approved
        $total    – int — stage count for this track
        $icon     – Font Awesome class string (eg. "far fa-file-alt")
        $tone     – one of: primary, info, success, warning, danger
        $href     – URL for the "View details" CTA
    Optional:
        $inProgress – int — stages with work in supervisor review / revision (latest submission not approved)
--}}
@php
    $tone = $tone ?? 'primary';
    $inProgress = (int) ($inProgress ?? 0);
    $total = (int) ($total ?? 0);
    $iconBgMap = [
        'primary' => 'bg-primary-soft text-primary',
        'info'    => 'bg-info-soft',
        'success' => 'bg-success-soft',
        'warning' => 'bg-warning-soft',
        'danger'  => 'bg-danger-soft',
    ];
    $progressBarToneClass = $tone === 'primary' ? '' : 'bg-' . $tone;
    $iconColorStyle = match ($tone) {
        'info'    => 'color: var(--prms-color-info-500);',
        'success' => 'color: var(--prms-color-success-500);',
        'warning' => 'color: var(--prms-color-warning-500);',
        'danger'  => 'color: var(--prms-color-danger-500);',
        default   => '',
    };
@endphp

<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <span class="prms-eyebrow">{{ $label }}</span>
                <div class="fs-2 fw-bold text-strong" style="line-height: 1;">{{ $percent }}%</div>
            </div>
            <div class="rounded-3 d-inline-flex align-items-center justify-content-center {{ $iconBgMap[$tone] ?? $iconBgMap['primary'] }}"
                 style="width: 44px; height: 44px; {{ $iconColorStyle }}">
                <i class="{{ $icon }}" aria-hidden="true"></i>
            </div>
        </div>

        <div class="progress mb-2" role="progressbar"
             aria-label="{{ $label }} progress"
             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar {{ $progressBarToneClass }}" style="width: {{ $percent }}%;"></div>
        </div>
        @if ($total < 1)
            <small class="text-muted">No stages configured for this track yet.</small>
        @else
            <small class="text-muted d-block">
                {{ $approved }} of {{ $total }} {{ $total === 1 ? 'stage' : 'stages' }} approved
                @if ($inProgress > 0)
                    <span class="d-block mt-1">{{ $inProgress }} {{ $inProgress === 1 ? 'stage' : 'stages' }} in review or revision</span>
                @endif
            </small>
        @endif
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <a href="{{ $href }}" class="btn btn-link btn-sm p-0">
            View details <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
        </a>
    </div>
</div>
