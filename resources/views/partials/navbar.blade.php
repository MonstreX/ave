<nav class="navbar navbar-default navbar-fixed-top ave-navbar">
    <div class="ave-navbar__content">
        <div class="ave-navbar__left">
            <button class="hamburger btn-link no-animation" data-ave-nav="toggle">
                <span class="hamburger-inner"></span>
            </button>
            @php
                use Monstrex\Ave\Facades\Breadcrumb;
                $breadcrumbs = Breadcrumb::generate();
            @endphp
            @section('breadcrumbs')
            <ol class="ave-navbar__breadcrumb hidden-xs">
                @forelse($breadcrumbs as $breadcrumb)
                    <li class="ave-navbar__breadcrumb-item">
                        @if($breadcrumb['url'] && !($breadcrumb['active'] ?? false))
                            <a href="{{ $breadcrumb['url'] }}" class="ave-navbar__breadcrumb-link">
                                @if($breadcrumb['icon'] ?? null)
                                    <i class="{{ $breadcrumb['icon'] }}"></i>
                                @endif
                                {{ $breadcrumb['label'] }}
                            </a>
                        @else
                            @if($breadcrumb['icon'] ?? null)
                                <i class="{{ $breadcrumb['icon'] }}"></i>
                            @endif
                            {{ $breadcrumb['label'] }}
                        @endif
                    </li>
                @empty
                    <li class="ave-navbar__breadcrumb-item">
                        <a href="{{ url('/') }}" class="ave-navbar__breadcrumb-link"><i class="voyager-boat"></i> {{ config('app.name') }}</a>
                    </li>
                @endforelse
            </ol>
            @show
        </div>
        <div class="ave-navbar__right">
            @php $user = ave_auth_user(); @endphp
            @if($user)
                @php
                    $langPath = base_path('vendor/monstrex/ave/lang');
                    if (!file_exists($langPath)) {
                        $langPath = __DIR__ . '/../../../lang';
                    }
                    $availableLocales = array_map('basename', \Illuminate\Support\Facades\File::directories($langPath));
                    $localeNames = [
                        'en' => 'EN',
                        'ru' => 'RU',
                    ];
                    $currentLocale = app()->getLocale();
                @endphp
                <form action="{{ route('ave.locale.switch') }}" method="POST" class="ave-navbar__locale-form" id="locale-switcher-form">
                    @csrf
                    <select name="locale" class="ave-navbar__locale-select" onchange="this.form.submit()">
                        @foreach($availableLocales as $locale)
                            <option value="{{ $locale }}" {{ $locale === $currentLocale ? 'selected' : '' }}>
                                {{ $localeNames[$locale] ?? strtoupper($locale) }}
                            </option>
                        @endforeach
                    </select>
                </form>
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
