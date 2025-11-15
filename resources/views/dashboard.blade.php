@extends('ave::layouts.master')

@section('page_header')
<div class="page-header">
    <h1 class="page-title">
        <i class="voyager-puzzle"></i> {{ __('ave::dashboard.title') }}
    </h1>
    <div class="page-header-actions">
        <a href="{{ route('ave.dashboard') }}" class="btn btn-success btn-add-new">
            <i class="voyager-plus"></i> <span>{{ __('ave::dashboard.create_item') }}</span>
        </a>
        <a href="{{ route('ave.dashboard') }}" class="btn btn-primary btn-add-new">
            <i class="voyager-list"></i> <span>{{ __('ave::dashboard.view_activity') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="page-content">
    @include('ave::partials.dashboard._typography')
    @include('ave::partials.dashboard._buttons')
    @include('ave::partials.dashboard._widgets')
    @include('ave::partials.dashboard._panels')
    @include('ave::partials.dashboard._forms')
    @include('ave::partials.dashboard._tables')
    @include('ave::partials.dashboard._interactive')
</div>
@endsection
