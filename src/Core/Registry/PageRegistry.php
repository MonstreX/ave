<?php

namespace Monstrex\Ave\Core\Registry;

use Monstrex\Ave\Core\Page;

/**
 * Registry for managing Page instances
 */
class PageRegistry
{
    protected array $pages = [];

    /**
     * Register a page class
     */
    public function register(string $slug, string $pageClass): self
    {
        if (!is_subclass_of($pageClass, Page::class)) {
            throw new \InvalidArgumentException("Class {$pageClass} must extend " . Page::class);
        }

        $this->pages[$slug] = $pageClass;
        return $this;
    }

    /**
     * Unregister a page
     */
    public function unregister(string $slug): self
    {
        unset($this->pages[$slug]);
        return $this;
    }

    /**
     * Check if page is registered
     */
    public function has(string $slug): bool
    {
        return isset($this->pages[$slug]);
    }

    /**
     * Get a page class
     */
    public function get(string $slug): ?string
    {
        return $this->pages[$slug] ?? null;
    }

    /**
     * Get all registered pages
     */
    public function all(): array
    {
        return $this->pages;
    }

    /**
     * Get page count
     */
    public function count(): int
    {
        return count($this->pages);
    }

    /**
     * Clear all pages
     */
    public function clear(): self
    {
        $this->pages = [];
        return $this;
    }
}
