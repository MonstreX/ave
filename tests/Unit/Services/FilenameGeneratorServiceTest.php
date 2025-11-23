<?php

namespace Monstrex\Ave\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Services\FilenameGeneratorService;
use Illuminate\Http\UploadedFile;

class FilenameGeneratorServiceTest extends TestCase
{
    /**
     * Test original strategy keeps filename as-is
     */
    public function test_original_strategy_keeps_filename(): void
    {
        $result = FilenameGeneratorService::generate('My Document.pdf', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
        ]);

        $this->assertEquals('My Document.pdf', $result);
    }

    /**
     * Test transliterate strategy converts to slug
     */
    public function test_transliterate_strategy_converts_to_slug(): void
    {
        $result = FilenameGeneratorService::generate('Мой Документ.pdf', [
            'strategy' => FilenameGeneratorService::STRATEGY_TRANSLITERATE,
            'locale' => 'ru',
        ]);

        // Should be transliterated and lowercased
        $this->assertStringEndsWith('.pdf', $result);
        $this->assertStringContainsString('moy', $result); // ensure Cyrillic characters transliterate to Latin
        // Result should be slug format with hyphens, not spaces
        $this->assertFalse(strpos($result, ' '));
    }

    /**
     * Test transliterate with custom separator
     */
    public function test_transliterate_with_custom_separator(): void
    {
        $result = FilenameGeneratorService::generate('My Test File.jpg', [
            'strategy' => FilenameGeneratorService::STRATEGY_TRANSLITERATE,
            'separator' => '_',
        ]);

        $this->assertStringEndsWith('.jpg', $result);
        // Should use underscore separator
        $this->assertStringContainsString('_', $result);
    }

    /**
     * Test unique strategy generates random name
     */
    public function test_unique_strategy_generates_random_name(): void
    {
        $result1 = FilenameGeneratorService::generate('document.pdf', [
            'strategy' => FilenameGeneratorService::STRATEGY_UNIQUE,
        ]);

        $result2 = FilenameGeneratorService::generate('document.pdf', [
            'strategy' => FilenameGeneratorService::STRATEGY_UNIQUE,
        ]);

        // Both should end with extension
        $this->assertStringEndsWith('.pdf', $result1);
        $this->assertStringEndsWith('.pdf', $result2);

        // Should be different (random)
        $this->assertNotEquals($result1, $result2);

        // Should be hex-like (32 chars + extension)
        $filename = pathinfo($result1, PATHINFO_FILENAME);
        $this->assertEquals(32, strlen($filename));
    }

    /**
     * Test uniqueness suffix added when file exists
     */
    public function test_uniqueness_suffix_when_file_exists(): void
    {
        $existingFiles = ['myfile.pdf'];
        $existsCallback = fn(string $filename) => in_array($filename, $existingFiles);

        $result = FilenameGeneratorService::generate('myfile.pdf', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
            'existsCallback' => $existsCallback,
        ]);

        $this->assertEquals('myfile-1.pdf', $result);
    }

    /**
     * Test multiple uniqueness suffixes
     */
    public function test_multiple_uniqueness_suffixes(): void
    {
        $existingFiles = ['photo.jpg', 'photo-1.jpg', 'photo-2.jpg'];
        $existsCallback = fn(string $filename) => in_array($filename, $existingFiles);

        $result = FilenameGeneratorService::generate('photo.jpg', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
            'existsCallback' => $existsCallback,
        ]);

        $this->assertEquals('photo-3.jpg', $result);
    }

    /**
     * Test file without extension
     */
    public function test_file_without_extension(): void
    {
        $result = FilenameGeneratorService::generate('README', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
        ]);

        $this->assertEquals('README', $result);
    }

    /**
     * Test transliterate preserves extension
     */
    public function test_transliterate_preserves_extension(): void
    {
        $result = FilenameGeneratorService::generate('Фото.jpeg', [
            'strategy' => FilenameGeneratorService::STRATEGY_TRANSLITERATE,
        ]);

        $this->assertStringEndsWith('.jpeg', $result);
    }

    /**
     * Test unique strategy preserves extension
     */
    public function test_unique_strategy_preserves_extension(): void
    {
        $result = FilenameGeneratorService::generate('document.docx', [
            'strategy' => FilenameGeneratorService::STRATEGY_UNIQUE,
        ]);

        $this->assertStringEndsWith('.docx', $result);
    }

    /**
     * Test special characters removed in original strategy
     */
    public function test_special_characters_removed_in_original(): void
    {
        $result = FilenameGeneratorService::generate('file?*:|"<>.txt', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
        ]);

        // Special filesystem characters should be removed
        $this->assertStringNotContainsString('?', $result);
        $this->assertStringNotContainsString('*', $result);
        $this->assertStringNotContainsString(':', $result);
        $this->assertStringNotContainsString('"', $result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    /**
     * Test empty filename defaults to 'file'
     */
    public function test_empty_filename_defaults_to_file(): void
    {
        $result = FilenameGeneratorService::generate('', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
        ]);

        $this->assertEquals('file', $result);
    }

    /**
     * Test with UploadedFile instance
     */
    public function test_with_uploaded_file_instance(): void
    {
        // Create a mock UploadedFile
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test-file.pdf');

        $result = FilenameGeneratorService::generate($file, [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
        ]);

        $this->assertEquals('test-file.pdf', $result);
    }

    /**
     * Test max length option
     */
    public function test_max_length_truncates_filename(): void
    {
        $result = FilenameGeneratorService::generate('this_is_a_very_long_filename_that_exceeds_limit.txt', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
            'maxLength' => 20,
        ]);

        $filename = pathinfo($result, PATHINFO_FILENAME);
        $this->assertEquals(20, strlen($filename));
        $this->assertStringEndsWith('.txt', $result);
        // Max length should be enforced
        $this->assertLessThanOrEqual(24, strlen($result)); // 20 + ".txt"
    }

    /**
     * Test transliterate with Russian text
     */
    public function test_transliterate_russian_text(): void
    {
        $result = FilenameGeneratorService::generate('Привет мир.txt', [
            'strategy' => FilenameGeneratorService::STRATEGY_TRANSLITERATE,
            'locale' => 'ru',
        ]);

        $this->assertStringEndsWith('.txt', $result);
        // Should not contain Cyrillic
        $this->assertEquals(0, preg_match('/[а-яёА-ЯЁ]/u', $result));
    }

    /**
     * Test default strategy is original
     */
    public function test_default_strategy_is_original(): void
    {
        $result = FilenameGeneratorService::generate('document.pdf');

        $this->assertEquals('document.pdf', $result);
    }

    /**
     * Test spaces replaced by separator in transliterate
     */
    public function test_spaces_replaced_in_transliterate(): void
    {
        $result = FilenameGeneratorService::generate('my document file.pdf', [
            'strategy' => FilenameGeneratorService::STRATEGY_TRANSLITERATE,
            'separator' => '-',
        ]);

        // Spaces should be replaced with separator
        $this->assertFalse(strpos($result, ' '));
        $this->assertStringContainsString('-', $result);
    }

    /**
     * Test uniqueness replace strategy (no suffix)
     */
    public function test_uniqueness_replace_doesnt_add_suffix(): void
    {
        $existingFiles = ['myfile.pdf'];
        $existsCallback = fn(string $filename) => in_array($filename, $existingFiles);

        $result = FilenameGeneratorService::generate('myfile.pdf', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
            'uniqueness' => FilenameGeneratorService::UNIQUENESS_REPLACE,
            'existsCallback' => $existsCallback,
        ]);

        // With UNIQUENESS_REPLACE, file is not made unique (callback not used)
        $this->assertEquals('myfile.pdf', $result);
    }

    /**
     * Test file with dot in basename
     */
    public function test_file_with_multiple_dots(): void
    {
        $result = FilenameGeneratorService::generate('my.backup.file.tar.gz', [
            'strategy' => FilenameGeneratorService::STRATEGY_ORIGINAL,
        ]);

        // Should keep the full name
        $this->assertEquals('my.backup.file.tar.gz', $result);
    }
}
