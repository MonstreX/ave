<?php

namespace Monstrex\Ave\Core\Form;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Fields\Fieldset;

class FormDataExtractor
{
    /**
     * Extract form data from model
     *
     * @param Form $form Form instance
     * @param Model $model Source model
     * @return array Extracted data
     */
    public static function extract(Form $form, Model $model): array
    {
        $data = [];

        foreach ($form->getAllFields() as $field) {
            $key = $field->key();
            $raw = $model->getAttribute($key);

            if ($field instanceof Fieldset) {
                // Fieldset handles its own extraction (JSON decode)
                $data[$key] = $field->extract($raw);
            } else {
                // Normal field extraction
                $data[$key] = $field->extract($raw);
            }
        }

        return $data;
    }

    /**
     * Fill form fields with default values
     *
     * @param Form $form Form instance
     * @return array Default data
     */
    public static function defaults(Form $form): array
    {
        $data = [];

        foreach ($form->getAllFields() as $field) {
            $key = $field->key();
            $default = $field->toArray()['default'] ?? null;

            if ($field instanceof Fieldset) {
                $data[$key] = [];
            } else {
                $data[$key] = $default;
            }
        }

        return $data;
    }
}
