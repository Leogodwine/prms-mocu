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
        <li class="nav-item {{ $hasChildren ? 'submenu' : '' }} {{ $parentActive ? 'active' : '' }} {{ $parentActive ? 'is-submenu-open' : '' }}">
            @if ($hasChildren)
                <a href="#{{ $collapseId }}"
                   class="prms-sidebar-subtoggle"
                   data-prms-submenu-toggle
                   aria-controls="{{ $collapseId }}"
                   aria-expanded="{{ $parentActive ? 'true' : 'false' }}">
                    <i class="{{ $item['icon'] }}"></i>
                    <p>{{ $item['label'] }}</p>
                    <span class="prms-sidebar-caret material-symbols-outlined" aria-hidden="true">expand_more</span>
                </a>
                <div class="collapse {{ $parentActive ? 'show' : '' }}" id="{{ $collapseId }}">
                    <ul class="nav nav-collapse prms-workspace-chapters">
                        <li class="{{ \App\Support\PrmsNavigationIndex::isWorkspaceOverviewActive($item) ? 'active' : '' }}">
                            <a href="{{ $item['url'] }}">
                                <span class="sub-item">
                                    <span class="material-symbols-outlined prms-ws-sub-icon" aria-hidden="true">{{ $item['overview_material_icon'] ?? 'dashboard' }}</span>
                                    <span class="prms-ws-chapter-label">{{ $item['overview_label'] ?? __('Track overview') }}</span>
                                </span>
                            </a>
                        </li>
                        @foreach ($children as $child)
                            @php $childActive = \App\Support\PrmsNavigationIndex::isActive($child); @endphp
                            <li class="{{ $childActive ? 'active' : '' }}">
                                <a href="{{ $child['url'] }}" title="{{ $child['status']['title'] ?? '' }}">
                                    <span class="sub-item">
                                        @if (! empty($child['material_icon']))
                                            <span class="material-symbols-outlined prms-ws-sub-icon" aria-hidden="true">{{ $child['material_icon'] }}</span>
                                        @elseif (! empty($child['step']))
                                            <span class="prms-ws-chapter-step" aria-hidden="true">{{ $child['step'] }}</span>
                                        @endif
                                        <span class="prms-ws-chapter-label">{{ $child['label'] }}</span>
                                    </span>
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
                @php
                    $unreadCount = ($item['route_is'] === 'notifications.*')
                        ? auth()->user()->unreadNotifications()->count()
                        : 0;
                @endphp
                <a href="{{ $item['url'] }}" @class(['prms-nav-link--has-badge' => $unreadCount > 0])>
                    <i class="{{ $item['icon'] }}"></i>
                    @if ($unreadCount > 0)
                        <span class="prms-nav-icon-badge" aria-hidden="true">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    @endif
                    <p class="prms-nav-link-text">
                        <span class="prms-nav-link-text__label">{{ $item['label'] }}</span>
                        @if ($unreadCount > 0)
                            <span class="badge rounded-pill bg-danger prms-nav-link-text__badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </p>
                </a>
            @endif
        </li>
    @endforeach
@endif
