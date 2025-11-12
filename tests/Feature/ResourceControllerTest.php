<?php

namespace Monstrex\Ave\Tests\Feature;

use BadMethodCallException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Monstrex\Ave\Core\Actions\BaseAction;
use Monstrex\Ave\Core\Actions\Contracts\BulkAction as BulkActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Filters\SelectFilter;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Http\Controllers\ResourceController;
use PHPUnit\Framework\TestCase;

class ResourceControllerTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        Container::setInstance($container);

        $translator = new Translator(new ArrayLoader(), 'en');
        $validatorFactory = new ValidationFactory($translator, $container);

        $container->instance('translator', $translator);
        $container->instance('validator', $validatorFactory);
        $container->instance(ValidationFactoryContract::class, $validatorFactory);
        $container->instance(ResponseFactoryContract::class, new TestingResponseFactory());

        if (!Request::hasMacro('validate')) {
            Request::macro('validate', function (array $rules) {
                /** @var ValidationFactoryContract $factory */
                $factory = Container::getInstance()->make(ValidationFactoryContract::class);
                $validator = $factory->make($this->all(), $rules);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                return $validator->validated();
            });
        }

        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $this->capsule->schema()->create('test_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('tenant_id')->default(1);
            $table->timestamps();
        });

        TestBulkAction::reset();
        TestResource::resetTableFactory();
    }

    protected function tearDown(): void
    {
        $this->capsule->schema()->drop('test_records');
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_index_applies_search_and_filters(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Alpha', 'is_published' => true, 'tenant_id' => 1],
            ['id' => 2, 'title' => 'Beta', 'is_published' => false, 'tenant_id' => 1],
            ['id' => 3, 'title' => 'Alpine', 'is_published' => true, 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()
                ->columns([
                    Column::make('title')->searchable()->sortable(),
                ])
                ->filters([
                    SelectFilter::make('is_published')->options([
                        '1' => 'Published',
                        '0' => 'Draft',
                    ]),
                ])
                ->perPage(2)
                ->defaultSort('id', 'asc');
        });

        $capturedPaginator = null;
        $capturedBadges = null;

        $renderer = $this->createMock(ResourceRenderer::class);
        $renderer->expects($this->once())
            ->method('index')
            ->willReturnCallback(function ($resourceClass, $table, $records, $request, $badges) use (&$capturedPaginator, &$capturedBadges) {
                $capturedPaginator = $records;
                $capturedBadges = $badges;
                return 'rendered';
            });

        $controller = $this->makeController($renderer);

        $request = Request::create('/admin/resource/test-items', 'GET', [
            'q' => 'Al',
            'is_published' => '1',
        ]);
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $response = $controller->index($request, 'test-items');

        $this->assertSame('rendered', $response);
        $this->assertInstanceOf(LengthAwarePaginator::class, $capturedPaginator);
        $this->assertSame(2, $capturedPaginator->total());

        $titles = array_map(
            fn ($record) => $record->title,
            $capturedPaginator->items()
        );
        $this->assertSame(['Alpha', 'Alpine'], $titles);
        $this->assertNotEmpty($capturedBadges, 'Expected at least one badge for applied filters');
    }

    public function test_bulk_action_returns_success_response(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Alpha', 'is_published' => true, 'tenant_id' => 1],
            ['id' => 2, 'title' => 'Beta', 'is_published' => false, 'tenant_id' => 1],
        ]);

        $controller = $this->makeController();

        $request = Request::create('/admin/resource/test-items/actions/test-bulk/bulk', 'POST', [
            'ids' => ['1', '2'],
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => new FakeUser(99, 1));

        $response = $controller->runBulkAction($request, 'test-items', 'test-bulk');

        $this->assertSame(['1', '2'], TestBulkAction::$handled['ids']);
        $this->assertSame(2, TestBulkAction::$handled['count']);

        $payload = $response->getData(true);
        $this->assertSame('success', $payload['status']);
        $this->assertSame('Bulk complete', $payload['message']);
    }

    public function test_bulk_action_throws_not_found_when_any_id_missing(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Alpha', 'is_published' => true, 'tenant_id' => 1],
        ]);

        $controller = $this->makeController();

        $request = Request::create('/admin/resource/test-items/actions/test-bulk/bulk', 'POST', [
            'ids' => ['1', '99'],
        ]);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage("Model '99' not found");

        $controller->runBulkAction($request, 'test-items', 'test-bulk');
    }

    public function test_bulk_action_throws_unauthorized_when_model_denied(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Alpha', 'is_published' => true, 'tenant_id' => 1],
            ['id' => 2, 'title' => 'Beta', 'is_published' => false, 'tenant_id' => 2],
        ]);

        $controller = $this->makeController();

        $request = Request::create('/admin/resource/test-items/actions/test-bulk/bulk', 'POST', [
            'ids' => ['1', '2'],
        ]);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage("Unauthorized");

        $controller->runBulkAction($request, 'test-items', 'test-bulk');
    }

    private function makeController(?ResourceRenderer $renderer = null): ResourceController
    {
        $resourceManager = $this->createMock(ResourceManager::class);
        $resourceManager->method('resource')->willReturn(TestResource::class);

        $renderer ??= $this->createMock(ResourceRenderer::class);
        $validator = $this->createMock(FormValidator::class);
        $persistence = $this->createMock(ResourcePersistence::class);

        return new ResourceController(
            $resourceManager,
            $renderer,
            $validator,
            $persistence
        );
    }
}

