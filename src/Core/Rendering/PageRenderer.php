<?php

namespace Monstrex\Ave\Core\Rendering;

use Monstrex\Ave\Core\Page;

class PageRenderer
{
    /**
     * Render a page
     *
     * @param Page $page
     * @param array $options
     * @return string
     */
    public function render(Page $page, array $options = []): string
    {
        $data = [
            'page' => $page,
            'slug' => $page->slug(),
            'label' => $page->label(),
            'content' => $page->render(),
        ];

        return view('ave::pages.show', array_merge($data, $options))->render();
    }
}
