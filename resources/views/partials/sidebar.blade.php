@php
    $dashboardRoute = $dashboardRoute ?? null;
    $menuItems = collect($menuItems ?? []);
    $resources = collect($resources ?? [])->filter();
    $useMenu = $menuItems->isNotEmpty();
    $user = ave_auth_user();
    $currentUrl = url()->current();
    $currentSlug = request()->route('slug');

    $renderMenu = function ($items, $depth = 0) use (&$renderMenu, $currentUrl, $currentSlug) {
        $html = '';

        foreach ($items as $index => $item) {
            if (($item['type'] ?? 'item') === 'divider') {
                $html .= '<li class="ave-sidebar__divider"></li>';
                continue;
            }

            $children = $item['children'] ?? [];
            $isActive = false;

            if (!empty($item['resource_slug']) && $item['resource_slug'] === $currentSlug) {
                $isActive = true;
            }

            if (!$isActive && isset($item['url'])) {
                $itemUrl = rtrim($item['url'], '/');
                $currUrl = rtrim($currentUrl, '/');
                if ($itemUrl !== '' && str_starts_with($currUrl, $itemUrl)) {
                    $isActive = true;
                }
            }

            foreach ($children as $child) {
                if (!empty($child['resource_slug']) && $child['resource_slug'] === $currentSlug) {
                    $isActive = true;
                    break;
                }
            }

            $hasChildren = !empty($children);
            $submenuId = 'ave-submenu-' . $depth . '-' . $index . '-' . substr(md5(($item['title'] ?? 'item') . $index), 0, 6);

            if ($hasChildren) {
                $html .= '<li class="ave-sidebar__item ave-sidebar__item--parent ' . ($isActive ? 'ave-sidebar__item--expanded' : '') . '" data-ave-menu="item">';
                $html .= '<button type="button" class="ave-sidebar__link ave-sidebar__toggle" data-ave-submenu="' . $submenuId . '" aria-controls="' . $submenuId . '" aria-expanded="' . ($isActive ? 'true' : 'false') . '">';
                $html .= '<span class="ave-sidebar__icon ' . e($item['icon'] ?? 'voyager-data') . '" aria-hidden="true"></span>';
                $html .= '<span class="ave-sidebar__label">' . e($item['title']) . '</span>';
                $html .= '<span class="ave-sidebar__caret voyager-angle-down" aria-hidden="true"></span>';
                $html .= '</button>';

                $submenuClass = 'ave-sidebar__submenu' . ($isActive ? ' ave-sidebar__submenu--open' : '');
                $html .= '<div class="' . $submenuClass . '" id="' . $submenuId . '" data-ave-menu="submenu">';
                $html .= '<ul class="ave-sidebar__submenu-list ave-sidebar__submenu-list--level-' . ($depth + 2) . '">';
                $html .= $renderMenu($children, $depth + 1);
                $html .= '</ul>';
                $html .= '</div>';
                $html .= '</li>';

                continue;
            }

            if ($depth === 0) {
                $html .= '<li class="ave-sidebar__item ' . ($isActive ? 'ave-sidebar__item--active' : '') . '">';
                $html .= '<a href="' . e($item['url'] ?? '#') . '" target="' . e($item['target'] ?? '_self') . '" class="ave-sidebar__link">';
                $html .= '<span class="ave-sidebar__icon ' . e($item['icon'] ?? 'voyager-dot') . '" aria-hidden="true"></span>';
                $html .= '<span class="ave-sidebar__label">' . e($item['title']) . '</span>';
                $html .= '</a>';
                $html .= '</li>';
                continue;
            }

            $html .= '<li class="ave-sidebar__submenu-item ' . ($isActive ? 'ave-sidebar__item--active' : '') . '">';
            $html .= '<a href="' . e($item['url'] ?? '#') . '" target="' . e($item['target'] ?? '_self') . '" class="ave-sidebar__submenu-link">';
            $html .= '<span class="ave-sidebar__icon ' . e($item['icon'] ?? 'voyager-dot') . '" aria-hidden="true"></span>';
            $html .= '<span class="ave-sidebar__label">' . e($item['title']) . '</span>';
            $html .= '</a>';
            $html .= '</li>';
        }

        return $html;
    };
@endphp

<div class="side-menu sidebar-inverse ave-sidebar" data-ave-sidebar>
    <nav class="ave-sidebar__nav" role="navigation" aria-label="Primary navigation">
        <div class="ave-sidebar__brand">
            <a class="ave-sidebar__brand-link" href="{{ $dashboardRoute ?? url('/') }}">
                <span class="ave-sidebar__brand-symbol" aria-hidden="true">
                    <img src="{{ asset('vendor/ave/assets/images/logo-icon-light.png') }}" alt="{{ config('app.name', 'Ave') }}">
                </span>
                <span class="ave-sidebar__brand-title">{{ config('app.name', 'Ave') }}</span>
            </a>
        </div>

        @if($user)
            <div class="ave-sidebar__user">
                <img src="{{ $user_avatar }}" alt="{{ $user->name }}" class="ave-sidebar__user-avatar">
                <span class="ave-sidebar__user-name">{{ $user->name }}</span>
            </div>
        @endif

        <div id="adminmenu" class="ave-sidebar__menu" data-ave-menu="container">
                <ul class="ave-sidebar__list" data-ave-menu-root>
                @if($dashboardRoute)
                    <li class="ave-sidebar__item {{ request()->routeIs('ave.dashboard') ? 'ave-sidebar__item--active' : '' }}">
                        <a href="{{ $dashboardRoute }}" class="ave-sidebar__link">
                            <span class="ave-sidebar__icon voyager-boat" aria-hidden="true"></span>
                            <span class="ave-sidebar__label">Dashboard</span>
                        </a>
                    </li>
                @endif

                @if($useMenu)
                    {!! $renderMenu($menuItems->toArray()) !!}
                @else
                    @foreach($resources as $entry)
                        <li class="ave-sidebar__item {{ request()->routeIs('ave.resource.*') && request()->route('slug') === $entry['slug'] ? 'ave-sidebar__item--active' : '' }}">
                            <a href="{{ route('ave.resource.index', ['slug' => $entry['slug']]) }}" class="ave-sidebar__link">
                                <span class="ave-sidebar__icon {{ $entry['icon'] }}" aria-hidden="true"></span>
                                <span class="ave-sidebar__label">{{ $entry['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
    </nav>
</div>
