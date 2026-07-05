@php
    $contributorsPanel = \App\Support\PrmsSidebarContributors::panelForUser(auth()->user());
@endphp

@if ($contributorsPanel !== null)
    <div class="prms-sidebar-contributors" aria-label="{{ __('Group contributors') }}">
        <div class="prms-sidebar-contributors__header">
            <span class="material-symbols-outlined prms-sidebar-contributors__icon" aria-hidden="true">groups</span>
            <div class="min-w-0">
                <p class="prms-sidebar-contributors__title mb-0">{{ __('Contributors') }}</p>
                <p class="prms-sidebar-contributors__group mb-0 text-truncate" title="{{ $contributorsPanel['group_name'] }}">
                    {{ $contributorsPanel['group_name'] }}
                </p>
            </div>
        </div>
        <ul class="prms-sidebar-contributors__list list-unstyled mb-0">
            @foreach ($contributorsPanel['members'] as $member)
                @php $isSelf = (int) auth()->id() === (int) $member->id; @endphp
                <li class="prms-sidebar-contributors__member @if ($isSelf) is-self @endif">
                    <span class="prms-sidebar-contributors__avatar" aria-hidden="true">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->name, 0, 1)) }}
                    </span>
                    <span class="prms-sidebar-contributors__meta min-w-0">
                        <span class="prms-sidebar-contributors__name text-truncate">
                            {{ $member->name }}
                            @if ($isSelf)
                                <span class="prms-sidebar-contributors__you">({{ __('You') }})</span>
                            @endif
                        </span>
                        <span class="prms-sidebar-contributors__id text-truncate">{{ $member->displayIdentifier() }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
