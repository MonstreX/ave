<?php

namespace Monstrex\Ave\Core\Rendering;

use Illuminate\Support\Facades\View;

class ViewResolver
{
    public function resolveResource(string $slug, string $view): string
    {
        $specific = "ave::resources.{$slug}.{$view}";
        if (View::exists($specific)) {
            return $specific;
        }

        $generic = "ave::resources.{$view}";
        if (View::exists($generic)) {
            return $generic;
        }

        return $generic;
    }

    public function resolvePage(string $slug): string
    {
        $specific = "ave::page.{$slug}";
        if (View::exists($specific)) {
            return $specific;
        }

        return 'ave::page.default';
    }
}
