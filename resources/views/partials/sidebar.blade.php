<div class="side-menu sidebar-inverse ave-sidebar" data-ave-sidebar>
    <nav class="ave-sidebar__nav" role="navigation" aria-label="Primary navigation">
        <div class="ave-sidebar__brand">
            <a class="ave-sidebar__brand-link" href="{{ route('ave.dashboard') }}">
                <span class="ave-sidebar__brand-symbol" aria-hidden="true">
                    <img src="{{ asset('vendor/ave/assets/images/logo-icon-light.png') }}" alt="{{ config('app.name', 'Ave') }}">
                </span>
                <span class="ave-sidebar__brand-title">{{ config('app.name', 'Ave') }}</span>
            </a>
        </div>

        <div class="ave-sidebar__user">
            <img src="{{ $user_avatar }}" alt="{{ Auth::user()->name }}" class="ave-sidebar__user-avatar">
            <span class="ave-sidebar__user-name">{{ Auth::user()->name }}</span>
        </div>

        @php
            $resourceRegistry = app()->bound(\Monstrex\Ave\Resources\ResourceRegistry::class)
                ? app(\Monstrex\Ave\Resources\ResourceRegistry::class)
                : null;

            $resourceEntries = [];
            if ($resourceRegistry) {
                foreach ($resourceRegistry->visibleInNavigation() as $resourceClass) {
                    /** @var class-string<\Monstrex\Ave\Resources\Resource> $resourceClass */
                    if (! $resourceClass::canView()) {
                        continue;
                    }

                    $resourceEntries[] = [
                        'class' => $resourceClass,
                        'label' => $resourceClass::getLabel(),
                        'icon' => $resourceClass::getIcon() ?: 'voyager-data',
                        'group' => $resourceClass::getNavigationGroup() ?? 'Resources',
                        'sort' => $resourceClass::getNavigationSort() ?? 0,
                        'routePrefix' => $resourceClass::getRouteNamePrefix(),
                    ];
                }
            }

            $groupedResources = collect($resourceEntries)
                ->sortBy([
                    ['group', 'asc'],
                    ['sort', 'asc'],
                    ['label', 'asc'],
                ])
                ->groupBy('group');
        @endphp

        <div id="adminmenu" class="ave-sidebar__menu" data-ave-menu="container">
            <ul class="ave-sidebar__list">
                <li class="ave-sidebar__item {{ request()->routeIs('ave.dashboard') ? 'ave-sidebar__item--active' : '' }}">
                    <a href="{{ route('ave.dashboard') }}" class="ave-sidebar__link">
                        <span class="ave-sidebar__icon voyager-boat" aria-hidden="true"></span>
                        <span class="ave-sidebar__label">Dashboard</span>
                    </a>
                </li>

                @foreach($groupedResources as $groupName => $resources)
                    @php
                        $menuId = 'ave-menu-' . \Illuminate\Support\Str::slug($groupName);
                        $isExpanded = $resources->contains(fn($entry) => request()->routeIs($entry['routePrefix'] . '.*'));
                    @endphp
                    <li class="ave-sidebar__item ave-sidebar__item--expanded" data-ave-menu="item">
                        <button
                            type="button"
                            class="ave-sidebar__link ave-sidebar__toggle"
                            data-ave-submenu="{{ $menuId }}"
                            aria-controls="{{ $menuId }}"
                            aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                        >
                            <span class="ave-sidebar__icon voyager-data" aria-hidden="true"></span>
                            <span class="ave-sidebar__label">{{ $groupName }}</span>
                            <span class="ave-sidebar__caret voyager-angle-down" aria-hidden="true"></span>
                        </button>

                        <div class="ave-sidebar__submenu {{ $isExpanded ? 'ave-sidebar__submenu--open' : '' }}" id="{{ $menuId }}" data-ave-menu="submenu">
                            <ul class="ave-sidebar__submenu-list ave-sidebar__submenu-list--level-2">
                                @foreach($resources as $entry)
                                    @php
                                        $resourceActive = request()->routeIs($entry['routePrefix'] . '.*');
                                    @endphp
                                    <li class="ave-sidebar__submenu-item @if($resourceActive) ave-sidebar__item--active @endif">
                                        <a href="{{ route($entry['routePrefix'] . '.index') }}" class="ave-sidebar__submenu-link">
                                            <span class="ave-sidebar__icon {{ $entry['icon'] }}" aria-hidden="true"></span>
                                            <span class="ave-sidebar__label">{{ $entry['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
</div>

