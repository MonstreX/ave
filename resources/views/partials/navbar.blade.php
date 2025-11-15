<nav class="navbar navbar-default navbar-fixed-top ave-navbar">
    <div class="ave-navbar__content">
        <div class="ave-navbar__left">
            <button class="hamburger btn-link no-animation" data-ave-nav="toggle">
                <span class="hamburger-inner"></span>
            </button>
            @php
                $dashboardRoute = \Illuminate\Support\Facades\Route::has('ave.dashboard') ? route('ave.dashboard') : null;
                $segments = [];
                if ($dashboardRoute) {
                    $currentPath = trim(parse_url(request()->url(), PHP_URL_PATH), '/');
                    $dashboardPath = trim(parse_url($dashboardRoute, PHP_URL_PATH), '/');
                    if ($dashboardPath !== '' && str_starts_with($currentPath, $dashboardPath)) {
                        $relative = trim(substr($currentPath, strlen($dashboardPath)), '/');
                    } else {
                        $relative = $currentPath;
                    }
                    $segments = $relative === '' ? [] : explode('/', $relative);
                    $segments = array_values(array_filter($segments, fn ($segment) => $segment !== ''));
                    if (!empty($segments)) {
                        $last = end($segments);
                        if (in_array($last, ['edit', 'create'], true)) {
                            array_pop($segments);
                        }
                    }
                    $visibleSegments = array_values(array_filter($segments, fn ($segment) => $segment !== 'resource'));
                }
            @endphp
            @section('breadcrumbs')
            <ol class="ave-navbar__breadcrumb hidden-xs">
                @if($dashboardRoute)
                    <li class="ave-navbar__breadcrumb-item">
                        <a href="{{ $dashboardRoute }}" class="ave-navbar__breadcrumb-link"><i class="voyager-boat"></i> {{ __('ave::dashboard.title') }}</a>
                    </li>
                    @php
                        $url = $dashboardRoute;
                        $visibleCount = isset($visibleSegments) ? count($visibleSegments) : 0;
                        $visibleIndex = 0;
                    @endphp
                    @foreach ($segments as $segment)
                        @php $url .= '/' . $segment; @endphp
                        @if ($segment === 'resource')
                            @continue
                        @endif
                        @php
                            $label = ucfirst(urldecode($segment));
                            $isLastVisible = ($visibleIndex === $visibleCount - 1);
                            $visibleIndex++;
                        @endphp
                        @if ($isLastVisible)
                            <li class="ave-navbar__breadcrumb-item">{{ $label }}</li>
                        @else
                            <li class="ave-navbar__breadcrumb-item">
                                <a href="{{ $url }}" class="ave-navbar__breadcrumb-link">{{ $label }}</a>
                            </li>
                        @endif
                    @endforeach
                @else
                    <li class="ave-navbar__breadcrumb-item">
                        <a href="{{ url('/') }}" class="ave-navbar__breadcrumb-link"><i class="voyager-boat"></i> {{ config('app.name') }}</a>
                    </li>
                @endif
            </ol>
            @show
        </div>
        <div class="ave-navbar__right">
            @php $user = ave_auth_user(); @endphp
            @if($user)
                <ul class="ave-navbar__nav">
                    <li class="dropdown profile">
                        <a href="#" class="dropdown-toggle text-right" data-toggle="dropdown" role="button"
                           aria-expanded="false">
                            <img src="{{ $user_avatar }}" class="profile-img" alt="{{ $user->name }}">
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-animated">
                            <li class="dropdown-profile">
                                <div class="dropdown-profile__avatar">
                                    <img src="{{ $user_avatar }}" alt="{{ $user->name }}">
                                </div>
                                <div class="dropdown-profile__details">
                                    <span class="dropdown-profile__name">{{ $user->name }}</span>
                                    <span class="dropdown-profile__email">{{ $user->email }}</span>
                                </div>
                            </li>
                            <li class="dropdown-divider" role="separator"></li>
                            @php
                                $profileUrl = null;
                                $resourceManager = app(\Monstrex\Ave\Core\ResourceManager::class);
                                $userResourceClass = $resourceManager->resource('users');
                                if ($userResourceClass && \Illuminate\Support\Facades\Route::has('ave.resource.edit')) {
                                    $resourceInstance = app($userResourceClass);
                                    if ($resourceInstance->can('update', $user, $user)) {
                                        $profileUrl = route('ave.resource.edit', [
                                            'slug' => $userResourceClass::getSlug(),
                                            'id' => $user->getKey(),
                                        ]);
                                    }
                                }
                            @endphp
                            @if($profileUrl)
                                <li>
                                    <a href="{{ $profileUrl }}" class="dropdown-item">
                                        <i class="voyager-person"></i>
                                        <span>{{ __('Profile') }}</span>
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a href="/" target="_blank" rel="noopener" class="dropdown-item">
                                    <i class="voyager-home"></i>
                                    <span>{{ __('Home') }}</span>
                                </a>
                            </li>
                            <li class="dropdown-divider" role="separator"></li>
                            <li class="dropdown-submenu">
                                <span class="dropdown-item">
                                    <i class="voyager-world"></i>
                                    <span>{{ app()->getLocale() === 'en' ? 'English' : 'Русский' }}</span>
                                </span>
                                <ul class="dropdown-menu dropdown-menu-locale">
                                    @php
                                        $langPath = base_path('vendor/monstrex/ave/lang');
                                        if (!file_exists($langPath)) {
                                            $langPath = __DIR__ . '/../../../lang';
                                        }
                                        $availableLocales = array_map('basename', \Illuminate\Support\Facades\File::directories($langPath));
                                        $localeNames = [
                                            'en' => 'English',
                                            'ru' => 'Русский',
                                        ];
                                        $currentLocale = app()->getLocale();
                                    @endphp
                                    @foreach($availableLocales as $locale)
                                        <li>
                                            <form action="{{ route('ave.locale.switch') }}" method="POST" class="locale-switch-form">
                                                @csrf
                                                <input type="hidden" name="locale" value="{{ $locale }}">
                                                <button type="submit" class="dropdown-item {{ $locale === $currentLocale ? 'active' : '' }}">
                                                    {{ $localeNames[$locale] ?? $locale }}
                                                    @if($locale === $currentLocale)
                                                        <i class="voyager-check" style="float: right;"></i>
                                                    @endif
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                            <li class="dropdown-divider" role="separator"></li>
                            @if(\Illuminate\Support\Facades\Route::has('ave.logout'))
                                <li class="dropdown-logout">
                                    <form action="{{ route('ave.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item dropdown-item--danger">
                                            <i class="voyager-power"></i>
                                            <span>{{ __('Logout') }}</span>
                                        </button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </li>
                </ul>
            @endif
        </div>
    </div>
</nav>
