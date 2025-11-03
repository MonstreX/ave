<?php

namespace Monstrex\Ave\Contracts;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Fields\FieldPersistenceResult;
use Monstrex\Ave\Core\FormContext;

interface HandlesPersistence
{
    public function prepareForSave(mixed $value, Request $request, FormContext $context): FieldPersistenceResult;
}
