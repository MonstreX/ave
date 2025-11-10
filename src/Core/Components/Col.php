<?php

namespace Monstrex\Ave\Core\Components;

/**
 * Col - Factory for Div with Bootstrap column class
 *
 * Convenient wrapper for creating column containers in Bootstrap grid layout.
 * Automatically applies class="col-{span}" for responsive column sizing.
 *
 * Bootstrap grid spans from 1-12.
 *
 * Example:
 *   Row::make()->schema([
 *       Col::make(6)->schema([TextInput::make('name')]),      // Half width
 *       Col::make(6)->schema([TextInput::make('email')]),     // Half width
 *   ])
 *
 *   Col::make()    // Defaults to col-12 (full width)
 *   Col::make(3)   // col-3 (quarter width)
 *   Col::make(4)   // col-4 (third width)
 *   Col::make(8)   // col-8 (two thirds width)
 */
class Col
{
    public static function make(int $span = 12, string $additionalClasses = ''): Div
    {
        $classes = "col-{$span}";
        if ($additionalClasses) {
            $classes .= ' ' . $additionalClasses;
        }
        return Div::make($classes);
    }
}
