<?php

namespace Monstrex\Ave\Console\Commands;

use Illuminate\Console\Command;
use Monstrex\Ave\Database\Seeders\CacheMenuSeeder;

class InstallCommand extends Command
{
    protected $signature = 'ave:install
                            {--force : Overwrite existing files}
                            {--no-migrate : Skip running migrations}';

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

        // Step 3: Run migrations
        if (!$this->option('no-migrate')) {
            $this->newLine();
            $this->comment('Running migrations...');
            $this->call('migrate');
            $this->info('✓ Migrations completed');
        }

        // Step 4: Seed menu items
        $this->newLine();
        $this->comment('Seeding menu items...');
        $this->callSilent('db:seed', ['--class' => CacheMenuSeeder::class]);
        $this->info('✓ Menu items seeded');

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

        $this->info('1. Create your first admin resource in app/Ave/Resources/');
        $this->info('2. Visit /admin in your browser');
        $this->newLine();

        $this->comment('Optional: Publish assets for customization:');
        $this->line('  php artisan vendor:publish --tag=ave-views');
        $this->line('  php artisan vendor:publish --tag=ave-lang');
        $this->line('  php artisan vendor:publish --tag=ave-migrations  # If you need to customize migrations');
    }
}
