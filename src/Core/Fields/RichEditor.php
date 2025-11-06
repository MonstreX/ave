<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Core\FormContext;

/**
 * RichEditor Field - input field for WYSIWYG HTML content editing
 *
 * Adaptation of v1 RichEditor for v2 using Jodit Editor.
 *
 * Features:
 * - Visual HTML editing (WYSIWYG)
 * - Source code view with Ace Editor
 * - Image uploads
 * - Text formatting (bold, italic, underline, strikethrough)
 * - Lists (ul, ol)
 * - Headings (h1-h6)
 * - Links and tables
 * - Customizable height
 *
 * Configuration uses a token-based feature system:
 * - By default, all features are enabled
 * - Use features() method to enable/disable specific features
 * - Use enable()/disable() for convenient feature toggling
 * - Use options() for arbitrary Jodit JS config
 *
 * Example:
 *   RichEditor::make('content')
 *       ->height(500)
 *       ->features(['-code', 'hr'])  // disable code, enable hr
 *       ->options(['upload.endpoint' => '/upload'])
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
     * Feature tokens (null = all enabled by default)
     * Format: ['feature1', '-feature2'] where '-' prefix disables
     */
    protected ?array $features = null;

    /**
     * Arbitrary options for Jodit JS config (dot-notation friendly)
     */
    protected array $options = [];

    /**
     * Preset class name or instance
     */
    protected ?string $presetClass = null;

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
     * Set feature tokens to enable/disable capabilities
     *
     * @param array|string|null $tokens Token list (e.g., ['images', 'tables', '-code'])
     *                                  or comma-separated string ('images, tables, -code')
     *                                  Prefix '-' disables a feature
     *                                  null means keep all defaults (all enabled)
     */
    public function features(array|string|null $tokens): static
    {
        if ($tokens === null) {
            $this->features = null;
            return $this;
        }

        $list = is_string($tokens)
            ? array_filter(array_map('trim', preg_split('/[,\s]+/u', $tokens)))
            : array_values(array_filter($tokens, fn($t) => $t !== null && $t !== ''));

        $this->features = $list ?: null;
        return $this;
    }

    /**
     * Enable specific feature tokens
     *
     * @param array|string $tokens Token names or comma-separated string
     */
    public function enable(array|string $tokens): static
    {
        $add = is_array($tokens) ? $tokens : preg_split('/[,\s]+/', (string)$tokens);
        $add = array_map(fn($t) => ltrim((string)$t, '+-'), $add);
        $this->features = array_values(array_unique(array_merge($this->features ?? [], $add)));
        return $this;
    }

    /**
     * Disable specific feature tokens
     *
     * @param array|string $tokens Token names or comma-separated string
     */
    public function disable(array|string $tokens): static
    {
        $del = is_array($tokens) ? $tokens : preg_split('/[,\s]+/', (string)$tokens);
        $del = array_map(fn($t) => '-' . ltrim((string)$t, '+-'), $del);
        $this->features = array_values(array_unique(array_merge($this->features ?? [], $del)));
        return $this;
    }

    /**
     * Set arbitrary options for Jodit JS config
     * Supports dot-notation (e.g., 'upload.endpoint')
     *
     * @param array $options Options to merge into JS config
     */
    public function options(array $options): static
    {
        $this->options = array_replace_recursive($this->options, $options);
        return $this;
    }

    /**
     * Apply a preset to this field
     *
     * Presets provide pre-configured feature sets and options.
     * Can be overridden with features() and options() calls.
     *
     * @param string|object $preset Preset class name or instance
     */
    public function preset(string|object $preset): static
    {
        $this->presetClass = is_string($preset) ? $preset : $preset::class;
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
     * Default feature set (all enabled by default)
     */
    protected function defaultFeatures(): array
    {
        return [
            'headings', 'paragraph', 'bold', 'italic', 'underline', 'strike',
            'lists', 'links', 'images', 'tables', 'blockquote', 'code',
            'inline-styles', 'undo', 'redo', 'source', 'font', 'fontsize', 'brush', 'hr',
        ];
    }

    /**
     * Map Jodit buttons to feature tokens
     */
    protected function buttonFeatureMap(): array
    {
        return [
            // formatting
            'bold' => 'bold', 'italic' => 'italic', 'underline' => 'underline',
            'strikethrough' => 'strike', 'paragraph' => 'paragraph',
            'h1' => 'headings', 'h2' => 'headings', 'h3' => 'headings',
            'h4' => 'headings', 'h5' => 'headings', 'h6' => 'headings',
            'font' => 'font', 'fontsize' => 'fontsize', 'brush' => 'brush',
            // lists
            'ul' => 'lists', 'ol' => 'lists',
            // inserts
            'link' => 'links', 'image' => 'images', 'table' => 'tables', 'hr' => 'hr',
            // blocks
            'blockquote' => 'blockquote', 'code' => 'code',
            // misc
            'undo' => 'undo', 'redo' => 'redo', 'source' => 'source',
        ];
    }

    /**
     * Map disabled feature tokens to Jodit plugin IDs to disable
     */
    protected function featureToPlugins(string $feature): array
    {
        return match ($feature) {
            'images'     => ['image'],
            'tables'     => ['table'],
            'links'      => ['link'],
            'lists'      => ['ul', 'ol'],
            'code'       => ['source'],
            default      => [],
        };
    }

    /**
     * Apply preset configuration if one is set
     * Presets can be overridden by explicit features() or options() calls
     */
    protected function applyPreset(): void
    {
        if (!$this->presetClass) {
            return;
        }

        $preset = new $this->presetClass();

        // Apply preset features only if features not explicitly set
        if ($this->features === null) {
            $presetFeatures = $preset->features();
            if ($presetFeatures !== null) {
                $this->features($presetFeatures);
            }
        }

        // Apply preset options (merge with existing)
        $presetOptions = $preset->options();
        if (!empty($presetOptions)) {
            $this->options($presetOptions);
        }
    }

    /**
     * Resolve final enabled feature set
     * If features is null, all defaults enabled
     * Otherwise, process tokens where '-' prefix disables features
     */
    protected function resolveFeatureSet(): array
    {
        $enabled = array_fill_keys($this->defaultFeatures(), true);

        if ($this->features === null) {
            return array_keys(array_filter($enabled));
        }

        foreach ($this->features as $tokRaw) {
            $tok = (string)$tokRaw;
            if ($tok === '') {
                continue;
            }

            $off = str_starts_with($tok, '-');
            $name = ltrim($tok, '+-');

            if (!array_key_exists($name, $enabled)) {
                if (!$off) {
                    $enabled[$name] = true;
                }
                continue;
            }

            $enabled[$name] = !$off;
        }

        return array_keys(array_filter($enabled));
    }

    /**
     * Get configuration for JavaScript
     */
    public function getJsConfig(): array
    {
        // Apply preset configuration first
        $this->applyPreset();

        $enabled = array_flip($this->resolveFeatureSet());

        $config = [
            'height' => $this->height,
            'theme'  => 'default',
            'showCharsCounter' => false,
            'showWordsCounter' => false,
            'showXPathInStatusbar' => false,
        ];

        // Get toolbar buttons and filter by features
        $buttons = $this->getToolbarButtons();
        $buttons = $this->filterButtonsByFeatures($buttons, $enabled);
        $config['buttons'] = $buttons;

        // Build disabled plugins list from disabled features
        $disabled = [];
        foreach ($this->defaultFeatures() as $feat) {
            if (!isset($enabled[$feat])) {
                $disabled = array_merge($disabled, $this->featureToPlugins($feat));
            }
        }

        if (!empty($disabled)) {
            $config['disablePlugins'] = array_values(array_unique($disabled));
        }

        // Inline styles policy
        $config['allowInlineStyles'] = isset($enabled['inline-styles']);

        // Max length guard
        if ($this->maxLength) {
            $config['maxCharacters'] = $this->maxLength;
        }

        // Placeholder
        if ($this->editorPlaceholder) {
            $config['placeholder'] = $this->editorPlaceholder;
        }

        // Merge user options on top
        if (!empty($this->options)) {
            $config = array_replace_recursive($config, $this->options);
        }

        return $config;
    }

    /**
     * Get toolbar buttons based on preset
     */
    protected function getToolbarButtons(): array
    {
        return match ($this->toolbar) {
            'minimal' => [
                'bold', 'italic', 'ul', 'ol',
            ],
            'basic' => [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '|',
                'ul', 'ol', '|',
                'link', 'image',
            ],
            'full' => [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '|',
                'font', 'fontsize', 'brush', 'paragraph', '|',
                'ul', 'ol', '|',
                'link', 'image', 'table', '|',
                'blockquote', 'code', '|',
                'undo', 'redo', '|',
                'source',
            ],
            default => ['source'],
        };
    }

    /**
     * Filter toolbar buttons by enabled features
     * Removes buttons whose features are disabled
     * Keeps separators '|' unless consecutive or at edges
     */
    protected function filterButtonsByFeatures(array $buttons, array $enabledLookup): array
    {
        $map = $this->buttonFeatureMap();
        $out = [];

        foreach ($buttons as $btn) {
            if ($btn === '|') {
                $out[] = $btn;
                continue;
            }

            $feature = $map[$btn] ?? null;

            if ($feature === null) {
                $out[] = $btn;
                continue;
            }

            if (isset($enabledLookup[$feature])) {
                $out[] = $btn;
            }
        }

        // Collapse consecutive separators
        $out = array_values(array_filter($out, function($v, $i) use ($out) {
            if ($v !== '|') {
                return true;
            }

            return ($i > 0 && $out[$i - 1] !== '|') && ($i < count($out) - 1 && $out[$i + 1] !== '|');
        }, ARRAY_FILTER_USE_BOTH));

        // Trim leading/trailing separators
        while (!empty($out) && $out[0] === '|') {
            array_shift($out);
        }
        while (!empty($out) && end($out) === '|') {
            array_pop($out);
        }

        return $out;
    }

    /**
     * Prepare for display
     */
    public function prepareForDisplay(FormContext $context): void
    {
        // Only fill if value is not already set (e.g., from fillFromDataSource in nested context)
        if (is_null($this->getValue())) {
            $this->fillFromDataSource($context->dataSource());
        }
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
            'type' => $this->type(),
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
