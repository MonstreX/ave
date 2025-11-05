<?php

namespace Monstrex\Ave\Tests\Unit\Support;

use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Support\StatePathCollectionGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Test StatePathCollectionGenerator: collection naming from state paths
 */
class StatePathCollectionGeneratorTest extends TestCase
{
    /**
     * Test simple root level field
     */
    public function test_simple_root_level_field(): void
    {
        $media = Media::make('avatar');

        $collection = StatePathCollectionGenerator::forMedia($media);

        $this->assertEquals('default', $collection);
    }

    /**
     * Test custom collection name (without nesting)
     */
    public function test_custom_collection_name(): void
    {
        $media = Media::make('avatar')->collection('images');

        $collection = StatePathCollectionGenerator::forMedia($media);

        $this->assertEquals('images', $collection);
    }

    /**
     * Test field nested in single fieldset
     */
    public function test_field_in_single_fieldset(): void
    {
        $fieldset = Fieldset::make('profile');
        $media = Media::make('avatar')->container($fieldset);

        $collection = StatePathCollectionGenerator::forMedia($media);

        $this->assertEquals('default.profile', $collection);
    }

    /**
     * Test field in fieldset with index
     */
    public function test_field_in_fieldset_with_index(): void
    {
        $fieldset = Fieldset::make('sections');
        // Simulate item [0]
        $fieldset = $fieldset->statePath('sections.0');

        $media = Media::make('image')->container($fieldset);

        $collection = StatePathCollectionGenerator::forMedia($media);

        $this->assertEquals('default.sections.0', $collection);
    }

    /**
     * Test deeply nested fields
     */
    public function test_deeply_nested_fields(): void
    {
        $level1 = Fieldset::make('sections');
        $level1 = $level1->statePath('sections.0');

        $level2 = Fieldset::make('items')->container($level1);
        $level2 = $level2->statePath('sections.0.items.1');

        $media = Media::make('gallery')->container($level2);

        $collection = StatePathCollectionGenerator::forMedia($media);

        $this->assertEquals('default.sections.0.items.1', $collection);
    }

    /**
     * Test custom collection with nesting
     */
    public function test_custom_collection_with_nesting(): void
    {
        $fieldset = Fieldset::make('profile');
        $media = Media::make('photos')
            ->collection('media')
            ->container($fieldset);

        $collection = StatePathCollectionGenerator::forMedia($media);

        $this->assertEquals('media.profile', $collection);
    }

    /**
     * Test isTemplateStatePath detection
     */
    public function test_is_template_state_path(): void
    {
        $this->assertTrue(
            StatePathCollectionGenerator::isTemplateStatePath('items.__TEMPLATE__')
        );

        $this->assertTrue(
            StatePathCollectionGenerator::isTemplateStatePath('sections.0.__TEMPLATE__.image')
        );

        $this->assertFalse(
            StatePathCollectionGenerator::isTemplateStatePath('items.0')
        );

        $this->assertFalse(
            StatePathCollectionGenerator::isTemplateStatePath('items')
        );
    }

    /**
     * Test cleanStatePath removes template marker
     */
    public function test_clean_state_path(): void
    {
        $path = StatePathCollectionGenerator::cleanStatePath('items.__TEMPLATE__.image');

        $this->assertEquals('items.image', $path);
    }

    /**
     * Test getParentPath extracts parent
     */
    public function test_get_parent_path(): void
    {
        // Multiple segments
        $parent = StatePathCollectionGenerator::getParentPath('sections.0.items');
        $this->assertEquals('sections.0', $parent);

        // Single segment
        $parent = StatePathCollectionGenerator::getParentPath('avatar');
        $this->assertNull($parent);
    }

    /**
     * Test getFieldName extracts field name
     */
    public function test_get_field_name(): void
    {
        $name = StatePathCollectionGenerator::getFieldName('sections.0.gallery');
        $this->assertEquals('gallery', $name);

        $name = StatePathCollectionGenerator::getFieldName('avatar');
        $this->assertEquals('avatar', $name);
    }

    /**
     * Test composePath builds state paths
     */
    public function test_compose_path(): void
    {
        // With parent
        $path = StatePathCollectionGenerator::composePath('sections.0', 'image');
        $this->assertEquals('sections.0.image', $path);

        // Root level
        $path = StatePathCollectionGenerator::composePath(null, 'avatar');
        $this->assertEquals('avatar', $path);

        // Empty parent (same as null)
        $path = StatePathCollectionGenerator::composePath('', 'avatar');
        $this->assertEquals('avatar', $path);
    }

    /**
     * Test template field returns null collection
     */
    public function test_template_field_collection_handling(): void
    {
        $fieldset = Fieldset::make('items');
        $media = Media::make('image')
            ->markAsTemplate()
            ->container($fieldset);

        // When isTemplate() is true, resolveCollectionName should skip
        $this->assertTrue($media->isTemplate());
        $this->assertStringContainsString('__TEMPLATE__', $media->getStatePath());
    }

    /**
     * Test collection naming consistency
     */
    public function test_collection_naming_consistency(): void
    {
        $fieldset = Fieldset::make('gallery');

        // Create two fields with same path
        $media1 = Media::make('images')->container($fieldset);
        $media2 = Media::make('images')->container($fieldset);

        $collection1 = StatePathCollectionGenerator::forMedia($media1);
        $collection2 = StatePathCollectionGenerator::forMedia($media2);

        // Same state path = same collection name
        $this->assertEquals($collection1, $collection2);
    }

    /**
     * Test collection naming with overrides
     */
    public function test_collection_with_override(): void
    {
        $fieldset = Fieldset::make('profile');
        $media = Media::make('avatar')
            ->useCollectionOverride('custom_avatars')
            ->container($fieldset);

        // Override should be used instead of 'default'
        $collection = StatePathCollectionGenerator::forMedia($media);

        // Override is the base collection
        $this->assertStringStartsWith('custom_avatars', $collection);
    }
}
