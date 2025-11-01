<?php

namespace Monstrex\Ave\Core\Rendering;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Monstrex\Ave\Core\Forms\FormContext;

class ResourceRenderer
{
    public function __construct(
        protected ViewResolver $views,
    ) {}

    public function index(string $resourceClass, $table, LengthAwarePaginator $records, Request $request)
    {
        $slug = $resourceClass::getSlug();
        $view = $this->views->resolveResource($slug, 'index');

        return view($view, [
            'resource' => $resourceClass,
            'slug' => $slug,
            'table' => $table,
            'records' => $records,
            'request' => $request,
        ]);
    }

    public function form(string $resourceClass, $form, $model, Request $request)
    {
        $slug = $resourceClass::getSlug();
        $view = $this->views->resolveResource($slug, 'form');
        $mode = $model && $model->exists ? 'edit' : 'create';

        $context = $mode === 'edit'
            ? FormContext::forEdit($model, [], $request)
            : FormContext::forCreate([], $request);

        if ($request->hasSession()) {
            $context->withOldInput($request->old());

            if ($request->session()->has('errors')) {
                $context->withErrors($request->session()->get('errors'));
            }
        }

        $dataSource = $context->dataSource();

        foreach ($form->getAllFields() as $field) {
            if ($dataSource) {
                $field->fillFromDataSource($dataSource);
            }

            if (method_exists($field, 'prepareForDisplay')) {
                $field->prepareForDisplay($context);
            }
        }

        return view($view, [
            'resource' => $resourceClass,
            'slug' => $slug,
            'form' => $form,
            'formLayout' => $form->layout(),
            'model' => $model,
            'context' => $context,
            'mode' => $mode,
            'request' => $request,
        ]);
    }
}
