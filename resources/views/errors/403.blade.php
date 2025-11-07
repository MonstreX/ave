@extends('ave::errors.layout')

@section('content')
    @php
        $title = 'Access Denied';
        $message = 'You don\'t have permission to access this resource.';
    @endphp
@endsection
