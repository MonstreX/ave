@extends('ave::layouts.master')

@section('breadcrumbs')
    <ol class="ave-navbar__breadcrumb hidden-xs">
        <li class="ave-navbar__breadcrumb-item">
            <a href="{{ (\Illuminate\Support\Facades\Route::has('ave.dashboard') ? route('ave.dashboard') : url('/')) }}" class="ave-navbar__breadcrumb-link">
                <i class="voyager-boat"></i> {{ __('Dashboard') }}
            </a>
        </li>
        <li class="ave-navbar__breadcrumb-item is-active">
            {{ $resource::getLabel() }}
        </li>
    </ol>
@endsection

@section('page_header')
    @include('ave::partials.index.toolbar')
@endsection

@section('content')
    <div class="page-content">

        @include('ave::partials.index.metrics')

        @include('ave::partials.index.filters')

        @include('ave::partials.index.bulk_actions')

        @include('ave::partials.index.table')

    </div>
@endsection
