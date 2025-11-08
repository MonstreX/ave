<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Support\StatePathCollectionGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Critical tests: Media field in real form scenarios
 */
class MediaFormScenarioTest extends TestCase
{
    /**
     * Blog post form with featured image
     */
    public function test_blog_post_form_scenario(): void
    {
        // Simple form: title + content + featured image
        $title = TextInput::make('title');
        $content = TextInput::make('body');
        $featured = Media::make('featured_image');

        $this->assertEquals('title', $title->getStatePath());
        $this->assertEquals('body', $content->getStatePath());
        $this->assertEquals('featured_image', $featured->getStatePath());

        $collection = StatePathCollectionGenerator::forMedia($featured);
        $this->assertEquals('featured_image', $collection);
    }

    /**
     * Product with variant fieldset
     */
    public function test_product_with_variants(): void
    {
        // Parent: products
        // Children: variants fieldset with images
        $variants = Fieldset::make('variants')->schema([
            TextInput::make('sku'),
            TextInput::make('size'),
            Media::make('image'),
        ]);

        // Variant [0]
        $variant0 = $variants->statePath('variants.0');
        $media0 = Media::make('image')->container($variant0);

        // Variant [1]
        $variant1 = $variants->statePath('variants.1');
        $media1 = Media::make('image')->container($variant1);

        $this->assertEquals('variants.0.image', $media0->getStatePath());
        $this->assertEquals('variants.1.image', $media1->getStatePath());

        $this->assertEquals('variants.0.image', StatePathCollectionGenerator::forMedia($media0));
        $this->assertEquals('variants.1.image', StatePathCollectionGenerator::forMedia($media1));
    }

    /**
     * Article with sections (rich text + media)
     */
    public function test_article_with_rich_sections(): void
    {
        $sections = Fieldset::make('sections')->schema([
            TextInput::make('title'),
            TextInput::make('content'),
            Media::make('featured'),
            Toggle::make('published'),
        ]);

        // Section [2]
        $section = $sections->statePath('sections.2');

        $featured = Media::make('featured')->container($section);
        $title = TextInput::make('title')->container($section);

        $this->assertEquals('sections.2.featured', $featured->getStatePath());
        $this->assertEquals('sections.2.title', $title->getStatePath());

        $collection = StatePathCollectionGenerator::forMedia($featured);
        $this->assertEquals('sections.2.featured', $collection);
    }

    /**
     * Real world: Testimonials with photos
     */
    public function test_testimonials_fieldset(): void
    {
        $testimonials = Fieldset::make('testimonials')->schema([
            TextInput::make('author'),
            Media::make('photo'),
            TextInput::make('quote'),
        ]);

        // Multiple testimonials
        for ($i = 0; $i < 3; $i++) {
            $ts = $testimonials->statePath("testimonials.{$i}");
            $photo = Media::make('photo')->container($ts);

            $expected = "testimonials.{$i}.photo";
            $this->assertEquals($expected, StatePathCollectionGenerator::forMedia($photo));
        }
    }

    /**
     * Team members with bio and avatar
     */
    public function test_team_members_form(): void
    {
        $team = Fieldset::make('members')->schema([
            TextInput::make('name'),
            TextInput::make('role'),
            Media::make('avatar'),
            Media::make('bio_image'),
        ]);

        // Member [1]
        $member = $team->statePath('members.1');

        $avatar = Media::make('avatar')->container($member);
        $bio = Media::make('bio_image')->container($member);

        $this->assertEquals('members.1.avatar', StatePathCollectionGenerator::forMedia($avatar));
        $this->assertEquals('members.1.bio_image', StatePathCollectionGenerator::forMedia($bio));
    }

    /**
     * Event with speakers and schedule
     */
    public function test_event_with_speakers(): void
    {
        // Event has speakers fieldset
        $speakers = Fieldset::make('speakers')->schema([
            TextInput::make('name'),
            Media::make('headshot'),
        ]);

        // Speaker [0]
        $speaker = $speakers->statePath('speakers.0');
        $headshot = Media::make('headshot')->container($speaker);

        $this->assertEquals('speakers.0.headshot', $headshot->getStatePath());
        $this->assertEquals('speakers.0.headshot', StatePathCollectionGenerator::forMedia($headshot));
    }

    /**
     * Gallery with caption (single media field)
     */
    public function test_simple_gallery_items(): void
    {
        $items = Fieldset::make('gallery')->schema([
            Media::make('image'),
            TextInput::make('caption'),
        ]);

        // Item [0]
        $item = $items->statePath('gallery.0');
        $image = Media::make('image')->container($item);

        $this->assertEquals('gallery.0.image', $image->getStatePath());
        $this->assertEquals('gallery.0.image', StatePathCollectionGenerator::forMedia($image));
    }

    /**
     * Verify state path doesn't contain HTML brackets
     */
    public function test_state_path_format(): void
    {
        $fieldset = Fieldset::make('items')->statePath('items.0');
        $media = Media::make('image')->container($fieldset);

        $path = $media->getStatePath();

        // Should use dots, not HTML brackets
        $this->assertStringNotContainsString('[', $path);
        $this->assertStringNotContainsString(']', $path);
        $this->assertEquals('items.0.image', $path);
    }

    /**
     * Verify collection name respects custom collection
     */
    public function test_custom_collection_in_form(): void
    {
        $items = Fieldset::make('gallery')->schema([
            Media::make('images')->collection('media'),
        ]);

        $item = $items->statePath('gallery.2');
        $media = Media::make('images')
            ->collection('media')
            ->container($item);

        // Should use custom collection base
        $this->assertEquals('gallery.2.media', StatePathCollectionGenerator::forMedia($media));
    }
}
