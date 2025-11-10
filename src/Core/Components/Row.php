<?php

namespace Monstrex\Ave\Core\Components;

/**
 * Row - Factory for Div with 'row' class
 *
 * Convenient wrapper for creating row containers in Bootstrap grid layout.
 * Automatically applies class="row" for horizontal field grouping.
 *
 * Example:
 *   Row::make()->schema([
 *       Col::make(6)->schema([TextInput::make('name')]),
 *       Col::make(6)->schema([TextInput::make('email')]),
 *   ])
 */
class Row
{
    public static function make(string $additionalClasses = ''): Div
    {
        $classes = 'row';
        if ($additionalClasses) {
            $classes .= ' ' . $additionalClasses;
        }
        return Div::make($classes);
    }
}
