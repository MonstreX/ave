<?php

namespace Monstrex\Ave\Tests\Unit\Core\Components;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Components\Tab;
use Monstrex\Ave\Core\Components\Tabs;
use InvalidArgumentException;

/**
 * TabsTest - Unit tests for Tab and Tabs components.
 *
 * Tests the Tab/Tabs component hierarchy which provides:
 * - Tabbed interface composition
 * - Tab management and activation
 * - Icon and badge support
 * - Fluent interface for configuration
 * - Unique ID generation
 */
class TabsTest extends TestCase
{
    /**
     * Test tab can be instantiated
     */
    public function test_tab_can_be_instantiated(): void
    {
        $tab = new Tab('General');
        $this->assertInstanceOf(Tab::class, $tab);
    }

    /**
     * Test tab make factory method
     */
    public function test_tab_make_factory_method(): void
    {
        $tab = Tab::make('Settings');
        $this->assertInstanceOf(Tab::class, $tab);
        $this->assertEquals('Settings', $tab->getLabel());
    }

    /**
     * Test tab label is set on instantiation
     */
    public function test_tab_label_from_constructor(): void
    {
        $tab = new Tab('Advanced');
        $this->assertEquals('Advanced', $tab->getLabel());
    }

    /**
     * Test tab auto-generates unique ID
     */
    public function test_tab_auto_generates_id(): void
    {
        $tab = new Tab('General');
        $id = $tab->getId();

        $this->assertNotEmpty($id);
        $this->assertStringStartsWith('tab-', $id);
        $this->assertStringContainsString('general', strtolower($id));
    }

    /**
     * Test tab ID is different for different instances
     */
    public function test_tab_id_is_unique_per_instance(): void
    {
        $tab1 = new Tab('General');
        $tab2 = new Tab('General');

        $this->assertNotEquals($tab1->getId(), $tab2->getId());
    }

    /**
     * Test tab custom ID override
     */
    public function test_tab_custom_id_override(): void
    {
        $tab = Tab::make('General')->id('custom-tab-id');
        $this->assertEquals('custom-tab-id', $tab->getId());
    }

    /**
     * Test tab icon method is fluent
     */
    public function test_tab_icon_method_is_fluent(): void
    {
        $tab = new Tab('General');
        $result = $tab->icon('fa fa-cog');

        $this->assertInstanceOf(Tab::class, $result);
        $this->assertSame($tab, $result);
    }

    /**
     * Test tab icon can be set
     */
    public function test_tab_icon_can_be_set(): void
    {
        $tab = Tab::make('Settings')->icon('fa fa-gear');
        $this->assertEquals('fa fa-gear', $tab->getIcon());
    }

    /**
     * Test tab icon defaults to null
     */
    public function test_tab_icon_default_null(): void
    {
        $tab = new Tab('General');
        $this->assertNull($tab->getIcon());
    }

    /**
     * Test tab icon can be cleared
     */
    public function test_tab_icon_can_be_cleared(): void
    {
        $tab = Tab::make('Settings')
            ->icon('fa fa-gear')
            ->icon(null);

        $this->assertNull($tab->getIcon());
    }

    /**
     * Test tab badge method is fluent
     */
    public function test_tab_badge_method_is_fluent(): void
    {
        $tab = new Tab('General');
        $result = $tab->badge('New');

        $this->assertInstanceOf(Tab::class, $result);
        $this->assertSame($tab, $result);
    }

    /**
     * Test tab badge can be set
     */
    public function test_tab_badge_can_be_set(): void
    {
        $tab = Tab::make('Notifications')->badge('3');
        $this->assertEquals('3', $tab->getBadge());
    }

    /**
     * Test tab badge defaults to null
     */
    public function test_tab_badge_default_null(): void
    {
        $tab = new Tab('General');
        $this->assertNull($tab->getBadge());
    }

