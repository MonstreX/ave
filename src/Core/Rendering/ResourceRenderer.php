<?php

namespace Monstrex\Ave\Core\Rendering;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Forms\FormContext;

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
     * CRITICAL: This is the key point where FormContext is created and all fields are prepared
     * for display. This happens BEFORE rendering to ensure:
     * - Fields are filled with data from the model (if editing)
     * - prepareForDisplay() is called on all fields
     * - Context is available in Blade templates
     * - Nested fields (FieldSet, Media) work correctly
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

        // 1. Create FormContext based on whether editing or creating
        $context = $model->exists
            ? FormContext::forEdit($model, [], $request)
            : FormContext::forCreate([], $request);

        // 2. Add old input from session (for validation error re-display)
        if ($request->hasSession()) {
            $context->withOldInput($request->old());
            // Add errors if they exist in session
            if ($request->session()->has('errors')) {
                $context->withErrors($request->session()->get('errors'));
            }
        }

        // 3. Prepare all form fields for display
        // This is CRITICAL: triggers fillFromDataSource() and prepareForDisplay() on all fields
        foreach ($form->getAllFields() as $field) {
            if (method_exists($field, 'prepareForDisplay')) {
                $field->prepareForDisplay($context);
            }
        }

        return view($view, [
            'resource' => $resourceClass,
            'slug'     => $slug,
            'form'     => $form,
            'model'    => $model,
            'context'  => $context,
            'request'  => $request,
        ]);
    }
}
