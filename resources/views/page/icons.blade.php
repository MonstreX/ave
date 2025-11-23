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

@push('css')
    <style>
        .icon-library-page h2 {
            margin-top: 0;
            font-size: 18px;
            text-transform: uppercase;
            color: #475569;
        }

        .icon-library-section {
            margin-bottom: 40px;
        }

        .glyphs {
            list-style: none;
            margin: 0;
            padding: 30px 0 20px 30px;
            border: 1px solid #d8e0e5;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .glyphs li {
            display: inline-block;
        }

        .glyphs .icon {
            color: #162a36;
        }

        .glyphs input {
            border: 1px solid #d8e0e5;
            border-radius: 5px;
            outline: 0;
            width: 150px;
            text-align: center;
            transition: box-shadow .2s, border-color .2s;
        }

        .glyphs input:focus,
        .glyphs input:hover {
            border-color: #fbde4a;
            box-shadow: inset 0 0 3px #fbde4a;
        }

        .glyphs.css-mapping li {
            width: 210px;
        }

        .glyphs.css-mapping .icon {
            margin-right: 10px;
            padding: 13px;
            height: 50px;
            width: 50px;
            font-size: 24px;
            border-radius: 6px;
            background: #f8fafc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .glyphs.css-mapping input {
            margin-top: 5px;
            padding: 8px;
            height: 40px;
        }

        .glyphs.character-mapping {
            padding-left: 20px;
            gap: 12px 18px;
        }

        .glyphs.character-mapping li {
            width: 110px;
        }

        .glyphs.character-mapping .icon {
            width: 55px;
            height: 55px;
            padding: 15px;
            font-size: 32px;
            border-radius: 6px;
            background: #f8fafc;
            margin: 0 auto 10px;
        }

        .glyphs.character-mapping input {
            padding: 6px 0;
            font-size: 12px;
            width: 100%;
        }
    </style>
@endpush

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
