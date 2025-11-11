<?php

namespace Monstrex\Ave\Core\Actions\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ActionContext
{
    public function __construct(
        protected string $mode,
        protected string $resourceClass,
        protected ?Authenticatable $user,
        protected ?Model $model = null,
        protected ?Collection $models = null,
        protected array $ids = [],
    ) {
    }

    public static function row(string $resourceClass, Authenticatable $user, Model $model): self
    {
        return new self('row', $resourceClass, $user, $model);
    }

    public static function bulk(string $resourceClass, Authenticatable $user, Collection $models, array $ids): self
    {
        return new self('bulk', $resourceClass, $user, null, $models, $ids);
    }

    public static function global(string $resourceClass, Authenticatable $user): self
    {
        return new self('global', $resourceClass, $user);
    }

    public static function form(string $resourceClass, Authenticatable $user, Model $model): self
    {
        return new self('form', $resourceClass, $user, $model);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function resourceClass(): string
    {
        return $this->resourceClass;
    }

    public function user(): ?Authenticatable
    {
        return $this->user;
    }

    public function model(): ?Model
    {
        return $this->model;
    }

    public function models(): ?Collection
    {
        return $this->models;
    }

    public function ids(): array
    {
        return $this->ids;
    }
}

