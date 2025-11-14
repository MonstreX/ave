<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Http\Controllers\Resource\Concerns\InteractsWithResources;

abstract class AbstractResourceAction
{
    use InteractsWithResources;

    public function __construct(protected ResourceManager $resources)
    {
    }
}
