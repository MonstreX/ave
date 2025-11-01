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
                    $segments = array_filter(explode('/', str_replace($dashboardRoute, '', request()->url())));
                }
            @endphp
            @section('breadcrumbs')
            <ol class="ave-navbar__breadcrumb hidden-xs">
                @if($dashboardRoute)
                    <li class="ave-navbar__breadcrumb-item">
                        <a href="{{ $dashboardRoute }}" class="ave-navbar__breadcrumb-link"><i class="voyager-boat"></i> {{ __('Dashboard') }}</a>
                    </li>
                    @php $url = $dashboardRoute; @endphp
                    @foreach ($segments as $segment)
                        @php $url .= '/' . $segment; @endphp
                        @if ($loop->last)
                            <li class="ave-navbar__breadcrumb-item">{{ ucfirst(urldecode($segment)) }}</li>
                        @else
                            <li class="ave-navbar__breadcrumb-item">
                                <a href="{{ $url }}" class="ave-navbar__breadcrumb-link">{{ ucfirst(urldecode($segment)) }}</a>
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
            @php $user = auth()->user(); @endphp
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
                            <li>
                                <a href="#" class="dropdown-item">
                                    <i class="voyager-person"></i>
                                    <span>{{ __('Profile') }}</span>
                                </a>
                            </li>
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
