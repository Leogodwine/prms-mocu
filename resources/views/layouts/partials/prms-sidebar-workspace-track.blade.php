@php
    $summary = \App\Support\StudentStageProgress::summarizeTrack($stages, $latestByStage);
    $done = $summary['approved'];
    $total = $summary['total'];
@endphp

<li class="prms-workspace-track-head" aria-hidden="true">
    <span class="prms-workspace-track-icon material-symbols-outlined" aria-hidden="true">{{ \App\Support\StudentStageProgress::trackMaterialIcon($trackType) }}</span>
    <span class="prms-workspace-track-title">{{ $trackLabel }}</span>
    @if ($total > 0)
        <span class="prms-workspace-track-count" title="{{ __(':done of :total approved', ['done' => $done, 'total' => $total]) }}">
            {{ $done }}/{{ $total }}
        </span>
    @endif
</li>

@foreach ($stages as $index => $stage)
    @php
        $navMeta = \App\Support\StudentStageProgress::navStatusMeta($latestByStage->get($stage->stage_name));
        $isActive = $onWorkspace && $workspaceType === $trackType && $workspaceStageId === $stage->id;
        $materialIcon = \App\Support\StudentStageProgress::stageMaterialIcon($stage->stage_name, $trackType, $index);
    @endphp
    <li class="prms-workspace-chapter {{ $isActive ? 'active' : '' }}">
        <a href="{{ route('student.index', ['type' => $trackType, 'stage_id' => $stage->id]) }}"
           class="prms-workspace-chapter-link"
           title="{{ $navMeta['title'] }}">
            <span class="prms-ws-step material-symbols-outlined" aria-hidden="true">{{ $materialIcon }}</span>
            <span class="prms-ws-label">{{ \App\Support\StudentStageProgress::shortStageLabel($stage->stage_name) }}</span>
        </a>
    </li>
@endforeach
