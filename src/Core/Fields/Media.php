<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Monstrex\Ave\Contracts\HandlesFormRequest;
use Monstrex\Ave\Contracts\HandlesNestedValue;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Contracts\HandlesNestedCleanup;
use Monstrex\Ave\Contracts\ProvidesValidationRules;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\Fields\FieldPersistenceResult;
use Monstrex\Ave\Core\Fields\Media\MediaConfiguration;
use Monstrex\Ave\Core\Fields\Media\MediaRenderer;
use Monstrex\Ave\Core\Fields\Media\MediaRequestPayload;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Media\MediaRepository;
use Monstrex\Ave\Support\StatePathCollectionGenerator;
use Monstrex\Ave\Support\MetaKeyGenerator;
use Monstrex\Ave\Media\Facades\Media as MediaFacade;

/**
 * Media Field - input field for managing files and images.
 *
 * Responsibilities split between configuration, request handling and rendering
 * helpers to keep the field's public API concise.
 */
class Media extends AbstractField implements ProvidesValidationRules, HandlesPersistence, HandlesFormRequest, HandlesNestedValue, HandlesNestedCleanup
{
    public const TYPE = 'media';

    protected MediaConfiguration $config;

    protected ?MediaRequestPayload $pendingPayload = null;

    public function __construct(string $key)
    {
        parent::__construct($key);

        $this->config = new MediaConfiguration();
    }

    public function __clone()
    {
        $this->config = clone $this->config;
        $this->pendingPayload = null;
    }

    /**
     * Set collection for grouping media.
     */
    public function collection(string $collection): static
    {
        $this->config->setCollection($collection);

        return $this;
    }

    /**
     * Override resulting collection name explicitly.
     */
    public function useCollectionOverride(?string $collection): static
    {
        $this->config->setCollectionOverride($collection);

        return $this;
    }

    /**
     * Enable/disable multiple file uploads.
     */
    public function multiple(bool $multiple = true, ?int $maxFiles = null): static
    {
        $this->config->setMultiple($multiple, $maxFiles);

        return $this;
    }

    /**
     * Set allowed MIME types.
     */
    public function accept(array $mimeTypes): static
    {
        $this->config->setAccept($mimeTypes);

        return $this;
    }

    /**
     * Quick set for images.
     */
    public function acceptImages(): static
    {
        $this->config->setAccept(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);

        return $this;
    }

