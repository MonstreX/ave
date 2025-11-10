<!DOCTYPE html>
<html lang="{{ config('app.locale', 'en') }}">
<head>
    <title>@yield('page_title', config('app.name') . ' - Admin')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="ave-route-prefix" content="{{ trim(config('ave.route_prefix', 'admin'), '/') }}"/>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/ave/css/app.css') }}">

    @yield('css')
    @stack('css')
    @yield('head')
</head>

<body class="ave-admin">

@include('ave::partials.icons')

<div id="toast-container"></div>

@if(session('toast'))
    <script type="application/json" id="ave-toast-data">
        {
            "type": "{{ session('toast.type', 'info') }}",
            "message": "{{ session('toast.message', '') }}"
        }
    </script>
@endif

<div id="ave-loader">
    <img src="{{ asset('vendor/ave/assets/images/logo-icon-light.png') }}" alt="Ave Loader">
</div>

<?php
$authUser = ave_auth_user();
$user_avatar = asset('vendor/ave/assets/images/captain-avatar.png');
if ($authUser && $authUser->avatar) {
    if (\Illuminate\Support\Str::startsWith($authUser->avatar, 'http://') ||
        \Illuminate\Support\Str::startsWith($authUser->avatar, 'https://')) {
        $user_avatar = $authUser->avatar;
    }
}
?>

<div class="app">
{{--    <div class="fadetoblack visible-xs"></div>--}}
    <div class="row app-wrapper">
        @include('ave::partials.navbar')
        @include('ave::partials.sidebar')
        <script>
            (function(){
                var appContainer = document.querySelector('.app'),
                    sidebar = appContainer.querySelector('.side-menu'),
                    navbar = appContainer.querySelector('.ave-navbar'),
                    loader = document.getElementById('ave-loader'),
                    hamburgerMenu = document.querySelector('.hamburger'),
                    sidebarTransition = sidebar ? sidebar.style.transition : '',
                    navbarTransition = navbar ? navbar.style.transition : '',
                    containerTransition = appContainer ? appContainer.style.transition : '';

                if (sidebar && navbar && appContainer) {
                    // Disable transitions temporarily
                    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition =
                    appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition =
                    navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = 'none';

                    // Check if sidebar should be expanded from localStorage
                    if (window.innerWidth > 768 && window.localStorage && window.localStorage.getItem('ave.stickySidebar') === 'true') {
                        appContainer.className += ' expanded no-animation';
                        if (loader) {
                            loader.style.left = (sidebar.clientWidth/2)+'px';
                        }
                        if (hamburgerMenu) {
                            hamburgerMenu.className += ' is-active no-animation';
                        }
                    }

                    // Restore transitions
                    navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = navbarTransition;
                    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition = sidebarTransition;
                    appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition = containerTransition;
                }
            })();
        </script>
        <!-- Main Content -->
        <div class="container-fluid">
            <div class="app-main padding-top">
                @yield('page_header')
                <div id="ave-notifications">
                    @yield('notifications')
                </div>
                @yield('content')
            </div>
        </div>
        @include('ave::partials/footer')
    </div>
</div>

<!-- Javascript -->
<script type="module" src="{{ asset('vendor/ave/js/app.js') }}"></script>

@yield('javascript')
@stack('javascript')

</body>
</html>
