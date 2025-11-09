<?php

namespace Monstrex\Ave\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Services\SlugService;

/**
 * SlugServiceTest - Tests for URL slug generation service.
 *
 * Tests the SlugService::make() method which converts strings to
 * URL-friendly slugs with support for multiple languages and custom separators.
 */
class SlugServiceTest extends TestCase
{
    /**
     * Test basic slug generation with default parameters
     */
    public function test_make_converts_basic_text_to_slug(): void
    {
        $result = SlugService::make('Hello World');
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test slug generation with custom separator
     */
    public function test_make_with_custom_separator(): void
    {
        $result = SlugService::make('Hello World', '_');
        $this->assertEquals('hello_world', $result);

        $result = SlugService::make('Hello World', '.');
        $this->assertEquals('hello.world', $result);

        $result = SlugService::make('Hello World', '~');
        $this->assertEquals('hello~world', $result);
    }

    /**
     * Test empty string returns empty string
     */
    public function test_make_returns_empty_string_for_empty_input(): void
    {
        $result = SlugService::make('');
        $this->assertEquals('', $result);
    }

    /**
     * Test whitespace-only string returns empty string
     */
    public function test_make_returns_empty_string_for_whitespace_only(): void
    {
        $result = SlugService::make('   ');
        $this->assertEquals('', $result);
    }

    /**
     * Test Russian text transliteration with default locale
     */
    public function test_make_transliterates_russian_text(): void
    {
        $result = SlugService::make('Привет мир');
        // Laravel's Str::slug() without locale converts to English characters
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test Russian text transliteration with ru locale
     */
    public function test_make_with_russian_locale(): void
    {
        $result = SlugService::make('Привет мир', '-', 'ru');
        // With ru locale, should transliterate properly
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString('Привет', $result);
    }

    /**
     * Test Ukrainian text with uk locale
     */
    public function test_make_with_ukrainian_locale(): void
    {
        $result = SlugService::make('Привіт світ', '-', 'uk');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString('Привіт', $result);
    }

    /**
     * Test handling of special characters
     */
    public function test_make_removes_special_characters(): void
    {
        $result = SlugService::make('Hello! @World #Test');
        // @ becomes 'at', ! and # are removed
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString('!', $result);
        $this->assertStringNotContainsString('#', $result);
    }

    /**
     * Test handling of punctuation
     */
    public function test_make_removes_punctuation(): void
    {
        $result = SlugService::make('Hello, World! How are you?');
        $this->assertEquals('hello-world-how-are-you', $result);
    }

    /**
     * Test lowercase conversion
     */
    public function test_make_converts_to_lowercase(): void
    {
        $result = SlugService::make('HELLO WORLD');
        $this->assertEquals('hello-world', $result);

        $result = SlugService::make('HeLLo WoRLd');
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test multiple spaces collapsed to single separator
     */
    public function test_make_collapses_multiple_spaces(): void
    {
        $result = SlugService::make('Hello    World');
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test leading/trailing spaces are removed
     */
    public function test_make_removes_leading_trailing_spaces(): void
    {
        $result = SlugService::make('   Hello World   ');
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test numbers in text are preserved
     */
    public function test_make_preserves_numbers(): void
    {
        $result = SlugService::make('Hello World 123');
        $this->assertEquals('hello-world-123', $result);
    }

    /**
     * Test slugs with only numbers
     */
    public function test_make_with_only_numbers(): void
    {
        $result = SlugService::make('12345');
        $this->assertEquals('12345', $result);
    }

    /**
     * Test mixed Latin and Cyrillic text
     */
    public function test_make_with_mixed_latin_and_cyrillic(): void
    {
        $result = SlugService::make('Hello Привет', '-', 'ru');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test very long text
     */
    public function test_make_with_long_text(): void
    {
        $longText = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua';
        $result = SlugService::make($longText);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString(' ', $result);
    }

    /**
     * Test text with hyphens
     */
    public function test_make_with_existing_hyphens(): void
    {
        $result = SlugService::make('hello-world');
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test text with underscores becomes slug
     */
    public function test_make_with_underscores(): void
    {
        $result = SlugService::make('hello_world');
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test German umlauts
     */
    public function test_make_with_german_umlauts(): void
    {
        $result = SlugService::make('Müller Schöne');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test French accents
     */
    public function test_make_with_french_accents(): void
    {
        $result = SlugService::make('Café Résumé Naïve');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test URLs are properly slugged
     */
    public function test_make_with_url_like_text(): void
    {
        $result = SlugService::make('My Blog Post Title Here');
        $this->assertEquals('my-blog-post-title-here', $result);
    }

    /**
     * Test article with apostrophe
     */
    public function test_make_removes_apostrophe(): void
    {
        $result = SlugService::make("Don't Worry Be Happy");
        $this->assertEquals('dont-worry-be-happy', $result);
    }

    /**
     * Test HTML entities are handled
     */
    public function test_make_with_special_html_like_chars(): void
    {
        $result = SlugService::make('Hello & World');
        $this->assertStringNotContainsString('&', $result);
    }

    /**
     * Test real-world Article title example
     */
    public function test_make_with_real_article_title(): void
    {
        $result = SlugService::make('Как создать идеальный сайт в 2024 году', '-', 'ru');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString(' ', $result);
        $this->assertStringNotContainsString('Как', $result);
    }

    /**
     * Test consistency - same input always produces same output
     */
    public function test_make_is_consistent(): void
    {
        $input = 'Hello World Test';
        $result1 = SlugService::make($input);
        $result2 = SlugService::make($input);
        $result3 = SlugService::make($input);

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    /**
     * Test consistency across different locales
     */
    public function test_make_is_deterministic_with_locales(): void
    {
        $input = 'Привет Мир';

        // Same locale should always produce same result
        $result1 = SlugService::make($input, '-', 'ru');
        $result2 = SlugService::make($input, '-', 'ru');
        $this->assertEquals($result1, $result2);

        // Different locales may produce different results
        $defaultResult = SlugService::make($input);
        $ruResult = SlugService::make($input, '-', 'ru');
        // Results may differ, but both should be valid slugs
        $this->assertIsString($defaultResult);
        $this->assertIsString($ruResult);
    }

    /**
     * Test that result is safe for URLs with basic Latin text
     */
    public function test_make_result_is_url_safe_basic_latin(): void
    {
        $inputs = [
            'Hello World!',
            'Test123',
            'My-Blog-Post',
        ];

        foreach ($inputs as $input) {
            $result = SlugService::make($input);
            // Should only contain alphanumeric, hyphens, and underscores for basic Latin
            $this->assertMatchesRegularExpression('/^[a-z0-9\-_]*$/', $result);
            $this->assertNotEmpty($result);
        }
    }

    /**
     * Test that result with Cyrillic is valid (with locale)
     */
    public function test_make_result_with_cyrillic_and_locale(): void
    {
        $result = SlugService::make('Привет мир', '-', 'ru');
        // With ru locale, should be transliterated to Latin
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test multiple separators can be used
     */
    public function test_make_with_various_separators(): void
    {
        $text = 'Hello World Test';

        $results = [
            '-' => 'hello-world-test',
            '_' => 'hello_world_test',
            '.' => 'hello.world.test',
            '~' => 'hello~world~test',
        ];

        foreach ($results as $separator => $expected) {
            $result = SlugService::make($text, $separator);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test null locale parameter (default behavior)
     */
    public function test_make_with_null_locale(): void
    {
        $result = SlugService::make('Hello World', '-', null);
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test single word slug
     */
    public function test_make_with_single_word(): void
    {
        $result = SlugService::make('Hello');
        $this->assertEquals('hello', $result);
    }

    /**
     * Test all uppercase becomes all lowercase
     */
    public function test_make_with_all_caps(): void
    {
        $result = SlugService::make('UPPERCASE');
        $this->assertEquals('uppercase', $result);
    }
}
