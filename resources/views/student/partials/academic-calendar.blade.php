@php
    $calendar = $academicCalendar ?? [
        'submission_deadlines' => collect(),
        'presentation_dates' => collect(),
        'evaluation_schedule' => collect(),
        'announcements' => collect(),
    ];

    $tabs = [
        'submissions' => [
            'label' => 'Submission Deadlines',
            'icon' => 'far fa-clock',
            'items' => $calendar['submission_deadlines'] ?? collect(),
            'empty' => 'No submission deadlines have been published yet. Your coordinator will post chapter and document due dates here.',
        ],
        'evaluations' => [
            'label' => 'Evaluation Schedule',
            'icon' => 'fas fa-clipboard-check',
            'items' => $calendar['evaluation_schedule'] ?? collect(),
            'empty' => 'No presentation evaluations are scheduled yet. They appear here after your supervisor records scores.',
        ],
        'presentations' => [
            'label' => 'Presentation Dates',
            'icon' => 'fas fa-chalkboard-teacher',
            'items' => $calendar['presentation_dates'] ?? collect(),
            'empty' => 'No presentation dates have been set yet. Check back for progress and final presentation windows.',
        ],
        'announcements' => [
            'label' => 'Important Announcements',
            'icon' => 'fas fa-bullhorn',
            'items' => $calendar['announcements'] ?? collect(),
            'empty' => 'No announcements at the moment.',
        ],
    ];

    $inGreeting = $embeddedInGreeting ?? false;
@endphp

<div @class([
    'card border-0 shadow-sm',
    'prms-greeting-calendar-card mb-0' => $inGreeting,
])>
    <div class="card-header bg-white border-bottom py-3">
        <h3 class="h6 fw-bold mb-0 d-flex align-items-center">
            <span aria-hidden="true" class="me-2">📅</span>
            Academic Calendar
        </h3>
    </div>
    <div class="card-body p-0">
        <ul class="nav nav-tabs nav-fill px-3 pt-3 flex-nowrap overflow-auto" role="tablist" aria-label="Academic calendar sections">
            @foreach ($tabs as $tabKey => $tab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-nowrap {{ $loop->first ? 'active' : '' }}"
                            id="prms-calendar-tab-{{ $tabKey }}"
                            data-bs-toggle="tab"
                            data-bs-target="#prms-calendar-pane-{{ $tabKey }}"
                            type="button"
                            role="tab"
                            aria-controls="prms-calendar-pane-{{ $tabKey }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <i class="{{ $tab['icon'] }} me-1" aria-hidden="true"></i>
                        <span class="d-none d-xl-inline">{{ $tab['label'] }}</span>
                        <span class="d-inline d-xl-none">{{ \Illuminate\Support\Str::before($tab['label'], ' ') }}</span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content p-3">
            @foreach ($tabs as $tabKey => $tab)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                     id="prms-calendar-pane-{{ $tabKey }}"
                     role="tabpanel"
                     aria-labelledby="prms-calendar-tab-{{ $tabKey }}">
                    @if ($tab['items']->isEmpty())
                        <p class="text-muted small mb-0">{{ $tab['empty'] }}</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($tab['items'] as $item)
                                <li class="py-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    @if ($tabKey === 'announcements')
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="fw-semibold text-strong">{{ $item['title'] }}</div>
                                                @if (! empty($item['body']))
                                                    <p class="small text-muted mb-0 mt-1">{{ $item['body'] }}</p>
                                                @endif
                                            </div>
                                            @if (! empty($item['date']))
                                                <span class="badge bg-light text-muted border flex-shrink-0">{{ $item['date'] }}</span>
                                            @endif
                                        </div>
                                    @elseif ($tabKey === 'evaluations')
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="fw-semibold text-strong">{{ $item['label'] }}</div>
                                                <div class="small text-muted">{{ $item['evaluator'] }}</div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge rounded-pill {{ ($item['status'] ?? '') === 'finalized' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                    {{ $item['status_label'] }}
                                                </span>
                                                @if (! empty($item['score']))
                                                    <div class="small text-muted mt-1">{{ $item['score'] }}/100</div>
                                                @endif
                                            </div>
                                        </div>
                                        @if (! empty($item['when']))
                                            <div class="small text-muted mt-1">
                                                <i class="far fa-calendar me-1" aria-hidden="true"></i>{{ $item['when'] }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="fw-semibold text-strong">{{ $item['label'] }}</div>
                                                <div class="small text-muted">{{ $item['scope'] }}</div>
                                            </div>
                                            @php
                                                $tone = match ($item['status'] ?? 'active') {
                                                    'closed' => 'bg-secondary',
                                                    'upcoming' => 'bg-info',
                                                    default => 'bg-success',
                                                };
                                            @endphp
                                            <span class="badge rounded-pill {{ $tone }}">{{ $item['status_label'] }}</span>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="far fa-calendar-alt me-1" aria-hidden="true"></i>{{ $item['window'] }}
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
