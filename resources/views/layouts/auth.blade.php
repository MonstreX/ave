<!DOCTYPE html>
<html lang="{{ config('app.locale', 'en') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="none" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="admin login">
    <title>@yield('title', 'Admin - '.config('app.name'))</title>

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/ave/css/app.css') }}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">

    <style>
        body {
            background-image:url('{{ asset('vendor/ave/assets/images/bg.jpg') }}');
            background-color: #FFFFFF;
        }
        body.ave-login .ave-login__sidebar {
            border-top:5px solid {{ config('ave.primary_color','#22A7F0') }};
        }
        @media (max-width: 767px) {
            body.ave-login .ave-login__sidebar {
                border-top:0px !important;
                border-left:5px solid {{ config('ave.primary_color','#22A7F0') }};
            }
        }
        body.ave-login .form-field-default.focused{
            border-color:{{ config('ave.primary_color','#22A7F0') }};
        }
        .btn-primary, .bar:before, .bar:after{
            background:{{ config('ave.primary_color','#22A7F0') }};
        }
        .remember-me-text{
            padding:0 5px;
        }
    </style>

    @yield('pre_css')
</head>
<body class="ave-login">
<div class="container-fluid">
    <div class="row">
        <div class="ave-login__faded-bg animated"></div>
        <div class="hidden-xs col-sm-7 col-md-8">
            <div class="clearfix">
                <div class="col-sm-12 col-md-10 col-md-offset-2">
                    <div class="ave-login__logo-title">
                        <img class="img-responsive pull-left flip ave-login__logo hidden-xs animated fadeIn"
                             src="{{ asset('vendor/ave/assets/images/logo-icon-light.png') }}"
                             alt="Logo Icon">
                        <div class="ave-login__copy animated fadeIn">
                            <h1>{{ config('app.name', 'Ave') }}</h1>
                            <p>{{ config('ave.description', 'Welcome to Ave') }}</p>
                        </div>
                    </div> <!-- .ave-login__logo-title -->
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-5 col-md-4 ave-login__sidebar">
           @yield('content')
        </div> <!-- .ave-login__sidebar -->
    </div> <!-- .row -->
</div> <!-- .container-fluid -->
@yield('post_js')
</body>
</html>



