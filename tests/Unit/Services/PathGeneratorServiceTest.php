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
     * Test dated strategy without model (root only)
     */
    public function test_dated_strategy_without_model(): void
    {
        $result = PathGeneratorService::generate([
            'root' => 'uploads/files',
            'strategy' => PathGeneratorService::STRATEGY_DATED,
            'year' => '2025',
            'month' => '11',
        ]);

        $this->assertEquals('uploads/files/2025/11', $result);
    }

    /**
     * Test flat strategy without model (only recordId)
     */
    public function test_flat_strategy_with_only_record_id(): void
    {
        $result = PathGeneratorService::generate([
            'root' => 'uploads',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'recordId' => 99,
        ]);

        $this->assertEquals('uploads/99', $result);
    }

    /**
     * Test default strategy is dated
     */
    public function test_default_strategy_is_dated(): void
    {
        $model = $this->createMockModel('posts', 5);

        $result = PathGeneratorService::generate([
            'root' => 'media',
            'model' => $model,
            'year' => '2024',
            'month' => '12',
        ]);

        $this->assertEquals('media/posts/2024/12', $result);
    }

    /**
     * Test root normalization (remove trailing slashes)
     */
    public function test_root_normalization(): void
    {
        $model = $this->createMockModel('articles', 1);

        $result = PathGeneratorService::generate([
            'root' => 'uploads/files/',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $model,
        ]);

        $this->assertEquals('uploads/files/articles/1', $result);
    }

    /**
     * Test path with trailing slash
     */
    public function test_generate_with_trailing_slash(): void
    {
        $model = $this->createMockModel('users', 10);

        $result = PathGeneratorService::generateWithSlash([
            'root' => 'media',
            'strategy' => PathGeneratorService::STRATEGY_FLAT,
            'model' => $model,
        ]);

        $this->assertEquals('media/users/10/', $result);
        $this->assertStringEndsWith('/', $result);
    }

    /**
     * Test custom callback strategy
     */
    public function test_custom_callback_strategy(): void
    {
        $model = $this->createMockModel('articles', 42);

        $callback = function($model, $recordId, $root, $date) {
            return "{$root}/custom/{$model->getTable()}/{$date->year}";
        };

        $result = PathGeneratorService::generate([
            'root' => 'uploads',
            'strategy' => 'callback',
            'model' => $model,
            'callback' => $callback,
            'year' => '2025',
        ]);

        $this->assertEquals('uploads/custom/articles/2025', $result);
    }

    /**
     * Test callback with null model
     */
    public function test_callback_with_null_model(): void
    {
        $callback = function($model, $recordId, $root, $date) {
            return "{$root}/standalone/{$date->year}/{$date->month}";
        };

        $result = PathGeneratorService::generate([
            'root' => 'files',
            'strategy' => 'callback',
            'callback' => $callback,
            'year' => '2025',
            'month' => '11',
        ]);

        $this->assertEquals('files/standalone/2025/11', $result);
    }

    /**
     * Test callback with leading/trailing slashes normalization
     */
    public function test_callback_normalizes_slashes(): void
    {
        $callback = function($model, $recordId, $root, $date) {
            return "/media/normalized/{$date->year}/";
        };

        $result = PathGeneratorService::generate([
            'root' => 'media',
            'strategy' => 'callback',
            'callback' => $callback,
            'year' => '2025',
        ]);

        // Should remove leading slash but keep internal structure
        $this->assertEquals('media/normalized/2025', $result);
        $this->assertFalse(str_starts_with($result, '/'));
        $this->assertFalse(str_ends_with($result, '/'));
    }

    /**
     * Test date object in callback
     */
    public function test_callback_receives_date_object(): void
    {
        $receivedDate = null;

        $callback = function($model, $recordId, $root, $date) use (&$receivedDate) {
            $receivedDate = $date;
            return $root;
        };

        PathGeneratorService::generate([
            'root' => 'media',
            'strategy' => 'callback',
            'callback' => $callback,
            'year' => '2025',
            'month' => '11',
        ]);

        $this->assertNotNull($receivedDate);
        $this->assertEquals('2025', $receivedDate->year);
        $this->assertEquals('11', $receivedDate->month);
        $this->assertEquals('2025/11', $receivedDate->full);
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
     * Test callback receives all parameters correctly
     */
    public function test_callback_receives_all_parameters(): void
    {
        $receivedParams = [];

        $callback = function($model, $recordId, $root, $date) use (&$receivedParams) {
            $receivedParams = [
                'model' => $model,
                'recordId' => $recordId,
                'root' => $root,
                'date' => $date,
            ];
            return $root;
        };

        $model = $this->createMockModel('articles', 42);

        PathGeneratorService::generate([
            'root' => 'media',
            'strategy' => 'callback',
            'model' => $model,
            'recordId' => 42,
            'callback' => $callback,
        ]);

        $this->assertNotNull($receivedParams['model']);
        $this->assertEquals(42, $receivedParams['recordId']);
        $this->assertEquals('media', $receivedParams['root']);
        $this->assertNotNull($receivedParams['date']);
    }
}
