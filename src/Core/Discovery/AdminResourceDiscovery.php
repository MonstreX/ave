<?php

namespace Monstrex\Ave\Core\Discovery;

use Monstrex\Ave\Core\Resource;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Discovers Resource classes in the application and package.
 */
class AdminResourceDiscovery
{
    protected array $discoveredResources = [];
    protected array $paths = [];
    protected bool $bootstrapped = false;

    public function __construct(array $paths = [])
    {
        $this->paths = array_values(array_filter($paths, fn ($path) => $path !== null));
    }

    public function addPath(string $path): self
    {
        $normalized = $this->normalizePath($path);

        if ($normalized && !in_array($normalized, $this->paths, true)) {
            $this->paths[] = $normalized;
        }

        return $this;
    }

    /**
     * Discover all Resource classes in configured paths.
     *
     * @return array<string,class-string<Resource>>
     */
    public function discover(): array
    {
        $this->discoveredResources = [];

        $this->ensureDefaultPaths();

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $this->scanDirectory($path);
        }

        return $this->discoveredResources;
    }

    /**
     * @return array<string,class-string<Resource>>
     */
    public function getResources(): array
    {
        return $this->discoveredResources;
    }

    /**
     * @return class-string<Resource>|null
     */
    public function getResource(string $slug): ?string
    {
        return $this->discoveredResources[$slug] ?? null;
    }

    protected function scanDirectory(string $path): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->checkFile($file);
        }
    }

    protected function checkFile(SplFileInfo $file): void
    {
        $className = $this->getClassNameFromFile($file);

        if (!$className || !class_exists($className)) {
            return;
        }

        try {
            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                return;
            }

            if (is_subclass_of($className, Resource::class)) {
                $this->discoveredResources[$className::getSlug()] = $className;
            }
        } catch (\Throwable) {
            // Ignore classes that cannot be reflected.
        }
    }

    protected function getClassNameFromFile(SplFileInfo $file): ?string
    {
        $content = @file_get_contents($file->getRealPath());

        if ($content === false) {
            return null;
        }

        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }

        if (!preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        $namespace = trim($namespaceMatches[1]);
        $className = trim($classMatches[1]);

        return sprintf('%s\\%s', $namespace, $className);
    }

    protected function ensureDefaultPaths(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        $defaults = [
            $this->normalizePath(__DIR__ . '/../../Resources'),
            $this->normalizePath(app_path('Ave/Resources')),
        ];

        foreach ($defaults as $path) {
            if ($path && !in_array($path, $this->paths, true)) {
                $this->paths[] = $path;
            }
        }

        $this->bootstrapped = true;
    }

    protected function normalizePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return rtrim($path, DIRECTORY_SEPARATOR);
    }
}
