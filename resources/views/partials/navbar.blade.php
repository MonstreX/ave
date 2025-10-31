<nav class="navbar navbar-default navbar-fixed-top ave-navbar">
    <div class="ave-navbar__content">
        <div class="ave-navbar__left">
            <button class="hamburger btn-link no-animation" data-ave-nav="toggle">
                <span class="hamburger-inner"></span>
            </button>
            @section('breadcrumbs')
            <ol class="ave-navbar__breadcrumb hidden-xs">
                @php
                $segments = array_filter(explode('/', str_replace(route('ave.dashboard'), '', Request::url())));
                $url = route('ave.dashboard');
                @endphp
                <li class="ave-navbar__breadcrumb-item">
                    <a href="{{ route('ave.dashboard')}}" class="ave-navbar__breadcrumb-link"><i class="voyager-boat"></i> {{ __('Dashboard') }}</a>
                </li>
                @foreach ($segments as $segment)
                    @php
                    $url .= '/'.$segment;
                    @endphp
                    @if ($loop->last)
                        <li class="ave-navbar__breadcrumb-item">{{ ucfirst(urldecode($segment)) }}</li>
                    @else
                        <li class="ave-navbar__breadcrumb-item">
                            <a href="{{ $url }}" class="ave-navbar__breadcrumb-link">{{ ucfirst(urldecode($segment)) }}</a>
                        </li>
                    @endif
                @endforeach

            </ol>
            @show
        </div>
        <div class="ave-navbar__right">
            <ul class="ave-navbar__nav">
                <li class="dropdown profile">
                    <a href="#" class="dropdown-toggle text-right" data-toggle="dropdown" role="button"
                       aria-expanded="false">
                        <img src="{{ $user_avatar }}" class="profile-img" alt="{{ Auth::user()->name }}">
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-animated">
                        <li class="dropdown-profile">
                            <div class="dropdown-profile__avatar">
                                <img src="{{ $user_avatar }}" alt="{{ Auth::user()->name }}">
                            </div>
                            <div class="dropdown-profile__details">
                                <span class="dropdown-profile__name">{{ Auth::user()->name }}</span>
                                <span class="dropdown-profile__email">{{ Auth::user()->email }}</span>
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
                        <li class="dropdown-logout">
                            <form action="{{ route('ave.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item dropdown-item--danger">
                                    <i class="voyager-power"></i>
                                    <span>{{ __('Logout') }}</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>





