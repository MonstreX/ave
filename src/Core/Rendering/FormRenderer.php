<?php

namespace Monstrex\Ave\Core\Rendering;

use Monstrex\Ave\Core\Form;

class FormRenderer
{
    protected FieldRenderer $fieldRenderer;

    public function __construct()
    {
        $this->fieldRenderer = new FieldRenderer();
    }

    /**
     * Render a form to HTML
     *
     * @param Form $form
     * @param $model
     * @param array $errors
     * @return string
     */
    public function render(Form $form, $model = null, array $errors = []): string
    {
        $html = '';

        foreach ($form->rows() as $row) {
            $html .= $this->renderRow($row, $model, $errors);
        }

        return $html;
    }

    /**
     * Render a form row
     *
     * @param array $row
     * @param $model
     * @param array $errors
     * @return string
     */
    protected function renderRow(array $row, $model = null, array $errors = []): string
    {
        $html = '<div class="form-row">';

        $columns = $row['columns'] ?? [];
        foreach ($columns as $column) {
            $html .= $this->renderColumn($column, $model, $errors);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render a form column
     *
     * @param array $column
     * @param $model
     * @param array $errors
     * @return string
     */
    protected function renderColumn(array $column, $model = null, array $errors = []): string
    {
        $span = $column['span'] ?? 12;
        $spanClass = 'col-md-' . floor(12 / (12 / $span));

        $html = '<div class="' . $spanClass . '">';

        $fields = $column['fields'] ?? [];
        foreach ($fields as $field) {
            $value = $this->getFieldValue($field, $model);
            $fieldErrors = $errors[$field->key()] ?? [];
            $html .= $this->fieldRenderer->render($field, $value, $fieldErrors);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get field value from model or use default
     *
     * @param $field
     * @param $model
     * @return mixed
     */
    protected function getFieldValue($field, $model = null)
    {
        if ($model && method_exists($model, 'getAttribute')) {
            return $model->getAttribute($field->key());
        }

        return null;
    }
}
