<?php

namespace Monstrex\Ave\Core;

use Monstrex\Ave\Admin\Access\AccessManager;
use Monstrex\Ave\Core\Discovery\AdminResourceDiscovery;
use Monstrex\Ave\Core\Registry\ResourceRegistry;

/**
 * Facade for discovering and accessing resource classes.
 */
class ResourceManager
{
    public function __construct(
        protected AdminResourceDiscovery $discovery,
        protected ResourceRegistry $registry,
        protected ?AccessManager $accessManager = null,
    ) {}

    /**
     * Register an additional discovery path (eg. custom package).
     */
    public function addDiscoveryPath(string $path): self
    {
        $this->discovery->addPath($path);

        return $this;
    }

    /**
     * Run discovery and register all found resources.
     */
    public function discover(): self
    {
        foreach ($this->discovery->discover() as $slug => $resourceClass) {
            $this->register($resourceClass, $slug);
        }

        return $this;
    }

    /**
     * Manually register a resource class.
     */
    public function register(string $resourceClass, ?string $slug = null): self
    {
        $resolvedSlug = $slug;

        if (class_exists($resourceClass) && is_subclass_of($resourceClass, Resource::class)) {
            $resolvedSlug ??= $resourceClass::getSlug();

            $this->registry->register($resourceClass, $resolvedSlug);

            if ($this->accessManager) {
                $this->accessManager->registerPermissions($resolvedSlug, $resourceClass::permissionAbilities());
            }

            return $this;
        }

        $resolvedSlug ??= $resourceClass;
        $this->registry->register($resourceClass, $resolvedSlug);

        return $this;
    }

    /**
     * Resolve resource class by slug.
     *
     * @return class-string<Resource>|null
     */
    public function resource(string $slug): ?string
    {
        return $this->registry->get($slug);
    }

    /**
     * Instantiate resource by slug.
     */
    public function instance(string $slug): ?Resource
    {
        $class = $this->resource($slug);

        return $class ? new $class() : null;
    }

    /**
     * Check if resource exists.
     */
    public function has(string $slug): bool
    {
        return $this->registry->has($slug);
    }

    /**
     * List registered resources.
     *
     * @return array<string,class-string<Resource>>
     */
    public function all(): array
    {
        return $this->registry->all();
    }

    public function count(): int
    {
        return $this->registry->count();
    }

    public function registry(): ResourceRegistry
    {
        return $this->registry;
    }

    public function discovery(): AdminResourceDiscovery
    {
        return $this->discovery;
    }
}
