<?php

namespace Monstrex\Ave\Core;

use Monstrex\Ave\Core\Discovery\AdminPageDiscovery;
use Monstrex\Ave\Core\Registry\PageRegistry;

/**
 * Facade for discovering and accessing page classes.
 */
class PageManager
{
    public function __construct(
        protected AdminPageDiscovery $discovery,
        protected PageRegistry $registry,
    ) {}

    public function addDiscoveryPath(string $path): self
    {
        $this->discovery->addPath($path);

        return $this;
    }

    public function discover(): self
    {
        foreach ($this->discovery->discover() as $slug => $pageClass) {
            $this->registry->register($pageClass, $slug);
        }

        return $this;
    }

    public function register(string $pageClass, ?string $slug = null): self
    {
        $this->registry->register($pageClass, $slug);

        return $this;
    }

    /**
     * @return class-string<Page>|null
     */
    public function page(string $slug): ?string
    {
        return $this->registry->get($slug);
    }

    public function instance(string $slug): ?Page
    {
        $class = $this->page($slug);

        return $class ? new $class() : null;
    }

    public function has(string $slug): bool
    {
        return $this->registry->has($slug);
    }

    /**
     * @return array<string,class-string<Page>>
     */
    public function all(): array
    {
        return $this->registry->all();
    }

    public function count(): int
    {
        return $this->registry->count();
    }

    public function registry(): PageRegistry
    {
        return $this->registry;
    }

    public function discovery(): AdminPageDiscovery
    {
        return $this->discovery;
    }
}
