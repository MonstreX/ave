<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Core\Forms\FormContext;

/**
 * RichEditor Field - input field for WYSIWYG HTML content editing
 *
 * Adaptation of v1 RichEditor for v2 using Jodit Editor.
 * Features:
 * - Visual HTML editing (WYSIWYG)
 * - Source code view with Ace Editor
 * - Image uploads
 * - Text formatting (bold, italic, underline, strikethrough)
 * - Lists (ul, ol)
 * - Headings (h1-h6)
 * - Links and tables
 * - Various toolbar presets (minimal, basic, full)
 * - Customizable height
 */
class RichEditor extends AbstractField
{
    /**
     * Editor height in pixels
     */
    protected int $height = 400;

    /**
     * Toolbar preset
     * Supported values: 'minimal', 'basic', 'full'
     *
     * minimal: bold, italic, lists
     * basic: headings, bold, italic, link, lists
     * full: headings, bold, italic, link, image, lists, blockquote, code
     */
    protected string $toolbar = 'full';

    /**
     * Whether to show menu bar
     */
    protected bool $showMenuBar = true;

    /**
     * Maximum length of HTML content in characters
     */
    protected ?int $maxLength = null;

    /**
     * Whether to allow inline styles
     */
    protected bool $allowInlineStyles = true;

    /**
     * Whether to allow image uploads
     */
    protected bool $allowImageUpload = true;

    /**
     * Whether to allow table creation
     */
    protected bool $allowTables = true;

    /**
     * Whether to allow list usage
     */
    protected bool $allowLists = true;

    /**
     * Whether to allow link creation
     */
    protected bool $allowLinks = true;

    /**
     * Whether to allow blockquote usage
     */
    protected bool $allowBlockquote = true;

    /**
     * Whether to allow code/pre usage
     */
    protected bool $allowCode = true;

    /**
     * Placeholder text for empty editor
     */
    protected ?string $editorPlaceholder = null;

    /**
     * Set editor height in pixels
     */
    public function height(int $height): static
    {
        $this->height = max(200, $height); // Minimum 200px
        return $this;
    }

    /**
     * Set toolbar preset
     *
     * @param string $toolbar 'minimal', 'basic', or 'full'
     */
    public function toolbar(string $toolbar): static
    {
        if (in_array($toolbar, ['minimal', 'basic', 'full'])) {
            $this->toolbar = $toolbar;
        }
        return $this;
    }

    /**
     * Show/hide menu bar
     */
    public function showMenuBar(bool $show = true): static
    {
        $this->showMenuBar = $show;
        return $this;
    }

    /**
     * Set maximum content length in characters
     */
    public function maxLength(int $length): static
    {
        $this->maxLength = max(100, $length); // Minimum 100 characters
        return $this;
    }

    /**
     * Allow/disallow inline styles
     */
    public function allowInlineStyles(bool $allow = true): static
    {
        $this->allowInlineStyles = $allow;
        return $this;
    }

    /**
     * Allow/disallow image uploads
     */
    public function allowImageUpload(bool $allow = true): static
    {
        $this->allowImageUpload = $allow;
        return $this;
    }

    /**
     * Allow/disallow tables
     */
    public function allowTables(bool $allow = true): static
    {
        $this->allowTables = $allow;
        return $this;
    }

    /**
     * Allow/disallow lists
     */
    public function allowLists(bool $allow = true): static
    {
        $this->allowLists = $allow;
        return $this;
    }

    /**
     * Allow/disallow links
     */
    public function allowLinks(bool $allow = true): static
    {
        $this->allowLinks = $allow;
        return $this;
    }

    /**
     * Allow/disallow blockquote
     */
    public function allowBlockquote(bool $allow = true): static
    {
        $this->allowBlockquote = $allow;
        return $this;
    }

    /**
     * Allow/disallow code/pre
     */
    public function allowCode(bool $allow = true): static
    {
        $this->allowCode = $allow;
        return $this;
    }

    /**
     * Set placeholder text
     */
    public function placeholder(string $text): static
    {
        $this->editorPlaceholder = $text;
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
     * Get toolbar preset
     */
    public function getToolbar(): string
    {
        return $this->toolbar;
    }

    /**
     * Check if menu bar is shown
     */
    public function hasMenuBar(): bool
    {
        return $this->showMenuBar;
    }

    /**
     * Get maximum content length
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * Get placeholder text
     */
    public function getPlaceholder(): ?string
    {
        return $this->editorPlaceholder;
    }

    /**
     * Get configuration for JavaScript
     */
    public function getJsConfig(): array
    {
        $config = [
            'height' => $this->height,
            'theme' => 'default',
            'allowInlineStyles' => $this->allowInlineStyles,
            'allowImageUpload' => $this->allowImageUpload,
            'allowTables' => $this->allowTables,
            'allowLists' => $this->allowLists,
            'allowLinks' => $this->allowLinks,
            'allowBlockquote' => $this->allowBlockquote,
            'allowCode' => $this->allowCode,
        ];

        // Disable features if not allowed
        if (!$this->allowTables) {
            $config['disablePlugins'] = array_merge($config['disablePlugins'] ?? [], ['table']);
        }

        if (!$this->allowImageUpload) {
            $config['disablePlugins'] = array_merge($config['disablePlugins'] ?? [], ['image']);
        }

        // Apply toolbar preset
        $config['buttons'] = $this->getToolbarButtons();

        return $config;
    }

    /**
     * Get toolbar buttons based on preset
     */
    protected function getToolbarButtons(): array
    {
        $buttons = match ($this->toolbar) {
            'minimal' => [
                'bold', 'italic', 'ul', 'ol'
            ],
            'basic' => [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '|',
                'ul', 'ol', '|',
                'link', 'image'
            ],
            'full' => [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '|',
                'font', 'fontsize', 'brush', 'paragraph', '|',
                'ul', 'ol', '|',
                'link', 'image', 'table', '|',
                'blockquote', 'code', '|',
                'undo', 'redo', '|',
                'source'
            ],
            default => ['source']
        };

        return $buttons;
    }

    /**
     * Prepare for display
     */
    public function prepareForDisplay(FormContext $context): void
    {
        // Fill value from data source
        $this->fillFromDataSource($context->dataSource());
    }

    /**
     * Convert to array for Blade template
     */
    public function toArray(): array
    {
        $value = $this->getValue() ?? '';

        // Ensure it's a string
        if (!is_string($value)) {
            $value = (string)$value;
        }

        return array_merge(parent::toArray(), [
            'type' => 'rich-editor',
            'height' => $this->getHeight(),
            'toolbar' => $this->getToolbar(),
            'showMenuBar' => $this->hasMenuBar(),
            'maxLength' => $this->getMaxLength(),
            'placeholder' => $this->getPlaceholder() ?? 'Start typing...',
            'jsConfig' => json_encode($this->getJsConfig()),
            'value' => $value,
        ]);
    }

    /**
     * Render field
     */
    public function render(FormContext $context): string
    {
        if (is_null($this->getValue())) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.rich-editor';

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
