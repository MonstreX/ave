<?php

namespace Monstrex\Ave\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateUserModelCommand extends Command
{
    protected $signature = 'ave:update-user-model
                            {--force : Overwrite existing modifications}';

    protected $description = 'Update User model to include locale field in fillable array';

    public function handle(): int
    {
        $this->info('Updating User model...');
        $this->newLine();

        // Get user model path
        $userModelPath = app_path('Models/User.php');

        if (!File::exists($userModelPath)) {
            $this->error('User model not found at: ' . $userModelPath);
            return self::FAILURE;
        }

        // Read the file
        $content = File::get($userModelPath);

        // Check if locale is already in fillable
        if (str_contains($content, "'locale'") || str_contains($content, '"locale"')) {
            $this->info('✓ Field "locale" is already in the User model');
            return self::SUCCESS;
        }

        // Find the $fillable array and add locale
        if (preg_match("/protected\s+\\\$fillable\s*=\s*\[([^\]]*?)\];/s", $content, $matches)) {
            $oldFillable = $matches[0];
            $items = $matches[1];

            // Check if it already has locale
            if (str_contains($items, 'locale')) {
                $this->info('✓ Field "locale" is already in the User model');
                return self::SUCCESS;
            }

            // Add locale to fillable
            $newFillable = str_replace(
                $oldFillable,
                "protected \$fillable = [\n        'name',\n        'email',\n        'password',\n        'locale',\n    ];",
                $content
            );

            // If that didn't work, try more flexible approach
            if ($newFillable === $content) {
                $this->warn('Could not automatically update $fillable array.');
                $this->info('Please manually add "locale" to the $fillable array in: ' . $userModelPath);
                return self::FAILURE;
            }

            // Write back
            File::put($userModelPath, $newFillable);

            $this->info('✓ User model updated successfully');
            $this->line('  Added "locale" to $fillable array');

            return self::SUCCESS;
        }

        $this->warn('Could not find $fillable array in User model.');
        $this->info('Please manually add "locale" to the $fillable array:');
        $this->line('  protected $fillable = [');
        $this->line('      "name",');
        $this->line('      "email",');
        $this->line('      "password",');
        $this->line('      "locale",  // Add this line');
        $this->line('  ];');

        return self::FAILURE;
    }
}
