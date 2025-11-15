@extends('ave::errors.layout')

@section('content')
    @php
        $title = __('ave::errors.404_title');
        $message = __('ave::errors.404_message');
    @endphp
@endsection
