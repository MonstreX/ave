<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Monstrex\Ave\Contracts\HandlesNestedCleanup;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Col;
use PHPUnit\Framework\TestCase;

class FieldsetProcessingTest extends TestCase
{
    public function test_fieldset_handles_nested_rows_and_columns(): void
    {
        $fieldset = Fieldset::make('features')->schema([
            Row::make()->schema([
                Col::make(6)->schema([
                    TextInput::make('title')->required(),
                ]),
                Col::make(6)->schema([
                    TextInput::make('subtitle')->required(),
                ]),
            ]),
        ]);

        $request = $this->makeRequest([
            'features' => [
                [
                    '_id' => 5,
                    'title' => 'Hero Section',
                    'subtitle' => 'Lead copy',
                ],
            ],
        ]);

        $context = FormContext::forCreate([], $request);

        $result = $fieldset->prepareForSave($request->input('features'), $request, $context);

        $this->assertSame('Hero Section', $result->value()[0]['title']);
        $this->assertSame('Lead copy', $result->value()[0]['subtitle']);

        $rules = $fieldset->buildValidationRules();
        $this->assertArrayHasKey('features.*.title', $rules);
        $this->assertArrayHasKey('features.*.subtitle', $rules);
    }

    public function test_fieldset_collects_cleanup_actions_for_deleted_items(): void
    {
        CleanupTrackingField::reset();

        $fieldset = Fieldset::make('features')->schema([
            CleanupTrackingField::make('title'),
        ]);

        $model = new FieldsetStubModel();
        $model->setAttribute('features', [
            [
                '_id' => 1,
                'title' => 'Legacy entry',
            ],
        ]);

        $request = $this->makeRequest(['features' => []]);
        $context = FormContext::forEdit($model, [], $request);

        $result = $fieldset->prepareForSave($request->input('features'), $request, $context);

        $this->assertTrue(CleanupTrackingField::$cleanupRequested);
        $this->assertCount(1, $result->deferredActions());
    }

    private function makeRequest(array $data): Request
    {
        return Request::create('/fieldset-test', 'POST', $data);
    }
}

class CleanupTrackingField extends TextInput implements HandlesNestedCleanup
{
    public static bool $cleanupRequested = false;

    public static function reset(): void
    {
        self::$cleanupRequested = false;
    }

    public function getNestedCleanupActions(mixed $value, array $itemData, ?FormContext $context = null): array
    {
        self::$cleanupRequested = true;

        return [
            static function (): void {
                // Intentionally left blank; we only need to ensure closures are registered.
            },
        ];
    }
}

class FieldsetStubModel extends Model
{
    protected $table = 'fieldset_tests';
    protected $guarded = [];
    public $timestamps = false;
}
