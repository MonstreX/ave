<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\ActionInterface;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

abstract class BaseAction implements ActionInterface
{
    protected string $key = '';
    protected string $label = '';
    protected ?string $icon = null;
    protected string $color = 'primary';
    protected ?string $confirm = null;
    protected ?string $ability = null;
    protected int $order = 100;

    public function key(): string
    {
        return $this->key ?: $this->defaultKey();
    }

    public function label(): string
    {
        return $this->label ?: ucfirst($this->key());
    }

    public function icon(): ?string
    {
        return $this->icon;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function confirm(): ?string
    {
        return $this->confirm;
    }

    public function form(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [];
    }

    public function ability(): ?string
    {
        return $this->ability;
    }

    public function order(): int
    {
        return $this->order;
    }

    public function authorize(ActionContext $context): bool
    {
        return true;
    }

    abstract public function handle(ActionContext $context, Request $request): mixed;

    protected function defaultKey(): string
    {
        $class = class_basename(static::class);

        return strtolower(preg_replace('/Action$/', '', $class));
    }
}
