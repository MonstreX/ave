@extends('ave::layouts.master')

@section('page_header')
<div class="page-header">
    <h1 class="page-title">
        <i class="voyager-folder"></i> {{ __('ave::file_manager.title') }}
    </h1>
</div>
@endsection

@section('content')
<div class="page-content">
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="voyager-folder"></i>
                        <span id="fm-current-path">{{ $currentPath ?: '/' }}</span>
                    </h3>
                    <div class="panel-actions flex-left">
                        <button type="button" class="btn btn-primary" id="fm-upload-btn">
                            <i class="voyager-upload"></i> {{ __('ave::file_manager.upload') }}
                        </button>
                        <button type="button" class="btn btn-success" id="fm-new-folder-btn">
                            <i class="voyager-folder"></i> {{ __('ave::file_manager.new_folder') }}
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    {{-- Breadcrumb navigation --}}
                    <nav aria-label="breadcrumb">
                        <ol class="ave-navbar__breadcrumb" style="margin-bottom: 15px;">
                            <li class="ave-navbar__breadcrumb-item">
                                <a href="#" data-fm-path="" class="fm-navigate ave-navbar__breadcrumb-link">
                                    <i class="voyager-home"></i> {{ __('ave::file_manager.root') }}
                                </a>
                            </li>
                            @if($currentPath)
                                @php
                                    $pathParts = explode(DIRECTORY_SEPARATOR, $currentPath);
                                    $buildPath = '';
                                @endphp
                                @foreach($pathParts as $index => $part)
                                    @php $buildPath .= ($buildPath ? DIRECTORY_SEPARATOR : '') . $part; @endphp
                                    <li class="ave-navbar__breadcrumb-item">
                                        @if($index < count($pathParts) - 1)
                                            <a href="#" data-fm-path="{{ $buildPath }}" class="fm-navigate ave-navbar__breadcrumb-link">{{ $part }}</a>
                                        @else
                                            {{ $part }}
                                        @endif
                                    </li>
                                @endforeach
                            @endif
                        </ol>
                    </nav>

                    {{-- File list --}}
                    <div class="fm-file-list" id="fm-file-list">
                        @include('ave::file-manager.partials.file-list', ['data' => $data])
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<input type="hidden" id="fm-current-path-input" value="{{ $currentPath }}">
<input type="hidden" id="fm-routes"
    data-list="{{ route('ave.file-manager.list') }}"
    data-read="{{ route('ave.file-manager.read') }}"
    data-save="{{ route('ave.file-manager.save') }}"
    data-directory="{{ route('ave.file-manager.create-directory') }}"
    data-upload="{{ route('ave.file-manager.upload') }}"
    data-delete="{{ route('ave.file-manager.delete') }}"
    data-rename="{{ route('ave.file-manager.rename') }}"
>
@endsection
