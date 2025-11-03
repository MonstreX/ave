<?php

namespace Monstrex\Ave\Core\Fields\Presets\RichEditor;

/**
 * Basic preset - standard content editor
 *
 * Includes: formatting, lists, links, images, tables
 * Excludes: code, blockquote (can be enabled if needed)
 */
class BasicPreset extends RichEditorPreset
{
    /**
     * Enable standard content editing features
     */
    public function features(): array|string|null
    {
        return [
            'headings',
            'paragraph',
            'bold',
            'italic',
            'underline',
            'strike',
            'lists',
            'links',
            'images',
            'tables',
            'inline-styles',
            'undo',
            'redo',
            'font',
            'fontsize',
            'brush',
            'hr',
            '-code',
            '-blockquote',
        ];
    }

    /**
     * Standard height for basic editor
     */
    public function options(): array
    {
        return [
            'height' => 400,
            'showCharsCounter' => false,
            'showWordsCounter' => false,
            'showXPathInStatusbar' => false,
        ];
    }

    public function description(): string
    {
        return 'Basic editor with standard content editing features';
    }
}
