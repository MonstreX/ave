<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Core\PageManager;
use Monstrex\Ave\Core\Rendering\ViewResolver;

/**
 * Controller for handling standalone pages (independent from resources)
 */
class PageController extends Controller
{
    public function __construct(
        protected PageManager $pages,
        protected ViewResolver $views
    ) {}

    /**
     * Display the page
     *
     * GET /admin/page/{slug}
     */
    public function show(Request $request, string $slug)
    {
        $pageClass = $this->pages->page($slug);

        if (!$pageClass) {
            abort(404, "Page '{$slug}' not found");
        }

        // Resolve view with fallback
        $view = $this->views->resolvePage($slug);

        // Render page content (using static method if available)
        $payload = method_exists($pageClass, 'render')
            ? $pageClass::render($request)
            : (new $pageClass())->render($request);

        return view($view, [
            'payload' => $payload,
            'slug'    => $slug,
            'page'    => $pageClass,
        ]);
    }
}
