<?php

namespace Monstrex\Ave\Core\Rendering;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ResourceRenderer
{
    public function __construct(
        protected ViewResolver $views
    ) {}

    /**
     * Render resource index view
     *
     * @param string $resourceClass Resource class name
     * @param mixed $table Table configuration
     * @param LengthAwarePaginator $records Paginated records
     * @param Request $request Current request
     * @return string Rendered HTML
     */
    public function index(string $resourceClass, $table, LengthAwarePaginator $records, Request $request)
    {
        $slug = $resourceClass::$slug ?? strtolower(class_basename($resourceClass));
        $view = $this->views->resolveResource($slug, 'index');

        return view($view, [
            'resource' => $resourceClass,
            'slug'     => $slug,
            'table'    => $table,
            'records'  => $records,
            'request'  => $request,
        ]);
    }

    /**
     * Render resource form view (create/edit)
     *
     * @param string $resourceClass Resource class name
     * @param mixed $form Form configuration
     * @param mixed $model Model instance or null
     * @param Request $request Current request
     * @return string Rendered HTML
     */
    public function form(string $resourceClass, $form, $model, Request $request)
    {
        $slug = $resourceClass::$slug ?? strtolower(class_basename($resourceClass));
        $view = $this->views->resolveResource($slug, 'form');

        return view($view, [
            'resource' => $resourceClass,
            'slug'     => $slug,
            'form'     => $form,
            'model'    => $model,
            'request'  => $request,
        ]);
    }
}
