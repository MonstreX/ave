<?php

namespace Monstrex\Ave\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Services\PathGeneratorService;
use Illuminate\Database\Eloquent\Model;

class PathGeneratorServiceTest extends TestCase
{
    /**
     * Create a mock model for testing
     */
    private function createMockModel(string $table = 'articles', int $id = 42): Model
    {
        $model = $this->createMock(Model::class);
        $model->method('getTable')->willReturn($table);
        $model->method('getKey')->willReturn($id);

        return $model;
    }

    /**
     * Test flat strategy with model
     */
    public function test_flat_strategy_with_model(): void
    {
        $model = $this->createMockModel('articles', 42);

        $result = PathGeneratorService::generate([
            'root' => 'uploads/files',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $model,
        ]);

        $this->assertEquals('uploads/files/articles/42', $result);
    }

    /**
     * Test dated strategy with model
     */
    public function test_dated_strategy_with_model(): void
    {
        $model = $this->createMockModel('users', 1);

        $result = PathGeneratorService::generate([
            'root' => 'media',
            'strategy' => PathGeneratorService::STRATEGY_DATED,
            'model' => $model,
            'year' => '2025',
            'month' => '11',
        ]);

        $this->assertEquals('media/users/2025/11', $result);
    }

    /**
     * Test paths without model
     */
    public function test_flat_without_model(): void
    {
        $result = PathGeneratorService::generate([
            'root' => 'uploads',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
        ]);

        $this->assertEquals('uploads', $result);
    }

    /**
     * Test dated without model
     */
    public function test_dated_without_model(): void
    {
        $result = PathGeneratorService::generate([
            'root' => 'media',
            'strategy' => PathGeneratorService::STRATEGY_DATED,
            'year' => '2025',
            'month' => '11',
        ]);

        $this->assertEquals('media/2025/11', $result);
    }

    /**
     * Test root normalization with trailing slashes
     */
    public function test_root_normalization(): void
    {
        $result = PathGeneratorService::generate([
            'root' => 'uploads/',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'recordId' => 123,
        ]);

        $this->assertEquals('uploads/123', $result);
        $this->assertFalse(str_contains($result, '//'));
    }

    /**
     * Test recordId overrides model's ID
     */
    public function test_record_id_overrides_model_id(): void
    {
        $model = $this->createMockModel('users', 1);

        $result = PathGeneratorService::generate([
            'root' => 'uploads',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $model,
            'recordId' => 999,  // Override
        ]);

        $this->assertEquals('uploads/users/999', $result);
    }

    /**
     * Test multiple models with different tables
     */
    public function test_multiple_models_with_different_tables(): void
    {
        $articles = $this->createMockModel('articles', 5);
        $users = $this->createMockModel('users', 1);

        $result1 = PathGeneratorService::generate([
            'root' => 'media',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $articles,
        ]);

        $result2 = PathGeneratorService::generate([
            'root' => 'media',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $users,
        ]);

        $this->assertEquals('media/articles/5', $result1);
        $this->assertEquals('media/users/1', $result2);
    }

    /**
     * Test flat strategy with zero record ID
     */
    public function test_flat_strategy_with_zero_record_id(): void
    {
        $result = PathGeneratorService::generate([
            'root' => 'uploads',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'recordId' => 0,
        ]);

        $this->assertEquals('uploads/0', $result);
    }

    /**
     * Test complex root path
     */
    public function test_complex_root_path(): void
    {
        $model = $this->createMockModel('photos', 33);

        $result = PathGeneratorService::generate([
            'root' => 'public/storage/uploads/media',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $model,
        ]);

        $this->assertEquals('public/storage/uploads/media/photos/33', $result);
    }

    /**
     * Test default year and month
     */
    public function test_default_year_and_month(): void
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $result = PathGeneratorService::generate([
            'root' => 'uploads',
            'strategy' => PathGeneratorService::STRATEGY_DATED,
        ]);

        $this->assertStringContainsString($currentYear, $result);
        $this->assertStringContainsString($currentMonth, $result);
    }

    /**
     * Test flat with non-numeric ID
     */
    public function test_flat_with_string_record_id(): void
    {
        $result = PathGeneratorService::generate([
            'root' => 'files',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'recordId' => 'abc123',
        ]);

        $this->assertEquals('files/abc123', $result);
    }

    /**
     * Test model with null ID
     */
    public function test_model_with_null_id(): void
    {
        $model = $this->createMock(Model::class);
        $model->method('getTable')->willReturn('drafts');
        $model->method('getKey')->willReturn(null);

        $result = PathGeneratorService::generate([
            'root' => 'uploads',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $model,
        ]);

        // Should include table but not ID
        $this->assertEquals('uploads/drafts', $result);
    }

    /**
     * Test generateWithSlash convenience method
     */
    public function test_generate_with_slash(): void
    {
        $result = PathGeneratorService::generateWithSlash([
            'root' => 'media',
            'strategy' => PathGeneratorService::STRATEGY_DATED,
            'year' => '2025',
            'month' => '11',
        ]);

        $this->assertTrue(str_ends_with($result, '/'));
        $this->assertEquals('media/2025/11/', $result);
    }

    /**
     * Test path consistency across calls
     */
    public function test_path_consistency(): void
    {
        $model = $this->createMockModel('articles', 42);
        $options = [
            'root' => 'media',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $model,
        ];

        $result1 = PathGeneratorService::generate($options);
        $result2 = PathGeneratorService::generate($options);

        $this->assertEquals($result1, $result2);
    }
}
