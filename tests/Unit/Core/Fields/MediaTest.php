<?php

namespace Tests\Unit\Core\Fields;

use Tests\TestCase;
use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Media\MediaRepository;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class MediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock MediaRepository для тестов
        $this->app->instance(MediaRepository::class, Mockery::mock(MediaRepository::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_be_created_with_key()
    {
        $media = Media::make('gallery');

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('gallery', $media->key());
    }

    /** @test */
    public function it_can_configure_collection()
    {
        $media = Media::make('photos')
            ->collection('product-images');

        $this->assertEquals('product-images', $media->getCollection());
    }

    /** @test */
    public function it_can_configure_multiple_uploads()
    {
        $media = Media::make('gallery')
            ->multiple(true, 10);

        $this->assertTrue($media->isMultiple());
        $this->assertEquals(10, $media->getMaxFiles());
    }

    /** @test */
    public function it_can_configure_accepted_types()
    {
        $media = Media::make('avatar')
            ->acceptImages();

        $accept = $media->getAccept();

        $this->assertContains('image/jpeg', $accept);
        $this->assertContains('image/png', $accept);
    }

    /** @test */
    public function it_can_configure_accepted_documents()
    {
        $media = Media::make('documents')
            ->acceptDocuments();

        $accept = $media->getAccept();

        $this->assertContains('application/pdf', $accept);
    }

    /** @test */
    public function it_can_configure_file_size_limit()
    {
        $media = Media::make('document')
            ->maxFileSize(5120); // 5MB

        $this->assertEquals(5120, $media->getMaxFileSize());
    }

    /** @test */
    public function it_can_configure_max_files()
    {
        $media = Media::make('gallery')
            ->maxFiles(20);

        $this->assertEquals(20, $media->getMaxFiles());
    }

    /** @test */
    public function it_can_configure_preview()
    {
        $media = Media::make('images')
            ->preview(true);

        $this->assertTrue($media->showsPreview());

        $media2 = Media::make('docs')
            ->preview(false);

        $this->assertFalse($media2->showsPreview());
    }

    /** @test */
    public function it_can_configure_columns()
    {
        $media = Media::make('gallery')
            ->columns(6);

        $this->assertEquals(6, $media->getColumns());
    }

    /** @test */
    public function it_can_configure_props()
    {
        $media = Media::make('gallery')
            ->props('title', 'alt', 'copyright');

        $props = $media->getPropNames();

        $this->assertCount(3, $props);
        $this->assertContains('title', $props);
        $this->assertContains('alt', $props);
        $this->assertContains('copyright', $props);
    }

    /** @test */
    public function it_resolves_collection_name_from_state_path()
    {
        $media = Media::make('image')
            ->statePath('product.gallery');

        $collectionName = $this->invokePrivateMethod($media, 'resolveCollectionName');

        $this->assertNotNull($collectionName);
        $this->assertIsString($collectionName);
    }

    /** @test */
    public function it_handles_nested_state_paths()
    {
        $media = Media::make('photo')
            ->statePath('items.0.gallery');

        $collectionName = $this->invokePrivateMethod($media, 'resolveCollectionName');

        $this->assertNotNull($collectionName);
        $this->assertIsString($collectionName);
        $this->assertStringContainsString('items', $collectionName);
    }

    /** @test */
    public function it_returns_field_persistence_result()
    {
        $repository = Mockery::mock(MediaRepository::class);
        $this->app->instance(MediaRepository::class, $repository);

        $media = Media::make('gallery')
            ->statePath('gallery');

        $request = Request::create('/', 'POST');
        // Симулируем метаданные загрузки
        $request->merge([
            '_ave_media_gallery' => json_encode([
                'uploaded' => ['file1.jpg', 'file2.jpg'],
                'order' => [],
                'props' => [],
                'deleted' => [],
            ])
        ]);

        $context = FormContext::forCreate([], $request);
        $result = $media->prepareForSave(null, $request, $context);

        // Проверяем что возвращается FieldPersistenceResult
        $this->assertInstanceOf(\Monstrex\Ave\Core\Fields\FieldPersistenceResult::class, $result);
        $this->assertIsArray($result->deferredActions());
    }

    /** @test */
    public function it_handles_deletion_payload()
    {
        $repository = Mockery::mock(MediaRepository::class);
        $repository->shouldReceive('count')->andReturn(3);
        $repository->shouldReceive('delete')->zeroOrMoreTimes();
        $this->app->instance(MediaRepository::class, $repository);

        $media = Media::make('gallery')
            ->statePath('gallery');

        $request = Request::create('/', 'POST');
        $request->merge([
            '_ave_media_gallery' => json_encode([
                'uploaded' => [],
                'order' => [],
                'props' => [],
                'deleted' => [1, 2], // Удаляем 2 файла
            ])
        ]);

        $model = Mockery::mock(Model::class);
        $model->shouldReceive('exists')->andReturn(true);
        $model->shouldReceive('getKey')->andReturn(1);

        $context = FormContext::forEdit($model, [], $request);
        $result = $media->prepareForSave(null, $request, $context);

        // Проверяем что результат валидный
        $this->assertInstanceOf(\Monstrex\Ave\Core\Fields\FieldPersistenceResult::class, $result);
    }

    /** @test */
    public function it_provides_nested_cleanup_actions_when_nested()
    {
        $media = Media::make('photo')
            ->statePath('items.0.photo');

        // Делаем поле вложенным
        $nestedMedia = $media->nestWithin('items', '0');

        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getKey')->andReturn(1);
        $model->shouldReceive('exists')->andReturn(true);

        $context = FormContext::forEdit($model);

        $cleanupActions = $nestedMedia->getNestedCleanupActions(
            'items_0_photo',
            ['_id' => 0],
            $context
        );

        // Проверяем что метод возвращает массив
        $this->assertIsArray($cleanupActions);
        // Cleanup actions могут быть пустыми если коллекции нет
    }

    /** @test */
    public function it_can_configure_path_strategy()
    {
        $media = Media::make('uploads')
            ->pathStrategy('dated');

        $this->assertEquals('dated', $media->getPathStrategy());
    }

    /** @test */
    public function it_can_configure_path_prefix()
    {
        $media = Media::make('uploads')
            ->pathPrefix('documents');

        $this->assertEquals('documents', $media->getPathPrefix());
    }

    /**
     * Helper для доступа к private методам
     */
    protected function invokePrivateMethod($object, $methodName, ...$args)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invoke($object, ...$args);
    }
}
