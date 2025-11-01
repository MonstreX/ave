<?php

namespace Monstrex\Ave\Core\Registry;

use InvalidArgumentException;
use Monstrex\Ave\Core\Page;

/**
 * Registry for managing Page classes keyed by slug.
 */
class PageRegistry
{
    /**
     * @var array<string,class-string<Page>>
     */
    protected array $pages = [];

    /**
     * Register a page class under its slug (or a custom one).
     */
    public function register(string $pageClass, ?string $slug = null): self
    {
        if (!is_subclass_of($pageClass, Page::class)) {
            throw new InvalidArgumentException("Class {$pageClass} must extend " . Page::class);
        }

        $slug ??= $pageClass::getSlug();
        $this->pages[$slug] = $pageClass;

        return $this;
    }

    /**
     * Unregister a page by slug.
     */
    public function unregister(string $slug): self
    {
        unset($this->pages[$slug]);

        return $this;
    }

    /**
     * Check if a page is registered.
     */
    public function has(string $slug): bool
    {
        return isset($this->pages[$slug]);
    }

    /**
     * Get a page class by slug.
     *
     * @return class-string<Page>|null
     */
    public function get(string $slug): ?string
    {
        return $this->pages[$slug] ?? null;
    }

    /**
     * Get all registered pages.
     *
     * @return array<string,class-string<Page>>
     */
    public function all(): array
    {
        return $this->pages;
    }

    /**
     * Registered page count.
     */
    public function count(): int
    {
        return count($this->pages);
    }

    /**
     * Reset registry.
     */
    public function clear(): self
    {
        $this->pages = [];

        return $this;
    }
}
