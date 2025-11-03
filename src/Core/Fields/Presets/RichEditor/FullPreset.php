<?php

namespace Monstrex\Ave\Core\Fields\Presets\RichEditor;

/**
 * Full feature preset - includes all available RichEditor features
 */
class FullPreset extends RichEditorPreset
{
    /**
     * Enable all features
     */
    public function features(): array|string|null
    {
        return null;  // null means all defaults enabled
    }

    /**
     * Default options for full preset
     */
    public function options(): array
    {
        return [
            'height' => 500,
            'showCharsCounter' => false,
            'showWordsCounter' => false,
            'showXPathInStatusbar' => false,
        ];
    }

    public function description(): string
    {
        return 'Full featured editor with all capabilities enabled';
    }
}
