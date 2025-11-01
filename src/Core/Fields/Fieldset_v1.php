<?php

namespace Monstrex\Ave\Core\Resources\Forms\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Monstrex\Ave\Models\Media as MediaModel;
use Monstrex\Ave\Core\Resources\Forms\Components\FormComponent;
use Monstrex\Ave\Core\Resources\Forms\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\Resources\Forms\FormContext;

/**
 * FieldSet - Multiple sets of fields stored in a single JSON column
 *
 * Allows creating repeatable field groups (like Filament's Repeater)
 * All data is stored in one JSON field in the database.
 *
 * Example:
 * FieldSet::make('features')
 *     ->schema([
 *         TextInput::make('title'),
 *         Textarea::make('description'),
 *         MediaField::make('icon_id'),
 *     ])
 *     ->sortable()
 *     ->minItems(1)
 *     ->maxItems(10);
 */
class FieldSet extends Field
{
    protected array $childSchema = [];

    protected array $itemInstances = [];

    protected array $itemIds = []; // Store unique IDs for each item

    protected bool $sortable = true;

    protected bool $collapsible = false;

    protected bool $collapsed = false;

    protected int $minItems = 0;

    protected ?int $maxItems = null;

    protected string $addButtonLabel = 'Add Item';

    protected string $deleteButtonLabel = 'Delete';

    protected ?string $headTitle = null; // Field name for title in header

    protected ?string $headPreview = null; // Field name for preview image in header

    protected array $preparedItems = [];

    protected function getDefaultViewTemplate(): string
    {
        return 'ave::components.forms.fieldset';
    }

    /**
     * Define the schema of fields for each item
     *
     * @param  array<FormComponent>  $components
     */
    public function schema(array $components): static
    {
        $this->childSchema = $components;

        return $this;
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
        parent::prepareForDisplay($context);

        $record = $context->record();
        if (! $record) {
            return;
        }

        // Get JSON data from model field (already decoded by model cast)
        $jsonData = data_get($record, $this->name);

        // Normalize to array
        $items = [];
        if (is_string($jsonData)) {
            $items = json_decode($jsonData, true) ?? [];
        } elseif (is_array($jsonData)) {
            $items = $jsonData;
        }

        // Collect item IDs (or generate if missing)
        $itemIds = [];

        // Create field instances for each item
        foreach ($items as $index => $itemData) {
            // Get or generate unique ID for this item
            $itemId = isset($itemData['_id']) ? (int) $itemData['_id'] : ($index + 1);
            $itemIds[$index] = $itemId;
            $itemFields = $this->cloneSchemaFields();
            $itemContext = FormContext::forData($itemData);

            // Fill each field with data using original field names
            foreach ($itemFields as $field) {
                if ($field instanceof Field) {
                    $field->assignForm($this->form());
                    $field->fillFromDataSource($itemContext->dataSource());

                    // For MediaField, set collection name from JSON BEFORE renaming
                    if ($field instanceof MediaField && $record) {
                        $originalFieldName = $field->getName(); // Use current name (not renamed yet)
                        $collectionName = $itemData[$originalFieldName] ?? null;

                        if ($collectionName && is_string($collectionName)) {
                            // Set collection name override for this field
                            $field->setCollectionNameOverride($collectionName);
                            // Load media from this collection
                            $field->fillFromCollectionName($record, $collectionName);
                        }
                    }

                    // Rename field for HTML form (title -> features[0][title])
                    // Pass both index (for array position) and itemId (for stable collection naming)
                    $this->renameFieldForItem($field, $index, $itemId);
                }
            }

            $this->itemInstances[$index] = $itemFields;
        }

        // Save item IDs for use in template
        $this->itemIds = $itemIds;
    }

    /**
     * Clone schema fields (without renaming)
     *
     * @return array<FormComponent> Cloned fields with original names
     */
    private function cloneSchemaFields(): array
    {
        $cloned = [];
        foreach ($this->childSchema as $component) {
            $cloned[] = clone $component;
        }

        return $cloned;
    }

