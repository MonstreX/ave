<?php

namespace Monstrex\Ave\Core\Discovery;

use Monstrex\Ave\Core\Resource;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Discovers Resource classes in the application
 */
class ResourceDiscovery
{
    protected array $discoveredResources = [];
    protected array $paths = [];

    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
    }

    /**
     * Add a path to search for resources
     */
    public function addPath(string $path): self
    {
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }
        return $this;
    }

    /**
     * Discover all Resource classes in configured paths
     */
    public function discover(): array
    {
        $this->discoveredResources = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $this->scanDirectory($path);
        }

        return $this->discoveredResources;
    }

    /**
     * Scan directory for Resource classes
     */
    protected function scanDirectory(string $path): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->checkFile($file);
        }
    }

    /**
     * Check if a file contains a Resource class
     */
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
        } catch (\Exception $e) {
            // Skip classes that cannot be reflected
        }
    }

    /**
     * Get class name from file path
     */
    protected function getClassNameFromFile(SplFileInfo $file): ?string
    {
        $content = file_get_contents($file->getRealPath());

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
        } else {
            return null;
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = trim($matches[1]);
        } else {
            return null;
        }

        return $namespace . '\\' . $className;
    }

    /**
     * Get all discovered resources
     */
    public function getResources(): array
    {
        return $this->discoveredResources;
    }

    /**
     * Get resource by slug
     */
    public function getResource(string $slug): ?string
    {
        return $this->discoveredResources[$slug] ?? null;
    }
}
