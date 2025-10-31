<?php

namespace Monstrex\Ave\Support;

use Illuminate\Support\Facades\File;

class PackageAssets
{
    protected const BASE_PATH = __DIR__.'/../..';

    public static function configs(): array
    {
        return [
            self::path('config/ave.php') => config_path('ave.php'),
        ];
    }

    public static function migrations(): array
    {
        return [
            self::path('migrations') => database_path('migrations'),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function assets(): array
    {
        // Publish the entire dist/ folder to public/vendor/ave
        $source = self::path('dist');
        $destination = public_path('vendor/ave');

        return is_dir($source) ? [$source => $destination] : [];
    }

    /**
     * @param  array<string,string>  $directories
     */
    public static function cleanAssetTargets(array $directories): void
    {
        foreach ($directories as $destination) {
            if (File::isDirectory($destination)) {
                File::deleteDirectory($destination);

                continue;
            }

            if (File::exists($destination)) {
                File::delete($destination);
            }
        }
    }

    protected static function path(string $relative): string
    {
        return rtrim(self::BASE_PATH.'/'.ltrim($relative, '/'), '/');
    }
}
