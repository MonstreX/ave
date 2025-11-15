@extends('ave::layouts.auth')

@section('content')
    <div class="ave-login__container">

        <p>{{ __('ave::auth.sign_in_below') }}</p>

        <form action="{{ route(ave_login_submit_route_name()) }}" method="POST">
            @csrf
            <div class="form-field form-field-default" id="emailGroup">
                <label>{{ __('ave::auth.email') }}</label>
                <div class="controls">
                    <input type="text" name="email" id="email" value="{{ old('email') }}" placeholder="{{ __('ave::auth.email_placeholder') }}" class="form-control" required>
                </div>
            </div>

            <div class="form-field form-field-default" id="passwordGroup">
                <label>{{ __('ave::auth.password') }}</label>
                <div class="controls">
                    <input type="password" name="password" placeholder="{{ __('ave::auth.password_placeholder') }}" class="form-control" required>
                </div>
            </div>

            <div class="form-field">
                <div class="checkbox" id="rememberMeGroup">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <label for="remember" class="remember-me-text">{{ __('ave::auth.remember_me') }}</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-10">
                <span class="signingin hidden"><span class="voyager-refresh"></span> {{ __('ave::auth.logging_in') }}</span>
                <span class="signin">{{ __('ave::auth.login') }}</span>
            </button>

        </form>

        <div class="clearfix"></div>

        @if(!$errors->isEmpty())
            <div class="alert alert-danger">
                <ul class="list-unstyled">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div> <!-- .ave-login__container -->
@endsection





