<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\Forms\FormContext;

/**
 * Media Field - поле для работы с файлами и изображениями
 *
 * Адаптация v1 MediaField для v2 с поддержкой:
 * - Загрузки файлов (единичной или множественной)
 * - Drag & drop загрузки
 * - Предпросмотра изображений
 * - Сортировки (drag-to-reorder)
 * - Удаления файлов
 * - Редактирования свойств медиа (название, alt, описание)
 * - Работы внутри FieldSet (вложенные медиа поля)
 * - Хранения в базе данных или JSON
 */
class Media extends AbstractField
{
    /**
     * Коллекция для хранения файлов
     * Используется для группировки медиа по типам (gallery, hero, icon и т.д.)
     */
    protected string $collection = 'default';

    /**
     * Позволить загружать несколько файлов
     */
    protected bool $multiple = false;

    /**
     * Максимальное количество файлов
     */
    protected ?int $maxFiles = null;

    /**
     * MIME типы, которые допустимы для загрузки
     */
    protected array $accept = [];

    /**
     * Максимальный размер одного файла в KB
     */
    protected ?int $maxFileSize = null;

    /**
     * Показывать ли предпросмотр изображений в сетке
     */
    protected bool $showPreview = true;

    /**
     * Преобразования изображений (ширина, высота, формат и т.д.)
     */
    protected array $imageConversions = [];

    /**
     * Количество колонок в сетке медиа (1-12)
     */
    protected int $columns = 6;

    /**
     * Свойства медиа, доступные для редактирования (название, alt, описание и т.д.)
     */
    protected array $propNames = [];

    /**
     * Исходное имя поля (до переименования в FieldSet)
     */
    protected ?string $originalName = null;

    /**
     * ID элемента FieldSet (если поле находится внутри FieldSet)
     */
    protected ?int $fieldSetItemId = null;

    /**
     * Override имени коллекции (из JSON FieldSet)
     */
    protected ?string $collectionNameOverride = null;

    /**
     * Ожидающие медиа операции (загрузка, удаление, переупорядочивание)
     */
    protected array $pendingMediaPayload = [];

    /**
     * Установить коллекцию для группировки медиа
     */
    public function collection(string $collection): static
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Включить/отключить загрузку нескольких файлов
     */
    public function multiple(bool $multiple = true, ?int $maxFiles = null): static
    {
        $this->multiple = $multiple;
        $this->maxFiles = $maxFiles;
        return $this;
    }

    /**
     * Установить допустимые MIME типы
     */
    public function accept(array $mimeTypes): static
    {
        $this->accept = $mimeTypes;
        return $this;
    }

    /**
     * Быстро установить для изображений
     */
    public function acceptImages(): static
    {
        $this->accept = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return $this;
    }

    /**
     * Быстро установить для документов
     */
    public function acceptDocuments(): static
    {
        $this->accept = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        return $this;
    }

    /**
     * Установить максимальный размер файла в KB
     */
    public function maxFileSize(int $sizeInKB): static
    {
        $this->maxFileSize = $sizeInKB;
        return $this;
    }

    /**
     * Установить максимальное количество файлов
     */
    public function maxFiles(int $count): static
    {
        $this->maxFiles = $count;
        return $this;
    }

    /**
     * Показывать/скрывать предпросмотр изображений
     */
    public function preview(bool $show = true): static
    {
        $this->showPreview = $show;
        return $this;
    }

    /**
     * Установить преобразования изображений
     * Например: ['thumbnail' => ['width' => 150, 'height' => 150], 'medium' => ['width' => 500]]
     */
    public function conversions(array $conversions): static
    {
        $this->imageConversions = $conversions;
        return $this;
    }

    /**
     * Установить количество колонок в сетке (1-12)
     */
    public function columns(int $columns): static
    {
        $this->columns = max(1, min(12, $columns));
        return $this;
    }

    /**
     * Определить свойства медиа для редактирования
     * Например: 'title', 'alt', 'description'
     */
    public function props(string ...$propNames): static
    {
        $this->propNames = $propNames;
        return $this;
    }

    /**
     * Получить исходное имя поля (до FieldSet переименования)
     */
    public function getOriginalName(): string
    {
        return $this->originalName ?? $this->key;
    }

    /**
     * Установить ID элемента FieldSet (для генерации стабильных имён коллекций)
     */
    public function setFieldSetItemId(int $itemId): void
    {
        $this->fieldSetItemId = $itemId;
    }

    /**
     * Переопределить имя коллекции (используется при загрузке из JSON FieldSet)
     */
    public function setCollectionNameOverride(string $collectionName): void
    {
        $this->collectionNameOverride = $collectionName;
    }