class TestRecord extends Model
{
    protected $table = 'test_records';
    public $timestamps = false;
    protected $guarded = [];
}

class TestResource extends Resource
{
    public static ?string $slug = 'test-items';
    public static ?string $model = TestRecord::class;
    private static ?\Closure $tableFactory = null;

    public static function actions(): array
    {
        return [TestBulkAction::class];
    }

    public static function table($ctx): Table
    {
        if (static::$tableFactory) {
            return call_user_func(static::$tableFactory, $ctx);
        }

        return Table::make();
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
        if (!$user) {
            return false;
        }

        if (!$model) {
            return true;
        }

        return (int) $model->tenant_id === (int) $user->tenant_id;
    }
}

class TestBulkAction extends BaseAction implements BulkActionContract
{
    protected string $key = 'test-bulk';
    protected ?string $ability = 'update';
    public static array $handled = [];

    public static function reset(): void
    {
        static::$handled = [];
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        static::$handled = [
            'ids' => $context->ids(),
            'count' => $context->models()?->count() ?? 0,
        ];

        return [
            'message' => 'Bulk complete',
            'reload' => false,
        ];
    }
}

class FakeUser implements Authenticatable
{
    public function __construct(
        private int $id,
        public int $tenant_id
    ) {
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

class TestingResponseFactory implements ResponseFactoryContract
{
    public function make($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    public function noContent($status = 204, array $headers = [])
    {
        return $this->make('', $status, $headers);
    }

    public function view($view, $data = [], $status = 200, array $headers = [])
    {
        return $this->make($view, $status, $headers);
    }

    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0)
    {
        return $this->json($data, $status, $headers, $options)->setCallback($callback);
    }

    public function stream($callback, $status = 200, array $headers = [])
    {
        throw new BadMethodCallException('Stream responses are not supported in tests.');
    }

    public function streamJson($data, $status = 200, $headers = [], $encodingOptions = 15)
    {
        throw new BadMethodCallException('Stream responses are not supported in tests.');
    }

    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment')
    {
        throw new BadMethodCallException('Stream responses are not supported in tests.');
    }

    public function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        throw new BadMethodCallException('Download responses are not supported in tests.');
    }

    public function file($file, array $headers = [])
    {
        throw new BadMethodCallException('File responses are not supported in tests.');
    }

    public function redirectTo($path, $status = 302, $headers = [], $secure = null)
    {
        return new RedirectResponse($path, $status, $headers);
    }

    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = [])
    {
        throw new BadMethodCallException('Redirect helpers are not supported in tests.');
    }

    public function redirectToAction($action, $parameters = [], $status = 302, $headers = [])
    {
        throw new BadMethodCallException('Redirect helpers are not supported in tests.');
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        return $this->redirectTo($path, $status, $headers, $secure);
    }

    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        return $this->redirectTo($default, $status, $headers, $secure);
    }
}
