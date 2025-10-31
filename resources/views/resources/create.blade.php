@extends('ave::layouts.master')

@section('breadcrumbs')
<ol class="ave-navbar__breadcrumb hidden-xs">
    <li class="ave-navbar__breadcrumb-item">
        <a href="{{ route('ave.dashboard') }}" class="ave-navbar__breadcrumb-link">
            <i class="voyager-boat"></i> {{ __('Dashboard') }}
        </a>
    </li>
    <li class="ave-navbar__breadcrumb-item">
        <a href="{{ route($routeBaseName . '.index') }}" class="ave-navbar__breadcrumb-link">
            {{ $resourceClass::getLabel() }}
        </a>
    </li>
    <li class="ave-navbar__breadcrumb-item is-active">
        {{ __('Create') }}
    </li>
</ol>
@endsection

@section('page_header')
<div class="page-header">
    <h1 class="page-title">
        <i class="voyager-plus"></i> Create {{ $resourceClass::getSingularLabel() }}
    </h1>
    <div class="page-header-actions">
        <a href="{{ route($routeBaseName . '.index') }}" class="btn btn-secondary">
            <i class="voyager-angle-left"></i> <span>Back</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="page-content">
    <div class="panel panel-bordered">
        <div class="panel-body">
            <form action="{{ route($routeBaseName . '.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {!! $form->render() !!}

                <div class="form-actions">
                    <a href="{{ route($routeBaseName . '.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Create {{ $resourceClass::getSingularLabel() }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



