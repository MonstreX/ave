@extends('ave::errors.layout')

@section('content')
    @php
        $title = __('ave::errors.403_title');
        $message = __('ave::errors.403_message');
    @endphp
@endsection
