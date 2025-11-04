<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Contracts\ProvidesValidationRules;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\Fields\FieldPersistenceResult;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Media\MediaRepository;
use Monstrex\Ave\Support\CollectionKeyGenerator;

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
class Media extends AbstractField implements ProvidesValidationRules, HandlesPersistence
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
     * Explicit collection override (optional).
     */
    protected ?string $collectionOverride = null;

    /**
     * Identifier of the nested item when the field is used inside a container.
     */
    protected ?string $nestedItemIdentifier = null;

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
     * Override resulting collection name explicitly.
     */
    public function useCollectionOverride(?string $collection): static
    {
        $this->collectionOverride = $collection;

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

    public function nestWithin(string $parentKey, string $itemIdentifier): static
    {
        $clone = parent::nestWithin($parentKey, $itemIdentifier);
        $clone->nestedItemIdentifier = $itemIdentifier;

        return $clone;
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

    protected function isNested(): bool
    {
        return $this->key !== $this->baseKey();
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
        return CollectionKeyGenerator::forMedia(
            $this->collection,
            $this->key,
            $this->collectionOverride
        );
    }

    /**
     * Fill field from Eloquent model
     */
    public function fillFromRecord(Model $record): void
    {
        $collection = $this->resolveCollectionName();

        $mediaItems = $record->media()
            ->where('collection_name', $collection)
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
        $this->useCollectionOverride($collectionName);

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

        if (is_string($mediaData)) {
            $this->useCollectionOverride($mediaData);
            $this->setValue(collect());

            return;
        }

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
        // If value is already set (e.g., from fillFromDataSource in ResourceRenderer),
        // and it's not empty, don't override it
        $currentValue = $this->getValue();
        if ($currentValue instanceof Collection && $currentValue->isNotEmpty()) {
            return;
        }
        if (is_array($currentValue) && !empty($currentValue)) {
            return;
        }

        $record = $context->record();

        // If we have a model record, load media from it
        if ($record && $record instanceof Model && $record->exists) {
            $this->fillFromRecord($record);
        }
        // If still empty and not from model, try data source (for FieldSet and JSON)
        if (empty($this->getValue())) {
            $this->fillFromDataSource($context->dataSource());
        }
    }

    /**
     * Processing before apply
     */
    public function prepareForSave(mixed $value, Request $request, FormContext $context): FieldPersistenceResult
    {
        $this->pendingMediaPayload = [];

        $metaKey = CollectionKeyGenerator::metaKeyForField($this->key);
        $uploadedIds = $this->parseIdList($request->input('__media_uploaded', [])[$metaKey] ?? []);
        $deletedIds = $this->parseIdList($request->input('__media_deleted', [])[$metaKey] ?? []);
        $order = $this->parseIdList($request->input('__media_order', [])[$metaKey] ?? []);
        $props = $this->normalisePropsInput($request->input('__media_props', [])[$metaKey] ?? []);

        Log::debug('Media request payload', [
            'field' => $this->key,
            'meta_key' => $metaKey,
            'raw_uploaded' => $request->input('__media_uploaded', [])[$metaKey] ?? null,
            'raw_deleted' => $request->input('__media_deleted', [])[$metaKey] ?? null,
            'raw_order' => $request->input('__media_order', [])[$metaKey] ?? null,
        ]);

        $record = $context->record();
        $collection = $this->resolveCollectionName();
        $repository = $this->mediaRepository();

        if (str_contains(Str::lower($collection), '__item__')) {
            Log::warning('Media collection still contains placeholder', [
                'field' => $this->key,
                'collection' => $collection,
                'html_key' => $this->key,
                'meta_key' => $metaKey,
            ]);
        }

        if (!empty($deletedIds) && $record && $record->exists) {
            $repository->delete($record, $collection, $deletedIds);
        }

        $payload = [
            'uploaded' => $uploadedIds,
            'order' => $order,
            'props' => $props,
            'meta_key' => $metaKey,
            'collection' => $collection,
            'deleted' => $deletedIds,
        ];

        $this->pendingMediaPayload = $payload;

        $deferred = [
            function (Model $savedRecord) use ($payload, $repository, $collection): void {
                if (!empty($payload['uploaded'])) {
                    $repository->attach($savedRecord, $collection, $payload['uploaded']);
                }

                if (!empty($payload['order'])) {
                    $repository->reorder($savedRecord, $collection, $payload['order']);
                }

                if (!empty($payload['props'])) {
                    $repository->updateProps($savedRecord, $collection, $payload['props']);
                }
            },
        ];

        $existingCount = ($record && $record->exists)
            ? $repository->count($record, $collection)
            : 0;
        $remainingAfterDeletion = max(0, $existingCount - count($deletedIds));
        $willHaveMedia = !empty($uploadedIds) || $remainingAfterDeletion > 0;

        $finalValue = null;

        if ($willHaveMedia) {
            $finalValue = $collection;
        } elseif (!$this->isNested()) {
            $finalValue = $value;
        }

        Log::debug('Media prepareForSave', [
            'field' => $this->key,
            'collection' => $collection,
            'meta_key' => $metaKey,
            'uploaded' => $uploadedIds,
            'deleted' => $deletedIds,
            'order' => $order,
            'props_keys' => array_keys($props),
            'existing_count' => $existingCount,
            'remaining_after_deletion' => $remainingAfterDeletion,
            'will_have_media' => $willHaveMedia,
            'final_value' => $finalValue,
        ]);

        if (empty($uploadedIds) && empty($order) && empty($props)) {
            return FieldPersistenceResult::make($finalValue);
        }

        return FieldPersistenceResult::make($finalValue, $deferred);
    }

    /**
     * Apply to data source
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        if ($this->isNested()) {
            $source->set($this->baseKey(), $value);
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

    public function buildValidationRules(): array
    {
        $rules = $this->getRules();

        if ($this->isRequired()) {
            if (!in_array('required', $rules, true)) {
                $rules[] = 'required';
            }
        } elseif (!in_array('nullable', $rules, true)) {
            $rules[] = 'nullable';
        }

        return [$this->key() => implode('|', array_filter($rules))];
    }

    /**
     * Convert to array for Blade template
     */
    public function toArray(): array
    {
        $mediaItems = $this->getValue() ?? collect();

        // If it is not a collection, convert
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
            'modelType' => null, // Will be populated in render()
            'modelId' => null,   // Will be populated in render()
            'metaKey' => CollectionKeyGenerator::metaKeyForField($this->key),
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

        // Add model information for media operations
        $record = $context->record();
        $fieldData['modelType'] = $record ? get_class($record) : null;
        $fieldData['modelId'] = $record?->id ?? null;

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
     * Parse ID list from string or array
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
     * Normalize media input properties
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

    private function mediaRepository(): MediaRepository
    {
        return app(MediaRepository::class);
    }
}






