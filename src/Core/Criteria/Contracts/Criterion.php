<?php

namespace Monstrex\Ave\Core\Criteria\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Criteria\ActionBadge;

interface Criterion
{
    public function key(): string;

    public function priority(): int;

    public function active(Request $request): bool;

    public function apply(Builder $query, Request $request): Builder;

    public function badge(Request $request): ?ActionBadge;
}

