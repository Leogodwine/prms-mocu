@php
    $user = auth()->user();
    $sidebarGroup = $user->projectGroups()->first();
    $sidebarStages = \App\Models\ProjectStage::orderBy('stage_order')->get();
    $sidebarLatest = \App\Support\StudentStageProgress::latestSubmissionByStage($user, $sidebarGroup);
    $sidebarTracks = \App\Support\StudentResearchEligibility::availableTracks($user);
    $sidebarProposal = \App\Support\StudentStageProgress::stagesForNavTrack($sidebarStages, 'proposal');
    $sidebarProject = \App\Support\StudentStageProgress::stagesForNavTrack($sidebarStages, 'project');
    $sidebarResearch = \App\Support\StudentStageProgress::stagesForNavTrack($sidebarStages, 'research');
    $onWorkspace = request()->routeIs('student.index');
    $workspaceType = request()->query('type', 'overview');
    $workspaceStageId = (int) request()->query('stage_id');
    $subpanelOpen = $onWorkspace;
@endphp

<div class="prms-sidebar-sub__backdrop {{ $subpanelOpen ? 'is-visible' : '' }}"
     id="prmsWorkspaceSubpanelBackdrop"
     aria-hidden="{{ $subpanelOpen ? 'false' : 'true' }}"></div>

<aside class="prms-sidebar-sub prms-sidebar-sub--workspace sidebar sidebar-style-2 {{ $subpanelOpen ? 'is-open' : '' }}"
       id="prmsWorkspaceSubpanel"
       data-background-color="dark"
       aria-label="{{ __('Project and research navigation') }}"
       aria-hidden="{{ $subpanelOpen ? 'false' : 'true' }}">
    <div class="prms-sidebar-sub__toolbar">
        <button type="button" class="prms-sidebar-sub__close" id="prmsWorkspaceSubpanelClose" aria-label="{{ __('Close panel') }}">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <div class="prms-sidebar-sub__heading">
            <span class="material-symbols-outlined prms-sidebar-sub__heading-icon" aria-hidden="true">menu_book</span>
            <div>
                <p class="prms-sidebar-sub__title">{{ __('Project & research') }}</p>
                <p class="prms-sidebar-sub__subtitle">{{ __('Chapters & progress') }}</p>
            </div>
        </div>
    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner prms-sidebar-sub__scroll">
        <div class="sidebar-content">
            <ul class="nav nav-primary prms-workspace-nav">
                <li class="prms-workspace-overview {{ $onWorkspace && $workspaceType === 'overview' ? 'active' : '' }}">
                    <a href="{{ route('student.index') }}">
                        <span class="prms-ws-overview-icon material-symbols-outlined" aria-hidden="true">dashboard</span>
                        <span class="prms-ws-label">{{ __('All progress') }}</span>
                    </a>
                </li>

                @include('layouts.partials.prms-sidebar-workspace-track', [
                    'trackLabel' => __('Research proposal'),
                    'trackIcon' => 'far fa-file-alt',
                    'trackType' => 'proposal',
                    'stages' => $sidebarProposal,
                    'latestByStage' => $sidebarLatest,
                    'onWorkspace' => $onWorkspace,
                    'workspaceType' => $workspaceType,
                    'workspaceStageId' => $workspaceStageId,
                ])

                @if (in_array('research', $sidebarTracks, true))
                    @include('layouts.partials.prms-sidebar-workspace-track', [
                        'trackLabel' => __('Research report'),
                        'trackIcon' => 'fas fa-book-open',
                        'trackType' => 'research',
                        'stages' => $sidebarResearch,
                        'latestByStage' => $sidebarLatest,
                        'onWorkspace' => $onWorkspace,
                        'workspaceType' => $workspaceType,
                        'workspaceStageId' => $workspaceStageId,
                    ])
                @endif

                @if (in_array('project', $sidebarTracks, true))
                    @include('layouts.partials.prms-sidebar-workspace-track', [
                        'trackLabel' => __('Project workspace'),
                        'trackIcon' => 'fas fa-laptop-code',
                        'trackType' => 'project',
                        'stages' => $sidebarProject,
                        'latestByStage' => $sidebarLatest,
                        'onWorkspace' => $onWorkspace,
                        'workspaceType' => $workspaceType,
                        'workspaceStageId' => $workspaceStageId,
                    ])
                @endif
            </ul>
        </div>
    </div>
</aside>
