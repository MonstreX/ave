<?php

namespace Monstrex\Ave\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ResourceUpdating
{
    use Dispatchable;

    public function __construct(
        public string $resourceClass,
        public Model $model,
        public array $data
    ) {}
}
