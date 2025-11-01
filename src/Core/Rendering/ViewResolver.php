<?php

namespace Monstrex\Ave\Core\Rendering;

use Illuminate\Support\Facades\View;

class ViewResolver
{
    /**
     * Resolve view for resource page
     *
     * @param string $slug Resource slug
     * @param string $view View name (index, form, etc.)
     * @return string Resolved view name
     */
    public function resolveResource(string $slug, string $view): string
    {
        // Try resource-specific view
        $specific = "ave::resource.{$slug}.{$view}";
        if (View::exists($specific)) {
            return $specific;
        }

        // Fallback to generic resource view
        $generic = "ave::resource.{$view}";
        if (View::exists($generic)) {
            return $generic;
        }

        // Final fallback
        return $generic;
    }

    /**
     * Resolve view for page
     *
     * @param string $slug Page slug
     * @return string Resolved view name
     */
    public function resolvePage(string $slug): string
    {
        // Try page-specific view
        $specific = "ave::page.{$slug}";
        if (View::exists($specific)) {
            return $specific;
        }

        // Fallback to default page view
        return "ave::page.default";
    }
}
