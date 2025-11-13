<?php

namespace Monstrex\Ave\Core\Rendering;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Monstrex\Ave\Core\Actions\Contracts\FormButtonAction;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Form;

class ResourceRenderer
{
    public function __construct(
        protected ViewResolver $views,
    ) {}

    public function index(string $resourceClass, $table, LengthAwarePaginator $records, Request $request, array $criteriaBadges = [])
    {
        $slug = $resourceClass::getSlug();
        $displayMode = $table?->getDisplayMode() ?? 'table';
        $view = $this->views->resolveResource($slug, 'index');
        $resourceInstance = new $resourceClass();
        $rowActions = method_exists($resourceClass, 'rowActions') ? $resourceClass::rowActions() : [];
        $bulkActions = method_exists($resourceClass, 'bulkActions') ? $resourceClass::bulkActions() : [];
        $globalActions = method_exists($resourceClass, 'globalActions') ? $resourceClass::globalActions() : [];

        return view($view, [
            'resource' => $resourceClass,
            'resourceInstance' => $resourceInstance,
            'slug' => $slug,
            'table' => $table,
            'records' => $records,
            'request' => $request,
            'criteriaBadges' => $criteriaBadges,
            'rowActions' => $rowActions,
            'bulkActions' => $bulkActions,
            'globalActions' => $globalActions,
            'displayMode' => $displayMode,
        ]);
    }

    public function form(string $resourceClass, $form, $model, Request $request)
    {
        $slug = $resourceClass::getSlug();
        $view = $this->views->resolveResource($slug, 'form');
        $mode = $model && $model->exists ? 'edit' : 'create';

        $context = $mode === 'edit'
            ? FormContext::forEdit($model, [], $request)
            : FormContext::forCreate([], $request, $model);

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

        $formActionsRaw = method_exists($resourceClass, 'formActions') ? $resourceClass::formActions() : [];
        [$buttonActions, $ajaxActions] = $this->prepareFormActions($formActionsRaw, $form, $model, $slug, $mode === 'edit');

        return view($view, [
            'resource' => $resourceClass,
            'resourceInstance' => new $resourceClass(),
            'slug' => $slug,
            'form' => $form,
            'formLayout' => $form->layout(),
            'model' => $model,
            'context' => $context,
            'mode' => $mode,
            'request' => $request,
            'formButtonActions' => $buttonActions,
            'ajaxFormActions' => $ajaxActions,
        ]);
    }

    /**
     * @param array<int,\Monstrex\Ave\Core\Actions\Contracts\ActionInterface> $actions
     * @return array{0:array<int,array<string,mixed>>,1:array<int,\Monstrex\Ave\Core\Actions\Contracts\ActionInterface>}
     */
    protected function prepareFormActions(array $actions, Form $form, mixed $model, string $slug, bool $isEdit): array
    {
        $buttonActions = [];
        $ajaxActions = [];

        foreach ($actions as $action) {
            if ($action instanceof FormButtonAction) {
                $label = $action->labelForMode($isEdit ? 'edit' : 'create');
                if ($action->key() === 'save' && method_exists($form, 'getSubmitLabel')) {
                    $label = $form->getSubmitLabel();
                }

                $buttonActions[] = [
                    'key' => $action->key(),
                    'type' => $action->buttonType(),
                    'intent' => $action->intent(),
                    'variant' => $action->color(),
                    'label' => $label,
                    'icon' => $action->icon(),
                    'url' => $action->resolveUrl($slug, $form, $model, $isEdit),
                    'confirm' => $action->confirm(),
                ];
                continue;
            }

            $ajaxActions[] = $action;
        }

        return [$buttonActions, $ajaxActions];
    }
}
