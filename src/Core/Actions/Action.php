<?php

namespace Monstrex\Ave\Core\Actions;

use Closure;

/**
 * Legacy table action builder.
 *
 * New action system lives under Core\Actions\Contracts + BaseAction.
 * This class remains for backward compatibility with Table DSL.
 */
class Action
{
    protected string $key;
    protected ?string $label = null;
    protected ?string $icon = null;
    protected ?string $color = null;
    protected ?Closure $callback = null;
    protected ?string $url = null;
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

    public function url(string $url): static
    {
        $this->url = $url;
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

    public function execute(mixed $record): mixed
    {
        if ($this->callback) {
            return call_user_func($this->callback, $record);
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'key'                 => $this->key,
            'label'               => $this->label ?? ucfirst($this->key),
            'icon'                => $this->icon,
            'color'               => $this->color,
            'url'                 => $this->url,
            'requiresConfirmation' => $this->requiresConfirmation,
            'confirmMessage'      => $this->confirmMessage,
        ];
    }
}

