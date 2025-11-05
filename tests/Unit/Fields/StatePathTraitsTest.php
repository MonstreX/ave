<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Core\Fields\Fieldset;
use PHPUnit\Framework\TestCase;

/**
 * Test state path traits: HasContainer, HasStatePath, IsTemplate
 */
class StatePathTraitsTest extends TestCase
{
    /**
     * Test that fields have container trait methods
     */
    public function test_field_has_container_methods(): void
    {
        $field = TextInput::make('name');

        $this->assertTrue(method_exists($field, 'container'));
        $this->assertTrue(method_exists($field, 'getContainer'));
        $this->assertTrue(method_exists($field, 'isNested'));
        $this->assertTrue(method_exists($field, 'getRootContainer'));
    }

    /**
     * Test that container() method returns clone
     */
    public function test_container_returns_clone(): void
    {
        $fieldset = Fieldset::make('items');
        $field = TextInput::make('name');

        $withContainer = $field->container($fieldset);

        // Original unchanged
        $this->assertNull($field->getContainer());
        // Clone has container
        $this->assertSame($fieldset, $withContainer->getContainer());
    }

    /**
     * Test isNested() checks for container
     */
    public function test_is_nested_checks_container(): void
    {
        $field = TextInput::make('name');
        $this->assertFalse($field->isNested());

        $fieldset = Fieldset::make('items');
        $nested = $field->container($fieldset);
        $this->assertTrue($nested->isNested());
    }

    /**
     * Test getRootContainer() traverses up tree
     */
    public function test_get_root_container_traverses_tree(): void
    {
        $form = Fieldset::make('form'); // Simulating a form
        $nested1 = Fieldset::make('section')->container($form);
        $nested2 = TextInput::make('name')->container($nested1);

        $root = $nested2->getRootContainer();
        $this->assertSame($form, $root);
    }

    /**
     * Test that fields have state path methods
     */
    public function test_field_has_state_path_methods(): void
    {
        $field = TextInput::make('name');

        $this->assertTrue(method_exists($field, 'statePath'));
        $this->assertTrue(method_exists($field, 'getStatePath'));
        $this->assertTrue(method_exists($field, 'getChildStatePath'));
    }

    /**
     * Test root level field state path is baseKey
     */
    public function test_root_field_state_path_is_base_key(): void
    {
        $field = TextInput::make('email');

        $this->assertEquals('email', $field->getStatePath());
        $this->assertEquals('email', $field->getChildStatePath());
    }

    /**
     * Test explicitly set state path overrides composition
     */
    public function test_explicit_state_path_overrides_composition(): void
    {
        $field = TextInput::make('name')->statePath('custom.path');

        $this->assertEquals('custom.path', $field->getStatePath());
    }

    /**
     * Test state path composition with container
     */
    public function test_state_path_composition_with_container(): void
    {
        $fieldset = Fieldset::make('items');
        $field = TextInput::make('name')->container($fieldset);

        // Child path should be parent path + own key
        $this->assertEquals('items.name', $field->getStatePath());
    }

    /**
     * Test deep nesting state paths
     */
    public function test_deep_nesting_state_paths(): void
    {
        $level1 = Fieldset::make('sections');
        $level2 = Fieldset::make('items')->container($level1);
        $field = TextInput::make('title')->container($level2);

        $this->assertEquals('sections.items.title', $field->getStatePath());
    }

    /**
     * Test that fields have template methods
     */
    public function test_field_has_template_methods(): void
    {
        $field = TextInput::make('name');

        $this->assertTrue(method_exists($field, 'markAsTemplate'));
        $this->assertTrue(method_exists($field, 'isTemplate'));
        $this->assertTrue(method_exists($field, 'getTemplateSafeStatePath'));
    }

    /**
     * Test markAsTemplate() returns clone
     */
    public function test_mark_as_template_returns_clone(): void
    {
        $field = TextInput::make('name');

        $template = $field->markAsTemplate();

        // Original unchanged
        $this->assertFalse($field->isTemplate());
        // Clone is marked
        $this->assertTrue($template->isTemplate());
    }

    /**
     * Test template safe state path replaces item ID
     */
    public function test_template_safe_state_path(): void
    {
        $fieldset = Fieldset::make('items');
        $field = TextInput::make('name')->markAsTemplate()->container($fieldset);

        $safePath = $field->getTemplateSafeStatePath();

        // Should have __TEMPLATE__ marker
        $this->assertStringContainsString('__TEMPLATE__', $safePath);
        $this->assertEquals('items.__TEMPLATE__', $safePath);
    }

    /**
     * Test non-template field returns regular state path
     */
    public function test_non_template_field_ignores_template_logic(): void
    {
        $fieldset = Fieldset::make('items');
        $field = TextInput::make('name')->container($fieldset);

        // Non-template uses regular state path
        $this->assertEquals('items.name', $field->getTemplateSafeStatePath());
    }

    /**
     * Test Media field has all traits
     */
    public function test_media_field_has_all_traits(): void
    {
        $media = Media::make('avatar');

        // HasContainer
        $this->assertTrue(method_exists($media, 'container'));

        // HasStatePath
        $this->assertTrue(method_exists($media, 'getStatePath'));

        // IsTemplate
        $this->assertTrue(method_exists($media, 'markAsTemplate'));
    }

    /**
     * Test Media field state path composition
     */
    public function test_media_field_state_path_composition(): void
    {
        $fieldset = Fieldset::make('gallery');
        $media = Media::make('images')->container($fieldset);

        $this->assertEquals('gallery.images', $media->getStatePath());
    }

    /**
     * Test Media template behavior
     */
    public function test_media_template_behavior(): void
    {
        $fieldset = Fieldset::make('gallery');
        $media = Media::make('images')
            ->markAsTemplate()
            ->container($fieldset);

        $this->assertTrue($media->isTemplate());
        $this->assertStringContainsString('__TEMPLATE__', $media->getTemplateSafeStatePath());
    }
}
