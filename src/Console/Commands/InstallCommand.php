<?php

namespace Monstrex\Ave\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Monstrex\Ave\Database\Seeders\AveDatabaseSeeder;
use Monstrex\Ave\Models\Role;
use Monstrex\Ave\Models\User;

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

        // Step 4: Seed baseline data
        $this->newLine();
        $this->comment('Seeding default data...');
        $this->call(AveDatabaseSeeder::class);
        $this->info('✓ Default data seeded');

        // Step 5: Create admin user
        $this->newLine();
        $this->comment('Creating admin user...');

        if (!$this->createAdminUser()) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Ave Admin Panel installed successfully!');
        $this->newLine();

        // Show next steps
        $this->showNextSteps();

        return self::SUCCESS;
    }

    protected function createAdminUser(): bool
    {
        $name = $this->ask('Enter admin name', 'admin');
        $email = $this->ask('Enter admin email', 'admin@email.com');
        $password = $this->secret('Enter admin password');
        $passwordConfirmation = $this->secret('Confirm password');

        if ($password !== $passwordConfirmation) {
            $this->error('✗ Passwords do not match!');
            return false;
        }

        if (empty($password)) {
            $this->error('✗ Password cannot be empty!');
            return false;
        }

        // Get user model from config
        $userModel = config('ave.user_model', User::class);

        // Check if user exists
        $existingUser = $userModel::where('email', $email)->first();

        if ($existingUser) {
            $this->warn('User with this email already exists.');
            if (!$this->confirm('Do you want to assign admin role to existing user?', true)) {
                $this->info('✓ Admin user creation skipped');
                return true;
            }
            $user = $existingUser;
        } else {
            // Create new user
            $user = $userModel::create([
                'name' => $name,
                'email' => $email,
                'password' => $password, // Auto-hashed by setPasswordAttribute
            ]);
        }

        // Attach admin role
        $adminRole = Role::where('slug', 'admin')->first();

        if (!$adminRole) {
            $this->error('✗ Admin role not found! Please run migrations first.');
            return false;
        }

        if (!DB::table('ave_role_user')
                ->where('user_id', $user->id)
                ->where('role_id', $adminRole->id)
                ->exists()) {
            DB::table('ave_role_user')->insert([
                'user_id' => $user->id,
                'role_id' => $adminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info('✓ Admin user created');
        return true;
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