    /**
     * Quick set for documents.
     */
    public function acceptDocuments(): static
    {
        $this->config->setAccept([
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        return $this;
    }

    /**
     * Set maximum file size in KB.
     */
    public function maxFileSize(int $sizeInKB): static
    {
        $this->config->setMaxFileSize($sizeInKB);

        return $this;
    }

    /**
     * Set maximum number of files.
     */
    public function maxFiles(int $count): static
    {
        $this->config->setMaxFiles($count);

        return $this;
    }

    /**
     * Show/hide image previews.
     */
    public function preview(bool $show = true): static
    {
        $this->config->setShowPreview($show);

        return $this;
    }

    /**
     * Set image transformations.
     * Example: ['thumbnail' => ['width' => 150, 'height' => 150], ...]
     */
    public function conversions(array $conversions): static
    {
        $this->config->setImageConversions($conversions);

        return $this;
    }

    /**
     * Set grid columns count (1-12).
     */
    public function columns(int $columns): static
    {
        $this->config->setColumns(max(1, min(12, $columns)));

        return $this;
    }

    /**
     * Define media properties for editing (title, alt, etc.).
     */
    public function props(string ...$propNames): static
    {
        $this->config->setPropNames($propNames);

        return $this;
    }

    /**
     * Get the meta key for this field (used in HTML data-meta-key attribute).
     *
     * The meta key is derived from the state path and used by JavaScript
     * to uniquely identify media fields, especially in nested contexts.
     *
     * Example: state path 'gallery.0.image' → meta key 'gallery_0_image'
     */
    public function getMetaKey(): string
    {
        return MetaKeyGenerator::fromStatePath($this->getStatePath());
    }

    public function nestWithin(string $parentKey, string $itemIdentifier): static
    {
        /** @var static $clone */
        $clone = parent::nestWithin($parentKey, $itemIdentifier);
        $clone->config = clone $this->config;

        return $clone;
    }

    public function getCollection(): string
    {
        // Use explicitly set collection, or fall back to field name
        $collection = $this->config->collection();
        return $collection !== null ? $collection : $this->baseKey();
    }

    public function hasCollectionOverride(): bool
    {
        return $this->config->collectionOverride() !== null;
    }

    public function getCollectionOverride(): ?string
    {
        return $this->config->collectionOverride();
    }

    public function isMultiple(): bool
    {
        return $this->config->isMultiple();
    }

    public function getAccept(): array
    {
        return $this->config->accept();
    }

    public function getAcceptString(): string
    {
        return implode(',', $this->config->accept());
    }

    public function getMaxFileSize(): ?int
    {
        return $this->config->maxFileSize();
    }

    public function getMaxFiles(): ?int
    {
        return $this->config->maxFiles();
    }

    public function showsPreview(): bool
    {
        return $this->config->showPreview();
    }

    public function getColumns(): int
    {
        return $this->config->columns();
    }

    public function getPropNames(): array
    {
        return $this->config->propNames();
    }

    protected function resolveCollectionName(): ?string
    {
        $statePath = $this->getStatePath();

        // For template fields, replace __TEMPLATE__ with __ITEM__ to generate collection name for display/upload
        // This preserves the structure and allows JS to replace __ITEM__ with actual item ID
        if (StatePathCollectionGenerator::isTemplateStatePath($statePath)) {
            $itemPath = str_replace('.__TEMPLATE__.', '.__ITEM__.', $statePath);
            // Temporarily set the item path to generate correct collection name
            $field = clone $this;
            $field = $field->statePath($itemPath);
            return StatePathCollectionGenerator::forMedia($field);
        }

        // Use state path approach: deterministic, compositional, no parsing
        return StatePathCollectionGenerator::forMedia($this);
    }

    public function fillFromRecord(Model $record): void
    {
        // Skip template fields - they don't have real data
        if ($this->isTemplate()) {
            return;
        }

        $collection = $this->resolveCollectionName();

        $mediaItems = $record->media()
            ->where('collection_name', $collection)
            ->orderBy('order')
            ->get();

        $this->setValue($mediaItems);
    }

    public function fillFromCollectionName(Model $record, string $collectionName): void
    {
        $this->useCollectionOverride($collectionName);

        $mediaItems = $record->media()
            ->where('collection_name', $collectionName)
            ->orderBy('order')
            ->get();

        $this->setValue($mediaItems);
    }

    public function fillFromDataSource(DataSourceInterface $source): void
    {
        $mediaData = $source->get($this->key) ?? [];

        if (is_string($mediaData)) {
            $this->useCollectionOverride($mediaData);
            $this->setValue(collect());

            return;
        }

        $collection = $mediaData instanceof Collection ? $mediaData : collect($mediaData);
        $this->setValue($collection);
    }

    public function applyNestedValue(mixed $storedValue, ?Model $record = null): void
    {
        if (is_string($storedValue)) {
            $this->useCollectionOverride($storedValue);

            if ($record instanceof Model && $record->exists) {
                $this->fillFromCollectionName($record, $storedValue);
            } else {
                $this->setValue(collect());
            }

            return;
        }

        if ($storedValue instanceof Collection) {
            $this->setValue($storedValue);
            return;
        }

        if (is_array($storedValue)) {
            $this->setValue(collect($storedValue));
        }
    }

    public function prepareForDisplay(FormContext $context): void
    {
        $currentValue = $this->getValue();
        if ($currentValue instanceof Collection && $currentValue->isNotEmpty()) {
            return;
        }
        if (is_array($currentValue) && !empty($currentValue)) {
            return;
        }

        $record = $context->record();

        if ($record && $record instanceof Model && $record->exists) {
            $this->fillFromRecord($record);
        }

        if (empty($this->getValue())) {
            $dataSource = $context->dataSource();
            if ($dataSource instanceof DataSourceInterface) {
                $this->fillFromDataSource($dataSource);
            }
        }
    }

    public function prepareRequest(Request $request, FormContext $context): void
    {
        // Skip template fields - they don't have real data
        if ($this->isTemplate()) {
            return;
        }

        $payload = $this->capturePayload($request);
        $collection = $this->resolveCollectionName();

        // Skip if collection cannot be resolved (e.g., template paths)
        if (!$collection) {
            return;
        }

        $record = $context->record();
        $repository = $this->mediaRepository();

        $existingCount = ($record && $record->exists)
            ? $repository->count($record, $collection)
            : 0;

        $remainingAfterDeletion = max(0, $existingCount - count($payload->deleted()));
        $willHaveMedia = !empty($payload->uploaded()) || $remainingAfterDeletion > 0;

        // Media fields are not stored in the model - they're handled separately via ave_media table
        // No need to merge anything into request for validation
    }

    public function prepareForSave(mixed $value, Request $request, FormContext $context): FieldPersistenceResult
    {
        // Skip template fields - they don't have real data
        if ($this->isTemplate()) {
            return FieldPersistenceResult::empty();
        }

        $payload = $this->pendingPayload ?? $this->capturePayload($request);
        $collection = $this->resolveCollectionName();

        // Skip if collection cannot be resolved (e.g., template paths)
        if (!$collection) {
            return FieldPersistenceResult::empty();
        }

        $repository = $this->mediaRepository();
        $record = $context->record();

        if (str_contains(Str::lower($collection), '__item__')) {
            Log::warning('Media collection still contains placeholder', [
                'field' => $this->key,
                'collection' => $collection,
                'html_key' => $this->key,
                'meta_key' => $payload->metaKey(),
            ]);
        }

        if (!empty($payload->deleted()) && $record && $record->exists) {
            $repository->delete($record, $collection, $payload->deleted());
        }

        $deferred = [
            function (Model $savedRecord) use ($payload, $repository, $collection): void {
                if (!empty($payload->uploaded())) {
                    $repository->attach($savedRecord, $collection, $payload->uploaded());
                }

                if (!empty($payload->order())) {
                    $repository->reorder($savedRecord, $collection, $payload->order());
                }

                if (!empty($payload->props())) {
                    $repository->updateProps($savedRecord, $collection, $payload->props());
                }
            },
        ];

        $existingCount = ($record && $record->exists)
            ? $repository->count($record, $collection)
            : 0;

        $remainingAfterDeletion = max(0, $existingCount - count($payload->deleted()));
        $willHaveMedia = !empty($payload->uploaded()) || $remainingAfterDeletion > 0;

        // Media value is always the collection name (used by HasMedia relation)
        // For nested fields (in Fieldset containers), always return collection name
        // even if media hasn't been uploaded yet
        $finalValue = ($willHaveMedia || $this->isNested()) ? $collection : null;

        Log::debug('Media prepareForSave', [
            'field' => $this->key,
            'collection' => $collection,
            'meta_key' => $payload->metaKey(),
            'uploaded' => $payload->uploaded(),
            'deleted' => $payload->deleted(),
            'order' => $payload->order(),
            'props_keys' => array_keys($payload->props()),
            'existing_count' => $existingCount,
            'remaining_after_deletion' => $remainingAfterDeletion,
            'will_have_media' => $willHaveMedia,
            'final_value' => $finalValue,
        ]);

        $result = FieldPersistenceResult::make(
            $finalValue,
            $payload->hasChanges() ? $deferred : []
        );

        $this->pendingPayload = null;

        return $result;
    }

    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        // Always use baseKey for nested fields (Fieldset will handle nesting via state path)
        $source->set($this->baseKey(), $value);
    }

    public function getRules(): array
    {
        // Media fields are not stored in the model - they're handled via ave_media table
        // No validation rules needed for the media field itself
        return [];
    }

    public function buildValidationRules(): array
    {
        $rules = $this->getRules();

        if ($this->isRequired() && !in_array('required', $rules, true)) {
            array_unshift($rules, 'required');
        } elseif (!in_array('nullable', $rules, true)) {
            array_unshift($rules, 'nullable');
        }

        return [$this->key() => implode('|', array_filter($rules))];
    }

    public function toArray(): array
    {
        $mediaItems = $this->getValue() ?? collect();

        if (!$mediaItems instanceof Collection) {
            $mediaItems = collect($mediaItems);
        }

        $actualCollection = $this->resolveCollectionName();

        return array_merge(parent::toArray(), [
            'type' => self::TYPE,
            'collection' => $actualCollection,
            'multiple' => $this->isMultiple(),
            'accept' => $this->getAccept(),
            'acceptString' => $this->getAcceptString(),
            'maxFileSize' => $this->getMaxFileSize(),
            'maxFiles' => $this->getMaxFiles(),
            'showPreview' => $this->showsPreview(),
            'columns' => $this->getColumns(),
            'mediaItems' => $mediaItems,
            'imageConversions' => $this->config->imageConversions(),
            'uploadUrl' => route('ave.media.upload'),
            'propNames' => $this->config->propNames(),
            'modelType' => null,
            'modelId' => null,
            'metaKey' => $this->isTemplate() ? '' : $this->metaKey(),
        ]);
    }

    public function render(FormContext $context): string
    {
        if (empty($this->getValue())) {
            $this->prepareForDisplay($context);
        }

        $renderer = new MediaRenderer();
        $view = $this->view ?: 'ave::components.forms.media';
        $fieldData = array_merge($this->toArray(), ['field' => $this]);

        return $renderer->render($view, $fieldData, $context, $this);
    }

    protected function capturePayload(Request $request): MediaRequestPayload
    {
        $this->pendingPayload = MediaRequestPayload::capture($this->metaKey(), $request);

        return $this->pendingPayload;
    }

    protected function metaKey(): string
    {
        return MetaKeyGenerator::fromStatePath($this->getStatePath());
    }

    protected function mediaRepository(): MediaRepository
    {
        return app(MediaRepository::class);
    }

    /**
     * Get cleanup actions for nested media field when item is deleted
     *
     * Returns delete collection action if this is a nested field in a Fieldset
     */
    public function getNestedCleanupActions(mixed $value, array $itemData, ?FormContext $context = null): array
    {
        // Only cleanup if this is a nested field in a Fieldset
        if (!$this->isNested()) {
            Log::debug('Media field is not nested, skipping cleanup', [
                'field' => $this->getKey(),
            ]);
            return [];
        }

        $collection = $this->resolveCollectionName();

        // If we don't have a collection name, there's nothing to clean up
        if (!$collection) {
            Log::debug('Media field has no collection name, skipping cleanup', [
                'field' => $this->getKey(),
            ]);
            return [];
        }

        // Get model from context
        $model = $context?->record();

        if (!$model || !$model->getKey()) {
            Log::debug('Media field has no model context, skipping cleanup', [
                'field' => $this->getKey(),
                'has_context' => $context !== null,
                'has_model' => $model !== null,
            ]);
            return [];
        }

        Log::debug('Media cleanup action prepared', [
            'field' => $this->getKey(),
            'collection' => $collection,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
        ]);

        // Return a closure that will be executed as a deferred action
        // The closure encapsulates all cleanup logic within the Media field
        return [
            function (Model $record) use ($collection) {
                Log::debug('Executing media collection cleanup', [
                    'collection' => $collection,
                    'model_type' => get_class($record),
                    'model_id' => $record->getKey(),
                ]);

                try {
                    $deleted = MediaFacade::model($record)->collection($collection)->delete();

                    Log::info('Media collection cleanup completed', [
                        'collection' => $collection,
                        'deleted_count' => $deleted,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Media collection cleanup failed', [
                        'collection' => $collection,
                        'error' => $e->getMessage(),
                    ]);
                }
            },
        ];
    }
}
