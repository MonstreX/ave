@extends('ave::errors.layout')

@section('content')
    @php
        $title = 'Invalid Configuration';
        $message = 'The resource configuration is invalid. Please check your Resource class definition.';
    @endphp
@endsection
