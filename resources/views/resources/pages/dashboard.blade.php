@extends('ave::layouts.app')

@section('page_title', 'Dashboard')

@section('content')
    <div class="page-content dashboard container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h4 class="panel-title">Welcome to Ave Admin</h4>
                    </div>
                    <div class="panel-body">
                        <p>This is a page-only resource example - a resource without an associated model.</p>

                        <div class="alert alert-success">
                            <h5>
                                <i class="fa fa-check-circle"></i>
                                Page-Only Resource
                            </h5>
                            <p>This resource demonstrates the power of page-only resources:</p>
                            <ul>
                                <li><strong>No Model:</strong> {{ $resourceClass::hasModel() ? 'Has model' : 'No model' }}</li>
                                <li><strong>Resource:</strong> {{ $resourceClass::getLabel() }}</li>
                                <li><strong>Slug:</strong> {{ $resourceClass::getSlug() }}</li>
                                <li><strong>Pages Available:</strong> {{ implode(', ', array_keys($resourceClass::getPages())) }}</li>
                            </ul>
                        </div>

                        <p>Page-only resources are perfect for:</p>
                        <ul>
                            <li>Dashboards and analytics pages</li>
                            <li>Settings and configuration pages</li>
                            <li>Reports and data visualizations</li>
                            <li>Custom admin pages with specific logic</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
