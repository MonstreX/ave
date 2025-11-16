<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Monstrex\Ave\Support\CleanJsonResponse;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\ResourceManager;

class FormJsonAction extends AbstractResourceAction
{
    public function __construct(ResourceManager $resources)
    {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug)
    {
        [$resourceClass] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        return CleanJsonResponse::make($resourceClass::form($request)->rows());
    }
}
