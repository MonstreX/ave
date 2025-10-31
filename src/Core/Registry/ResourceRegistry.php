<?php

namespace Monstrex\Ave\Core\Registry;

use Monstrex\Ave\Core\Resource;

/**
 * Registry for managing Resource instances
 */
class ResourceRegistry
{
    protected array $resources = [];

    /**
     * Register a resource class
     */
    public function register(string $slug, string $resourceClass): self
    {
        if (!is_subclass_of($resourceClass, Resource::class)) {
            throw new \InvalidArgumentException("Class {$resourceClass} must extend " . Resource::class);
        }

        $this->resources[$slug] = $resourceClass;
        return $this;
    }

    /**
     * Unregister a resource
     */
    public function unregister(string $slug): self
    {
        unset($this->resources[$slug]);
        return $this;
    }

    /**
     * Check if resource is registered
     */
    public function has(string $slug): bool
    {
        return isset($this->resources[$slug]);
    }

    /**
     * Get a resource class
     */
    public function get(string $slug): ?string
    {
        return $this->resources[$slug] ?? null;
    }

    /**
     * Get all registered resources
     */
    public function all(): array
    {
        return $this->resources;
    }

    /**
     * Get resource count
     */
    public function count(): int
    {
        return count($this->resources);
    }

    /**
     * Clear all resources
     */
    public function clear(): self
    {
        $this->resources = [];
        return $this;
    }
}
