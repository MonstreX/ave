<?php

namespace Monstrex\Ave\Facades;

use Illuminate\Support\Facades\Facade;
use Monstrex\Ave\Services\BreadcrumbService;

/**
 * @method static \Monstrex\Ave\Services\BreadcrumbService register(string $pattern, callable $resolver)
 * @method static \Illuminate\Support\Collection generate()
 */
class Breadcrumb extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BreadcrumbService::class;
    }
}
