<?php

namespace Monstrex\Ave\Core\Fields\Presets\RichEditor;

/**
 * Minimal preset - basic text formatting only
 *
 * Includes: bold, italic, underline, strike, lists, links
 * Excludes: images, tables, code, blockquote, headings
 */
class MinimalPreset extends RichEditorPreset
{
    /**
     * Enable only essential formatting features
     */
    public function features(): array|string|null
    {
        return [
            'bold',
            'italic',
            'underline',
            'strike',
            'lists',
            'links',
            'undo',
            'redo',
            '-images',
            '-tables',
            '-code',
            '-blockquote',
            '-headings',
        ];
    }

    /**
     * Compact height for minimal editor
     */
    public function options(): array
    {
        return [
            'height' => 250,
            'showCharsCounter' => false,
            'showWordsCounter' => false,
            'showXPathInStatusbar' => false,
        ];
    }

    public function description(): string
    {
        return 'Minimal editor with basic text formatting only';
    }
}
