<?php

namespace Monstrex\Ave\Core\Fields\File;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\Fields\File as FileField;
use Monstrex\Ave\Core\FormContext;

/**
 * Dedicated renderer for the File field view.
 */
class FileRenderer
{
    /**
     * @param array<string,mixed> $fieldData
     */
    public function render(string $view, array $fieldData, FormContext $context, ?FileField $field = null): string
    {
        $record = $context->record();

        // Execute custom path generator if provided
        if ($field && $field->getPathGenerator() && $record) {
            $customPath = $this->executePathGenerator(
                $field->getPathGenerator(),
                $record,
                $record->getKey()
            );
            $fieldData['customPath'] = $customPath;
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
    private function executePathGenerator(\Closure $callback, Model $model, mixed $recordId): string
    {
        $root = config('ave.files.root', 'uploads/files');
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
