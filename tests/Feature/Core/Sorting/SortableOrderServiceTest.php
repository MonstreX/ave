<?php

namespace Monstrex\Ave\Tests\Feature\Core\Sorting;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Exceptions\ResourceException;
use PHPUnit\Framework\TestCase;

class SortableOrderServiceTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $this->capsule->schema()->create('sorting_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('parent_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        $this->capsule->schema()->dropIfExists('sorting_records');
        parent::tearDown();
    }

    public function test_reorder_updates_positions_and_wraps_within_transaction(): void
    {
        SortableTestRecord::insert([
            ['id' => 1, 'title' => 'A', 'sort_order' => 0],
            ['id' => 2, 'title' => 'B', 'sort_order' => 1],
            ['id' => 3, 'title' => 'C', 'sort_order' => 2],
        ]);

        $resource = new SortingTestResource();
        $user = new SortingTestUser(1);

        $realConnection = $this->capsule->getConnection();
        $connection = $this->createMock(ConnectionInterface::class);
        $connection
            ->expects($this->once())
            ->method('transaction')
            ->willReturnCallback(function (callable $callback) use ($realConnection) {
                return $realConnection->transaction($callback);
            });

        $service = new SortableOrderService($connection);

        $service->reorder(
            [3 => 0, 1 => 1, 2 => 2],
            'sort_order',
            $resource,
            $user,
            SortableTestRecord::class,
            'sorting-test'
        );

        $this->assertSame(0, SortableTestRecord::find(3)->sort_order);
        $this->assertSame(1, SortableTestRecord::find(1)->sort_order);
        $this->assertSame(2, SortableTestRecord::find(2)->sort_order);
    }

    public function test_tree_rebuild_rolls_back_when_failure_occurs_during_upsert(): void
    {
        SortableTestRecord::insert([
            ['id' => 1, 'title' => 'Parent', 'sort_order' => 0, 'parent_id' => null],
            ['id' => 2, 'title' => 'Child', 'sort_order' => 1, 'parent_id' => null],
        ]);

        $resource = new SortingTestResource();
        $user = new SortingTestUser(1);
        $table = Table::make()->tree('parent_id', 'sort_order', 5);

        $service = new class($this->capsule->getConnection()) extends SortableOrderService {
            public bool $failAfterUpsert = false;

            protected function chunkedUpsert(string $modelClass, array $payload, string $primaryKey, array $columns): void
            {
                parent::chunkedUpsert($modelClass, $payload, $primaryKey, $columns);

                if ($this->failAfterUpsert) {
                    throw new \RuntimeException('forced failure');
                }
            }
        };
        $service->failAfterUpsert = true;

        $this->expectException(\RuntimeException::class);

        try {
            $service->rebuildTree(
                [
                    [
                        'id' => 1,
                        'children' => [
                            ['id' => 2],
                        ],
                    ],
                ],
                $table,
                $resource,
                $user,
                SortableTestRecord::class,
                'sorting-test'
            );
        } finally {
            // Ensure transaction rolled back
            $this->assertNull(SortableTestRecord::find(2)->parent_id);
        }
    }
}

class SortableTestRecord extends Model
{
    protected $table = 'sorting_records';
    public $timestamps = false;
    protected $guarded = [];
}

class SortingTestResource extends Resource
{
    public static ?string $slug = 'sorting-test';
    public static ?string $model = SortableTestRecord::class;

    public static function table($ctx): Table
    {
        return Table::make()->sortable('sort_order');
    }

    public static function form($ctx): Form
    {
        return Form::make();
    }

    public function can(string $ability, ?Authenticatable $user, mixed $model = null): bool
    {
        return $user !== null;
    }
}

class SortingTestUser implements Authenticatable
{
    public function __construct(private int $id)
    {
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // no-op
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }
}
