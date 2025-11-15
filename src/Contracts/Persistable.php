<?php

namespace Monstrex\Ave\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;

interface Persistable
{
    /**
     * Create a new model instance from form data
     *
     * @param string $resourceClass Resource class name
     * @param Form $form Form instance
     * @param array $data Validated form data
     * @param Request $request Current request
     * @return Model Created model
     */
    public function create(string $resourceClass, Form $form, array $data, Request $request, FormContext $context): Model;

    /**
     * Update an existing model instance
     *
     * @param string $resourceClass Resource class name
     * @param Form $form Form instance
     * @param Model $model Model to update
     * @param array $data Validated form data
     * @param Request $request Current request
     * @return Model Updated model
     */
    public function update(string $resourceClass, Form $form, Model $model, array $data, Request $request, FormContext $context): Model;

    /**
     * Delete a model instance
     *
     * @param string $resourceClass Resource class name
     * @param Model $model Model to delete
     * @param Request $request Current request
     * @return void
     */
    public function delete(string $resourceClass, Model $model, Request $request): void;
}
