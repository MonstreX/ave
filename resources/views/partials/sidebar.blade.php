@php
    $dashboardRoute = $dashboardRoute ?? null;
    $resources = $resources instanceof \Illuminate\Support\Collection
        ? $resources
        : collect($resources ?? []);
    $resources = $resources->filter();
    $user = auth()->user();
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
            <ul class="ave-sidebar__list">
                @if($dashboardRoute)
                    <li class="ave-sidebar__item {{ request()->routeIs('ave.dashboard') ? 'ave-sidebar__item--active' : '' }}">
                        <a href="{{ $dashboardRoute }}" class="ave-sidebar__link">
                            <span class="ave-sidebar__icon voyager-boat" aria-hidden="true"></span>
                            <span class="ave-sidebar__label">Dashboard</span>
                        </a>
                    </li>
                @endif

                @foreach($resources as $entry)
                    <li class="ave-sidebar__item {{ request()->routeIs('ave.resource.*') && request()->route('slug') === $entry['slug'] ? 'ave-sidebar__item--active' : '' }}">
                        <a href="{{ route('ave.resource.index', ['slug' => $entry['slug']]) }}" class="ave-sidebar__link">
                            <span class="ave-sidebar__icon {{ $entry['icon'] }}" aria-hidden="true"></span>
                            <span class="ave-sidebar__label">{{ $entry['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
</div>





