@extends('ave::layouts.master')

@php
    $icons = $payload['icons'] ?? [];
@endphp

@section('page_header')
    <div class="page-header">
        <h1 class="page-title">
            <i class="voyager-compass"></i> {{ $payload['title'] ?? 'Icons' }}
        </h1>
        <p class="page-subtitle">{{ $payload['description'] ?? '' }}</p>
    </div>
@endsection

@section('content')
    <div class="page-content icon-library-page">
        <div class="panel panel-bordered">
            <div class="panel-body">
                @if(empty($icons))
                    <p class="text-muted">{{ __('ave::icons.empty') }}</p>
                @else
                    <div class="icon-library-section">
                        <h2>{{ __('ave::icons.css_heading') }}</h2>
                        <p class="text-muted">{{ __('ave::icons.css_help') }}</p>
                        <ul class="glyphs css-mapping" id="css-icons">
                            @foreach($icons as $icon)
                                <li data-icon-name="{{ $icon['class'] }}">
                                    <div class="icon {{ $icon['class'] }}"></div>
                                    <input type="text" readonly value="{{ $icon['class'] }}">
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="icon-library-section">
                        <h2>{{ __('ave::icons.entity_heading') }}</h2>
                        <p class="text-muted">{{ __('ave::icons.entity_help') }}</p>
                        <ul class="glyphs character-mapping" id="entity-icons">
                            @foreach($icons as $icon)
                                <li>
                                    <div class="icon" data-icon="{!! $icon['entity'] !!}"></div>
                                    <input type="text" readonly value="{{ $icon['entity'] }}">
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('javascript')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.glyphs input').forEach(function (input) {
                input.addEventListener('click', function () {
                    input.select();
                });
            });
        });
    </script>
@endpush
