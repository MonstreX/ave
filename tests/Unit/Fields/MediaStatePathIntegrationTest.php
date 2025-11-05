<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Support\StatePathCollectionGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests: Media field with state paths in real scenarios
 */
class MediaStatePathIntegrationTest extends TestCase
{
    /**
     * Test simple media field at root
     */
    public function test_simple_media_field(): void
    {
        $media = Media::make('avatar');

        $this->assertEquals('avatar', $media->getStatePath());
        $this->assertEquals('default', StatePathCollectionGenerator::forMedia($media));
    }

    /**
     * Test media in fieldset with single item
     */
    public function test_media_in_fieldset(): void
    {
        $fieldset = Fieldset::make('profile');
        $media = Media::make('avatar')->container($fieldset);

        $this->assertEquals('profile.avatar', $media->getStatePath());
        $this->assertEquals('default.profile', StatePathCollectionGenerator::forMedia($media));
    }

    /**
     * Test media in fieldset items (indexed)
     */
    public function test_media_in_fieldset_items(): void
    {
        // Simulate Fieldset at items[0]
        $fieldset = Fieldset::make('items')->statePath('items.0');

        $media = Media::make('image')
            ->container($fieldset);

        $this->assertEquals('items.0.image', $media->getStatePath());
        $this->assertEquals('default.items.0', StatePathCollectionGenerator::forMedia($media));
    }

    /**
     * Test multiple media fields in same fieldset
     */
    public function test_multiple_media_in_fieldset(): void
    {
        $fieldset = Fieldset::make('gallery');

        $featured = Media::make('featured')->container($fieldset);
        $thumbnail = Media::make('thumbnail')->container($fieldset);

        $this->assertEquals('gallery.featured', $featured->getStatePath());
        $this->assertEquals('gallery.thumbnail', $thumbnail->getStatePath());

        // Both use same parent path, different field names
        $this->assertEquals('default.gallery', StatePathCollectionGenerator::forMedia($featured));
        $this->assertEquals('default.gallery', StatePathCollectionGenerator::forMedia($thumbnail));
    }

    /**
     * Test nested fieldsets (2 levels)
     */
    public function test_nested_fieldsets_2_levels(): void
    {
        // Level 1: sections[0]
        $sections = Fieldset::make('sections')->statePath('sections.0');

        // Level 2: items[1]
        $items = Fieldset::make('items')->container($sections)->statePath('sections.0.items.1');

        // Leaf: media field
        $media = Media::make('image')->container($items);

        $this->assertEquals('sections.0.items.1.image', $media->getStatePath());
        $this->assertEquals('default.sections.0.items.1', StatePathCollectionGenerator::forMedia($media));
    }

    /**
     * Test nested fieldsets (3 levels)
     */
    public function test_nested_fieldsets_3_levels(): void
    {
        // Level 1
        $level1 = Fieldset::make('chapters')
            ->statePath('chapters.0');

        // Level 2
        $level2 = Fieldset::make('sections')
            ->container($level1)
            ->statePath('chapters.0.sections.1');

        // Level 3
        $level3 = Fieldset::make('content')
            ->container($level2)
            ->statePath('chapters.0.sections.1.content');

        // Media field
        $media = Media::make('image')->container($level3);

        $this->assertEquals('chapters.0.sections.1.content.image', $media->getStatePath());
        $this->assertEquals('default.chapters.0.sections.1.content', StatePathCollectionGenerator::forMedia($media));
    }

    /**
     * Test media with custom collection in nested context
     */
    public function test_media_custom_collection_nested(): void
    {
        $fieldset = Fieldset::make('profile');

        $media = Media::make('photos')
            ->collection('user_media')
            ->container($fieldset);

        $this->assertEquals('profile.photos', $media->getStatePath());
        $this->assertEquals('user_media.profile', StatePathCollectionGenerator::forMedia($media));
    }

    /**
     * Test media with override in nested context
     */
    public function test_media_override_collection_nested(): void
    {
        $fieldset = Fieldset::make('gallery');

        $media = Media::make('images')
            ->useCollectionOverride('featured_images')
            ->container($fieldset);

        $this->assertEquals('gallery.images', $media->getStatePath());
        $this->assertEquals('featured_images.gallery', StatePathCollectionGenerator::forMedia($media));
    }

    /**
     * Test template field in fieldset
     */
    public function test_template_field_in_fieldset(): void
    {
        $fieldset = Fieldset::make('items');

        $template = Media::make('image')
            ->markAsTemplate()
            ->container($fieldset);

        $this->assertTrue($template->isTemplate());
        $this->assertEquals('items.__TEMPLATE__', $template->getTemplateSafeStatePath());

        // Template should not get real collection
        $this->assertStringContainsString('__TEMPLATE__', $template->getStatePath());
    }

    /**
     * Test mixed fields in fieldset
     */
    public function test_mixed_fields_in_fieldset(): void
    {
        $fieldset = Fieldset::make('article');

        $title = TextInput::make('title')->container($fieldset);
        $media = Media::make('cover')->container($fieldset);
        $content = TextInput::make('body')->container($fieldset);

        $this->assertEquals('article.title', $title->getStatePath());
        $this->assertEquals('article.cover', $media->getStatePath());
        $this->assertEquals('article.body', $content->getStatePath());
    }

    /**
     * Test fieldset with multiple items and media
     */
    public function test_fieldset_multiple_items_media(): void
    {
        // Item [0]
        $item0 = Fieldset::make('items')->statePath('items.0');
        $media0 = Media::make('image')->container($item0);

        // Item [1]
        $item1 = Fieldset::make('items')->statePath('items.1');
        $media1 = Media::make('image')->container($item1);

        // Different item IDs = different collections
        $this->assertEquals('default.items.0', StatePathCollectionGenerator::forMedia($media0));
        $this->assertEquals('default.items.1', StatePathCollectionGenerator::forMedia($media1));
    }

    /**
     * Test state path with explicit override
     */
    public function test_explicit_state_path_override(): void
    {
        $fieldset = Fieldset::make('gallery');

        $media = Media::make('images')
            ->statePath('custom.deep.path')
            ->container($fieldset);

        // Explicit path takes precedence
        $this->assertEquals('custom.deep.path', $media->getStatePath());
    }

    /**
     * Test state path composition is predictable
     */
    public function test_state_path_composition_predictability(): void
    {
        // Create fieldset manually
        $fieldset = Fieldset::make('sections');

        // Add media
        $media = Media::make('banner')->container($fieldset);

        // Verify composition
        $expected = 'sections.banner';
        $this->assertEquals($expected, $media->getStatePath());

        // Verify collection generation
        $collection = StatePathCollectionGenerator::forMedia($media);
        $this->assertEquals('default.sections', $collection);

        // Verify parent path extraction
        $parent = StatePathCollectionGenerator::getParentPath($media->getStatePath());
        $this->assertEquals('sections', $parent);

        // Verify field name extraction
        $fieldName = StatePathCollectionGenerator::getFieldName($media->getStatePath());
        $this->assertEquals('banner', $fieldName);
    }

    /**
     * Test that container awareness is unidirectional
     */
    public function test_container_awareness_unidirectional(): void
    {
        $fieldset = Fieldset::make('gallery');
        $media = Media::make('images')->container($fieldset);

        // Media knows about fieldset
        $this->assertSame($fieldset, $media->getContainer());

        // Fieldset doesn't auto-track media (containers don't maintain child refs)
        // This is by design - containers only provide path prefix
    }
}
