<?php

namespace Monstrex\Ave\Tests\Unit\Phase7;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Rendering\PageRenderer;
use Monstrex\Ave\Core\Page;

class PageRendererTest extends TestCase
{
    protected PageRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new PageRenderer();
    }

    public function test_page_renderer_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PageRenderer::class, $this->renderer);
    }

    public function test_render_with_page(): void
    {
        $page = new TestPage();

        // Mock view - this test is incomplete due to Laravel dependency
        $this->markTestIncomplete('Requires Laravel view() function');
    }
}

class TestPage extends Page
{
    public static function slug(): string
    {
        return 'test-page';
    }

    public static function label(): string
    {
        return 'Test Page';
    }

    public static function render($ctx): array
    {
        return ['content' => 'Test page content'];
    }
}
