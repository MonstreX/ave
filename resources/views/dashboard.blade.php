@extends('ave::layouts.master')

@section('page_header')
<div class="page-header">
    <h1 class="page-title">
        <i class="voyager-dashboard"></i> {{ __('ave::dashboard.title') }}
    </h1>
</div>
@endsection

@section('content')
<div class="page-content dashboard-page">
    {{-- Welcome Section --}}
    <div class="dashboard-welcome">
        <div class="welcome-card">
            <h2>{{ __('ave::dashboard.welcome', ['name' => ave_auth_user()?->name ?? 'Admin']) }}</h2>
            <p>{{ __('ave::dashboard.welcome_message') }}</p>
        </div>
    </div>

    {{-- System Information Grid --}}
    <div class="dashboard-grid">
        {{-- PHP Information --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="voyager-code"></i>
                <h3>{{ __('ave::dashboard.php_info') }}</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.php_version') }}:</span>
                    <span class="info-value">{{ PHP_VERSION }}</span>
                    @if (version_compare(PHP_VERSION, '8.2.0', '<'))
                        <span class="badge badge-warning">{{ __('ave::dashboard.outdated') }}</span>
                    @else
                        <span class="badge badge-success">{{ __('ave::dashboard.ok') }}</span>
                    @endif
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.memory_limit') }}:</span>
                    <span class="info-value">{{ ini_get('memory_limit') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.max_execution_time') }}:</span>
                    <span class="info-value">{{ ini_get('max_execution_time') }}s</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.upload_max_filesize') }}:</span>
                    <span class="info-value">{{ ini_get('upload_max_filesize') }}</span>
                </div>
            </div>
        </div>

        {{-- Laravel Information --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="voyager-rocket"></i>
                <h3>{{ __('ave::dashboard.laravel_info') }}</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.laravel_version') }}:</span>
                    <span class="info-value">{{ app()->version() }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.environment') }}:</span>
                    <span class="info-value">{{ app()->environment() }}</span>
                    @if (app()->environment('production'))
                        <span class="badge badge-success">{{ __('ave::dashboard.production') }}</span>
                    @else
                        <span class="badge badge-info">{{ __('ave::dashboard.development') }}</span>
                    @endif
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.debug_mode') }}:</span>
                    <span class="info-value">{{ config('app.debug') ? __('ave::dashboard.enabled') : __('ave::dashboard.disabled') }}</span>
                    @if (config('app.debug') && app()->environment('production'))
                        <span class="badge badge-danger">{{ __('ave::dashboard.warning') }}</span>
                    @endif
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.timezone') }}:</span>
                    <span class="info-value">{{ config('app.timezone') }}</span>
                </div>
            </div>
        </div>

        {{-- Ave Information --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="voyager-helm"></i>
                <h3>{{ __('ave::dashboard.ave_info') }}</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.ave_version') }}:</span>
                    <span class="info-value">{{ config('ave.version', '0.7.0') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.route_prefix') }}:</span>
                    <span class="info-value">/{{ config('ave.route_prefix', 'admin') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.auth_guard') }}:</span>
                    <span class="info-value">{{ config('ave.auth_guard', 'web') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.storage_disk') }}:</span>
                    <span class="info-value">{{ config('ave.storage.disk', 'public') }}</span>
                </div>
            </div>
        </div>

        {{-- Database Information --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="voyager-data"></i>
                <h3>{{ __('ave::dashboard.database_info') }}</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.connection') }}:</span>
                    <span class="info-value">{{ config('database.default') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.driver') }}:</span>
                    <span class="info-value">{{ config('database.connections.' . config('database.default') . '.driver') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.database') }}:</span>
                    <span class="info-value">{{ DB::getDatabaseName() }}</span>
                </div>
                @php
                    try {
                        DB::connection()->getPdo();
                        $dbConnected = true;
                    } catch (\Exception $e) {
                        $dbConnected = false;
                    }
                @endphp
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.status') }}:</span>
                    @if ($dbConnected)
                        <span class="badge badge-success">{{ __('ave::dashboard.connected') }}</span>
                    @else
                        <span class="badge badge-danger">{{ __('ave::dashboard.disconnected') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Server Information --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="voyager-world"></i>
                <h3>{{ __('ave::dashboard.server_info') }}</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.server_software') }}:</span>
                    <span class="info-value">{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.server_os') }}:</span>
                    <span class="info-value">{{ PHP_OS }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.server_ip') }}:</span>
                    <span class="info-value">{{ $_SERVER['SERVER_ADDR'] ?? 'N/A' }}</span>
                </div>
                @php
                    try {
                        // Try to use storage path (accessible within open_basedir)
                        $diskFree = @disk_free_space(storage_path());
                        $diskTotal = @disk_total_space(storage_path());
                    } catch (\Exception $e) {
                        $diskFree = false;
                        $diskTotal = 0;
                    }

                    if ($diskFree === false || $diskTotal === 0) {
                        $diskUsedPercent = 0;
                        $diskFreeFormatted = 'N/A';
                    } else {
                        $diskUsedPercent = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0;

                        // Format disk free space
                        if ($diskFree >= 1073741824) {
                            $diskFreeFormatted = round($diskFree / 1073741824, 2) . ' GB';
                        } else {
                            $diskFreeFormatted = round($diskFree / 1048576, 2) . ' MB';
                        }
                    }
                @endphp
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.disk_usage') }}:</span>
                    <span class="info-value">{{ $diskUsedPercent }}% ({{ __('ave::dashboard.disk_free') }}: {{ $diskFreeFormatted }})</span>
                    @if ($diskUsedPercent > 90)
                        <span class="badge badge-danger">{{ __('ave::dashboard.critical') }}</span>
                    @elseif ($diskUsedPercent > 75)
                        <span class="badge badge-warning">{{ __('ave::dashboard.warning') }}</span>
                    @else
                        <span class="badge badge-success">{{ __('ave::dashboard.ok') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Cache Information --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="voyager-bolt"></i>
                <h3>{{ __('ave::dashboard.cache_info') }}</h3>
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.cache_driver') }}:</span>
                    <span class="info-value">{{ config('cache.default') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.session_driver') }}:</span>
                    <span class="info-value">{{ config('session.driver') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('ave::dashboard.queue_driver') }}:</span>
                    <span class="info-value">{{ config('queue.default') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- System Warnings --}}
    @php
        $warnings = [];
        if (config('app.debug') && app()->environment('production')) {
            $warnings[] = __('ave::dashboard.warning_debug_production');
        }
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $warnings[] = __('ave::dashboard.warning_php_version');
        }
        if (!function_exists('opcache_get_status')) {
            $warnings[] = __('ave::dashboard.warning_opcache');
        }
        if ($diskUsedPercent > 90) {
            $warnings[] = __('ave::dashboard.warning_disk_space');
        }
    @endphp

    @if (count($warnings) > 0)
    <div class="dashboard-warnings">
        <div class="alert alert-warning">
            <strong><i class="voyager-warning"></i> {{ __('ave::dashboard.system_warnings') }}:</strong>
            <ul>
                @foreach ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
@endsection
