<?php

namespace Monstrex\Ave\Tests\Feature\Forms;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Http\Controllers\ResourceController;
use Monstrex\Ave\Support\Http\RequestDebugSanitizer;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

class FieldsetPersistenceTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        Container::setInstance($container);

        $translator = new Translator(new ArrayLoader(), 'en');
        $validatorFactory = new ValidationFactory($translator, $container);

        Facade::setFacadeApplication($container);
        $logger = new NullLogger();

        $container->instance('translator', $translator);
        $container->instance('validator', $validatorFactory);
        $container->instance(ValidationFactoryContract::class, $validatorFactory);
        $container->instance(ResponseFactoryContract::class, new FieldsetResponseFactory());
        $container->instance('log', $logger);
        $container->instance(LoggerInterface::class, $logger);

        $redirector = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['route', 'to'])
            ->getMock();
        $redirector->method('route')->willReturnCallback(
            fn ($name, $parameters = [], $status = 302, $headers = []) => new FieldsetRedirectResponse(
                is_string($name) ? $name : (string) $name,
                (array) $parameters,
                $status,
                $headers
            )
        );
        $redirector->method('to')->willReturnCallback(
            fn ($path, $status = 302, $headers = [], $secure = null) => new FieldsetRedirectResponse(
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
                    if ($this->hasSession()) {
                        $errorBag = new ViewErrorBag();
                        $errorBag->put('default', $validator->errors());
                        $this->session()->flash('errors', $errorBag);
                        $this->session()->flashInput($this->input());
                    }
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

        $this->capsule->schema()->create('fieldset_test_records', function (Blueprint $table) {
            $table->increments('id');
            $table->json('items')->nullable();
            $table->timestamps();
        });

        $dbManager = new class($this->capsule) {
            public function __construct(private Capsule $capsule)
            {
            }

            public function transaction(callable $callback)
            {
                return $this->capsule->getConnection()->transaction($callback);
            }
        };

        Container::getInstance()->instance('db', $dbManager);
        Container::getInstance()->instance('events', new Dispatcher(Container::getInstance()));
    }

    protected function tearDown(): void
    {
        $this->capsule->schema()->dropIfExists('fieldset_test_records');
        parent::tearDown();
    }

    public function test_fieldset_payload_persists_nested_arrays(): void
    {
        $controller = $this->makeController();
        $request = $this->makeRequest([
            'items' => [
                ['_id' => 'temp-1', 'title' => 'Hero banner', 'subtitle' => 'Top'],
                ['_id' => 'temp-2', 'title' => 'Footer block', 'subtitle' => 'Bottom'],
            ],
        ]);

        $response = $controller->store($request, 'fieldset-tests');

        $this->assertInstanceOf(FieldsetRedirectResponse::class, $response);
        $this->assertSame('ave.resource.index', $response->route);

        $record = FieldsetTestRecord::query()->first();
        $this->assertNotNull($record);
        $this->assertCount(2, $record->items);
        $this->assertSame('Hero banner', $record->items[0]['title']);
        $this->assertSame('Footer block', $record->items[1]['title']);
        $this->assertArrayHasKey('_id', $record->items[0]);
    }

    public function test_fieldset_old_input_available_after_validation_error(): void
    {
        $controller = $this->makeController();
        $request = $this->makeRequest([
            'items' => [
                ['_id' => 'tmp-1', 'title' => '', 'subtitle' => 'Missing title'],
            ],
        ]);

        try {
            $controller->store($request, 'fieldset-tests');
            $this->fail('ValidationException expected.');
        } catch (ValidationException $exception) {
            $oldInput = $request->session()->get('_old_input', []);
            $this->assertArrayHasKey('items', $oldInput);
            $this->assertSame('', $oldInput['items'][0]['title']);

            /** @var ViewErrorBag|null $errors */
            $errors = $request->session()->get('errors');

            $context = FormContext::forCreate()->withOldInput($oldInput)->withErrors($errors);
            $this->assertTrue($context->hasOldInput('items.0.title'));
            $this->assertSame('', $context->oldInput('items.0.title'));

            $this->assertTrue($context->hasError('items.0.title'));
            $this->assertNotEmpty($context->getErrors('items.0.title'));
        }
    }

    private function makeController(): ResourceController
    {
        $renderer = $this->createMock(ResourceRenderer::class);

        return new ResourceController(
            $this->resourceManagerStub(),
            $renderer,
            new FormValidator(),
            new ResourcePersistence(),
            new SortableOrderService($this->capsule->getConnection()),
            new RequestDebugSanitizer()
        );
    }

    private function resourceManagerStub(): ResourceManager
    {
        $resourceManager = $this->createMock(ResourceManager::class);
        $resourceManager->method('resource')->willReturn(FieldsetTestResource::class);

        return $resourceManager;
    }

    private function makeRequest(array $payload): Request
    {
        $request = Request::create('/admin/resource/fieldset-tests', 'POST', $payload);
        $session = new SessionStore('testing', new ArraySessionHandler(120));
        $session->start();

        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => new FieldsetTestUser(1));

        return $request;
    }
}

class FieldsetTestRecord extends Model
{
    protected $table = 'fieldset_test_records';
    protected $guarded = [];
    protected $casts = [
        'items' => 'array',
    ];
}

class FieldsetTestResource extends Resource
{
    public static ?string $slug = 'fieldset-tests';
    public static ?string $model = FieldsetTestRecord::class;

    public static function form($ctx): Form
    {
        return Form::make()->schema([
            Div::make('row')->schema([
                Div::make('col-12')->schema([
                    Fieldset::make('items')
                        ->schema([
                            TextInput::make('title')->label('Title')->required(),
                            TextInput::make('subtitle')->label('Subtitle'),
                        ])
                        ->minItems(1)
                        ->addButtonLabel('Add block'),
                ]),
            ]),
        ]);
    }

    public function can(string $ability, ?Authenticatable $user, mixed $model = null): bool
    {
        return $user !== null;
    }
}

class FieldsetTestUser implements Authenticatable
{
    public function __construct(private int $id) {}

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

class FieldsetResponseFactory implements ResponseFactoryContract
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
        return $this->make(json_encode($data, $options), $status, $headers);
    }

    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0)
    {
        return $this->json($data, $status, $headers, $options);
    }

    public function stream($callback, $status = 200, array $headers = [])
    {
        return $this->make('', $status, $headers);
    }

    public function streamJson($data, $status = 200, $headers = [], $encodingOptions = 15)
    {
        return $this->json($data, $status, $headers, $encodingOptions);
    }

    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment')
    {
        return $this->make('', 200, $headers);
    }

    public function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        return $this->make('', 200, $headers);
    }

    public function file($file, array $headers = [])
    {
        return $this->make('', 200, $headers);
    }

    public function redirectTo($path, $status = 302, $headers = [], $secure = null)
    {
        return new FieldsetRedirectResponse($path, [], $status, $headers);
    }

    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = [])
    {
        return new FieldsetRedirectResponse($route, (array) $parameters, $status, $headers);
    }

    public function redirectToAction($action, $parameters = [], $status = 302, $headers = [])
    {
        return new FieldsetRedirectResponse($action, (array) $parameters, $status, $headers);
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

class FieldsetRedirectResponse extends Response
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
