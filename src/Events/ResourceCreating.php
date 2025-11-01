<?php

namespace Monstrex\Ave\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ResourceCreating
{
    use Dispatchable;

    public function __construct(
        public string $resourceClass,
        public array $data
    ) {}
}
