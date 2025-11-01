<?php

namespace Monstrex\Ave\Core\Discovery;

use Monstrex\Ave\Core\Page;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Discovers Page classes in the application
 */
class AdminPageDiscovery
{
    protected array $discoveredPages = [];
    protected array $paths = [];

    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
    }

    /**
     * Add a path to search for pages
     */
    public function addPath(string $path): self
    {
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }
        return $this;
    }

    /**
     * Discover all Page classes in configured paths
     */
    public function discover(): array
    {
        $this->discoveredPages = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $this->scanDirectory($path);
        }

        return $this->discoveredPages;
    }

    /**
     * Scan directory for Page classes
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
     * Check if a file contains a Page class
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

            if (is_subclass_of($className, Page::class)) {
                $this->discoveredPages[$className::slug()] = $className;
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
     * Get all discovered pages
     */
    public function getPages(): array
    {
        return $this->discoveredPages;
    }

    /**
     * Get page by slug
     */
    public function getPage(string $slug): ?string
    {
        return $this->discoveredPages[$slug] ?? null;
    }
}
