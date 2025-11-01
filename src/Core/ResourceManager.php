<?php

namespace Monstrex\Ave\Core;

use Monstrex\Ave\Core\Discovery\AdminResourceDiscovery;
use Monstrex\Ave\Core\Registry\ResourceRegistry;
use Monstrex\Ave\Core\Resource;

/**
 * Manager for Resources with discovery and registry
 */
class ResourceManager
{
    protected ResourceDiscovery $discovery;
    protected ResourceRegistry $registry;

    public function __construct(
        ?ResourceDiscovery $discovery = null,
        ?ResourceRegistry $registry = null
    ) {
        $this->discovery = $discovery ?? new ResourceDiscovery();
        $this->registry = $registry ?? new ResourceRegistry();
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
     * Discover and register resources
     */
    public function discover(): self
    {
        $discovered = $this->discovery->discover();

        foreach ($discovered as $slug => $resourceClass) {
            $this->registry->register($slug, $resourceClass);
        }

        return $this;
    }

    /**
     * Register a resource
     */
    public function register(string $slug, string $resourceClass): self
    {
        $this->registry->register($slug, $resourceClass);
        return $this;
    }

    /**
     * Get resource class by slug
     */
    public function get(string $slug): ?string
    {
        return $this->registry->get($slug);
    }

    /**
     * Get resource instance by slug
     */
    public function getInstance(string $slug): ?Resource
    {
        $resourceClass = $this->get($slug);

        if (!$resourceClass) {
            return null;
        }

        return new $resourceClass();
    }

    /**
     * Check if resource exists
     */
    public function has(string $slug): bool
    {
        return $this->registry->has($slug);
    }

    /**
     * Get all resources
     */
    public function all(): array
    {
        return $this->registry->all();
    }

    /**
     * Get all resource instances
     */
    public function allInstances(): array
    {
        $instances = [];

        foreach ($this->all() as $slug => $resourceClass) {
            $instances[$slug] = new $resourceClass();
        }

        return $instances;
    }

    /**
     * Get resource count
     */
    public function count(): int
    {
        return $this->registry->count();
    }

    /**
     * Get discovery instance
     */
    public function getDiscovery(): ResourceDiscovery
    {
        return $this->discovery;
    }

    /**
     * Get registry instance
     */
    public function getRegistry(): ResourceRegistry
    {
        return $this->registry;
    }
}
