@php
function fm_format_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
@endphp

@if(isset($data['error']))
    <div class="alert alert-danger">
        <i class="voyager-warning"></i> {{ $data['error'] }}
    </div>
@else
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th style="width: 40px;"></th>
                <th>{{ __('ave::file_manager.name') }}</th>
                <th style="width: 60px;">{{ __('ave::file_manager.preview') }}</th>
                <th style="width: 120px;">{{ __('ave::file_manager.size') }}</th>
                <th style="width: 180px;">{{ __('ave::file_manager.modified') }}</th>
                <th style="width: 140px;">{{ __('ave::file_manager.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @if($data['parent_path'] !== null || $data['current_path'])
                <tr class="fm-item fm-parent">
                    <td><i class="voyager-folder" style="color: #3498db; font-size: 20px;"></i></td>
                    <td>
                        <a href="#" class="fm-navigate" data-fm-path="{{ $data['parent_path'] ?? '' }}">..</a>
                    </td>
                    <td></td>
                    <td>—</td>
                    <td>—</td>
                    <td></td>
                </tr>
            @endif

            @forelse($data['items'] as $item)
                @php
                    $isImage = in_array($item['extension'] ?? '', ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'bmp']);
                @endphp
                <tr class="fm-item" data-path="{{ $item['path'] }}" data-type="{{ $item['type'] }}">
                    <td>
                        @if($item['type'] === 'directory')
                            <i class="voyager-folder" style="color: #f39c12; font-size: 20px;"></i>
                        @else
                            @php
                                $iconClass = match($item['extension'] ?? '') {
                                    'txt', 'md', 'log' => 'voyager-file-text',
                                    'html', 'htm', 'php', 'js', 'json', 'xml', 'css' => 'voyager-code',
                                    'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'bmp' => 'voyager-images',
                                    'pdf' => 'voyager-documentation',
                                    'zip', 'rar', 'tar', 'gz', '7z' => 'voyager-archive',
                                    'mp3', 'wav', 'ogg', 'flac' => 'voyager-music',
                                    'mp4', 'avi', 'mov', 'wmv', 'webm' => 'voyager-video',
                                    'doc', 'docx', 'odt' => 'voyager-file-text',
                                    'xls', 'xlsx', 'ods', 'csv' => 'voyager-file-text',
                                    default => 'voyager-file-text'
                                };
                            @endphp
                            <i class="{{ $iconClass }}" style="font-size: 20px;"></i>
                        @endif
                    </td>
                    <td>
                        @if($item['type'] === 'directory')
                            <a href="#" class="fm-navigate" data-fm-path="{{ $item['path'] }}">{{ $item['name'] }}</a>
                        @elseif($item['editable'] ?? false)
                            <a href="#" class="fm-edit" data-fm-path="{{ $item['path'] }}">{{ $item['name'] }}</a>
                        @else
                            {{ $item['name'] }}
                        @endif
                    </td>
                    <td>
                        @if($item['type'] === 'file' && $isImage)
                            <img src="{{ $item['url'] ?? '' }}" alt="" style="max-height: 30px; max-width: 50px;">
                        @endif
                    </td>
                    <td>
                        @if($item['type'] === 'file')
                            {{ fm_format_size($item['size']) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        {{ date('d.m.Y H:i', $item['modified']) }}
                    </td>
                    <td>
                        <div class="table-action-buttons">
                            @if($item['editable'] ?? false)
                                <a href="#" class="table-action-icon fm-edit" data-fm-path="{{ $item['path'] }}" title="{{ __('ave::common.edit') }}">
                                    <i class="voyager-edit"></i>
                                </a>
                            @endif
                            <a href="#" class="table-action-icon fm-rename-btn" data-fm-path="{{ $item['path'] }}" data-fm-name="{{ $item['name'] }}" title="{{ __('ave::file_manager.rename') }}">
                                <i class="voyager-pen"></i>
                            </a>
                            <a href="#" class="table-action-icon delete fm-delete" data-fm-path="{{ $item['path'] }}" title="{{ __('ave::common.delete') }}">
                                <i class="voyager-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        {{ __('ave::file_manager.empty_folder') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endif