    /**
     * Проверить, находится ли поле внутри FieldSet
     */
    protected function isNestedInFieldSet(): bool
    {
        return str_contains($this->key, '[');
    }

    /**
     * Получить коллекцию
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * Проверить, множественный ли загруз
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Получить допустимые MIME типы
     */
    public function getAccept(): array
    {
        return $this->accept;
    }

    /**
     * Получить строку Accept для input[type=file]
     */
    public function getAcceptString(): string
    {
        return implode(',', $this->accept);
    }

    /**
     * Получить максимальный размер файла
     */
    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    /**
     * Получить максимальное количество файлов
     */
    public function getMaxFiles(): ?int
    {
        return $this->maxFiles;
    }

    /**
     * Показывать ли предпросмотр
     */
    public function showsPreview(): bool
    {
        return $this->showPreview;
    }

    /**
     * Получить количество колонок в сетке
     */
    public function getColumns(): int
    {
        return $this->columns;
    }

    /**
     * Разрешить реальное имя коллекции
     *
     * Приоритет:
     * 1. Override из JSON FieldSet (при загрузке существующих данных)
     * 2. Сгенерировать из fieldSetItemId (при создании/сохранении)
     * 3. Использовать свойство collection по умолчанию
     */
    protected function resolveCollectionName(): string
    {
        if ($this->collectionNameOverride !== null) {
            return $this->collectionNameOverride;
        }

        if ($this->fieldSetItemId !== null) {
            $fieldSetName = strstr($this->key, '[', true);
            $originalName = $this->originalName ?? $this->key;
            return "{$fieldSetName}_{$this->fieldSetItemId}_{$originalName}";
        }

        return $this->collection;
    }

    /**
     * Заполнить поле из Eloquent модели
     */
    public function fillFromRecord(Model $record): void
    {
        $mediaItems = $record->media()
            ->where('collection_name', $this->collection)
            ->orderBy('order')
            ->get();

        $this->setValue($mediaItems);
    }

    /**
     * Заполнить поле медиа из конкретной коллекции
     * Используется когда Media находится внутри FieldSet и имя коллекции хранится в JSON
     */
    public function fillFromCollectionName(Model $record, string $collectionName): void
    {
        $mediaItems = $record->media()
            ->where('collection_name', $collectionName)
            ->orderBy('order')
            ->get();

        $this->setValue($mediaItems);
    }

    /**
     * Заполнить из источника данных (для FieldSet и JSON)
     */
    public function fillFromDataSource(DataSourceInterface $source): void
    {
        $mediaData = $source->get($this->key) ?? [];

        if (!$mediaData instanceof Collection) {
            $mediaData = collect($mediaData);
        }

        $this->setValue($mediaData);
    }

    /**
     * Подготовить к отображению
     */
    public function prepareForDisplay(FormContext $context): void
    {
        $this->fillFromDataSource($context->dataSource());
    }

    /**
     * Обработка перед применением
     */
    public function beforeApply(Request $request, FormContext $context): void
    {
        $this->pendingMediaPayload = [];

        // FieldSet обрабатывает вложенные медиа поля самостоятельно
        if ($this->isNestedInFieldSet()) {
            return;
        }

        $uploadedIds = $this->parseIdList($request->input($this->key . '_uploaded', []));
        $deletedIds = $this->parseIdList($request->input($this->key . '_deleted', []));
        $order = $this->parseIdList($request->input($this->key . '_order', []));
        $props = $this->normalisePropsInput($request->input($this->key . '_props', []));

        $record = $context->record();

        // Удалить помеченные для удаления медиа файлы
        if (!empty($deletedIds) && $record && $record->exists) {
            $record->media()
                ->where('collection_name', $this->collection)
                ->whereIn('id', $deletedIds)
                ->delete();
        }

        if (empty($uploadedIds) && empty($order) && empty($props)) {
            return;
        }

        $payload = [
            'uploaded' => $uploadedIds,
            'order' => $order,
            'props' => $props,
        ];

        $this->pendingMediaPayload = $payload;

        // Запланировать операции на выполнение после сохранения записи
        if (method_exists($this, 'afterRecordSaved')) {
            $this->afterRecordSaved($context, function (Model $savedRecord, FormContext $savedContext) use ($payload) {
                if (!empty($payload['uploaded'])) {
                    $this->attachMedia($savedRecord, $this->collection, $payload['uploaded']);
                }

                if (!empty($payload['order'])) {
                    $this->syncMediaOrder($savedRecord, $this->collection, $payload['order']);
                }

                if (!empty($payload['props'])) {
                    $this->syncMediaProps($savedRecord, $this->collection, $payload['props']);
                }
            });
        }
    }

