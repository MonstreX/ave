<?php

namespace Monstrex\Ave\Core\Components;

/**
 * Col - Factory for Div with Bootstrap column class
 *
 * Convenient wrapper for creating column containers in Bootstrap grid layout.
 * Automatically applies responsive classes for adaptive design.
 *
 * Bootstrap grid spans from 1-12.
 *
 * Example:
 *   Row::make()->schema([
 *       Col::make(6)->schema([TextInput::make('name')]),      // col-12 col-md-6
 *       Col::make(6)->schema([TextInput::make('email')]),     // col-12 col-md-6
 *   ])
 *
 *   Col::make()    // col-12 (full width on all devices)
 *   Col::make(3)   // col-12 col-md-3 (full on mobile, quarter on desktop)
 *   Col::make(4)   // col-12 col-md-4 (full on mobile, third on desktop)
 *   Col::make(6)   // col-12 col-md-6 (full on mobile, half on desktop)
 *   Col::make(12)  // col-12 (full width on all devices)
 */
class Col
{
    public static function make(int $span = 12, string $additionalClasses = ''): Div
    {
        // For spans < 12, add responsive classes: col-12 col-md-{span}
        // This makes fields full-width on mobile, specified width on desktop
        if ($span < 12) {
            $classes = "col-12 col-md-{$span}";
        } else {
            $classes = "col-{$span}";
        }

        if ($additionalClasses) {
            $classes .= ' ' . $additionalClasses;
        }
        return Div::make($classes);
    }
}
