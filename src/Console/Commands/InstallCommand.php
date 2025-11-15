<?php

namespace Monstrex\Ave\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'ave:install
                            {--force : Overwrite existing files}
                            {--skip-migrations : Skip publishing migrations}';

    protected $description = 'Install Ave Admin Panel package';

    public function handle(): int
    {
        $this->info('Installing Ave Admin Panel...');
        $this->newLine();

        // Step 1: Publish configuration
        $this->comment('Publishing configuration...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'ave-config',
            '--force' => $this->option('force'),
        ]);
        $this->info('✓ Configuration published');

        // Step 2: Publish assets
        $this->comment('Publishing assets...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'ave-assets',
            '--force' => true, // Always force assets
        ]);
        $this->info('✓ Assets published');

        // Step 3: Publish migrations (optional)
        if (!$this->option('skip-migrations')) {
            $this->comment('Publishing migrations...');
            $this->callSilent('vendor:publish', [
                '--tag' => 'ave-migrations',
                '--force' => $this->option('force'),
            ]);
            $this->info('✓ Migrations published');
        }

        $this->newLine();
        $this->info('Ave Admin Panel installed successfully!');
        $this->newLine();

        // Show next steps
        $this->showNextSteps();

        return self::SUCCESS;
    }

    protected function showNextSteps(): void
    {
        $this->comment('Next steps:');
        $this->newLine();

        // Check if config exists and is configured
        $envPath = base_path('.env');
        $envContent = File::exists($envPath) ? File::get($envPath) : '';
        $guardConfigured = str_contains($envContent, 'AVE_AUTH_GUARD');

        if (!$guardConfigured) {
            $this->warn('1. Configure authentication guard in config/auth.php:');
            $this->line('   Add to guards array:');
            $this->line("   'ave' => [");
            $this->line("       'driver' => 'session',");
            $this->line("       'provider' => 'users',");
            $this->line("   ],");
            $this->newLine();
            $this->warn('2. Set AVE_AUTH_GUARD in your .env file:');
            $this->line('   AVE_AUTH_GUARD=ave');
            $this->newLine();
        }

        if (!$this->option('skip-migrations')) {
            $this->warn('3. Run migrations:');
            $this->line('   php artisan migrate');
            $this->newLine();
        }

        $this->info('4. Create your first admin resource in app/Ave/Resources/');
        $this->newLine();
        $this->info('5. Visit /admin in your browser');
        $this->newLine();

        $this->comment('Optional: Publish views and translations for customization:');
        $this->line('  php artisan vendor:publish --tag=ave-views');
        $this->line('  php artisan vendor:publish --tag=ave-lang');
    }
}
