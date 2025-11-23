<?php

namespace Monstrex\Ave\Pages;

use Monstrex\Ave\Core\Page;

class IconLibraryPage extends Page
{
    public static ?string $label = 'Icon Library';
    public static ?string $icon = 'voyager-compass';
    public static ?string $slug = 'icons';

    public static function render($ctx): array
    {
        return [
            'title' => __('ave::icons.title'),
            'description' => __('ave::icons.description'),
            'icons' => self::loadIcons(),
        ];
    }

    /**
     * @return array<int,array{class:string,entity:string}>
     */
    protected static function loadIcons(): array
    {
        $path = realpath(__DIR__ . '/../../resources/css/foundation/_icons.scss');

        if (! $path || ! is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $pattern = '/\.((?:voyager|ave)-[a-z0-9\-]+):before\s*\{\s*content:\s*"([^"]+)";\s*\}/i';
        preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

        $icons = [];
        $seen = [];

        foreach ($matches as $match) {
            $class = $match[1];
            $code = $match[2];

            if (isset($seen[$class])) {
                continue;
            }

            $icons[] = [
                'class' => $class,
                'entity' => self::formatEntity($code),
            ];

            $seen[$class] = true;
        }

        usort($icons, fn ($a, $b) => strcmp($a['class'], $b['class']));

        return $icons;
    }

    protected static function formatEntity(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        if ($code[0] === '\\') {
            $hex = ltrim($code, '\\');

            if ($hex === '') {
                return '';
            }

            if (ctype_xdigit($hex)) {
                return '&#x' . strtolower($hex) . ';';
            }

            if (ctype_digit($hex)) {
                return '&#x' . dechex((int) $hex) . ';';
            }
        }

        $char = mb_substr($code, 0, 1, 'UTF-8');
        $ord = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
        $point = $ord ? reset($ord) : ord($code[0]);

        return '&#x' . dechex($point) . ';';
    }
}
