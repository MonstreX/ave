@extends('ave::layouts.master')

@section('content')
    <div class="page-content">

        @yield('resource_toolbar')

        @yield('resource_filters')

        <div class="panel panel-bordered">
            <div class="panel-body">
                @yield('resource_search')

                @yield('resource_table')
            </div>
        </div>

        <div class="d-flex justify-content-between">
            @yield('resource_bulk_actions')
            @yield('resource_pagination')
        </div>

        @yield('resource_footer')

    </div>
@endsection
