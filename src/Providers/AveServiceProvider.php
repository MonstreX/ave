<?php

namespace Monstrex\Ave\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * AveServiceProvider Class
 *
 * Main service provider for the Ave admin panel package.
 * Registers all services, loads configuration, migrations, and views.
 *
 * NOTE: This is a minimal version for PHASE-0. Full registration happens in PHASE-9.
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
        // Load config - will be created in PHASE-9
        if (file_exists(__DIR__ . '/../../config/ave.php')) {
            $this->mergeConfigFrom(__DIR__ . '/../../config/ave.php', 'ave');
        }
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        // Minimal boot for PHASE-0
        // Full implementation comes in PHASE-9 after all components are built
    }

    /**
     * Register asset and config publishing
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        // Will be implemented in PHASE-9
    }
}
