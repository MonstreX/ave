<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Models\Media as MediaModel;

/**
 * FieldSet - Multiple sets of fields stored in a single JSON column
 *
 * Allows creating repeatable field groups (like Filament's Repeater)
 * All data is stored in one JSON field in the database.
 *
 * Features:
 * - Repeatable field groups with schema definition
 * - Drag-and-drop sorting (when enabled)
 * - Collapsible items
 * - Min/Max items validation
 * - Integration with Media fields inside fieldset items
 * - Image preview in headers
 * - JSON storage with media collection binding
 *
 * Example:
 * Fieldset::make('features')
 *     ->schema([
 *         TextInput::make('title'),
 *         Textarea::make('description'),
 *         Media::make('icon'),
 *     ])
 *     ->sortable()
 *     ->minItems(1)
 *     ->maxItems(10)
 *     ->headTitle('title')
 *     ->headPreview('icon');
 */
class Fieldset extends AbstractField
{
    /** @var array<AbstractField> */
    protected array $childSchema = [];

    protected array $itemInstances = [];

    protected array $itemIds = [];

    protected bool $sortable = true;

    protected bool $collapsible = false;

    protected bool $collapsed = false;

    protected int $minItems = 0;

    protected ?int $maxItems = null;

    protected string $addButtonLabel = 'Add Item';

    protected string $deleteButtonLabel = 'Delete';

    protected ?string $headTitle = null;

    protected ?string $headPreview = null;

    protected array $preparedItems = [];

    public static function make(string $key): static
    {
        return parent::make($key)
            ->default([])
            ->rules(['array']);
    }

    /**
     * Define the schema of fields for each item
     *
     * @param  array<AbstractField>  $fields
     */
    public function schema(array $fields): static
    {
        $this->childSchema = $fields;
        return $this;
    }

    /**
     * @return array<AbstractField>
     */
    public function getChildSchema(): array
    {
        return $this->childSchema;
    }

