@extends('ave::layouts.app')

@section('page_title', 'Tree View')

@section('content')
    <div class="page-content browse container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h4 class="panel-title">{{ $title ?? 'Tree View' }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            <h5>
                                <i class="fa fa-info-circle"></i>
                                Tree View (Coming Soon)
                            </h5>
                            <p>The hierarchical tree view for this resource is not yet implemented.</p>
                            <p><strong>Resource:</strong> {{ $resource::getLabel() }}</p>
                            <p>This feature will be available in a future version.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
