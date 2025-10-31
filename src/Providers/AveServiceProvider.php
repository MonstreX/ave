<?php

namespace Monstrex\Ave\Providers;

use Illuminate\Support\ServiceProvider;
use Monstrex\Ave\Core\Registry\ResourceRegistry;
use Monstrex\Ave\Core\Registry\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ViewResolver;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;

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
        // Load config
        $this->mergeConfigFrom(__DIR__ . '/../../config/ave.php', 'ave');

        // Register services as singletons
        $this->app->singleton(ResourceRegistry::class, function () {
            return new ResourceRegistry();
        });

        $this->app->singleton(ResourceManager::class, function ($app) {
            return new ResourceManager($app->make(ResourceRegistry::class));
        });

        $this->app->singleton(FormValidator::class);
        $this->app->singleton(ResourcePersistence::class);
        $this->app->singleton(ViewResolver::class, function ($app) {
            return new ViewResolver($app['view']);
        });
        $this->app->singleton(ResourceRenderer::class);
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ave');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');

        // Register publishing
        $this->registerPublishing();

        // Load resources
        $resourceManager = $this->app->make(ResourceManager::class);
        $resourceManager->loadAll();
    }

    /**
     * Register asset and config publishing
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/ave.php' => config_path('ave.php'),
        ], 'ave-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'ave-migrations');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../../dist' => public_path('vendor/ave'),
        ], 'ave-assets');

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/ave'),
        ], 'ave-views');
    }
}
