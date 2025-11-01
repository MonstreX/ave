<?php

namespace Monstrex\Ave\Core\Actions;

use Closure;
use Illuminate\Http\Request;
use ReflectionFunction;

class BulkAction
{
    protected string $key;
    protected ?string $label = null;
    protected ?string $icon = null;
    protected ?string $color = null;
    protected ?Closure $callback = null;
    protected bool $requiresConfirmation = false;
    protected ?string $confirmMessage = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function handle(Closure $callback): static
    {
        $this->callback = $callback;
        return $this;
    }

    public function requiresConfirmation(bool $requires = true, ?string $message = null): static
    {
        $this->requiresConfirmation = $requires;
        $this->confirmMessage = $message;
        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function execute(iterable $records, Request $request): mixed
    {
        if (!$this->callback) {
            return null;
        }

        $reflection = new ReflectionFunction($this->callback);
        $parameterCount = $reflection->getNumberOfParameters();

        return match (true) {
            $parameterCount === 0 => call_user_func($this->callback),
            $parameterCount === 1 => call_user_func($this->callback, $records),
            default => call_user_func($this->callback, $records, $request),
        };
    }

    public function toArray(): array
    {
        return [
            'key'                 => $this->key,
            'label'               => $this->label ?? ucfirst($this->key),
            'icon'                => $this->icon,
            'color'               => $this->color,
            'requiresConfirmation' => $this->requiresConfirmation,
            'confirmMessage'      => $this->confirmMessage,
        ];
    }
}
