<?php

namespace Monstrex\Ave\Core\Columns;

use Closure;

class Column
{
    protected string $key;
    protected ?string $label = null;
    protected bool $sortable = false;
    protected bool $searchable = false;
    protected bool $hidden = false;
    protected ?Closure $formatCallback = null;
    protected ?string $align = null;
    protected ?int $width = null;

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

    public function sortable(bool $on = true): static
    {
        $this->sortable = $on;
        return $this;
    }

    public function searchable(bool $on = true): static
    {
        $this->searchable = $on;
        return $this;
    }

    public function hidden(bool $on = true): static
    {
        $this->hidden = $on;
        return $this;
    }

    public function align(string $align): static
    {
        $this->align = $align;
        return $this;
    }

    public function width(int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function format(Closure $callback): static
    {
        $this->formatCallback = $callback;
        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function formatValue(mixed $value, mixed $record): mixed
    {
        if ($this->formatCallback) {
            return call_user_func($this->formatCallback, $value, $record);
        }
        return $value;
    }

    public function toArray(): array
    {
        return [
            'key'        => $this->key,
            'label'      => $this->label ?? ucfirst(str_replace('_', ' ', $this->key)),
            'sortable'   => $this->sortable,
            'searchable' => $this->searchable,
            'hidden'     => $this->hidden,
            'align'      => $this->align ?? 'left',
            'width'      => $this->width,
        ];
    }
}
