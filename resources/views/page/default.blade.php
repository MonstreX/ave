@extends('ave::layouts.master')

@section('page_header')
    <div class="page-header">
        <h1 class="page-title">{{ $payload['title'] ?? 'Page' }}</h1>
    </div>
@endsection

@section('content')
    <div class="page-content">
        <div class="panel panel-bordered">
            <div class="panel-body">
                {{ $payload['content'] ?? __('Nothing to display yet.') }}
            </div>
        </div>
    </div>
@endsection
