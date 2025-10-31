<?php

namespace Monstrex\Ave\Core\Controllers;

use Illuminate\Routing\Controller;

/**
 * Controller for handling standalone pages (independent from resources)
 */
class PageController extends Controller
{
    protected string $pageClass;

    /**
     * Set the page class
     */
    public function setPage(string $pageClass): void
    {
        $this->pageClass = $pageClass;
    }

    /**
     * Display the page
     */
    public function show()
    {
        $page = $this->getPageInstance();

        // Render page through static render() method
        $content = $page::render();

        return [
            'page' => [
                'slug' => $page::slug(),
                'label' => $page::label(),
                'content' => $content,
            ],
        ];
    }

    /**
     * Get an instance of the page
     */
    protected function getPageInstance()
    {
        return new $this->pageClass();
    }
}
