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
    public function render(string $view, array $fieldData, FormContext $context): string
    {
        $record = $context->record();

        $fieldData['modelType'] = $record ? get_class($record) : null;
        $fieldData['modelId'] = $record instanceof Model ? $record->getKey() : null;

        return view($view, [
            ...$fieldData,
            'context' => $context,
            'hasError' => $context->hasError($fieldData['key']),
            'errors' => $context->getErrors($fieldData['key']),
            'attributes' => '',
        ])->render();
    }
}

