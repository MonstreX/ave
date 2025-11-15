<?php

namespace Monstrex\Ave\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
use Monstrex\Ave\Console\Commands\InstallCommand;
use Monstrex\Ave\Console\Commands\MakeResourceCommand;
use Monstrex\Ave\View\Composers\SidebarComposer;
use Monstrex\Ave\Support\PackageAssets;
use Monstrex\Ave\Routing\RouteRegistrar;
use Monstrex\Ave\Admin\Access\AccessManager;
use Monstrex\Ave\Media\MediaStorage;
use Monstrex\Ave\Core\Media\MediaRepository;
use Monstrex\Ave\Exceptions\AveException;
use Monstrex\Ave\Exceptions\ResourceException;
use RuntimeException;

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
        $this->app->singleton(AccessManager::class);

        $this->app->singleton(ResourceManager::class, function ($app) {
            return new ResourceManager(
                $app->make(AdminResourceDiscovery::class),
                $app->make(ResourceRegistry::class),
                $app->make(AccessManager::class),
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
        $this->app->singleton(MediaRepository::class, function ($app) {
            $mediaModel = config('ave.media_model', 'Monstrex\\Ave\\Models\\Media');

            return new MediaRepository($mediaModel);
        });

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
        $this->registerExceptionHandlers();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ave');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'ave');

        View::composer('ave::partials.sidebar', SidebarComposer::class);

        $this->registerGateIntegration();
        RouteRegistrar::create($this->app['router'])->register();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MakeResourceCommand::class,
            ]);
        }

        // Discover and register resources/pages
        $this->discoverAndRegister();
    }


    /**
     * Register Laravel Gate integration for Ave ACL system
     *
     * This enables canonical Laravel authorization (@can, authorize(), Gate::allows())
     * while using Ave's permission matrix stored in database tables.
     *
     * @return void
     */
    protected function registerGateIntegration(): void
    {
        Gate::before(function ($user, $ability, $arguments) {
            try {
                // Skip if AccessManager is not available (e.g., during testing)
                if (!$this->app->bound(AccessManager::class)) {
                    return null;
                }

                $accessManager = $this->app->make(AccessManager::class);

                // Skip if ACL is disabled
                if (!$accessManager->isEnabled()) {
                    return null; // Let other gates/policies handle
                }

                // Try to extract resource slug from arguments
                $resourceSlug = $this->extractResourceSlug($ability, $arguments);

                if (!$resourceSlug) {
                    return null; // Not an Ave resource check, let other handlers process
                }

                // Check permission through AccessManager
                return $accessManager->allows($user, $resourceSlug, $ability) ?: null;
            } catch (\Throwable $e) {
                // Silently fail and let other handlers process
                // This handles cases where config is not available (e.g., unit tests)
                return null;
            }
        });
    }

    /**
     * Extract resource slug from Gate ability check
     *
     * Supports multiple formats:
     * 1. Ability as "resource_slug.ability" (e.g., "posts.create")
     * 2. Ability as simple name with model/resource in arguments
     *
     * @param string $ability
     * @param array $arguments
     * @return string|null
     */
    protected function extractResourceSlug(string $ability, array $arguments): ?string
    {
        // Format 1: "resource_slug.ability"
        if (str_contains($ability, '.')) {
            $parts = explode('.', $ability, 2);
            return $parts[0] ?? null;
        }

        // Format 2: Extract from model class or resource instance in arguments
        if (empty($arguments)) {
            return null;
        }

        $firstArg = $arguments[0] ?? null;

        // If it's a Resource instance
        if (is_object($firstArg) && method_exists($firstArg, 'getSlug')) {
            return $firstArg::getSlug();
        }

        // If it's an Eloquent model, try to find corresponding resource
        if (is_object($firstArg) && $firstArg instanceof \Illuminate\Database\Eloquent\Model) {
            $modelClass = get_class($firstArg);
            return $this->findResourceSlugByModel($modelClass);
        }

        // If it's a model class name (string)
        if (is_string($firstArg) && class_exists($firstArg)) {
            return $this->findResourceSlugByModel($firstArg);
        }

        return null;
    }

    /**
     * Find resource slug by model class
     *
     * @param string $modelClass
     * @return string|null
     */
    protected function findResourceSlugByModel(string $modelClass): ?string
    {
        if (!$this->app->bound(ResourceManager::class)) {
            return null;
        }

        $resourceManager = $this->app->make(ResourceManager::class);
        $resources = $resourceManager->all();

        foreach ($resources as $slug => $resourceClass) {
            if (!class_exists($resourceClass)) {
                continue;
            }

            $resource = new $resourceClass();
            if (isset($resource::$model) && $resource::$model === $modelClass) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Register exception handlers for Ave exceptions
     *
     * @return void
     */
    protected function registerExceptionHandlers(): void
    {
        $exceptionHandler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        // Handle all Ave exceptions (AveException and subclasses)
        $exceptionHandler->renderable(
            function (AveException $e, $request) {
                // Only handle Ave admin routes
                if (! $this->isAveRequest($request)) {
                    return null; // Let other handlers process
                }

                $statusCode = $e->getStatusCode();
                $message = $e->getMessage();
                $defaultMessages = [
                    403 => 'You don\'t have permission to access this resource.',
                    404 => 'The page you\'re looking for doesn\'t exist.',
                    422 => 'Validation failed. Please check your input.',
                    500 => 'Something went wrong on our end.',
                ];

                // Use custom message or default
                $message = $message ?: ($defaultMessages[$statusCode] ?? 'An error occurred.');

                // Handle AJAX/API requests - return JSON
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'code' => $statusCode,
                    ], $statusCode);
                }

                // Handle regular requests - return HTML error view
                return response()->view('ave::errors.' . $statusCode, [
                    'code' => $statusCode,
                    'message' => $message,
                    'exception' => config('app.debug') ? $e : null,
                ], $statusCode);
            }
        );

        // Handle all other exceptions in Ave admin routes when not in debug mode
        if (!config('app.debug')) {
            $exceptionHandler->renderable(
                function (\Throwable $e, $request) {
                    // Only handle Ave admin routes
                    if (! $this->isAveRequest($request)) {
                        return null; // Let other handlers process
                    }

                    $statusCode = 500;
                    $message = 'Something went wrong on our end.';

                    // Handle AJAX/API requests - return JSON
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => $message,
                            'code' => $statusCode,
                        ], $statusCode);
                    }

                    // Handle regular requests - return HTML error view
                    return response()->view('ave::errors.' . $statusCode, [
                        'code' => $statusCode,
                        'message' => $message,
                        'exception' => null,
                    ], $statusCode);
                }
            );
        }
    }

    /**
     * Discover and register resources and pages
     *
     * @return void
     */
    protected function discoverAndRegister(): void
    {
        $discovered = $this->performDiscovery();
        $this->registerDiscovered($discovered);
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

        // Publish translations
        $this->publishes([
            $packagePath . 'lang' => $this->app->langPath('vendor/ave'),
        ], 'ave-lang');

        // Publish stubs
        $this->publishes([
            $packagePath . 'stubs' => base_path('stubs/ave'),
        ], 'ave-stubs');

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

    /**
     * Determine if request belongs to Ave admin prefix.
     */
    protected function isAveRequest(Request $request): bool
    {
        foreach ($this->aveRoutePatterns() as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build glob patterns for current Ave route prefix.
     *
     * @return array<int,string>
     */
    protected function aveRoutePatterns(): array
    {
        $prefix = trim((string) config('ave.route_prefix', 'admin'), '/');

        if ($prefix === '') {
            return ['admin', 'admin/*'];
        }

        return [$prefix, "{$prefix}/*"];
    }
}
