<?php

namespace Monstrex\Ave\Core\Rendering;

use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Fields\Select;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Hidden;
use Monstrex\Ave\Core\Fields\DateTimePicker;
use Monstrex\Ave\Core\Fields\FileUpload;
use Monstrex\Ave\Core\Fields\RichText;
use Monstrex\Ave\Core\Fields\Fieldset;

class FieldRenderer
{
    /**
     * Render a field to HTML
     *
     * @param AbstractField $field
     * @param mixed $value
     * @param array $errors
     * @return string
     */
    public function render(AbstractField $field, $value = null, array $errors = []): string
    {
        $componentName = $this->getComponentName($field);
        $data = $this->prepareFieldData($field, $value, $errors);

        return view("ave::components.forms.{$componentName}", $data)->render();
    }

    /**
     * Get the blade component name for a field type
     *
     * @param AbstractField $field
     * @return string
     */
    protected function getComponentName(AbstractField $field): string
    {
        return match ($field::class) {
            TextInput::class => 'text-input',
            Textarea::class => 'textarea',
            Toggle::class => 'toggle',
            Select::class => 'select',
            Number::class => 'number-input',
            Hidden::class => 'text-input', // Hidden uses same component
            DateTimePicker::class => 'datetime-input',
            FileUpload::class => 'media-field',
            RichText::class => 'rich-editor',
            Fieldset::class => 'fieldset',
            default => 'text-input',
        };
    }

    /**
     * Prepare field data for blade view
     *
     * @param AbstractField $field
     * @param mixed $value
     * @param array $errors
     * @return array
     */
    protected function prepareFieldData(AbstractField $field, $value = null, array $errors = []): array
    {
        $data = $field->toArray();

        return [
            'name' => $field->key(),
            'label' => $data['label'] ?? $field->key(),
            'value' => $value ?? $data['default'] ?? '',
            'type' => $field->type(),
            'required' => $data['required'] ?? false,
            'placeholder' => $data['placeholder'] ?? '',
            'helpText' => $data['help'] ?? '',
            'disabled' => $data['disabled'] ?? false,
            'readonly' => $data['readonly'] ?? false,
            'hasError' => !empty($errors),
            'errors' => $errors,
            'attributes' => $data['attributes'] ?? '',
            // Field-specific data
            'options' => $data['options'] ?? [],
            'multiple' => $data['multiple'] ?? false,
            'emptyOption' => $data['emptyOption'] ?? null,
            'size' => $data['size'] ?? 5,
            'prefix' => $data['prefix'] ?? null,
            'suffix' => $data['suffix'] ?? null,
            'class' => $data['class'] ?? '',
        ];
    }
}
