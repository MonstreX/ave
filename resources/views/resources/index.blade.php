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
    <div class="page-header">
        <h1 class="page-title">
            <i class="{{ $resource::getIcon() }}"></i> {{ $resource::getLabel() }}
        </h1>
        <div class="page-header-actions">
            @include('ave::partials.index.actions-inline', [
                'resource' => $resource,
                'resourceInstance' => $resourceInstance ?? null,
                'slug' => $slug,
                'globalActions' => $globalActions ?? [],
                'bulkActions' => $bulkActions ?? [],
            ])
        </div>
    </div>
@endsection

@section('content')
    <div class="page-content">

        @include('ave::partials.index.metrics')

        @include('ave::partials.index.table', [
            'table' => $table,
            'records' => $records,
            'rowActions' => $rowActions ?? [],
            'bulkActions' => $bulkActions ?? [],
            'criteriaBadges' => $criteriaBadges ?? [],
            'resource' => $resource,
            'resourceInstance' => $resourceInstance ?? null,
        ])

    </div>
@endsection
