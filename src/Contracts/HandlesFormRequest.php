<?php

namespace Monstrex\Ave\Contracts;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\FormContext;

interface HandlesFormRequest
{
    public function prepareRequest(Request $request, FormContext $context): void;
}
