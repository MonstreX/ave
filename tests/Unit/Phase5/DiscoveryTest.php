<?php

namespace Monstrex\Ave\Tests\Unit\Phase5;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Discovery\ResourceDiscovery;
use Monstrex\Ave\Core\Discovery\PageDiscovery;

class DiscoveryTest extends TestCase
{
    public function test_resource_discovery_can_be_created()
    {
        $discovery = new ResourceDiscovery();
        $this->assertInstanceOf(ResourceDiscovery::class, $discovery);
    }

    public function test_resource_discovery_add_path()
    {
        $discovery = new ResourceDiscovery();
        $discovery->addPath(__DIR__);

        $this->assertInstanceOf(ResourceDiscovery::class, $discovery);
    }

    public function test_resource_discovery_fluent_interface()
    {
        $discovery = new ResourceDiscovery();
        $result = $discovery->addPath(__DIR__)->addPath(__DIR__);

        $this->assertInstanceOf(ResourceDiscovery::class, $result);
    }

    public function test_resource_discovery_discover_returns_array()
    {
        $discovery = new ResourceDiscovery();
        $resources = $discovery->discover();

        $this->assertIsArray($resources);
    }

    public function test_resource_discovery_get_resources()
    {
        $discovery = new ResourceDiscovery();
        $resources = $discovery->getResources();

        $this->assertIsArray($resources);
    }

    public function test_page_discovery_can_be_created()
    {
        $discovery = new PageDiscovery();
        $this->assertInstanceOf(PageDiscovery::class, $discovery);
    }

    public function test_page_discovery_add_path()
    {
        $discovery = new PageDiscovery();
        $discovery->addPath(__DIR__);

        $this->assertInstanceOf(PageDiscovery::class, $discovery);
    }

    public function test_page_discovery_fluent_interface()
    {
        $discovery = new PageDiscovery();
        $result = $discovery->addPath(__DIR__)->addPath(__DIR__);

        $this->assertInstanceOf(PageDiscovery::class, $result);
    }

    public function test_page_discovery_discover_returns_array()
    {
        $discovery = new PageDiscovery();
        $pages = $discovery->discover();

        $this->assertIsArray($pages);
    }

    public function test_page_discovery_get_pages()
    {
        $discovery = new PageDiscovery();
        $pages = $discovery->getPages();

        $this->assertIsArray($pages);
    }
}
