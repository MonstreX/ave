<?php

namespace Monstrex\Ave\Tests\Unit\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Criteria\CriteriaPipeline;
use Monstrex\Ave\Core\Criteria\FieldEqualsFilter;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Table;
use PHPUnit\Framework\TestCase;

class CriteriaPipelineTest extends TestCase
{
    public function test_pipeline_applies_registered_criteria(): void
    {
        $table = Table::make()->columns([
            Column::make('title')->searchable(true),
            Column::make('created_at')->sortable(true),
        ]);

        $request = Request::create('/admin', 'GET', [
            'q' => 'demo',
            'sort' => 'created_at',
            'status' => 'published',
        ]);

        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->addMethods(['orderBy'])
            ->getMock();
        $builder->method('where')->willReturnSelf();
        $builder->method('orderBy')->willReturnSelf();

        $pipeline = CriteriaPipeline::make(TestResource::class, $table, $request);
        $result = $pipeline->apply($builder);

        $this->assertSame($builder, $result);
        $badges = $pipeline->badges();
        $this->assertNotEmpty($badges);
        $this->assertGreaterThanOrEqual(2, count($badges));
    }
}

class TestCriteriaModel extends Model
{
    use SoftDeletes;

    protected $table = 'test_models';
}

class TestResource extends Resource
{
    public static ?string $model = TestCriteriaModel::class;
    public static array $searchable = ['title'];
    public static array $sortable = ['created_at'];

    public static function getCriteria(): array
    {
        return [
            new FieldEqualsFilter('status', 'status', '=', 'Status'),
        ];
    }
}