    /**
     * Test tab badge can be cleared
     */
    public function test_tab_badge_can_be_cleared(): void
    {
        $tab = Tab::make('Notifications')
            ->badge('3')
            ->badge(null);

        $this->assertNull($tab->getBadge());
    }

    /**
     * Test tab fluent interface chaining
     */
    public function test_tab_fluent_interface_chaining(): void
    {
        $tab = Tab::make('Advanced Settings')
            ->id('advanced-tab')
            ->icon('fa fa-sliders')
            ->badge('Beta');

        $this->assertEquals('Advanced Settings', $tab->getLabel());
        $this->assertEquals('advanced-tab', $tab->getId());
        $this->assertEquals('fa fa-sliders', $tab->getIcon());
        $this->assertEquals('Beta', $tab->getBadge());
    }

    /**
     * Test tabs can be instantiated
     */
    public function test_tabs_can_be_instantiated(): void
    {
        $tabs = new Tabs();
        $this->assertInstanceOf(Tabs::class, $tabs);
    }

    /**
     * Test tabs make factory method
     */
    public function test_tabs_make_factory_method(): void
    {
        $tabs = Tabs::make();
        $this->assertInstanceOf(Tabs::class, $tabs);
    }

    /**
     * Test tabs make with tab array
     */
    public function test_tabs_make_with_tabs(): void
    {
        $tabs = Tabs::make([
            Tab::make('General'),
            Tab::make('Settings')
        ]);

        $this->assertInstanceOf(Tabs::class, $tabs);
    }

    /**
     * Test tabs active method is fluent
     */
    public function test_tabs_active_method_is_fluent(): void
    {
        $tabs = new Tabs();
        $result = $tabs->active('tab-1');

        $this->assertInstanceOf(Tabs::class, $result);
        $this->assertSame($tabs, $result);
    }

    /**
     * Test tabs active tab can be set
     */
    public function test_tabs_active_tab_can_be_set(): void
    {
        $tabs = Tabs::make()->active('settings-tab');
        $this->assertEquals('settings-tab', $tabs->getActiveTab());
    }

    /**
     * Test tabs active tab defaults to null
     */
    public function test_tabs_active_tab_default_null(): void
    {
        $tabs = new Tabs();
        $this->assertNull($tabs->getActiveTab());
    }

    /**
     * Test tabs generates unique DOM ID
     */
    public function test_tabs_generates_unique_dom_id(): void
    {
        $tabs = new Tabs();
        $domId = $tabs->getDomId();

        $this->assertNotEmpty($domId);
        $this->assertStringStartsWith('tabs-', $domId);
    }

    /**
     * Test tabs different instances have different DOM IDs
     */
    public function test_tabs_dom_id_is_unique(): void
    {
        $tabs1 = new Tabs();
        $tabs2 = new Tabs();

        $this->assertNotEquals($tabs1->getDomId(), $tabs2->getDomId());
    }

    /**
     * Test tabs schema accepts only Tab components
     */
    public function test_tabs_schema_accepts_tab_components(): void
    {
        $tabs = Tabs::make();
        $result = $tabs->schema([
            Tab::make('First'),
            Tab::make('Second')
        ]);

        $this->assertInstanceOf(Tabs::class, $result);
    }

    /**
     * Test tabs schema rejects non-Tab components
     */
    public function test_tabs_schema_rejects_non_tab_components(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tabs container expects Tab components');

        Tabs::make()->schema(['Not a Tab']);
    }

    /**
     * Test tabs schema rejects mixed components
     */
    public function test_tabs_schema_rejects_mixed_components(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Tabs::make()->schema([
            Tab::make('Valid'),
            'Invalid'
        ]);
    }

    /**
     * Test tabs schema can be called multiple times
     */
    public function test_tabs_schema_can_be_called_multiple_times(): void
    {
        $tabs = Tabs::make()
            ->schema([Tab::make('First')])
            ->schema([Tab::make('Second')]);

        $this->assertInstanceOf(Tabs::class, $tabs);
    }

