@extends('ave::errors.layout')

@section('content')
    @php
        $title = __('ave::errors.500_title');
        $message = __('ave::errors.500_message');
    @endphp
@endsection
