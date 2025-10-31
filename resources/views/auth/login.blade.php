@extends('ave::layouts.auth')

@section('content')
    <div class="ave-login__container">

        <p>Sign in below</p>

        <form action="{{ route('ave.login') }}" method="POST">
            @csrf
            <div class="form-field form-field-default" id="emailGroup">
                <label>Email</label>
                <div class="controls">
                    <input type="text" name="email" id="email" value="{{ old('email') }}" placeholder="Email" class="form-control" required>
                </div>
            </div>

            <div class="form-field form-field-default" id="passwordGroup">
                <label>Password</label>
                <div class="controls">
                    <input type="password" name="password" placeholder="Password" class="form-control" required>
                </div>
            </div>

            <div class="form-field">
                <div class="checkbox" id="rememberMeGroup">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <label for="remember" class="remember-me-text">Remember Me</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-10">
                <span class="signingin hidden"><span class="voyager-refresh"></span> Logging in...</span>
                <span class="signin">Login</span>
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





