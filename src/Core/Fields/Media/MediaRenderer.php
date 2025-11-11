<?php

namespace Monstrex\Ave\Core\Fields\Media;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\Fields\Media as MediaField;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Support\StorageProfile;

/**
 * Small dedicated renderer for the Media field view.
 */
class MediaRenderer
{
    /**
     * @param array<string,mixed> $fieldData
     */
    public function render(string $view, array $fieldData, FormContext $context, ?MediaField $field = null): string
    {
        $record = $context->record();

        $fieldData['modelType'] = $record ? get_class($record) : null;
        $fieldData['modelId'] = $record instanceof Model ? $record->getKey() : null;

        // Add metaKey for JavaScript compatibility
        // The metaKey is used in HTML data-meta-key attribute for JS to identify the field
        if ($field) {
            $fieldData['metaKey'] = $field->getMetaKey();

            // Execute custom path generator if provided
            if ($field->getPathGenerator() && $record) {
                $customPath = $this->executePathGenerator(
                    $field->getPathGenerator(),
                    $record,
                    $record->getKey(),
                    $fieldData['pathPrefix'] ?? null
                );
                $fieldData['customPath'] = $customPath;
            }
        }

        return view($view, [
            ...$fieldData,
            'context' => $context,
            'hasError' => $context->hasError($fieldData['key']),
            'errors' => $context->getErrors($fieldData['key']),
            'attributes' => '',
        ])->render();
    }

    /**
     * Execute path generator callback with model context
     *
     * @param \Closure $callback Generator callback
     * @param Model $model Current model instance
     * @param mixed $recordId Model record ID
     * @return string Generated path
     */
    private function executePathGenerator(\Closure $callback, Model $model, mixed $recordId, ?string $pathPrefix = null): string
    {
        $profile = StorageProfile::make();

        if ($pathPrefix) {
            $profile = $profile->with(['path_prefix' => $pathPrefix]);
        }

        $root = $profile->resolvedRoot($pathPrefix);
        $date = (object) [
            'year' => date('Y'),
            'month' => date('m'),
            'full' => date('Y/m'),
        ];

        $result = $callback($model, $recordId, $root, $date);

        // Ensure result is a string and normalize
        $result = (string) $result;
        $result = ltrim($result, '/');

        return rtrim($result, '/');
    }
}

