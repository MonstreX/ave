<?php

namespace Monstrex\Ave\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Monstrex\Ave\Core\Registry\ResourceRegistry;
use Monstrex\Ave\Core\Registry\PageRegistry;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\PageManager;
use Monstrex\Ave\Core\Rendering\ViewResolver;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Discovery\AdminResourceDiscovery;
use Monstrex\Ave\Core\Discovery\AdminPageDiscovery;
use Monstrex\Ave\Console\Commands\CacheClearCommand;
use Monstrex\Ave\View\Composers\SidebarComposer;
use Monstrex\Ave\Support\PackageAssets;
use Monstrex\Ave\Media\MediaStorage;

/**
 * AveServiceProvider Class
 *
 * Main service provider for the Ave admin panel package.
 * Registers all services, loads configuration, migrations, and views.
 */
class AveServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        // Register singletons
        $this->app->singleton(ResourceRegistry::class);
        $this->app->singleton(PageRegistry::class);
        $this->app->singleton(AdminResourceDiscovery::class);
        $this->app->singleton(AdminPageDiscovery::class);

        $this->app->singleton(ResourceManager::class, function ($app) {
            return new ResourceManager(
                $app->make(AdminResourceDiscovery::class),
                $app->make(ResourceRegistry::class),
            );
        });

        $this->app->singleton(PageManager::class, function ($app) {
            return new PageManager(
                $app->make(AdminPageDiscovery::class),
                $app->make(PageRegistry::class),
            );
        });

        $this->app->singleton(ViewResolver::class);
        $this->app->singleton(ResourceRenderer::class);
        $this->app->singleton(FormValidator::class);
        $this->app->singleton(ResourcePersistence::class);

        // Register Media Storage service
        $this->app->singleton('media-storage', function ($app) {
            return new MediaStorage();
        });

        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../../config/ave.php', 'ave');
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPublishing();

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ave');

        View::composer('ave::partials.sidebar', SidebarComposer::class);

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheClearCommand::class,
            ]);
        }

        // Discover and register resources/pages
        $this->discoverAndRegister();
    }

    /**
     * Discover and register resources and pages with caching
     *
     * @return void
     */
    protected function discoverAndRegister(): void
    {
        $cacheEnabled = config('ave.cache_discovery', true);
        $cacheTtl = config('ave.cache_ttl', 3600);

        if ($cacheEnabled) {
            $cached = Cache::remember('ave.discovery', $cacheTtl, function () {
                return $this->performDiscovery();
            });

            $this->registerDiscovered($cached);
        } else {
            $discovered = $this->performDiscovery();
            $this->registerDiscovered($discovered);
        }
    }

    /**
     * Perform discovery of resources and pages
     *
     * @return array Discovered classes
     */
    protected function performDiscovery(): array
    {
        $resourceDiscovery = $this->app->make(AdminResourceDiscovery::class);
        $pageDiscovery = $this->app->make(AdminPageDiscovery::class);

        return [
            'resources' => $resourceDiscovery->discover(),
            'pages' => $pageDiscovery->discover(),
        ];
    }

    /**
     * Register discovered resources and pages
     *
     * @param array $discovered Discovered classes
     * @return void
     */
    protected function registerDiscovered(array $discovered): void
    {
        $resourceManager = $this->app->make(ResourceManager::class);
        $pageManager = $this->app->make(PageManager::class);

        foreach ($discovered['resources'] as $slug => $resourceClass) {
            $resourceManager->register($resourceClass, $slug);
        }

        foreach ($discovered['pages'] as $slug => $pageClass) {
            $pageManager->register($pageClass, $slug);
        }
    }

    /**
     * Register publishing of package assets, config, views, and migrations
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Publish config
        $this->publishes(PackageAssets::configs(), 'ave-config');

        // Publish migrations
        $this->publishes(PackageAssets::migrations(), 'ave-migrations');

        // Publish views
        $packagePath = __DIR__ . '/../../';
        $this->publishes([
            $packagePath . 'resources/views' => resource_path('views/vendor/ave'),
        ], 'ave-views');

        // Publish assets (dist folder)
        $assetMappings = PackageAssets::assets();

        if ($this->isVendorPublishCommand()) {
            PackageAssets::cleanAssetTargets($assetMappings);
        }

        $this->publishes($assetMappings, 'ave-assets');
    }

    /**
     * Check if the current command is vendor:publish
     *
     * @return bool
     */
    protected function isVendorPublishCommand(): bool
    {
        $arguments = $_SERVER['argv'] ?? [];

        foreach ($arguments as $argument) {
            if ($argument === 'vendor:publish') {
                return true;
            }
        }

        return false;
    }
}
