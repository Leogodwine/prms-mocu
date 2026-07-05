@php
    $stageList = $stages->values();
    $currentIdx = null;
    foreach ($stageList as $i => $s) {
        $row = $latestByStage->get($s->stage_name);
        if (($row?->status) !== 'approved') {
            $currentIdx = $i;
            break;
        }
    }

    $approvedCount = 0;
    foreach ($stageList as $s) {
        if (($latestByStage->get($s->stage_name)?->status) === 'approved') {
            $approvedCount++;
        }
    }
    $totalStages = $stageList->count();
    $percentage = $totalStages > 0 ? (int) round(($approvedCount / $totalStages) * 100) : 0;
@endphp

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h6 fw-bold text-strong d-flex align-items-center mb-0">
                @if (!empty($trackIcon))
                    <i class="{{ $trackIcon }} text-primary me-2" aria-hidden="true"></i>
                @else
                    <i class="fas fa-route text-primary me-2" aria-hidden="true"></i>
                @endif
                {{ $trackLabel }} journey
            </h3>
            <span class="badge bg-primary">{{ $percentage }}% complete</span>
        </div>

        <div class="progress mb-4" role="progressbar"
             aria-label="{{ $trackLabel }} completion"
             aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $percentage }}%;"></div>
        </div>

        <ol class="prms-stepper" role="list" aria-label="{{ $trackLabel }} stage progress">
            @foreach ($stageList as $idx => $stage)
                @php
                    $latest       = $latestByStage->get($stage->stage_name);
                    $latestStatus = $latest?->status;
                    $isDraft      = $latestStatus === 'draft';
                    $isApproved   = $latestStatus === 'approved';
                    $isPending    = $latestStatus === 'pending';
                    $isReturned   = $latestStatus === 'needs_revision';
                    $isRejected   = $latestStatus === 'rejected';
                    $isCurrent    = $idx === $currentIdx;

                    if ($isApproved) {
                        $stateLabel = 'Completed';
                        $stepClass = 'is-done';
                        $marker = '<i class="fas fa-check" aria-hidden="true"></i>';
                    } elseif ($isDraft) {
                        $stateLabel = 'Draft — ready to submit';
                        $stepClass = 'is-draft';
                        $marker = (string) ($idx + 1);
                    } elseif ($isPending) {
                        $stateLabel = 'Awaiting review';
                        $stepClass = 'is-pending';
                        $marker = '<i class="far fa-clock" aria-hidden="true"></i>';
                    } elseif ($isReturned) {
                        $stateLabel = 'Returned for revision';
                        $stepClass = 'is-returned';
                        $marker = '<i class="fas fa-undo" aria-hidden="true"></i>';
                    } elseif ($isRejected) {
                        $stateLabel = 'Rejected';
                        $stepClass = 'is-rejected';
                        $marker = '<i class="fas fa-times" aria-hidden="true"></i>';
                    } elseif ($isCurrent) {
                        $stateLabel = 'In progress';
                        $stepClass = 'is-current';
                        $marker = (string) ($idx + 1);
                    } else {
                        $stateLabel = 'Not started';
                        $stepClass = 'is-todo';
                        $marker = (string) ($idx + 1);
                    }

                    $title = \App\Support\StudentStageProgress::shortStageLabel($stage->stage_name);
                    $stepUrl = ! empty($trackType)
                        ? route('student.index', ['type' => $trackType, 'stage_id' => $stage->id])
                        : null;
                @endphp

                <li class="prms-step {{ $stepClass }} {{ $loop->last ? 'is-last' : '' }}">
                    @if ($stepUrl)
                        <a href="{{ $stepUrl }}" class="text-decoration-none text-reset d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                            <div class="prms-step-marker" aria-hidden="true">{!! $marker !!}</div>
                            <div class="prms-step-text">
                                <span class="prms-step-title">{{ $title }}</span>
                                <span class="prms-step-status">{{ $stateLabel }}</span>
                            </div>
                        </a>
                    @else
                        <div class="prms-step-marker" aria-hidden="true">{!! $marker !!}</div>
                        <div class="prms-step-text">
                            <span class="prms-step-title">{{ $title }}</span>
                            <span class="prms-step-status">{{ $stateLabel }}</span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</div>
