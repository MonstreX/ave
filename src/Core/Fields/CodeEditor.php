<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Core\FormContext;

/**
 * CodeEditor Field - input field for code editing with syntax highlighting
 *
 * Adaptation of v1 CodeEditor for v2 using Ace Editor.
 * Features:
 * - Syntax highlighting (HTML, CSS, JavaScript, JSON, XML, etc.)
 * - Line numbering
 * - Code folding
 * - Auto-completion
 * - Light and Dark themes
 * - Configurable height
 * - Multi-cursor editing
 */
class CodeEditor extends AbstractField
{
    /**
     * Editor height in pixels
     */
    protected int $height = 400;

    /**
     * Programming language for syntax highlighting
     * Supported: html, css, javascript, json, xml
     */
    protected string $language = 'html';

    /**
     * Editor theme (light or dark)
     */
    protected string $theme = 'light';

    /**
     * Whether to show line numbers
     */
    protected bool $lineNumbers = true;

    /**
     * Whether to enable code folding
     */
    protected bool $codeFolding = true;

    /**
     * Whether to enable auto-completion
     */
    protected bool $autoComplete = true;

    /**
     * Tab size in spaces
     */
    protected int $tabSize = 2;

    /**
     * Whether to use auto-height based on content
     */
    protected bool $autoHeight = false;

    /**
     * Set editor height
     */
    public function height(int $height): static
    {
        $this->height = max(200, $height); // Minimum 200px
        return $this;
    }

    /**
     * Set programming language
     * Supported: 'html', 'css', 'javascript', 'json', 'xml'
     */
    public function language(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Set editor theme
     * Supported: 'light', 'dark', 'monokai'
     */
    public function theme(string $theme): static
    {
        // Accept any theme name - Ace will handle it or fall back to default
        // Supported: light, dark, monokai (mapped in JavaScript)
        $this->theme = $theme;
        return $this;
    }

    /**
     * Show/hide line numbers
     */
    public function lineNumbers(bool $enabled = true): static
    {
        $this->lineNumbers = $enabled;
        return $this;
    }

    /**
     * Enable/disable code folding
     */
    public function codeFolding(bool $enabled = true): static
    {
        $this->codeFolding = $enabled;
        return $this;
    }

    /**
     * Enable/disable auto-completion
     */
    public function autoComplete(bool $enabled = true): static
    {
        $this->autoComplete = $enabled;
        return $this;
    }

    /**
     * Set tab size
     */
    public function tabSize(int $size): static
    {
        $this->tabSize = max(1, min(8, $size)); // Minimum 1, maximum 8
        return $this;
    }

    /**
     * Enable/disable auto-height based on content
     */
    public function autoHeight(bool $enabled = true): static
    {
        $this->autoHeight = $enabled;
        return $this;
    }

    /**
     * Get editor height
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Get programming language
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Get editor theme
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Check if line numbers are shown
     */
    public function hasLineNumbers(): bool
    {
        return $this->lineNumbers;
    }

    /**
     * Check if code folding is enabled
     */
    public function hasCodeFolding(): bool
    {
        return $this->codeFolding;
    }

    /**
     * Check if auto-completion is enabled
     */
    public function hasAutoComplete(): bool
    {
        return $this->autoComplete;
    }

    /**
     * Get tab size
     */
    public function getTabSize(): int
    {
        return $this->tabSize;
    }

    /**
     * Check if auto-height is enabled
     */
    public function hasAutoHeight(): bool
    {
        return $this->autoHeight;
    }

    /**
     * Convert to array for Blade template
     */
    public function toArray(): array
    {
        $value = $this->getValue() ?? '';

        // If it's an array, convert to JSON for display
        if (is_array($value)) {
            $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return array_merge(parent::toArray(), [
            'type' => $this->type(),
            'height' => $this->getHeight(),
            'language' => $this->getLanguage(),
            'theme' => $this->getTheme(),
            'lineNumbers' => $this->hasLineNumbers(),
            'codeFolding' => $this->hasCodeFolding(),
            'autoComplete' => $this->hasAutoComplete(),
            'tabSize' => $this->getTabSize(),
            'autoHeight' => $this->hasAutoHeight(),
            'value' => $value,
        ]);
    }

    /**
     * Render field
     */
    public function render(FormContext $context): string
    {
        // Fill from data source if not already filled
        if (is_null($this->getValue())) {
            $this->fillFromDataSource($context->dataSource());
        }

        $view = $this->view ?? $this->resolveDefaultView();

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
