<?php

namespace Monstrex\Ave\Core\Manager;

use Monstrex\Ave\Core\Discovery\PageDiscovery;
use Monstrex\Ave\Core\Registry\PageRegistry;
use Monstrex\Ave\Core\Page;

/**
 * Manager for Pages with discovery and registry
 */
class PageManager
{
    protected PageDiscovery $discovery;
    protected PageRegistry $registry;

    public function __construct(
        ?PageDiscovery $discovery = null,
        ?PageRegistry $registry = null
    ) {
        $this->discovery = $discovery ?? new PageDiscovery();
        $this->registry = $registry ?? new PageRegistry();
    }

    /**
     * Add path to discovery
     */
    public function addDiscoveryPath(string $path): self
    {
        $this->discovery->addPath($path);
        return $this;
    }

    /**
     * Discover and register pages
     */
    public function discover(): self
    {
        $discovered = $this->discovery->discover();

        foreach ($discovered as $slug => $pageClass) {
            $this->registry->register($slug, $pageClass);
        }

        return $this;
    }

    /**
     * Register a page
     */
    public function register(string $slug, string $pageClass): self
    {
        $this->registry->register($slug, $pageClass);
        return $this;
    }

    /**
     * Get page class by slug
     */
    public function get(string $slug): ?string
    {
        return $this->registry->get($slug);
    }

    /**
     * Get page instance by slug
     */
    public function getInstance(string $slug): ?Page
    {
        $pageClass = $this->get($slug);

        if (!$pageClass) {
            return null;
        }

        return new $pageClass();
    }

    /**
     * Check if page exists
     */
    public function has(string $slug): bool
    {
        return $this->registry->has($slug);
    }

    /**
     * Get all pages
     */
    public function all(): array
    {
        return $this->registry->all();
    }

    /**
     * Get all page instances
     */
    public function allInstances(): array
    {
        $instances = [];

        foreach ($this->all() as $slug => $pageClass) {
            $instances[$slug] = new $pageClass();
        }

        return $instances;
    }

    /**
     * Get page count
     */
    public function count(): int
    {
        return $this->registry->count();
    }

    /**
     * Get discovery instance
     */
    public function getDiscovery(): PageDiscovery
    {
        return $this->discovery;
    }

    /**
     * Get registry instance
     */
    public function getRegistry(): PageRegistry
    {
        return $this->registry;
    }
}
