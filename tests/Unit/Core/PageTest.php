<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Page;

class PageTest extends TestCase
{
    public function test_page_get_slug()
    {
        $slug = TestPage::getSlug();
        $this->assertEquals('testpage', $slug);
    }

    public function test_page_get_label()
    {
        $label = TestPage::getLabel();
        $this->assertEquals('TestPage', $label);
    }

    public function test_page_render_returns_array()
    {
        $result = TestPage::render(null);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
    }

    public function test_page_custom_label()
    {
        $label = TestPageWithLabel::getLabel();
        $this->assertEquals('Custom Dashboard', $label);
    }
}

class TestPage extends Page
{
}

class TestPageWithLabel extends Page
{
    public static ?string $label = 'Custom Dashboard';
}
