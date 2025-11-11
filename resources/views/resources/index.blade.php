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
    @include('ave::partials.index.toolbar', [
        'globalActions' => $globalActions ?? [],
    ])
@endsection

@section('content')
    <div class="page-content">

        @include('ave::partials.index.metrics')

        @include('ave::partials.index.filters', [
            'table' => $table,
            'records' => $records,
            'slug' => $slug,
        ])

        @include('ave::partials.index.criteria_badges', [
            'criteriaBadges' => $criteriaBadges ?? [],
            'slug' => $slug,
        ])

        @include('ave::partials.index.bulk_actions', [
            'bulkActions' => $bulkActions ?? [],
        ])

        @include('ave::partials.index.table', [
            'rowActions' => $rowActions ?? [],
            'bulkActions' => $bulkActions ?? [],
        ])

    </div>
@endsection
