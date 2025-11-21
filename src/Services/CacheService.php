<?php

namespace Monstrex\Ave\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Available cache types and their configurations.
     */
    public function getTypes(): array
    {
        return [
            'application' => [
                'label' => __('ave::cache.application'),
                'description' => __('ave::cache.application_desc'),
                'icon' => 'voyager-data',
            ],
            'config' => [
                'label' => __('ave::cache.config'),
                'description' => __('ave::cache.config_desc'),
                'icon' => 'voyager-settings',
            ],
            'route' => [
                'label' => __('ave::cache.route'),
                'description' => __('ave::cache.route_desc'),
                'icon' => 'voyager-compass',
            ],
            'view' => [
                'label' => __('ave::cache.view'),
                'description' => __('ave::cache.view_desc'),
                'icon' => 'voyager-browser',
            ],
            'all' => [
                'label' => __('ave::cache.all'),
                'description' => __('ave::cache.all_desc'),
                'icon' => 'voyager-refresh',
            ],
        ];
    }

    /**
     * Clear specific cache type.
     */
    public function clear(string $type): array
    {
        return match ($type) {
            'application' => $this->clearApplication(),
            'config' => $this->clearConfig(),
            'route' => $this->clearRoute(),
            'view' => $this->clearView(),
            'all' => $this->clearAll(),
            default => ['success' => false, 'message' => __('ave::cache.unknown_type')],
        };
    }

    /**
     * Clear application cache.
     */
    public function clearApplication(): array
    {
        try {
            Cache::flush();

            return [
                'success' => true,
                'message' => __('ave::cache.cleared_application'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear config cache.
     */
    public function clearConfig(): array
    {
        try {
            Artisan::call('config:clear');

            return [
                'success' => true,
                'message' => __('ave::cache.cleared_config'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear route cache.
     */
    public function clearRoute(): array
    {
        try {
            Artisan::call('route:clear');

            return [
                'success' => true,
                'message' => __('ave::cache.cleared_route'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear view cache.
     */
    public function clearView(): array
    {
        try {
            Artisan::call('view:clear');

            return [
                'success' => true,
                'message' => __('ave::cache.cleared_view'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear all caches.
     */
    public function clearAll(): array
    {
        $results = [];
        $allSuccess = true;

        foreach (['application', 'config', 'route', 'view'] as $type) {
            $result = $this->clear($type);
            $results[$type] = $result;
            if (! $result['success']) {
                $allSuccess = false;
            }
        }

        return [
            'success' => $allSuccess,
            'message' => $allSuccess
                ? __('ave::cache.cleared_all')
                : __('ave::cache.cleared_partial'),
            'details' => $results,
        ];
    }
}
