<?php

namespace Monstrex\Ave\Core\Fields\Media;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\FormContext;

/**
 * Small dedicated renderer for the Media field view.
 */
class MediaRenderer
{
    /**
     * @param array<string,mixed> $fieldData
     */
    public function render(string $view, array $fieldData, FormContext $context, ?Media $field = null): string
    {
        $record = $context->record();

        $fieldData['modelType'] = $record ? get_class($record) : null;
        $fieldData['modelId'] = $record instanceof Model ? $record->getKey() : null;

        // Add metaKey for JavaScript compatibility
        // The metaKey is used in HTML data-meta-key attribute for JS to identify the field
        if ($field) {
            $fieldData['metaKey'] = $field->getMetaKey();
        }

        return view($view, [
            ...$fieldData,
            'context' => $context,
            'hasError' => $context->hasError($fieldData['key']),
            'errors' => $context->getErrors($fieldData['key']),
            'attributes' => '',
        ])->render();
    }
}