    /**
     * Применить к источнику данных
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        if ($this->isNestedInFieldSet()) {
            $source->set($this->key, $value);
        }
    }

    /**
     * Разрешить правила валидации
     */
    public function getRules(): array
    {
        $rules = parent::getRules();

        if ($this->maxFiles && $this->multiple) {
            $rules[] = "max:{$this->maxFiles}";
        }

        return $rules;
    }

    /**
     * Преобразовать в массив для Blade шаблона
     */
    public function toArray(): array
    {
        $mediaItems = $this->getValue() ?? collect();

        // Если это не коллекция, преобразовать
        if (!$mediaItems instanceof Collection) {
            $mediaItems = collect($mediaItems);
        }

        $actualCollection = $this->resolveCollectionName();

        return array_merge(parent::toArray(), [
            'type' => 'media',
            'collection' => $actualCollection,
            'multiple' => $this->isMultiple(),
            'accept' => $this->getAccept(),
            'acceptString' => $this->getAcceptString(),
            'maxFileSize' => $this->getMaxFileSize(),
            'maxFiles' => $this->getMaxFiles(),
            'showPreview' => $this->showsPreview(),
            'columns' => $this->getColumns(),
            'mediaItems' => $mediaItems,
            'imageConversions' => $this->imageConversions,
            'uploadUrl' => route('ave.media.upload'),
            'propNames' => $this->propNames,
        ]);
    }

    /**
     * Отобразить поле
     */
    public function render(FormContext $context): string
    {
        if (empty($this->getValue())) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.media';

        return view($view, [
            'field' => $this,
            'context' => $context,
        ])->render();
    }

    /**
     * Парсить список ID из строки или массива
     */
    private function parseIdList(mixed $value): array
    {
        if (is_string($value)) {
            if (trim($value) === '') {
                return [];
            }
            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $entry) {
            if ($entry === null || $entry === '') {
                continue;
            }
            $ids[] = (int)$entry;
        }

        return array_values(array_unique(array_filter($ids, fn(int $id) => $id > 0)));
    }

    /**
     * Нормализовать входные свойства медиа
     */
    private function normalisePropsInput(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $result = [];
        foreach ($input as $key => $value) {
            $id = (int)$key;
            if ($id <= 0) {
                continue;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? $decoded : [];
            }

            if (!is_array($value)) {
                continue;
            }

            $result[$id] = $value;
        }

        return $result;
    }

    /**
     * Присоединить загруженные медиа файлы к записи
     */
    private function attachMedia(Model $record, string $collectionName, array $mediaIds): void
    {
        if (empty($mediaIds)) {
            return;
        }

        // Получить модель Media из приложения
        $mediaModel = app(config('ave.media_model', 'Monstrex\Ave\Models\Media'));

        $mediaModel::whereIn('id', $mediaIds)->update([
            'model_type' => get_class($record),
            'model_id' => $record->getKey(),
            'collection_name' => $collectionName,
        ]);
    }

    /**
     * Синхронизировать порядок медиа файлов
     */
    private function syncMediaOrder(Model $record, string $collectionName, array $orderedIds): void
    {
        if (empty($orderedIds)) {
            return;
        }

        $mediaModel = app(config('ave.media_model', 'Monstrex\Ave\Models\Media'));

        $mediaItems = $mediaModel
            ->where('model_type', get_class($record))
            ->where('model_id', $record->getKey())
            ->where('collection_name', $collectionName)
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        foreach ($orderedIds as $index => $mediaId) {
            $media = $mediaItems->get($mediaId);
            if (!$media) {
                continue;
            }

            $media->order = $index;
            $media->save();
        }
    }

    /**
     * Синхронизировать свойства медиа файлов
     */
    private function syncMediaProps(Model $record, string $collectionName, array $props): void
    {
        if (empty($props)) {
            return;
        }

        $mediaModel = app(config('ave.media_model', 'Monstrex\Ave\Models\Media'));

        $mediaItems = $mediaModel
            ->where('model_type', get_class($record))
            ->where('model_id', $record->getKey())
            ->where('collection_name', $collectionName)
            ->whereIn('id', array_keys($props))
            ->get()
            ->keyBy('id');

        foreach ($props as $mediaId => $values) {
            $media = $mediaItems->get($mediaId);
            if (!$media) {
                continue;
            }

            $currentProps = json_decode($media->props, true) ?? [];
            $media->props = json_encode(array_merge($currentProps, $values));
            $media->save();
        }
    }
}
