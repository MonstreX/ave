<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Support\StatePathCollectionGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Critical tests for Fieldset + state path integration
 */
class FieldsetCriticalTest extends TestCase
{
    /**
     * Test fieldset composition with mixed field types
     */
    public function test_fieldset_with_mixed_fields(): void
    {
        $fieldset = Fieldset::make('items')->schema([
            TextInput::make('title'),
            Media::make('image'),
            TextInput::make('description'),
        ]);

        // Simulate item [0]
        $fieldset = $fieldset->statePath('items.0');

        $title = TextInput::make('title')->container($fieldset);
        $media = Media::make('image')->container($fieldset);
        $desc = TextInput::make('description')->container($fieldset);

        $this->assertEquals('items.0.title', $title->getStatePath());
        $this->assertEquals('items.0.image', $media->getStatePath());
        $this->assertEquals('items.0.description', $desc->getStatePath());
    }

    /**
     * Test multiple fieldset items have different collections
     */
    public function test_multiple_items_different_collections(): void
    {
        // Item 0
        $fs0 = Fieldset::make('gallery')->statePath('gallery.0');
        $media0 = Media::make('hero')->container($fs0);

        // Item 1
        $fs1 = Fieldset::make('gallery')->statePath('gallery.1');
        $media1 = Media::make('hero')->container($fs1);

        // Different collections despite same field structure
        $col0 = StatePathCollectionGenerator::forMedia($media0);
        $col1 = StatePathCollectionGenerator::forMedia($media1);

        $this->assertEquals('gallery.0.hero', $col0);
        $this->assertEquals('gallery.1.hero', $col1);
        $this->assertNotEquals($col0, $col1);
    }

    /**
     * Test fieldset getItemStatePath method
     */
    public function test_fieldset_get_item_state_path(): void
    {
        $fieldset = Fieldset::make('items');

        $this->assertEquals('items.0', $fieldset->getItemStatePath(0));
        $this->assertEquals('items.1', $fieldset->getItemStatePath(1));
        $this->assertEquals('items.42', $fieldset->getItemStatePath(42));
    }

    /**
     * Test getChildStatePath returns correct parent path
     */
    public function test_fieldset_child_state_path(): void
    {
        $fieldset = Fieldset::make('gallery')->statePath('gallery.0');

        // getChildStatePath returns this fieldset's path (for children to compose from)
        $this->assertEquals('gallery.0', $fieldset->getChildStatePath());
    }

    /**
     * Test template field path construction
     */
    public function test_template_field_path_construction(): void
    {
        $fieldset = Fieldset::make('items');
        $template = Media::make('image')->markAsTemplate()->container($fieldset);

        // Template should have __TEMPLATE__ marker
        $safePath = $template->getTemplateSafeStatePath();
        $this->assertStringContainsString('__TEMPLATE__', $safePath);
        $this->assertEquals('items.__TEMPLATE__', $safePath);
    }

    /**
     * Test media collection prevents template pollution
     */
    public function test_template_prevents_collection_pollution(): void
    {
        $fieldset = Fieldset::make('items');

        // Real item
        $real = Media::make('image')->container($fieldset)->statePath('items.0.image');

        // Template item
        $template = Media::make('image')
            ->markAsTemplate()
            ->container($fieldset)
            ->statePath('items.__TEMPLATE__.image');

        // Different collections
        $realCol = StatePathCollectionGenerator::forMedia($real);
        $this->assertTrue(StatePathCollectionGenerator::isTemplateStatePath($template->getStatePath()));

        // Real collection should not contain __TEMPLATE__
        $this->assertStringNotContainsString('__TEMPLATE__', $realCol);
    }

    /**
     * Test explicit state path overrides composition
     */
    public function test_explicit_state_path_override(): void
    {
        $fieldset = Fieldset::make('gallery');

        // Explicit path takes precedence over composition
        $media = Media::make('image')
            ->statePath('custom.deep.path')
            ->container($fieldset);

        $this->assertEquals('custom.deep.path', $media->getStatePath());
    }

    /**
     * Test state path with custom collection
     */
    public function test_state_path_with_custom_collection(): void
    {
        $fieldset = Fieldset::make('gallery')->statePath('gallery.0');

        $media = Media::make('images')
            ->collection('special')
            ->container($fieldset);

        $collection = StatePathCollectionGenerator::forMedia($media);

        // Collection should use custom base name with parent path
        $this->assertEquals('gallery.0.special', $collection);
    }

    /**
     * Test multiple media in same fieldset item
     */
    public function test_multiple_media_same_item(): void
    {
        $fieldset = Fieldset::make('gallery')->statePath('gallery.0');

        $featured = Media::make('featured')->container($fieldset);
        $thumbnail = Media::make('thumbnail')->container($fieldset);
        $gallery = Media::make('gallery_items')->container($fieldset);

        $col1 = StatePathCollectionGenerator::forMedia($featured);
        $col2 = StatePathCollectionGenerator::forMedia($thumbnail);
        $col3 = StatePathCollectionGenerator::forMedia($gallery);

        // Different collections for different fields (field name = collection name)
        $this->assertEquals('gallery.0.featured', $col1);
        $this->assertEquals('gallery.0.thumbnail', $col2);
        $this->assertEquals('gallery.0.gallery_items', $col3);
    }

    /**
     * Test state path consistency across operations
     */
    public function test_state_path_consistency(): void
    {
        $fieldset = Fieldset::make('items')->statePath('items.0');
        $media = Media::make('image')->container($fieldset);

        // Multiple calls should return same path
        $path1 = $media->getStatePath();
        $path2 = $media->getStatePath();
        $path3 = $media->getStatePath();

        $this->assertEquals($path1, $path2);
        $this->assertEquals($path2, $path3);
        $this->assertEquals('items.0.image', $path1);
    }
}
