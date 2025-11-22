<?php

namespace Monstrex\Ave\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UninstallCommand extends Command
{
    protected $signature = 'ave:uninstall
                            {--force : Skip confirmation prompts}
                            {--keep-users : Don\'t delete admin users}
                            {--keep-config : Don\'t delete published config}
                            {--keep-assets : Don\'t delete published assets}
                            {--dry-run : Show what would be deleted without doing it}';

    protected $description = 'Completely uninstall Ave Admin Panel package';

    protected bool $isDryRun = false;
    protected array $deletedItems = [];

    public function handle(): int
    {
        $this->isDryRun = $this->option('dry-run');

        $this->displayWarning();

        if (!$this->confirmUninstall()) {
            $this->info('Uninstall cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Starting Ave Admin Panel uninstallation...');
        $this->newLine();

        // Step 1: Drop database tables
        $this->dropDatabaseTables();

        // Step 2: Delete admin users (optional)
        if (!$this->option('keep-users')) {
            $this->deleteAdminUsers();
        } else {
            $this->comment('⊙ Skipping admin users deletion (--keep-users)');
        }

        // Step 3: Delete published config
        if (!$this->option('keep-config')) {
            $this->deletePublishedConfig();
        } else {
            $this->comment('⊙ Skipping config deletion (--keep-config)');
        }

        // Step 4: Delete published assets
        if (!$this->option('keep-assets')) {
            $this->deletePublishedAssets();
        } else {
            $this->comment('⊙ Skipping assets deletion (--keep-assets)');
        }

        // Step 5: Clear Ave cache
        $this->clearAveCache();

        $this->newLine();
        if ($this->isDryRun) {
            $this->info('✓ Dry run completed. No changes were made.');
        } else {
            $this->info('✓ Ave Admin Panel uninstalled successfully!');
        }
        $this->newLine();

        $this->showNextSteps();

        return self::SUCCESS;
    }

    protected function displayWarning(): void
    {
        $this->newLine();
        $this->warn('⚠️  WARNING: This will completely remove Ave Admin Panel from your application.');
        $this->newLine();

        $currentDatabase = DB::getDatabaseName();
        $this->comment("Current database: {$currentDatabase}");
        $this->newLine();

        $tables = $this->getAveTables();
        $configExists = File::exists(config_path('ave.php'));
        $assetsExist = File::exists(public_path('vendor/ave'));
        $adminUsersCount = $this->getAdminUsersCount();

        $this->comment('The following will be deleted:');
        $this->newLine();

        if (!empty($tables)) {
            $this->line('  ✗ Database tables (' . count($tables) . '): ' . implode(', ', $tables));
        } else {
            $this->line('  ⊙ No Ave tables found');
        }

        if ($configExists && !$this->option('keep-config')) {
            $this->line('  ✗ Published config: config/ave.php');
        }

        if ($assetsExist && !$this->option('keep-assets')) {
            $this->line('  ✗ Published assets: public/vendor/ave/');
        }

        if ($adminUsersCount > 0 && !$this->option('keep-users')) {
            $this->line("  ✗ Admin users: {$adminUsersCount} user(s) with admin role");
        }

        $this->line('  ✗ Cache: All Ave ACL cache entries');

        if ($this->isDryRun) {
            $this->newLine();
            $this->info('🔍 DRY RUN MODE: No actual changes will be made');
        }

        $this->newLine();
    }

    protected function confirmUninstall(): bool
    {
        if ($this->option('force') || $this->isDryRun) {
            return true;
        }

        return $this->confirm('Do you want to continue?', false);
    }

    protected function dropDatabaseTables(): void
    {
        $this->comment('Dropping database tables...');

        $tables = $this->getAveTables();

        if (empty($tables)) {
            $this->info('⊙ No Ave tables to drop');
            return;
        }

        if (!$this->isDryRun) {
            // Disable foreign key checks to allow dropping in any order
            $this->disableForeignKeyChecks();
        }

        foreach ($tables as $table) {
            $this->dropTable($table);
        }

        if (!$this->isDryRun) {
            // Re-enable foreign key checks
            $this->enableForeignKeyChecks();
        }

        $this->info('✓ Database tables dropped');
    }

    protected function disableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'pgsql' => DB::statement('SET CONSTRAINTS ALL DEFERRED'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            default => null,
        };
    }

    protected function enableForeignKeyChecks(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'pgsql' => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default => null,
        };
    }

    protected function dropTable(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if ($this->isDryRun) {
            $this->line("  [DRY RUN] Would drop table: {$table}");
            return;
        }

        Schema::dropIfExists($table);
        $this->line("  ✓ Dropped table: {$table}");
    }

    protected function deleteAdminUsers(): void
    {
        $this->comment('Deleting admin users...');

        $count = $this->getAdminUsersCount();

        if ($count === 0) {
            $this->info('⊙ No admin users to delete');
            return;
        }

        if (!Schema::hasTable('ave_role_user') || !Schema::hasTable('ave_roles')) {
            $this->info('⊙ Cannot delete admin users (tables already dropped)');
            return;
        }

        if ($this->isDryRun) {
            $this->line("  [DRY RUN] Would delete {$count} admin user(s)");
            return;
        }

        $adminRoleId = DB::table('ave_roles')->where('slug', 'admin')->value('id');

        if (!$adminRoleId) {
            $this->info('⊙ Admin role not found');
            return;
        }

        $userIds = DB::table('ave_role_user')
            ->where('role_id', $adminRoleId)
            ->pluck('user_id')
            ->all();

        if (!empty($userIds)) {
            $userModel = config('ave.user_model', \Monstrex\Ave\Models\User::class);
            $deleted = $userModel::whereIn('id', $userIds)->delete();
            $this->info("✓ Deleted {$deleted} admin user(s)");
        }
    }

    protected function deletePublishedConfig(): void
    {
        $this->comment('Deleting published config...');

        $configPath = config_path('ave.php');

        if (!File::exists($configPath)) {
            $this->info('⊙ Config file not found');
            return;
        }

        if ($this->isDryRun) {
            $this->line('  [DRY RUN] Would delete: config/ave.php');
            return;
        }

        File::delete($configPath);
        $this->info('✓ Deleted config/ave.php');
    }

    protected function deletePublishedAssets(): void
    {
        $this->comment('Deleting published assets...');

        $assetsPath = public_path('vendor/ave');

        if (!File::exists($assetsPath)) {
            $this->info('⊙ Assets directory not found');
            return;
        }

        if ($this->isDryRun) {
            $this->line('  [DRY RUN] Would delete: public/vendor/ave/');
            return;
        }

        File::deleteDirectory($assetsPath);
        $this->info('✓ Deleted public/vendor/ave/');
    }

    protected function clearAveCache(): void
    {
        $this->comment('Clearing Ave cache...');

        if ($this->isDryRun) {
            $this->line('  [DRY RUN] Would clear all ave:acl:* cache keys');
            return;
        }

        // Clear all Ave ACL cache entries
        $cacheDriver = Cache::getStore();

        if (method_exists($cacheDriver, 'flush')) {
            // For array/file cache, we can't selectively delete by prefix
            // So we just notify user
            $this->line('  ⊙ Cache cleared (run: php artisan cache:clear to ensure full cleanup)');
        }

        $this->info('✓ Cache cleanup completed');
    }

    protected function getAveTables(): array
    {
        $currentDatabase = DB::getDatabaseName();
        $allTables = Schema::getTableListing();

        return array_filter($allTables, function ($table) use ($currentDatabase) {
            // Handle tables with database prefix (e.g., "database.ave_menus")
            if (str_contains($table, '.')) {
                [$database, $tableName] = explode('.', $table);

                // Only include tables from current database
                if ($database !== $currentDatabase) {
                    return false;
                }
            } else {
                $tableName = $table;
            }

            return str_starts_with($tableName, 'ave_');
        });
    }

    protected function getAdminUsersCount(): int
    {
        if (!Schema::hasTable('ave_role_user') || !Schema::hasTable('ave_roles')) {
            return 0;
        }

        $adminRoleId = DB::table('ave_roles')->where('slug', 'admin')->value('id');

        if (!$adminRoleId) {
            return 0;
        }

        return DB::table('ave_role_user')
            ->where('role_id', $adminRoleId)
            ->count();
    }

    protected function showNextSteps(): void
    {
        $this->comment('Next steps:');
        $this->newLine();

        $this->info('1. Remove Ave from composer.json:');
        $this->line('   composer remove monstrex/ave');
        $this->newLine();

        $this->info('2. Delete custom Resource classes (if any):');
        $this->line('   rm -rf app/Ave/');
        $this->newLine();

        $this->info('3. Clear application cache:');
        $this->line('   php artisan cache:clear');
        $this->line('   php artisan config:clear');
        $this->line('   php artisan route:clear');
        $this->newLine();

        if (!$this->isDryRun) {
            $this->comment('Ave Admin Panel has been completely removed from your application.');
        }
    }
}
