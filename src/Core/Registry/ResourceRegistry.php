<?php

namespace Monstrex\Ave\Core\Registry;

use InvalidArgumentException;
use Monstrex\Ave\Core\Resource;

/**
 * Registry for managing Resource classes keyed by slug.
 */
class ResourceRegistry
{
    /**
     * @var array<string,class-string<Resource>>
     */
    protected array $resources = [];

    /**
     * Register a resource class under its slug (or a custom one).
     */
    public function register(string $resourceClass, ?string $slug = null): self
    {
        if (!is_subclass_of($resourceClass, Resource::class)) {
            throw new InvalidArgumentException("Class {$resourceClass} must extend " . Resource::class);
        }

        $slug ??= $resourceClass::getSlug();
        $this->resources[$slug] = $resourceClass;

        return $this;
    }

    /**
     * Unregister a resource by slug.
     */
    public function unregister(string $slug): self
    {
        unset($this->resources[$slug]);

        return $this;
    }

    /**
     * Check if a resource is registered.
     */
    public function has(string $slug): bool
    {
        return isset($this->resources[$slug]);
    }

    /**
     * Get a resource class by slug.
     *
     * @return class-string<Resource>|null
     */
    public function get(string $slug): ?string
    {
        return $this->resources[$slug] ?? null;
    }

    /**
     * Get all registered resources.
     *
     * @return array<string,class-string<Resource>>
     */
    public function all(): array
    {
        return $this->resources;
    }

    /**
     * Registered resource count.
     */
    public function count(): int
    {
        return count($this->resources);
    }

    /**
     * Reset registry.
     */
    public function clear(): self
    {
        $this->resources = [];

        return $this;
    }
}
