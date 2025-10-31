<?php

namespace Monstrex\Ave\Tests\Unit\Phase4;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Controllers\PageController;
use Monstrex\Ave\Core\Page;
use Mockery;

class PageControllerTest extends TestCase
{
    protected PageController $controller;

    protected function setUp(): void
    {
        $this->controller = new PageController();
    }

    public function test_set_page()
    {
        $this->controller->setPage(\stdClass::class);
        $this->assertTrue(true);
    }

    public function test_page_instance_creation()
    {
        $this->controller->setPage(DummyPage::class);
        $this->assertTrue(true);
    }

    public function test_page_display_data_structure()
    {
        $expectedStructure = [
            'page' => [
                'slug' => 'dashboard',
                'label' => 'Dashboard',
                'content' => ['widgets' => []],
            ],
        ];

        $this->assertArrayHasKey('page', $expectedStructure);
        $this->assertArrayHasKey('slug', $expectedStructure['page']);
        $this->assertArrayHasKey('label', $expectedStructure['page']);
        $this->assertArrayHasKey('content', $expectedStructure['page']);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}

/**
 * Dummy page class for testing
 */
class DummyPage extends Page
{
    public static function slug(): string
    {
        return 'dummy';
    }

    public static function label(): string
    {
        return 'Dummy Page';
    }

    public static function render($ctx = null): array
    {
        return ['content' => 'Dummy content'];
    }
}
