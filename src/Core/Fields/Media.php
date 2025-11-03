<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\Forms\FormContext;

/**
 * Media Field - input field for managing files and images
 *
 * Adaptation of v1 MediaField for v2 with support for:
 * - Single and multiple file uploads
 * - Drag & drop uploads
 * - Image previews
 * - Sorting (drag-to-reorder)
 * - File deletion
 * - Media property editing (title, alt, description)
 * - Usage inside FieldSet (nested media fields)
 * - Database or JSON storage
 */
class Media extends AbstractField
{
    /**
     * Collection for storing files
     * Used to group media by types (gallery, hero, icon, etc.)
     */
    protected string $collection = 'default';

    /**
     * Whether to allow multiple file uploads
     */
    protected bool $multiple = false;

    /**
     * Maximum number of files
     */
    protected ?int $maxFiles = null;

    /**
     * MIME types allowed for upload
     */
    protected array $accept = [];

    /**
     * Maximum file size in KB
     */
    protected ?int $maxFileSize = null;

    /**
     * Whether to show image previews in grid
     */
    protected bool $showPreview = true;

    /**
     * Image transformations (width, height, format, etc.)
     */
    protected array $imageConversions = [];

    /**
     * Number of columns in media grid (1-12)
     */
    protected int $columns = 6;

    /**
     * Media properties available for editing (title, alt, description, etc.)
     */
    protected array $propNames = [];

    /**
     * Original field name (before FieldSet renaming)
     */
    protected ?string $originalName = null;

    /**
     * FieldSet item ID (if field is inside FieldSet)
     */
    protected ?int $fieldSetItemId = null;

    /**
     * Collection name override (from FieldSet JSON)
     */
    protected ?string $collectionNameOverride = null;

    /**
     * Pending media operations (uploads, deletions, reordering)
     */
    protected array $pendingMediaPayload = [];

    /**
     * Set collection for grouping media
     */
    public function collection(string $collection): static
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Enable/disable multiple file uploads
     */
    public function multiple(bool $multiple = true, ?int $maxFiles = null): static
    {
        $this->multiple = $multiple;
        $this->maxFiles = $maxFiles;
        return $this;
    }

    /**
     * Set allowed MIME types
     */
    public function accept(array $mimeTypes): static
    {
        $this->accept = $mimeTypes;
        return $this;
    }

    /**
     * Quick set for images
     */
    public function acceptImages(): static
    {
        $this->accept = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return $this;
    }

    /**
     * Quick set for documents
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
     * Set maximum file size in KB
     */
    public function maxFileSize(int $sizeInKB): static
    {
        $this->maxFileSize = $sizeInKB;
        return $this;
    }

    /**
     * Set maximum number of files
     */
    public function maxFiles(int $count): static
    {
        $this->maxFiles = $count;
        return $this;
    }

    /**
     * Show/hide image previews
     */
    public function preview(bool $show = true): static
    {
        $this->showPreview = $show;
        return $this;
    }

    /**
     * Set image transformations
     * Example: ['thumbnail' => ['width' => 150, 'height' => 150], 'medium' => ['width' => 500]]
     */
    public function conversions(array $conversions): static
    {
        $this->imageConversions = $conversions;
        return $this;
    }

    /**
     * Set grid columns count (1-12)
     */
    public function columns(int $columns): static
    {
        $this->columns = max(1, min(12, $columns));
        return $this;
    }

    /**
     * Define media properties for editing
     * Example: 'title', 'alt', 'description'
     */
    public function props(string ...$propNames): static
    {
        $this->propNames = $propNames;
        return $this;
    }

    /**
     * Get original field name (before FieldSet renaming)
     */
    public function getOriginalName(): string
    {
        return $this->originalName ?? $this->key;
    }

    /**
     * Set FieldSet item ID (for generating stable collection names)
     */
    public function setFieldSetItemId(int $itemId): void
    {
        $this->fieldSetItemId = $itemId;
    }

    /**
     * Override collection name (used when loading from FieldSet JSON)
     */
    public function setCollectionNameOverride(string $collectionName): void
    {
        $this->collectionNameOverride = $collectionName;
    }

    /**
     * Check if field is inside FieldSet
     */
    protected function isNestedInFieldSet(): bool
    {
        return str_contains($this->key, '[');
    }

    /**
     * Get collection
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * Check if multiple upload
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Get allowed MIME types
     */
    public function getAccept(): array
    {
        return $this->accept;
    }

    /**
     * Get Accept string for input[type=file]
     */
    public function getAcceptString(): string
    {
        return implode(',', $this->accept);
    }

    /**
     * Get maximum file size
     */
    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    /**
     * Get maximum number of files
     */
    public function getMaxFiles(): ?int
    {
        return $this->maxFiles;
    }

    /**
     * Show preview
     */
    public function showsPreview(): bool
    {
        return $this->showPreview;
    }

    /**
     * Get grid columns count
     */
    public function getColumns(): int
    {
        return $this->columns;
    }

    /**
     * Resolve actual collection name
     *
     * Priority:
     * 1. Override from FieldSet JSON (when loading existing data)
     * 2. Generate from fieldSetItemId (when creating/saving)
     * 3. Use default collection property
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
     * Fill field from Eloquent model
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
     * Fill media field from specific collection
     * Used when Media is inside FieldSet and collection name is stored in JSON
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
     * Fill from data source (for FieldSet and JSON)
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
     * Prepare for display
     */
    public function prepareForDisplay(FormContext $context): void
    {
        $this->fillFromDataSource($context->dataSource());
    }

    /**
     * Processing before apply
     */
    public function beforeApply(Request $request, FormContext $context): void
    {
        $this->pendingMediaPayload = [];

        // FieldSet handles nested media fields on its own
        if ($this->isNestedInFieldSet()) {
            return;
        }

        $uploadedIds = $this->parseIdList($request->input($this->key . '_uploaded', []));
        $deletedIds = $this->parseIdList($request->input($this->key . '_deleted', []));
        $order = $this->parseIdList($request->input($this->key . '_order', []));
        $props = $this->normalisePropsInput($request->input($this->key . '_props', []));

        $record = $context->record();

        // Delete media marked for deletion
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

        // Schedule operations to run after record is saved
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
     * Apply to data source
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        if ($this->isNestedInFieldSet()) {
            $source->set($this->key, $value);
        }
    }

    /**
     * Get validation rules
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
     * Convert to array для Blade шаблона
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
     * Render field
     */
    public function render(FormContext $context): string
    {
        if (empty($this->getValue())) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.media';

        // Extract error information from context
        $hasError = $context->hasError($this->key);
        $errors = $context->getErrors($this->key);

        // Get all field data as array (includes mediaItems, collection, etc.)
        $fieldData = $this->toArray();

        return view($view, [
            'field'      => $this,
            'context'    => $context,
            'hasError'   => $hasError,
            'errors'     => $errors,
            'attributes' => '',
            ...$fieldData,
        ])->render();
    }

    /**
     * Parse ID list из строки или массива
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
