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
use Illuminate\Routing\Redirector;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Core\Actions\Contracts\FormAction as FormActionContract;
use Monstrex\Ave\Core\Actions\Contracts\GlobalAction as GlobalActionContract;
use Monstrex\Ave\Core\Actions\Contracts\RowAction as RowActionContract;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Monstrex\Ave\Core\Actions\BaseAction;
use Monstrex\Ave\Core\Actions\Contracts\BulkAction as BulkActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Columns\BooleanColumn;
use Monstrex\Ave\Core\Filters\SelectFilter;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Http\Controllers\Resource\Actions\CreateAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\DestroyAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\EditAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\IndexAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\ReorderAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\StoreAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\UpdateAction;
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

        $redirector = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['route', 'to'])
            ->getMock();
        $redirector->method('route')->willReturnCallback(
            fn ($name, $parameters = [], $status = 302, $headers = []) => new TestingRedirectResponse(
                is_string($name) ? $name : (string) $name,
                (array) $parameters,
                $status,
                $headers
            )
        );
        $redirector->method('to')->willReturnCallback(
            fn ($path, $status = 302, $headers = [], $secure = null) => new TestingRedirectResponse(
                $path,
                [],
                $status,
                $headers
            )
        );
        $container->instance('redirect', $redirector);

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
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
        });

        TestBulkAction::reset();
        TestRowAction::reset();
        TestGlobalAction::reset();
        TestFormAction::reset();
        TestResource::resetFactories();
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
        $this->expectExceptionMessage("Bulk action 'update' denied for");

        $controller->runBulkAction($request, 'test-items', 'test-bulk');
    }

    public function test_row_action_executes_successfully(): void
    {
        $record = TestRecord::create([
            'id' => 10,
            'title' => 'Row item',
            'is_published' => true,
            'tenant_id' => 1,
        ]);

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/actions/test-row', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => new FakeUser(5, 1));

        $response = $controller->runRowAction($request, 'test-items', (string) $record->getKey(), 'test-row');
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame($record->getKey(), TestRowAction::$handled['id']);
    }

    public function test_row_action_denied_by_policy(): void
    {
        $record = TestRecord::create([
            'id' => 11,
            'title' => 'Forbidden',
            'tenant_id' => 2,
        ]);

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/actions/test-row', 'POST');
        $request->setUserResolver(fn () => new FakeUser(7, 1));

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage("Unauthorized");

        $controller->runRowAction($request, 'test-items', (string) $record->getKey(), 'test-row');
    }

    public function test_global_action_executes(): void
    {
        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/actions/test-global/global', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => new FakeUser(9, 1));

        $response = $controller->runGlobalAction($request, 'test-items', 'test-global');
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertTrue(TestGlobalAction::$called);
    }

    public function test_form_action_executes(): void
    {
        $record = TestRecord::create([
            'id' => 12,
            'title' => 'Form action',
            'tenant_id' => 1,
        ]);

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/actions/test-form/form', 'POST', [
            'note' => 'ready',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => new FakeUser(3, 1));

        $response = $controller->runFormAction($request, 'test-items', 'test-form', (string) $record->getKey());
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame('ready', TestFormAction::$handled['note']);
    }

    public function test_form_action_validation_error(): void
    {
        $record = TestRecord::create([
            'id' => 13,
            'title' => 'Form invalid',
            'tenant_id' => 1,
        ]);

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/actions/test-form/form', 'POST', []);
        $request->setUserResolver(fn () => new FakeUser(4, 1));

        $this->expectException(ValidationException::class);

        $controller->runFormAction($request, 'test-items', 'test-form', (string) $record->getKey());
    }

    public function test_create_renders_form_view(): void
    {
        $renderer = $this->createMock(ResourceRenderer::class);
        $renderer->expects($this->once())
            ->method('form')
            ->with(
                TestResource::class,
                $this->isInstanceOf(Form::class),
                $this->callback(fn ($model) => $model instanceof TestRecord && !$model->exists),
                $this->isInstanceOf(Request::class)
            )
            ->willReturn('rendered-create');

        $controller = $this->makeController($renderer);
        $request = Request::create('/admin/resource/test-items/create', 'GET');
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $result = $controller->create($request, 'test-items');
        $this->assertSame('rendered-create', $result);
    }

    public function test_edit_renders_form_view(): void
    {
        $record = TestRecord::create([
            'id' => 14,
            'title' => 'Edit me',
            'tenant_id' => 1,
        ]);

        $renderer = $this->createMock(ResourceRenderer::class);
        $renderer->expects($this->once())
            ->method('form')
            ->with(
                TestResource::class,
                $this->isInstanceOf(Form::class),
                $this->callback(fn ($model) => $model instanceof TestRecord && $model->getKey() === $record->getKey()),
                $this->isInstanceOf(Request::class)
            )
            ->willReturn('rendered-edit');

        $controller = $this->makeController($renderer);
        $request = Request::create('/admin/resource/test-items/14/edit', 'GET');
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $result = $controller->edit($request, 'test-items', (string) $record->getKey());
        $this->assertSame('rendered-edit', $result);
    }

    public function test_store_invokes_persistence_and_redirects(): void
    {
        TestResource::setFormFactory(fn () => Form::make());

        $renderer = $this->createMock(ResourceRenderer::class);
        $validator = $this->createMock(FormValidator::class);
        $validator->expects($this->once())
            ->method('rulesFromForm')
            ->willReturn(['title' => 'required']);

        $persistence = $this->createMock(ResourcePersistence::class);
        $persistence->expects($this->once())
            ->method('create')
            ->with(
                TestResource::class,
                $this->isInstanceOf(Form::class),
                $this->equalTo(['title' => 'Created']),
                $this->isInstanceOf(Request::class),
                $this->isInstanceOf(\Monstrex\Ave\Core\FormContext::class)
            )
            ->willReturn(new TestRecord(['id' => 200, 'title' => 'Created', 'tenant_id' => 1]));

        $controller = $this->makeController($renderer, $validator, $persistence);

        $request = Request::create('/admin/resource/test-items', 'POST', ['title' => 'Created']);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $response = $controller->store($request, 'test-items');
        $this->assertInstanceOf(TestingRedirectResponse::class, $response);
        $this->assertSame('ave.resource.index', $response->route);
        $this->assertSame(['status' => 'Created successfully', 'model_id' => 200], $response->flash);
    }

    public function test_store_save_and_continue_redirects_back_to_edit(): void
    {
        TestResource::setFormFactory(fn () => Form::make());

        $renderer = $this->createMock(ResourceRenderer::class);
        $validator = $this->createMock(FormValidator::class);
        $validator->method('rulesFromForm')->willReturn(['title' => 'required']);

        $persistence = $this->createMock(ResourcePersistence::class);
        $persistence->method('create')
            ->willReturn(new TestRecord(['id' => 201, 'title' => 'Created', 'tenant_id' => 1]));

        $controller = $this->makeController($renderer, $validator, $persistence);

        $request = Request::create('/admin/resource/test-items', 'POST', [
            'title' => 'Created',
            '_ave_form_action' => 'save-continue',
        ]);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $response = $controller->store($request, 'test-items');
        $this->assertInstanceOf(TestingRedirectResponse::class, $response);
        $this->assertSame('ave.resource.edit', $response->route);
        $this->assertSame(['slug' => 'test-items', 'id' => 201], $response->parameters);
        $this->assertSame(['status' => 'Created successfully'], $response->flash);
    }

    public function test_update_invokes_persistence_and_redirects(): void
    {
        TestResource::setFormFactory(fn () => Form::make());
        $record = TestRecord::create([
            'id' => 300,
            'title' => 'Before',
            'tenant_id' => 1,
        ]);

        $renderer = $this->createMock(ResourceRenderer::class);
        $validator = $this->createMock(FormValidator::class);
        $validator->expects($this->once())
            ->method('rulesFromForm')
            ->willReturn(['title' => 'required']);

        $persistence = $this->createMock(ResourcePersistence::class);
        $persistence->expects($this->once())
            ->method('update')
            ->willReturn($record);

        $controller = $this->makeController($renderer, $validator, $persistence);

        $request = Request::create('/admin/resource/test-items/300', 'POST', ['title' => 'After']);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $response = $controller->update($request, 'test-items', '300');
        $this->assertInstanceOf(TestingRedirectResponse::class, $response);
        $this->assertSame('ave.resource.index', $response->route);
        $this->assertSame(['status' => 'Updated successfully', 'model_id' => 300], $response->flash);
    }

    public function test_update_save_and_continue_redirects_back_to_edit(): void
    {
        TestResource::setFormFactory(fn () => Form::make());
        $record = TestRecord::create([
            'id' => 301,
            'title' => 'Before',
            'tenant_id' => 1,
        ]);

        $renderer = $this->createMock(ResourceRenderer::class);
        $validator = $this->createMock(FormValidator::class);
        $validator->method('rulesFromForm')->willReturn(['title' => 'required']);

        $persistence = $this->createMock(ResourcePersistence::class);
        $persistence->method('update')->willReturn($record);

        $controller = $this->makeController($renderer, $validator, $persistence);

        $request = Request::create('/admin/resource/test-items/301', 'POST', [
            'title' => 'After',
            '_ave_form_action' => 'save-continue',
        ]);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $response = $controller->update($request, 'test-items', '301');
        $this->assertInstanceOf(TestingRedirectResponse::class, $response);
        $this->assertSame('ave.resource.edit', $response->route);
        $this->assertSame(['slug' => 'test-items', 'id' => 301], $response->parameters);
        $this->assertSame(['status' => 'Updated successfully'], $response->flash);
    }

    public function test_destroy_invokes_delete_and_redirects(): void
    {
        $record = TestRecord::create([
            'id' => 400,
            'title' => 'Destroy me',
            'tenant_id' => 1,
        ]);

        $renderer = $this->createMock(ResourceRenderer::class);
        $validator = $this->createMock(FormValidator::class);
        $persistence = $this->createMock(ResourcePersistence::class);
        $persistence->expects($this->once())
            ->method('delete')
            ->with(TestResource::class, $this->isInstanceOf(TestRecord::class));

        $controller = $this->makeController($renderer, $validator, $persistence);

        $request = Request::create('/admin/resource/test-items/400', 'DELETE');
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $response = $controller->destroy($request, 'test-items', '400');
        $this->assertInstanceOf(TestingRedirectResponse::class, $response);
        $this->assertSame('ave.resource.index', $response->route);
        $this->assertSame(['status' => 'Deleted successfully'], $response->flash);
    }

    public function test_table_json_returns_schema(): void
    {
        TestResource::setTableFactory(function () {
            return Table::make()->columns([
                Column::make('title')->label('Title'),
            ]);
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/table.json', 'GET');
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $response = $controller->tableJson($request, 'test-items');
        $payload = $response->getData(true);

        $this->assertSame('Title', $payload['columns'][0]['label']);
    }

    public function test_form_json_returns_layout(): void
    {
        TestResource::setFormFactory(fn () => Form::make());

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/form.json', 'GET');
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $response = $controller->formJson($request, 'test-items');
        $payload = $response->getData(true);

        $this->assertIsArray($payload);
    }

    public function test_table_json_requires_authorization(): void
    {
        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/table.json', 'GET');
        $request->setUserResolver(fn () => null);

        $this->expectException(ResourceException::class);
        $controller->tableJson($request, 'test-items');
    }

    public function test_form_json_requires_authorization(): void
    {
        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/form.json', 'GET');
        $request->setUserResolver(fn () => null);

        $this->expectException(ResourceException::class);
        $controller->formJson($request, 'test-items');
    }

    public function test_index_uses_api_per_page_preference(): void
    {
        TestRecord::insert([
            ['title' => 'A', 'tenant_id' => 1],
            ['title' => 'B', 'tenant_id' => 1],
            ['title' => 'C', 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()
                ->columns([Column::make('title')])
                ->perPage(25)
                ->perPageOptions([10, 25, 50]);
        });

        $capturedPerPage = null;
        $renderer = $this->createMock(ResourceRenderer::class);
        $renderer->expects($this->once())
            ->method('index')
            ->willReturnCallback(function ($resourceClass, $table, LengthAwarePaginator $records) use (&$capturedPerPage) {
                $capturedPerPage = $records->perPage();
                return 'rendered';
            });

        $controller = $this->makeController($renderer);
        $request = Request::create('/admin/resource/test-items?per_page=10', 'GET');
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $controller->index($request, 'test-items');
        $this->assertSame(10, $capturedPerPage);
    }

    public function test_index_rejects_invalid_per_page(): void
    {
        TestResource::setTableFactory(function () {
            return Table::make()
                ->columns([Column::make('title')])
                ->perPageOptions([10, 25]);
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items?per_page=5', 'GET');
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $this->expectException(ResourceException::class);
        $controller->index($request, 'test-items');
    }

    public function test_reorder_updates_order_for_items(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Item 1', 'order' => 0, 'tenant_id' => 1],
            ['id' => 2, 'title' => 'Item 2', 'order' => 1, 'tenant_id' => 1],
            ['id' => 3, 'title' => 'Item 3', 'order' => 2, 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()->sortable('order');
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/reorder', 'POST', [
            'items' => [
                ['id' => 3, 'order' => 0],
                ['id' => 1, 'order' => 1],
                ['id' => 2, 'order' => 2],
            ],
        ]);
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $response = $controller->reorder($request, 'test-items');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);

        // Verify order was updated
        $this->assertSame(0, TestRecord::find(3)->order);
        $this->assertSame(1, TestRecord::find(1)->order);
        $this->assertSame(2, TestRecord::find(2)->order);
    }

    public function test_reorder_requires_sortable_mode(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Item 1', 'order' => 0, 'tenant_id' => 1],
        ]);

        // No sortable() call
        TestResource::setTableFactory(function () {
            return Table::make();
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/reorder', 'POST', [
            'items' => [
                ['id' => 1, 'order' => 0],
            ],
        ]);
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $this->expectException(ResourceException::class);
        $controller->reorder($request, 'test-items');
    }

    public function test_update_tree_updates_hierarchy_and_order(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Parent 1', 'parent_id' => null, 'order' => 0, 'tenant_id' => 1],
            ['id' => 2, 'title' => 'Child 1', 'parent_id' => null, 'order' => 1, 'tenant_id' => 1],
            ['id' => 3, 'title' => 'Child 2', 'parent_id' => null, 'order' => 2, 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()->tree('parent_id', 'order');
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/update-tree', 'POST', [
            'tree' => [
                [
                    'id' => 1,
                    'children' => [
                        ['id' => 2],
                        ['id' => 3],
                    ],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $response = $controller->updateTree($request, 'test-items');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);

        // Verify hierarchy was updated
        $item1 = TestRecord::find(1);
        $item2 = TestRecord::find(2);
        $item3 = TestRecord::find(3);

        $this->assertNull($item1->parent_id);
        $this->assertSame(0, $item1->order);

        $this->assertSame(1, $item2->parent_id);
        $this->assertSame(0, $item2->order);

        $this->assertSame(1, $item3->parent_id);
        $this->assertSame(1, $item3->order);
    }

    public function test_update_tree_requires_tree_mode(): void
    {
        TestResource::setTableFactory(function () {
            return Table::make(); // default table mode
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/update-tree', 'POST', [
            'tree' => [],
        ]);
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $this->expectException(ResourceException::class);
        $controller->updateTree($request, 'test-items');
    }

    public function test_update_tree_respects_max_depth(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Parent', 'parent_id' => null, 'order' => 0, 'tenant_id' => 1],
            ['id' => 2, 'title' => 'Child', 'parent_id' => null, 'order' => 1, 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()->tree('parent_id', 'order', 1); // only root level allowed
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/update-tree', 'POST', [
            'tree' => [
                [
                    'id' => 1,
                    'children' => [
                        ['id' => 2],
                    ],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $this->expectException(ResourceException::class);
        $controller->updateTree($request, 'test-items');
    }

    public function test_index_loads_all_records_for_tree_mode(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Item 1', 'parent_id' => null, 'order' => 0, 'tenant_id' => 1],
            ['id' => 2, 'title' => 'Item 2', 'parent_id' => 1, 'order' => 0, 'tenant_id' => 1],
            ['id' => 3, 'title' => 'Item 3', 'parent_id' => null, 'order' => 1, 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()
                ->tree('parent_id', 'order')
                ->columns([Column::make('title')])
                ->perPage(2); // Pagination should be ignored
        });

        $capturedPaginator = null;
        $renderer = $this->createMock(ResourceRenderer::class);
        $renderer->expects($this->once())
            ->method('index')
            ->willReturnCallback(function ($resourceClass, $table, $records, $request, $badges) use (&$capturedPaginator) {
                $capturedPaginator = $records;
                return 'rendered';
            });

        $controller = $this->makeController($renderer);
        $request = Request::create('/admin/resource/test-items', 'GET');
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $response = $controller->index($request, 'test-items');

        $this->assertSame('rendered', $response);
        $this->assertInstanceOf(LengthAwarePaginator::class, $capturedPaginator);
        // All 3 records should be loaded, not just 2 (perPage)
        $this->assertSame(3, $capturedPaginator->total());
        $this->assertSame(3, $capturedPaginator->count());
    }

    public function test_index_tree_mode_with_no_records(): void
    {
        // No records inserted
        TestResource::setTableFactory(function () {
            return Table::make()
                ->tree('parent_id', 'order')
                ->columns([Column::make('title')]);
        });

        $capturedPaginator = null;
        $renderer = $this->createMock(ResourceRenderer::class);
        $renderer->expects($this->once())
            ->method('index')
            ->willReturnCallback(function ($resourceClass, $table, $records, $request, $badges) use (&$capturedPaginator) {
                $capturedPaginator = $records;
                return 'rendered';
            });

        $controller = $this->makeController($renderer);
        $request = Request::create('/admin/resource/test-items', 'GET');
        $request->setUserResolver(fn () => new FakeUser(10, 1));

        $response = $controller->index($request, 'test-items');

        $this->assertSame('rendered', $response);
        $this->assertInstanceOf(LengthAwarePaginator::class, $capturedPaginator);
        // Should handle empty result without division by zero
        $this->assertSame(0, $capturedPaginator->total());
        $this->assertSame(0, $capturedPaginator->count());
    }

    public function test_inline_update_updates_boolean_column(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Alpha', 'is_published' => false, 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()
                ->columns([
                    BooleanColumn::make('is_published')->inlineToggle(),
                ]);
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/1/inline', 'PATCH', [
            'field' => 'is_published',
            'value' => '1',
        ]);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $response = $controller->inlineUpdate($request, 'test-items', '1');

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertTrue($payload['state']);
        $this->assertSame('1', $payload['canonical']);
        $this->assertSame(1, TestRecord::find(1)->is_published);
    }

    public function test_inline_update_returns_error_for_non_inline_field(): void
    {
        TestRecord::insert([
            ['id' => 1, 'title' => 'Alpha', 'is_published' => false, 'tenant_id' => 1],
        ]);

        TestResource::setTableFactory(function () {
            return Table::make()
                ->columns([
                    Column::make('title'),
                ]);
        });

        $controller = $this->makeController();
        $request = Request::create('/admin/resource/test-items/1/inline', 'PATCH', [
            'field' => 'title',
            'value' => 'Updated',
        ]);
        $request->setUserResolver(fn () => new FakeUser(1, 1));

        $response = $controller->inlineUpdate($request, 'test-items', '1');

        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);

        $this->assertSame('error', $payload['status']);
        $this->assertSame('Field is not inline editable.', $payload['message']);
    }

    private function makeController(
        ?ResourceRenderer $renderer = null,
        ?FormValidator $validator = null,
        ?ResourcePersistence $persistence = null
    ): ResourceController
    {
        $renderer ??= $this->createMock(ResourceRenderer::class);
        $validator ??= $this->createMock(FormValidator::class);
        $persistence ??= $this->createMock(ResourcePersistence::class);

        $resources = $this->resourceManagerStub();
        $sortingService = $this->sortingService();

        return new ResourceController(
            $resources,
            $renderer,
            $validator,
            $persistence,
            $sortingService,
            new IndexAction($resources, $renderer),
            new CreateAction($resources, $renderer),
            new StoreAction($resources, $validator, $persistence),
            new EditAction($resources, $renderer),
            new UpdateAction($resources, $validator, $persistence),
            new DestroyAction($resources, $persistence),
            new ReorderAction($resources, $sortingService),
            new UpdateGroupAction($resources),
            new UpdateTreeAction($resources, $sortingService),
            new InlineUpdateAction($resources),
            new RunRowAction($resources),
            new RunBulkAction($resources),
            new RunGlobalAction($resources),
            new RunFormAction($resources),
            new TableJsonAction($resources),
            new FormJsonAction($resources),
            new GetModalFormAction($resources, $renderer),
            new GetModalFormCreateAction($resources, $renderer)
        );
    }

    private function sortingService(): SortableOrderService
    {
        return new SortableOrderService($this->capsule->getConnection());
    }

    private function resourceManagerStub(): ResourceManager
    {
        $resourceManager = $this->createMock(ResourceManager::class);
        $resourceManager->method('resource')->willReturn(TestResource::class);

        return $resourceManager;
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
    private static ?\Closure $formFactory = null;

    public static function actions(): array
    {
        return [
            TestBulkAction::class,
            TestRowAction::class,
            TestGlobalAction::class,
            TestFormAction::class,
        ];
    }

    public static function table($ctx): Table
    {
        if (static::$tableFactory) {
            return call_user_func(static::$tableFactory, $ctx);
        }

        return Table::make();
    }

    public static function form($ctx): Form
    {
        if (static::$formFactory) {
            return call_user_func(static::$formFactory, $ctx);
        }

        return Form::make();
    }

    public static function setTableFactory(?\Closure $factory): void
    {
        static::$tableFactory = $factory;
    }

    public static function setFormFactory(?\Closure $factory): void
    {
        static::$formFactory = $factory;
    }

    public static function resetFactories(): void
    {
        static::$tableFactory = null;
        static::$formFactory = null;
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

class TestRowAction extends BaseAction implements RowActionContract
{
    protected string $key = 'test-row';
    protected string $label = 'Test Row';
    public static array $handled = [];

    public static function reset(): void
    {
        static::$handled = [];
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        static::$handled = [
            'id' => $context->model()?->getKey(),
        ];

        return [
            'message' => 'Row completed',
        ];
    }
}

class TestGlobalAction extends BaseAction implements GlobalActionContract
{
    protected string $key = 'test-global';
    protected string $label = 'Test Global';
    public static bool $called = false;

    public static function reset(): void
    {
        static::$called = false;
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        static::$called = true;
        return [
            'message' => 'Global completed',
        ];
    }
}

class TestFormAction extends BaseAction implements FormActionContract
{
    protected string $key = 'test-form';
    protected string $label = 'Test Form';
    public static array $handled = [];

    public static function reset(): void
    {
        static::$handled = [];
    }

    public function rules(): array
    {
        return [
            'note' => 'required',
        ];
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        static::$handled = [
            'note' => $request->input('note'),
        ];

        return [
            'message' => 'Form action completed',
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

class TestingRedirectResponse extends Response
{
    public array $flash = [];

    public function __construct(
        public string $route,
        public array $parameters = [],
        int $status = 302,
        array $headers = []
    ) {
        parent::__construct('', $status, $headers);
    }

    public function with($key, $value = null): self
    {
        if (is_array($key)) {
            $this->flash = array_merge($this->flash, $key);
            return $this;
        }

        $this->flash[$key] = $value;
        return $this;
    }
}
