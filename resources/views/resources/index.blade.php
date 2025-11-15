@extends('ave::layouts.master')

@section('breadcrumbs')
    <ol class="ave-navbar__breadcrumb hidden-xs">
        <li class="ave-navbar__breadcrumb-item">
            <a href="{{ (\Illuminate\Support\Facades\Route::has('ave.dashboard') ? route('ave.dashboard') : url('/')) }}" class="ave-navbar__breadcrumb-link">
                <i class="voyager-boat"></i> {{ __('ave::dashboard.title') }}
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

        @if(($displayMode ?? 'table') === 'tree')
            @include('ave::partials.index.tree-view', [
                'table' => $table,
                'records' => $records,
                'rowActions' => $rowActions ?? [],
                'criteriaBadges' => $criteriaBadges ?? [],
                'resource' => $resource,
                'resourceInstance' => $resourceInstance ?? null,
                'slug' => $slug,
            ])
        @elseif(($displayMode ?? 'table') === 'sortable-grouped')
            @include('ave::partials.index.sortable-grouped-list', [
                'table' => $table,
                'records' => $records,
                'groupedRecords' => $groupedRecords ?? null,
                'rowActions' => $rowActions ?? [],
                'bulkActions' => $bulkActions ?? [],
                'criteriaBadges' => $criteriaBadges ?? [],
                'resource' => $resource,
                'resourceInstance' => $resourceInstance ?? null,
                'slug' => $slug,
            ])
        @elseif(($displayMode ?? 'table') === 'sortable')
            @include('ave::partials.index.sortable-list', [
                'table' => $table,
                'records' => $records,
                'rowActions' => $rowActions ?? [],
                'bulkActions' => $bulkActions ?? [],
                'criteriaBadges' => $criteriaBadges ?? [],
                'resource' => $resource,
                'resourceInstance' => $resourceInstance ?? null,
                'slug' => $slug,
            ])
        @else
            @include('ave::partials.index.table', [
                'table' => $table,
                'records' => $records,
                'rowActions' => $rowActions ?? [],
                'bulkActions' => $bulkActions ?? [],
                'criteriaBadges' => $criteriaBadges ?? [],
                'resource' => $resource,
                'resourceInstance' => $resourceInstance ?? null,
            ])
        @endif

    </div>
@endsection
