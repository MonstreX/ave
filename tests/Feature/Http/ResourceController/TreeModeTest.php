<?php

namespace Monstrex\Ave\Tests\Feature\Http\ResourceController;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Http\Controllers\Resource\Actions\IndexAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\CreateAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\StoreAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\EditAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\UpdateAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\DestroyAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\ReorderAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\UpdateGroupAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\UpdateTreeAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\InlineUpdateAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunRowAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunBulkAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunGlobalAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunFormAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\TableJsonAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\FormJsonAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\GetModalFormAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\GetModalFormCreateAction;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Http\Controllers\ResourceController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TreeModeTest extends TestCase
{
    private Capsule $capsule;

    private ResourceController $controller;

    /** @var ResourceManager&MockObject */
    private ResourceManager $resourceManager;

    /** @var ResourceRenderer&MockObject */
    private ResourceRenderer $renderer;

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

        $this->capsule->schema()->create('tree_test_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('parent_id')->nullable();
        });

        TreeTestResource::resetTableFactory();

        $this->resourceManager = $this->createMock(ResourceManager::class);
        $this->resourceManager->method('resource')
            ->with('tree-test')
            ->willReturn(TreeTestResource::class);

        $this->renderer = $this->createMock(ResourceRenderer::class);
        $validator = $this->createMock(FormValidator::class);
        $persistence = $this->createMock(ResourcePersistence::class);

        $sortingService = new SortableOrderService($this->capsule->getConnection());

        $this->controller = new ResourceController(
            $this->resourceManager,
            $this->renderer,
            $validator,
            $persistence,
            $sortingService,
            new IndexAction($this->resourceManager, $this->renderer),
            new CreateAction($this->resourceManager, $this->renderer),
            new StoreAction($this->resourceManager, $validator, $persistence),
            new EditAction($this->resourceManager, $this->renderer),
            new UpdateAction($this->resourceManager, $validator, $persistence),
            new DestroyAction($this->resourceManager, $persistence),
            new ReorderAction($this->resourceManager, $sortingService),
            new UpdateGroupAction($this->resourceManager),
            new UpdateTreeAction($this->resourceManager, $sortingService),
            new InlineUpdateAction($this->resourceManager),
            new RunRowAction($this->resourceManager),
            new RunBulkAction($this->resourceManager),
            new RunGlobalAction($this->resourceManager),
            new RunFormAction($this->resourceManager),
            new TableJsonAction($this->resourceManager),
            new FormJsonAction($this->resourceManager),
            new GetModalFormAction($this->resourceManager, $this->renderer),
            new GetModalFormCreateAction($this->resourceManager, $this->renderer)
        );
    }

    protected function tearDown(): void
    {
        TreeTestResource::resetTableFactory();
        $this->capsule->schema()->dropIfExists('tree_test_records');
        parent::tearDown();
    }

    public function test_tree_mode_throws_when_limit_exceeded_and_force_enabled(): void
    {
        $this->seedRecords(600);

        TreeTestResource::setTableFactory(
            fn () => Table::make()
                ->tree()
                ->maxInstantLoad(500)
                ->forcePaginationOnOverflow(true)
        );

        $request = $this->makeRequest();
        $this->renderer->expects($this->never())->method('index');

        $this->expectException(ResourceException::class);
        $this->expectExceptionCode(422);

        $this->controller->index($request, 'tree-test');
    }

    public function test_tree_mode_returns_all_records_within_limit(): void
    {
        $this->seedRecords(50);

        TreeTestResource::setTableFactory(
            fn () => Table::make()
                ->tree()
                ->maxInstantLoad(500)
        );

        $request = $this->makeRequest();

        $this->renderer->expects($this->once())
            ->method('index')
            ->willReturnCallback(function (
                string $resourceClass,
                Table $table,
                LengthAwarePaginator $records,
                Request $incomingRequest,
                array $badges
            ) {
                $this->assertSame(TreeTestResource::class, $resourceClass);
                $this->assertSame(50, $records->total());
                $this->assertCount(50, $records->items());
                $this->assertEmpty($badges);

                return new Response('ok', 200);
            });

        $response = $this->controller->index($request, 'tree-test');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_tree_mode_soft_limit_truncates_and_adds_badge(): void
    {
        $this->seedRecords(600);

        TreeTestResource::setTableFactory(
            fn () => Table::make()
                ->tree()
                ->maxInstantLoad(100)
                ->forcePaginationOnOverflow(false)
        );

        $request = $this->makeRequest();

        $this->renderer->expects($this->once())
            ->method('index')
            ->willReturnCallback(function (
                string $resourceClass,
                Table $table,
                LengthAwarePaginator $records,
                Request $incomingRequest,
                array $badges
            ) {
                $this->assertSame(101, $records->total(), 'Total should reflect overflow sentinel.');
                $this->assertCount(100, $records->items(), 'Records should be truncated to limit.');
                $this->assertNotEmpty($badges, 'Limit warning badge must be present.');
                $this->assertSame('limit-warning', $badges[0]['variant']);
                $this->assertSame(100, $badges[0]['value']);

                return new Response('ok', 200);
            });

        $response = $this->controller->index($request, 'tree-test');
        $this->assertSame(200, $response->getStatusCode());
    }

    private function seedRecords(int $count): void
    {
        $payload = [];
        for ($i = 1; $i <= $count; $i++) {
            $payload[] = [
                'title' => "Record {$i}",
                'order' => $i,
            ];
        }

        TreeTestRecord::query()->insert($payload);
    }

    private function makeRequest(): Request
    {
        $request = Request::create('/admin/resource/tree-test', 'GET');
        $request->setUserResolver(fn () => new TreeTestUser(1));

        return $request;
    }
}

class TreeTestRecord extends Model
{
    protected $table = 'tree_test_records';
    public $timestamps = false;
    protected $guarded = [];
}

class TreeTestResource extends Resource
{
    public static ?string $slug = 'tree-test';
    public static ?string $model = TreeTestRecord::class;

    private static ?\Closure $tableFactory = null;

    public static function table($ctx): Table
    {
        if (static::$tableFactory) {
            return call_user_func(static::$tableFactory, $ctx);
        }

        return Table::make()->tree();
    }

    public static function form($ctx): Form
    {
        return Form::make();
    }

    public static function setTableFactory(?\Closure $factory): void
    {
        static::$tableFactory = $factory;
    }

    public static function resetTableFactory(): void
    {
        static::$tableFactory = null;
    }

    public function can(string $ability, ?Authenticatable $user, mixed $model = null): bool
    {
        return $user !== null;
    }
}

class TreeTestUser implements Authenticatable
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