    /**
     * Test tabs fluent interface chaining
     */
    public function test_tabs_fluent_interface_chaining(): void
    {
        $tabs = Tabs::make([
            Tab::make('General'),
            Tab::make('Settings')
        ])->active('general-tab');

        $this->assertEquals('general-tab', $tabs->getActiveTab());
    }

    /**
     * Test multiple tabs instances
     */
    public function test_multiple_tabs_instances(): void
    {
        $tabs1 = Tabs::make();
        $tabs2 = Tabs::make();

        $this->assertNotSame($tabs1, $tabs2);
        $this->assertNotEquals($tabs1->getDomId(), $tabs2->getDomId());
    }

    /**
     * Test multiple tab instances
     */
    public function test_multiple_tab_instances(): void
    {
        $tab1 = Tab::make('General');
        $tab2 = Tab::make('Settings');
        $tab3 = Tab::make('Advanced');

        $this->assertNotSame($tab1, $tab2);
        $this->assertNotSame($tab2, $tab3);
        $this->assertNotEquals($tab1->getId(), $tab2->getId());
    }

    /**
     * Test tab with special characters in label
     */
    public function test_tab_label_with_special_characters(): void
    {
        $tab = new Tab('User & Admin Settings');
        $this->assertEquals('User & Admin Settings', $tab->getLabel());
    }

    /**
     * Test tab ID generation from special characters
     */
    public function test_tab_id_from_special_character_label(): void
    {
        $tab = new Tab('User & Admin');
        $id = $tab->getId();

        // Should slug the label but include the object hash
        $this->assertStringStartsWith('tab-', $id);
        $this->assertNotEmpty($id);
    }

    /**
     * Test tabs with single tab
     */
    public function test_tabs_with_single_tab(): void
    {
        $tabs = Tabs::make([
            Tab::make('Only Tab')
        ]);

        $this->assertInstanceOf(Tabs::class, $tabs);
    }

    /**
     * Test tabs with many tabs
     */
    public function test_tabs_with_many_tabs(): void
    {
        $tabArray = [];
        for ($i = 1; $i <= 10; $i++) {
            $tabArray[] = Tab::make("Tab {$i}");
        }

        $tabs = Tabs::make($tabArray);
        $this->assertInstanceOf(Tabs::class, $tabs);
    }

    /**
     * Test tab method visibility
     */
    public function test_tab_methods_are_public(): void
    {
        $tab = new Tab('General');
        $reflection = new \ReflectionClass($tab);

        $publicMethods = [
            'make',
            'id',
            'icon',
            'badge',
            'getId',
            'getLabel',
            'getIcon',
            'getBadge'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Tab should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test tabs method visibility
     */
    public function test_tabs_methods_are_public(): void
    {
        $tabs = new Tabs();
        $reflection = new \ReflectionClass($tabs);

        $publicMethods = [
            'make',
            'schema',
            'active',
            'getActiveTab',
            'getDomId'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Tabs should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test tab namespace
     */
    public function test_tab_namespace(): void
    {
        $tab = new Tab('General');
        $reflection = new \ReflectionClass($tab);
        $this->assertEquals('Monstrex\\Ave\\Core\\Components', $reflection->getNamespaceName());
    }

    /**
     * Test tabs namespace
     */
    public function test_tabs_namespace(): void
    {
        $tabs = new Tabs();
        $reflection = new \ReflectionClass($tabs);
        $this->assertEquals('Monstrex\\Ave\\Core\\Components', $reflection->getNamespaceName());
    }

    /**
     * Test tab class name
     */
    public function test_tab_class_name(): void
    {
        $tab = new Tab('General');
        $reflection = new \ReflectionClass($tab);
        $this->assertEquals('Tab', $reflection->getShortName());
    }

    /**
     * Test tabs class name
     */
    public function test_tabs_class_name(): void
    {
        $tabs = new Tabs();
        $reflection = new \ReflectionClass($tabs);
        $this->assertEquals('Tabs', $reflection->getShortName());
    }
}