    /**
     * Rename field for specific item index
     *
     * Changes "title" to "features[0][title]" for HTML form rendering
     *
     * @param  Field  $field  Field to rename
     * @param  int  $index  Item index (position in array)
     * @param  int  $itemId  Unique item ID (stable across sorting)
     */
    private function renameFieldForItem(Field $field, int $index, int $itemId): void
    {
        $reflection = new \ReflectionClass($field);
        $property = $reflection->getProperty('name');
        $property->setAccessible(true);

        $originalName = $property->getValue($field);

        // For MediaField, save original name and item ID
        if ($field instanceof MediaField) {
            $originalNameProp = $reflection->getProperty('originalName');
            $originalNameProp->setAccessible(true);
            $originalNameProp->setValue($field, $originalName);

            // Set item ID for stable collection naming
            $field->setFieldSetItemId($itemId);
        }

        $newName = "{$this->name}[{$index}][{$originalName}]";
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

        $rawItems = $request->input($this->name, []);

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
                if (! $schemaField instanceof Field) {
                    continue;
                }

                $fieldName = $schemaField->getName();

                if ($schemaField instanceof MediaField) {
                    $fullFieldName = "{$this->name}[{$index}][{$fieldName}]";
                    $collectionName = "{$this->name}_{$itemId}_{$fieldName}";

                    $uploadedIds = $this->parseIdList($request->input($fullFieldName.'_uploaded', []));
                    $deletedIds = $this->parseIdList($request->input($fullFieldName.'_deleted', []));
                    $order = $this->parseIdList($request->input($fullFieldName.'_order', []));
                    $props = $this->normalisePropsInput($request->input($fullFieldName.'_props', []));

                    if (! empty($itemData[$fieldName] ?? null) || ! empty($uploadedIds) || ! empty($order) || ! empty($props)) {
                        $hasData = true;
                    }

                    if (! empty($deletedIds) && $record && $record->exists) {
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

                    $itemData[$fieldName] = $collectionName;

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

            if (! $hasData) {
                unset($items[$index]);

                continue;
            }

            foreach ($mediaPayloads as $payload) {
                if (empty($payload['uploaded']) && empty($payload['order']) && empty($payload['props'])) {
                    continue;
                }

                $this->afterRecordSaved($context, function (Model $savedRecord, FormContext $savedContext) use ($payload) {
                    $collectionName = $payload['collection'];

                    if (! empty($payload['uploaded'])) {
                        $this->attachMedia($savedRecord, $collectionName, $payload['uploaded']);
                    }

                    if (! empty($payload['order'])) {
                        $this->syncMediaOrder($savedRecord, $collectionName, $payload['order']);
                    }

                    if (! empty($payload['props'])) {
                        $this->syncMediaProps($savedRecord, $collectionName, $payload['props']);
                    }
                });
            }
        }

        $this->preparedItems = array_values($items);
    }

    public function apply(Request $request, DataSourceInterface $source, FormContext $context): void
    {
        $source->set($this->name, $this->preparedItems);
    }

    public function afterApply(Request $request, Model $record, FormContext $context): void
    {
        $this->preparedItems = [];
    }

    /**
     * Get validation rules for FieldSet
     *
     * Validates min/max items count
     */
    public function resolveValidationRules(FormContext $context): array
    {
        $rules = parent::resolveValidationRules($context);

        if ($this->minItems > 0) {
            $rules[] = 'required';
            $rules[] = 'array';
            $rules[] = "min:{$this->minItems}";
        }

        if ($this->maxItems) {
            $rules[] = 'array';
            $rules[] = "max:{$this->maxItems}";
        }

        return $rules;
    }

    /**
     * Get child components (for Form::flattenFields)
     *
     * FieldSet handles all child fields internally, so it only returns itself.
     * Child field data is extracted directly from the request during beforeApply().
     */
    public function flattenFields(): array
    {
        return [$this];
    }

    private function valueIsMeaningful(mixed $value): bool
    {
        if (is_array($value)) {
            return ! empty($value);
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

        if (! is_array($value)) {
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
        if (! is_array($input)) {
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

            if (! is_array($value)) {
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
            if (! $media) {
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
            if (! $media) {
                continue;
            }

            $currentProps = json_decode($media->props, true) ?? [];
            $media->props = json_encode(array_merge($currentProps, $values));
            $media->save();
        }
    }

    /**
     * Prepare template fields for JavaScript item creation
     *
     * Clones schema fields with __INDEX__ placeholder in names
     * for use in the <template> tag
     *
     * @return array<FormComponent> Template fields
     */
    public function prepareTemplateFields(): array
    {
        $templateFields = [];

        foreach ($this->childSchema as $component) {
            $clone = clone $component;

            if ($clone instanceof Field) {
                $reflection = new \ReflectionClass($clone);
                $property = $reflection->getProperty('name');
                $property->setAccessible(true);

                $originalName = $property->getValue($clone);
                $templateName = "{$this->name}[__INDEX__][{$originalName}]";
                $property->setValue($clone, $templateName);

                // For MediaField, set collection name pattern with __INDEX__ placeholder
                if ($clone instanceof MediaField) {
                    $collectionPattern = "{$this->name}___INDEX___{$originalName}";

                    // Set via reflection to ensure it sticks
                    $overrideProperty = $reflection->getProperty('collectionNameOverride');
                    $overrideProperty->setAccessible(true);
                    $overrideProperty->setValue($clone, $collectionPattern);
                }

                $clone->assignForm($this->form());
            }

            $templateFields[] = $clone;
        }

        return $templateFields;
    }

    /**
     * Additional data for Blade template
     */
    protected function additionalViewData(FormContext $context): array
    {
        return [
            'fieldComponent' => $this,
            'itemInstances' => $this->itemInstances,
            'itemIds' => $this->itemIds,
            'childSchema' => $this->childSchema,
            'templateFields' => $this->prepareTemplateFields(),
            'sortable' => $this->sortable,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'addButtonLabel' => $this->addButtonLabel,
            'deleteButtonLabel' => $this->deleteButtonLabel,
            'minItems' => $this->minItems,
            'maxItems' => $this->maxItems,
            'headTitle' => $this->headTitle,
            'headPreview' => $this->headPreview,
        ];
    }
}
