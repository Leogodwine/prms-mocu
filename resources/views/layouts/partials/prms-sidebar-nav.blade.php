{{-- Role-based sidebar items (sourced from PrmsNavigationIndex) --}}
@if (auth()->check())
    @foreach (\App\Support\PrmsNavigationIndex::sidebarForUser(auth()->user()) as $item)
        @php
            $children = $item['children'] ?? [];
            $hasChildren = count($children) > 0;
            $collapseId = $item['collapse_id'] ?? ('nav-' . md5((string) ($item['label'] ?? '')));
            $parentActive = $hasChildren
                ? \App\Support\PrmsNavigationIndex::isWorkspaceParentActive($item)
                : \App\Support\PrmsNavigationIndex::isActive($item);
        @endphp
        <li class="nav-item {{ $hasChildren ? 'submenu' : '' }} {{ $parentActive ? 'active' : '' }}">
            @if ($hasChildren)
                <a data-bs-toggle="collapse"
                   href="#{{ $collapseId }}"
                   aria-expanded="{{ $parentActive ? 'true' : 'false' }}">
                    <i class="{{ $item['icon'] }}"></i>
                    <p>
                        {{ $item['label'] }}
                    </p>
                    <span class="caret"></span>
                </a>
                <div class="collapse {{ $parentActive ? 'show' : '' }}" id="{{ $collapseId }}">
                    <ul class="nav nav-collapse prms-workspace-chapters">
                        <li class="{{ \App\Support\PrmsNavigationIndex::isWorkspaceOverviewActive($item) ? 'active' : '' }}">
                            <a href="{{ $item['url'] }}">
                                <span class="sub-item">{{ __('All chapters') }}</span>
                            </a>
                        </li>
                        @foreach ($children as $child)
                            @php $childActive = \App\Support\PrmsNavigationIndex::isActive($child); @endphp
                            <li class="{{ $childActive ? 'active' : '' }}">
                                <a href="{{ $child['url'] }}" title="{{ $child['status']['title'] ?? '' }}">
                                    <span class="sub-item">{{ $child['label'] }}</span>
                                    @if (! empty($child['status']['icon']))
                                        <i class="{{ $child['status']['icon'] }} prms-ws-chapter-status text-{{ $child['status']['class'] ?? 'secondary' }}"
                                           aria-hidden="true"></i>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <a href="{{ $item['url'] }}">
                    <i class="{{ $item['icon'] }}"></i>
                    <p>
                        {{ $item['label'] }}
                        @if ($item['route_is'] === 'notifications.*')
                            @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                            @if ($unreadCount > 0)
                                <span class="badge badge-primary">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                            @endif
                        @endif
                    </p>
                </a>
            @endif
        </li>
    @endforeach
@endif
