<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Core\Forms\FormContext;

/**
 * CodeEditor Field - поле для редактирования кода с синтаксическим подсвечиванием
 *
 * Адаптация v1 CodeEditor для v2 с использованием Ace Editor.
 * Поддерживает:
 * - Синтаксическое подсвечивание (HTML, CSS, JavaScript, JSON, XML и др.)
 * - Нумерацию строк
 * - Свёртывание кода (code folding)
 * - Автодополнение
 * - Light и Dark темы
 * - Конфигурируемую высоту
 * - Множественное редактирование
 */
class CodeEditor extends AbstractField
{
    /**
     * Высота редактора в пикселях
     */
    protected int $height = 400;

    /**
     * Язык программирования для синтаксического подсвечивания
     * Поддерживаемые: html, css, javascript, json, xml, php, python, sql и др.
     */
    protected string $language = 'html';

    /**
     * Тема редактора (light или dark)
     */
    protected string $theme = 'light';

    /**
     * Показывать ли номера строк
     */
    protected bool $lineNumbers = true;

    /**
     * Включить ли свёртывание кода
     */
    protected bool $codeFolding = true;

    /**
     * Включить ли автодополнение
     */
    protected bool $autoComplete = true;

    /**
     * Размер табуляции в пробелах
     */
    protected int $tabSize = 2;

    /**
     * Установить высоту редактора
     */
    public function height(int $height): static
    {
        $this->height = max(200, $height); // Минимум 200px
        return $this;
    }

    /**
     * Установить язык программирования
     * Примеры: 'html', 'css', 'javascript', 'json', 'xml', 'php', 'python', 'sql'
     */
    public function language(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Установить тему редактора
     */
    public function theme(string $theme): static
    {
        $this->theme = in_array($theme, ['light', 'dark']) ? $theme : 'light';
        return $this;
    }

    /**
     * Показывать/скрывать номера строк
     */
    public function lineNumbers(bool $enabled = true): static
    {
        $this->lineNumbers = $enabled;
        return $this;
    }

    /**
     * Включить/отключить свёртывание кода
     */
    public function codeFolding(bool $enabled = true): static
    {
        $this->codeFolding = $enabled;
        return $this;
    }

    /**
     * Включить/отключить автодополнение
     */
    public function autoComplete(bool $enabled = true): static
    {
        $this->autoComplete = $enabled;
        return $this;
    }

    /**
     * Установить размер табуляции
     */
    public function tabSize(int $size): static
    {
        $this->tabSize = max(1, min(8, $size)); // Минимум 1, максимум 8
        return $this;
    }

    /**
     * Получить высоту редактора
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Получить язык программирования
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Получить тему редактора
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Проверить, показывать ли номера строк
     */
    public function hasLineNumbers(): bool
    {
        return $this->lineNumbers;
    }

    /**
     * Проверить, включено ли свёртывание кода
     */
    public function hasCodeFolding(): bool
    {
        return $this->codeFolding;
    }

    /**
     * Проверить, включено ли автодополнение
     */
    public function hasAutoComplete(): bool
    {
        return $this->autoComplete;
    }

    /**
     * Получить размер табуляции
     */
    public function getTabSize(): int
    {
        return $this->tabSize;
    }

    /**
     * Подготовить для отображения
     */
    public function prepareForDisplay(FormContext $context): void
    {
        // Заполнить значение из источника данных
        $this->fillFromDataSource($context->dataSource());
    }

    /**
     * Преобразовать в массив для Blade шаблона
     */
    public function toArray(): array
    {
        $value = $this->getValue() ?? '';

        // Если это массив, преобразовать в JSON для отображения
        if (is_array($value)) {
            $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return array_merge(parent::toArray(), [
            'type' => 'code-editor',
            'height' => $this->getHeight(),
            'language' => $this->getLanguage(),
            'theme' => $this->getTheme(),
            'lineNumbers' => $this->hasLineNumbers(),
            'codeFolding' => $this->hasCodeFolding(),
            'autoComplete' => $this->hasAutoComplete(),
            'tabSize' => $this->getTabSize(),
            'value' => $value,
        ]);
    }

    /**
     * Отобразить поле
     */
    public function render(FormContext $context): string
    {
        if (is_null($this->getValue())) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.code-editor';

        // Extract error information from context
        $hasError = $context->hasError($this->key);
        $errors = $context->getErrors($this->key);

        // Get all field data as array
        $fieldData = $this->toArray();

        return view($view, [
            'field'      => $this,
            'context'    => $context,
            'hasError'   => $hasError,
            'errors'     => $errors,
            'attributes' => '',
            ...$fieldData,
        ])->render();
    }
}