    /**
     * Enable/disable drag-and-drop sorting
     */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * Enable collapsible items
     */
    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;
        return $this;
    }

    /**
     * Set items collapsed by default
     * Automatically enables collapsible
     */
    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;
        if ($collapsed) {
            $this->collapsible = true;
        }
        return $this;
    }

    /**
     * Set minimum number of items
     */
    public function minItems(int $min): static
    {
        $this->minItems = $min;
        return $this;
    }

    /**
     * Set maximum number of items
     */
    public function maxItems(int $max): static
    {
        $this->maxItems = $max;
        return $this;
    }

    public function getMinItems(): int
    {
        return $this->minItems;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    /**
     * Customize "Add" button label
     */
    public function addButtonLabel(string $label): static
    {
        $this->addButtonLabel = $label;
        return $this;
    }

    /**
     * Customize "Delete" button label
     */
    public function deleteButtonLabel(string $label): static
    {
        $this->deleteButtonLabel = $label;
        return $this;
    }

    /**
     * Set field name for title in header
     */
    public function headTitle(string $fieldName): static
    {
        $this->headTitle = $fieldName;
        return $this;
    }

    /**
     * Set field name for preview image in header
     */
    public function headPreview(string $fieldName): static
    {
        $this->headPreview = $fieldName;
        return $this;
    }

    /**
     * Prepare FieldSet for display
     *
     * Loads JSON data from model and creates field instances for each item
     */
    public function prepareForDisplay(FormContext $context): void
    {
        $dataSource = $context->dataSource();
        $items = $dataSource ? $dataSource->get($this->key) : [];

        if (!is_array($items)) {
            $items = [];
        }

        $this->itemInstances = [];
        $this->itemIds = [];

        $record = $context->record();

        foreach (array_values($items) as $index => $itemData) {
            $itemData = is_array($itemData) ? $itemData : [];

            // Get or generate unique ID for this item
            $itemId = isset($itemData['_id']) ? (int) $itemData['_id'] : ($index + 1);
            $this->itemIds[$index] = $itemId;

            $itemFields = [];

            // Clone schema fields for this item
            foreach ($this->childSchema as $fieldDefinition) {
                $field = clone $fieldDefinition;

                // Rename field for array structure
                $this->renameFieldForItem($field, $index, $itemId);

                // Create data source for this item
                $itemDataSource = new ArrayDataSource($itemData);
                $field->fillFromDataSource($itemDataSource);

                // For Media field, set collection name from JSON BEFORE rendering
                if ($field instanceof Media && $record) {
                    $originalFieldName = $fieldDefinition->getKey();
                    $collectionName = $itemData[$originalFieldName] ?? null;

                    \Log::info('FieldSet Media Loading Debug', [
                        'fieldset_name' => $this->key,
                        'field_name' => $originalFieldName,
                        'collection_name' => $collectionName,
                        'itemData_keys' => array_keys($itemData),
                        'itemData' => $itemData,
                    ]);

                    if ($collectionName && is_string($collectionName)) {
                        $field->setCollectionNameOverride($collectionName);
                        $field->fillFromCollectionName($record, $collectionName);

                        $mediaLoaded = $field->getValue();
                        \Log::info('FieldSet Media After Load', [
                            'media_count' => $mediaLoaded ? count($mediaLoaded) : 0,
                            'media_ids' => $mediaLoaded ? $mediaLoaded->pluck('id')->toArray() : [],
                        ]);
                    }
                }

                // Skip prepareForDisplay for Media fields - they're already populated
                // via fillFromCollectionName() above
                if (!($field instanceof Media) && method_exists($field, 'prepareForDisplay')) {
                    $field->prepareForDisplay(FormContext::forData($itemData));
                }

                $itemFields[] = $field;
            }

            $this->itemInstances[$index] = [
                'id' => 'item-' . $itemId,
                'index' => $index,
                'fields' => $itemFields,
                'data' => $itemData,
            ];
        }
    }

    /**
     * Rename field for specific item index
     *
     * Changes "title" to "features[0][title]" for HTML form rendering
     *
     * @param  AbstractField  $field  Field to rename
     * @param  int  $index  Item index (position in array)
     * @param  int  $itemId  Unique item ID (stable across sorting)
     */
    private function renameFieldForItem(AbstractField $field, int $index, int $itemId): void
    {
        $reflection = new \ReflectionClass($field);
        $property = $reflection->getProperty('key');
        $property->setAccessible(true);

        $originalName = $property->getValue($field);

        // For Media field, save original name and item ID
        if ($field instanceof Media) {
            // Save original field name before renaming
            $originalNameProp = $reflection->getProperty('originalName');
            $originalNameProp->setAccessible(true);
            $originalNameProp->setValue($field, $originalName);

            // Set item ID for stable collection naming
            $field->setFieldSetItemId($itemId);
        }

        $newName = "{$this->key}[{$index}][{$originalName}]";
        $property->setValue($field, $newName);
    }

    /**
     * Save FieldSet data to model
     *
     * Collects all items from request and saves as array.
     * Laravel automatically encodes to JSON if field is cast as 'array'.
     */
    public function beforeApply(Request $request, FormContext $context): void
    {
        $this->preparedItems = [];

        $rawItems = $request->input($this->key, []);

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        $items = array_filter($rawItems, 'is_array');
        $items = array_values($items);

        $record = $context->record();

        foreach ($items as $index => &$itemData) {
            if (isset($itemData['_id'])) {
                $itemData['_id'] = (int) $itemData['_id'];
            }

            $itemId = $itemData['_id'] ?? ($index + 1);
            $hasData = false;
            $mediaPayloads = [];

            foreach ($this->childSchema as $schemaField) {
                if (!$schemaField instanceof AbstractField) {
                    continue;
                }

                $fieldName = $schemaField->getKey();

                if ($schemaField instanceof Media) {
                    $fullFieldName = "{$this->key}[{$index}][{$fieldName}]";
                    $collectionName = "{$this->key}_{$itemId}_{$fieldName}";

                    $uploadedIds = $this->parseIdList($request->input($fullFieldName.'_uploaded', []));
                    $deletedIds = $this->parseIdList($request->input($fullFieldName.'_deleted', []));
                    $order = $this->parseIdList($request->input($fullFieldName.'_order', []));
                    $props = $this->normalisePropsInput($request->input($fullFieldName.'_props', []));

                    \Log::info('FieldSet Media beforeApply', [
                        'fullFieldName' => $fullFieldName,
                        'collectionName' => $collectionName,
                        'uploadedIds' => $uploadedIds,
                        'order' => $order,
                        'itemData_field' => $itemData[$fieldName] ?? null,
                    ]);

                    if (!empty($itemData[$fieldName] ?? null) || !empty($uploadedIds) || !empty($order) || !empty($props)) {
                        $hasData = true;
                    }

                    // Delete media if requested
                    if (!empty($deletedIds) && $record && $record->exists) {
                        $record->media()
                            ->where('collection_name', $collectionName)
                            ->whereIn('id', $deletedIds)
                            ->delete();
                    }

                    $mediaPayloads[] = [
                        'collection' => $collectionName,
                        'uploaded' => $uploadedIds,
                        'order' => $order,
                        'props' => $props,
                    ];

                    // Store collection name in JSON instead of media data
                    $itemData[$fieldName] = $collectionName;

                    \Log::info('FieldSet Media beforeApply after', [
                        'collectionName_stored' => $itemData[$fieldName],
                        'hasData' => $hasData,
                    ]);

                    continue;
                }

                if ($fieldName === '_id') {
                    continue;
                }

                $value = $itemData[$fieldName] ?? null;

                if ($this->valueIsMeaningful($value)) {
                    $hasData = true;
                }
            }

            // Remove empty items
            if (!$hasData) {
                unset($items[$index]);
                continue;
            }

            // Handle media attachments after record is saved
            foreach ($mediaPayloads as $payload) {
                if (empty($payload['uploaded']) && empty($payload['order']) && empty($payload['props'])) {
                    continue;
                }

                if ($record && $record->exists) {
                    $collectionName = $payload['collection'];

                    if (!empty($payload['uploaded'])) {
                        $this->attachMedia($record, $collectionName, $payload['uploaded']);
                    }

                    if (!empty($payload['order'])) {
                        $this->syncMediaOrder($record, $collectionName, $payload['order']);
                    }

                    if (!empty($payload['props'])) {
                        $this->syncMediaProps($record, $collectionName, $payload['props']);
                    }
                }
            }
        }

        $this->preparedItems = array_values($items);
    }

    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        $source->set($this->key, $this->preparedItems ?: []);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => 'fieldset',
            'childSchema' => $this->childSchema,
            'itemInstances' => $this->itemInstances,
            'itemIds' => $this->itemIds,
            'sortable' => $this->sortable,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'minItems' => $this->minItems,
            'maxItems' => $this->maxItems,
            'addButtonLabel' => $this->addButtonLabel,
            'deleteButtonLabel' => $this->deleteButtonLabel,
            'headTitle' => $this->headTitle,
            'headPreview' => $this->headPreview,
        ]);
    }

    public function getItemFields(int $index): array
    {
        return $this->itemInstances[$index]['fields'] ?? [];
    }

    public function getItemId(int $index): string
    {
        return $this->itemInstances[$index]['id'] ?? ('item-' . $index);
    }

    public function getRowTitle(int $index): string
    {
        $data = $this->itemInstances[$index]['data'] ?? [];

        if ($this->headTitle) {
            return (string) ($data[$this->headTitle] ?? 'Item ' . ($index + 1));
        }

        return 'Item ' . ($index + 1);
    }

    public function render(FormContext $context): string
    {
        if (empty($this->itemInstances) && !empty($this->childSchema)) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.fieldset';
        $errors = $context->getErrors($this->key);

        return view($view, [
            'field' => $this,
            'context' => $context,
            'hasError' => !empty($errors),
            'errors' => $errors,
            'attributes' => '',
            ...$this->toArray(),
        ])->render();
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function getAddButtonLabel(): string
    {
        return $this->addButtonLabel;
    }

    public function getDeleteButtonLabel(): string
    {
        return $this->deleteButtonLabel;
    }

    public function getHeadTitle(): ?string
    {
        return $this->headTitle;
    }

    public function getHeadPreview(): ?string
    {
        return $this->headPreview;
    }

    /**
     * Prepare template fields for JavaScript item creation
     *
     * @return array<AbstractField>
     */
    public function prepareTemplateFields(): array
    {
        $templateFields = [];

        foreach ($this->childSchema as $component) {
            $clone = clone $component;

            if ($clone instanceof AbstractField) {
                $reflection = new \ReflectionClass($clone);
                $property = $reflection->getProperty('key');
                $property->setAccessible(true);

                $originalName = $property->getValue($clone);
                $templateName = "{$this->key}[__INDEX__][{$originalName}]";
                $property->setValue($clone, $templateName);

                // For Media field, set collection name pattern with __INDEX__ placeholder
                if ($clone instanceof Media) {
                    $collectionPattern = "{$this->key}___INDEX___{$originalName}";

                    // Set via reflection to ensure it sticks
                    $overrideProperty = $reflection->getProperty('collectionNameOverride');
                    $overrideProperty->setAccessible(true);
                    $overrideProperty->setValue($clone, $collectionPattern);
                }
            }

            $templateFields[] = $clone;
        }

        return $templateFields;
    }

    private function valueIsMeaningful(mixed $value): bool
    {
        if (is_array($value)) {
            return !empty($value);
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }

    /**
     * @return array<int>
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
            $ids[] = (int) $entry;
        }

        return array_values(array_unique(array_filter($ids, fn (int $id) => $id > 0)));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function normalisePropsInput(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $result = [];
        foreach ($input as $key => $value) {
            $id = (int) $key;
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

    private function attachMedia(Model $record, string $collectionName, array $mediaIds): void
    {
        if (empty($mediaIds)) {
            return;
        }

        MediaModel::whereIn('id', $mediaIds)->update([
            'model_type' => get_class($record),
            'model_id' => $record->getKey(),
            'collection_name' => $collectionName,
        ]);
    }

    private function syncMediaOrder(Model $record, string $collectionName, array $orderedIds): void
    {
        if (empty($orderedIds)) {
            return;
        }

        $mediaItems = MediaModel::where('model_type', get_class($record))
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
     * @param  array<int,array<string,mixed>>  $props
     */
    private function syncMediaProps(Model $record, string $collectionName, array $props): void
    {
        if (empty($props)) {
            return;
        }

        $mediaItems = MediaModel::where('model_type', get_class($record))
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
